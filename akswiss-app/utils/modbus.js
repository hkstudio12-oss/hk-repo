/**
 * Modbus RTU frame helpers for the AK SWISS fridge BLE protocol.
 *
 * Reconstructed faithfully from the original compiled app so that the new
 * app speaks the EXACT same bytes to the fridge firmware. Do not change the
 * framing, CRC polynomial, or register addressing without a real device to
 * test against — the hardware will silently ignore a malformed frame.
 *
 * Protocol summary:
 *   - Transport: BLE write (characteristic under service 0000FFE0-...).
 *   - Framing:   standard Modbus RTU, CRC appended low-byte then high-byte.
 *   - CRC:       CRC-16/MODBUS (poly 0xA001 reflected, init 0xFFFF).
 *   - Registers: 30001-39999 -> address (reg - 30001)  [input registers]
 *                40001-49999 -> address (reg - 40001)  [holding registers]
 *   - Func codes used: 03/04 read, 06 write-single, 0x10 write-multiple.
 */

/** CRC-16/MODBUS. Returns { low, high } bytes. */
export function crc16(bytes) {
  let crc = 0xffff
  for (const b of bytes) {
    crc ^= b & 0xff
    for (let i = 0; i < 8; i++) {
      const lsb = crc & 1
      crc >>= 1
      if (lsb) crc ^= 0xa001
    }
  }
  return { low: crc & 0xff, high: (crc >> 8) & 0xff }
}

const toHexStr = (bytes) =>
  bytes.map((b) => (b & 0xff).toString(16).padStart(2, '0').toUpperCase()).join(' ')

/**
 * Build a read (fc 1-4) or write-single (fc 6) RTU frame.
 * @param {number} addr      slave address 1-255
 * @param {number} func      1,2,3,4 (read) or 6 (write single)
 * @param {number} register  logical register (30001-39999 or 40001-49999)
 * @param {number} qtyOrValue quantity to read (1-127) or value to write (may be negative; sent as signed 16-bit)
 * @returns {string} space separated uppercase hex
 */
export function buildRtu(addr, func, register, qtyOrValue) {
  if (addr < 1 || addr > 255) throw new Error('slave address must be 1-255')
  if (![1, 2, 3, 4, 6].includes(func)) throw new Error('function code must be one of 1,2,3,4,6')

  let body
  if ([1, 2, 3, 4].includes(func)) {
    if (qtyOrValue < 1 || qtyOrValue > 127) throw new Error('read quantity must be 1-127')
    let n
    if (register >= 30001 && register <= 39999) n = register - 30001
    else if (register >= 40001 && register <= 49999) n = register - 40001
    else throw new Error('unsupported register range (30001-39999 / 40001-49999)')
    body = [addr, func, (n >> 8) & 0xff, n & 0xff, (qtyOrValue >> 8) & 0xff, qtyOrValue & 0xff]
  } else {
    // fc 6 write single; value may be negative (two's complement 16-bit)
    const n = register >= 40001 && register <= 49999 ? register - 40001 : register
    body = [addr, func, (n >> 8) & 0xff, n & 0xff, (qtyOrValue >> 8) & 0xff, qtyOrValue & 0xff]
  }
  const c = crc16(body)
  return toHexStr([...body, c.low, c.high])
}

/**
 * Build a write-multiple-registers (fc 0x10) RTU frame.
 * @param {number} addr      slave address
 * @param {number} register  raw start register value
 * @param {number[]} values  register values (each 0-65535)
 */
export function buildWriteMultiple(addr, register, values) {
  if (!values || values.length === 0) throw new Error('at least one value required')
  values.forEach((v, i) => {
    if (v < 0 || v > 65535) throw new Error(`value #${i + 1} out of range (0-65535)`)
  })
  const qty = values.length
  const byteCount = 2 * qty
  const body = [
    addr & 0xff,
    0x10,
    (register >> 8) & 0xff,
    register & 0xff,
    (qty >> 8) & 0xff,
    qty & 0xff,
    byteCount & 0xff,
  ]
  values.forEach((v) => body.push((v >> 8) & 0xff, v & 0xff))
  const c = crc16(body)
  return toHexStr([...body, c.low, c.high])
}

/** Hex string ("FF EC" or "FFEC") -> ArrayBuffer for uni.writeBLECharacteristicValue. */
export function hexToBuffer(hex) {
  const pairs = hex.replace(/\s/g, '').match(/.{2}/g)
  if (!pairs) throw new Error('invalid hex string')
  return new Uint8Array(pairs.map((p) => parseInt(p, 16))).buffer
}

/** ArrayBuffer -> array of uppercase hex byte strings. */
export function bufferToHexBytes(buffer) {
  const u8 = new Uint8Array(buffer)
  const out = []
  for (let i = 0; i < u8.length; i++) out.push(u8[i].toString(16).padStart(2, '0').toUpperCase())
  return out
}

/** Interpret a 16-bit hex value (e.g. "FFEC" or "FF EC") as a signed integer. */
export function toSigned16(hex) {
  let v = parseInt(String(hex).replace(/\s/g, ''), 16)
  if (v > 32767) v -= 65536
  return v
}

/** ASCII string -> space separated hex (used for AT command payloads / device name). */
export function asciiToHex(str, join = ' ') {
  const out = []
  for (let i = 0; i < str.length; i++) {
    const code = str.charCodeAt(i)
    out.push(code.toString(16).padStart(2, '0'))
  }
  return out.join(join)
}

/** Space separated hex -> ASCII string (drops non-ASCII and NULs). */
export function hexToAscii(hex, sep = ' ') {
  let clean = hex.replace(new RegExp(sep, 'g'), '')
  if (clean.length % 2 !== 0) clean = '0' + clean
  let out = ''
  for (let i = 0; i < clean.length; i += 2) {
    const code = parseInt(clean.substr(i, 2), 16)
    if (code >= 0 && code <= 127) out += String.fromCharCode(code)
  }
  return out.replace(/\0/g, '')
}
