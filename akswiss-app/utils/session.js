/**
 * Holds the currently connected BLE channel + latest status so the Connect
 * screen can hand off to the Control screen without reconnecting.
 */
let channel = null
let device = null

export function setChannel(ch, dev) {
  channel = ch
  device = dev || null
}
export function getChannel() {
  return channel
}
export function getDevice() {
  return device
}
export function clearChannel() {
  channel = null
  device = null
}
