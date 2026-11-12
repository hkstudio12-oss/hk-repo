/**
 * BLE layer for the AK SWISS fridge.
 *
 * Reconstructed from the original app. Handles: scanning, connecting,
 * discovering the target service/characteristic, enabling notifications,
 * sending Modbus commands, and parsing status frames the fridge pushes back.
 *
 * The register map (REG) and the status-frame layout are firmware-defined —
 * they must match the fridge exactly. The UI around this can change freely;
 * these bytes cannot.
 */
import {
  buildRtu,
  hexToBuffer,
  bufferToHexBytes,
  toSigned16,
  asciiToHex,
  hexToAscii,
} from './modbus.js'

// Target GATT service on the fridge module.
export const SERVICE_UUID = '0000FFE0-0000-1000-8000-00805F9B34FB'

// Holding / input registers (firmware-defined). Do not renumber.
export const REG = {
  SET_TEMP: 40001, // fc6 write set temperature (signed degrees)
  MODE: 40002, // fc6 write cooling mode 0=Turbo 1=Eco 2=Keep
  POWER: 40003, // fc6 write on/off (1/0)
  UNIT: 40005, // fc6 write unit 0=Celsius 1=Fahrenheit
  READ_STATUS: 30001, // fc4 read 11 input registers (live status)
  READ_CONFIG: 40001, // fc3 read 28 holding registers (config snapshot)
}

export const MODES = { 0: 'Turbo', 1: 'Eco', 2: 'Keep' }

// Alarm bit labels, in the order the firmware reports them.
export const ALARMS = [
  'Low battery',
  'Input voltage is high',
  'Temperature sensor fault',
  'External fan fault',
  'Internal fan fault',
  'Compressor fault',
]

const SLAVE = 1 // fridge slave address

function platform() {
  try {
    return uni.getSystemInfoSync().platform
  } catch (e) {
    return 'android'
  }
}

/** Open the Bluetooth adapter. Resolves even if already open. */
export function openAdapter() {
  return new Promise((resolve, reject) => {
    uni.openBluetoothAdapter({
      success: resolve,
      fail: (err) => {
        // 10001 = already opened
        if (err && err.code === 10001) resolve(err)
        else reject(err)
      },
    })
  })
}

/**
 * Scan for fridges. Calls onFound(device) for each discovered peripheral.
 * Returns a stop() function.
 */
export function startScan(onFound) {
  uni.onBluetoothDeviceFound((res) => {
    (res.devices || []).forEach((d) => {
      if (d.name || d.localName) onFound(d)
    })
  })
  uni.startBluetoothDevicesDiscovery({ allowDuplicatesKey: false })
  return () => uni.stopBluetoothDevicesDiscovery()
}

/**
 * Connect to a peripheral and resolve a "channel" object holding the ids
 * needed to read/write. Discovers the writable characteristic under SERVICE_UUID.
 */
export async function connect(deviceId) {
  await new Promise((resolve, reject) =>
    uni.createBLEConnection({ deviceId, success: resolve, fail: reject })
  )

  const { services } = await new Promise((resolve, reject) =>
    uni.getBLEDeviceServices({ deviceId, success: resolve, fail: reject })
  )
  const service = (services || []).find(
    (s) => s.uuid.toUpperCase() === SERVICE_UUID.toUpperCase()
  )
  if (!service) throw new Error('Fridge service not found on this device')
  const serviceId = service.uuid

  const { characteristics } = await new Promise((resolve, reject) =>
    uni.getBLEDeviceCharacteristics({ deviceId, serviceId, success: resolve, fail: reject })
  )
  const writable = (characteristics || []).find(
    (c) => c.properties && (c.properties.write || c.properties.writeNoResponse)
  )
  const notify = (characteristics || []).find(
    (c) => c.properties && (c.properties.notify || c.properties.indicate)
  )
  if (!writable) throw new Error('No writable characteristic found')

  const channel = {
    deviceId,
    serviceId,
    characteristicId: writable.uuid,
    notifyCharacteristicId: notify ? notify.uuid : writable.uuid,
  }
  return channel
}

export function disconnect(deviceId) {
  return new Promise((resolve) =>
    uni.closeBLEConnection({ deviceId, success: resolve, fail: resolve })
  )
}

/**
 * Enable notifications and route parsed status frames to onStatus(status).
 * Returns a cleanup function.
 */
export function listen(channel, onStatus) {
  const handler = (res) => {
    const bytes = bufferToHexBytes(res.value)
    const status = parseFrame(bytes)
    if (status) onStatus(status)
  }
  uni.onBLECharacteristicValueChange(handler)
  uni.notifyBLECharacteristicValueChange({
    deviceId: channel.deviceId,
    serviceId: channel.serviceId,
    characteristicId: channel.notifyCharacteristicId,
    state: true,
  })
  return () => {
    uni.notifyBLECharacteristicValueChange({
      deviceId: channel.deviceId,
      serviceId: channel.serviceId,
      characteristicId: channel.notifyCharacteristicId,
      state: false,
      fail: () => {},
    })
  }
}

// Low-level write with Android writeNoResponse + a small settle delay and retries.
function write(channel, hex, retriesLeft = 5) {
  const value = hexToBuffer(hex)
  const writeType = platform() === 'android' ? 'writeNoResponse' : 'write'
  const delay = platform() === 'android' ? 300 : 0
  return new Promise((resolve, reject) => {
    setTimeout(() => {
      uni.writeBLECharacteristicValue({
        deviceId: channel.deviceId,
        serviceId: channel.serviceId,
        characteristicId: channel.characteristicId,
        value,
        writeType,
        success: resolve,
        fail: (err) => {
          if (retriesLeft > 0) write(channel, hex, retriesLeft - 1).then(resolve).catch(reject)
          else reject(new Error('command send failed: ' + (err && err.errMsg)))
        },
      })
    }, delay)
  })
}

/** Send one Modbus command. Mirrors the original di(addr, func, register, valueOrQty). */
export function send(channel, func, register, valueOrQty) {
  const hex = buildRtu(SLAVE, func, register, valueOrQty)
  return write(channel, hex)
}

// ---- High-level fridge operations -----------------------------------------

export const setTemperature = (ch, degrees) => send(ch, 6, REG.SET_TEMP, Math.round(degrees))
export const setMode = (ch, mode) => send(ch, 6, REG.MODE, mode) // 0/1/2
export const setPower = (ch, on) => send(ch, 6, REG.POWER, on ? 1 : 0)
export const setUnit = (ch, fahrenheit) => send(ch, 6, REG.UNIT, fahrenheit ? 1 : 0)
export const requestStatus = (ch) => send(ch, 4, REG.READ_STATUS, 11)
export const requestConfig = (ch) => send(ch, 3, REG.READ_CONFIG, 28)

// Device name over AT commands (used on the settings screen).
export async function setDeviceName(channel, name) {
  const payload = asciiToHex('AT+BLUFINAME=' + name + '\r\n')
  await write(channel, payload)
  await write(channel, asciiToHex('AT+BLEWF=1\r\n'))
}

// ---- Status frame parsing --------------------------------------------------

/**
 * Parse a status/notify frame. Returns null for frames we don't recognise.
 * 27-byte fc04 response = live status; 61-byte fc03 response = config snapshot.
 */
export function parseFrame(bytes) {
  if (!bytes || bytes.length < 7) return null
  const fc = bytes[1]

  // Live status (fc 04, 11 input registers).
  if (bytes.length === 27 && fc === '04') {
    const alarmByte = parseInt(bytes[20] || '0', 16) // best-effort alarm flags
    return {
      kind: 'status',
      currentTemp: toSigned16(bytes[3] + bytes[4]),
      mode: parseInt(bytes[6], 16),
      voltage: Number(parseInt(bytes[8], 16) / 10).toFixed(1),
      battery: parseInt(bytes[10], 16),
      alarmBits: alarmByte,
      alarms: ALARMS.filter((_, i) => alarmByte & (1 << i)),
    }
  }

  // Config snapshot (fc 03, 28 holding registers).
  if (bytes.length === 61 && fc === '03') {
    return {
      kind: 'config',
      setTemp: toSigned16(bytes[3] + bytes[4]),
      mode: parseInt(bytes[12], 16),
    }
  }

  // Device name advertisement (name encoded from byte 36 onward).
  if (bytes.length > 40) {
    const nameHex = bytes
      .slice(36, bytes.length - 2)
      .filter((_, i) => i % 2 !== 0)
      .join(' ')
    const name = hexToAscii(nameHex)
    if (name) return { kind: 'name', name }
  }

  return null
}
