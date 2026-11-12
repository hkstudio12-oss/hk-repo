<template>
  <view class="page">
    <view class="status" :style="{ height: statusBar + 'px' }"></view>
    <scroll-view scroll-y class="scroll">
      <view class="wrap">
        <!-- Top bar -->
        <view class="top">
          <button class="icon-btn" @tap="back"><text class="chev">‹</text></button>
          <view class="title-col">
            <text class="dname">{{ deviceName }}</text>
            <view class="link-row">
              <view class="dot" :style="{ backgroundColor: connected ? '#5BD69A' : '#D52B1E' }"></view>
              <text class="link-text">{{ connected ? 'Connected via Bluetooth' : 'Reconnecting…' }}</text>
            </view>
          </view>
          <button class="icon-btn" @tap="goSettings"><text class="gear">⚙</text></button>
        </view>

        <!-- Vitals -->
        <view class="vitals">
          <view class="vital">
            <text class="vlabel">Battery</text>
            <text class="vval">{{ battery != null ? battery + '%' : '—' }}</text>
          </view>
          <view class="vital">
            <text class="vlabel">Input</text>
            <text class="vval">{{ voltage || '—' }} V</text>
          </view>
          <view class="vital">
            <text class="vlabel">Mode</text>
            <text class="vval">{{ modeName }}</text>
          </view>
        </view>

        <!-- Dial -->
        <view class="dial" :style="{ opacity: on ? 1 : 0.45 }">
          <canvas canvas-id="dial" id="dial" class="dial-canvas"></canvas>
          <view class="dial-center">
            <text class="dial-cap">Inside now</text>
            <view class="dial-temp">
              <text class="dial-val">{{ fmt(currentDisplay) }}</text>
              <text class="dial-unit">{{ unitSym }}</text>
            </view>
            <text class="dial-stat" :style="{ color: statusColor }">{{ statusLabel }}</text>
          </view>
        </view>

        <!-- Stepper -->
        <view class="stepper">
          <button class="step-btn" @tap="dec"><text class="sign">−</text></button>
          <view class="step-mid">
            <text class="step-cap">Set temperature</text>
            <text class="step-val">{{ fmt(setDisplay) }}{{ unitSym }}</text>
          </view>
          <button class="step-btn" @tap="inc"><text class="sign">+</text></button>
        </view>

        <!-- Modes -->
        <text class="section">Cooling mode</text>
        <view class="modes">
          <button
            v-for="m in modeList"
            :key="m.i"
            class="mode"
            :class="{ on: mode === m.i }"
            @tap="pickMode(m.i)"
          >
            <text class="mode-name" :class="{ on: mode === m.i }">{{ m.name }}</text>
            <text class="mode-hint" :class="{ on: mode === m.i }">{{ m.hint }}</text>
          </button>
        </view>

        <!-- Quick actions -->
        <view class="actions">
          <button class="act" :class="{ danger: !on }" @tap="togglePower">
            <text class="act-text" :class="{ danger: !on }">{{ on ? 'Turn off' : 'Turn on' }}</text>
          </button>
          <button class="act" @tap="toggleUnit">
            <text class="unit-c" :class="{ on: unit === 'C' }">°C</text>
            <text class="unit-sep"> / </text>
            <text class="unit-f" :class="{ on: unit === 'F' }">°F</text>
          </button>
        </view>

        <!-- Active alarm -->
        <view v-if="alarms.length" class="alarm">
          <text class="alarm-text">⚠ {{ alarms[alarms.length - 1] }}</text>
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<script>
import {
  listen,
  disconnect,
  setTemperature,
  setMode,
  setPower,
  setUnit,
  requestStatus,
  requestConfig,
  MODES,
} from '@/utils/ble.js'
import { getChannel, getDevice, clearChannel } from '@/utils/session.js'

export default {
  data() {
    return {
      statusBar: 20,
      deviceName: 'Sports Utility Refrigerator',
      connected: false,
      on: true,
      unit: 'C', // 'C' | 'F'
      setC: -6,
      curC: -4,
      mode: 1,
      battery: null,
      voltage: '',
      alarms: [],
      channel: null,
      stopListen: null,
      poll: null,
      sendTimer: null,
    }
  },
  computed: {
    modeName() {
      return MODES[this.mode] || '—'
    },
    modeList() {
      return [
        { i: 0, name: 'Turbo', hint: 'Fastest chill' },
        { i: 1, name: 'Eco', hint: 'Saves battery' },
        { i: 2, name: 'Keep', hint: 'Hold steady' },
      ]
    },
    unitSym() {
      return this.unit === 'C' ? '°C' : '°F'
    },
    toF() {
      return (c) => Math.round(c * 1.8 + 32)
    },
    currentDisplay() {
      return this.unit === 'C' ? this.curC : this.toF(this.curC)
    },
    setDisplay() {
      return this.unit === 'C' ? this.setC : this.toF(this.setC)
    },
    statusLabel() {
      if (!this.on) return 'Cooling is off'
      const diff = this.curC - this.setC
      if (diff > 0) return 'Cooling · ' + diff + '° to go'
      return 'At set temperature'
    },
    statusColor() {
      if (!this.on) return '#A3A6AC'
      return this.curC - this.setC > 0 ? '#8ACBFF' : '#A3A6AC'
    },
  },
  onLoad() {
    try {
      this.statusBar = uni.getSystemInfoSync().statusBarHeight || 20
    } catch (e) {}
    const ch = getChannel()
    const dev = getDevice()
    if (dev && (dev.name || dev.localName)) this.deviceName = dev.name || dev.localName
    if (!ch) {
      uni.redirectTo({ url: '/pages/connect/connect' })
      return
    }
    this.channel = ch
    this.connected = true
    this.stopListen = listen(ch, (s) => this.onStatus(s))
    this.refresh()
    this.poll = setInterval(() => this.refresh(), 3000)
  },
  onReady() {
    this.$nextTick(() => this.drawDial())
  },
  onUnload() {
    if (this.poll) clearInterval(this.poll)
    if (this.stopListen) this.stopListen()
    if (this.channel) disconnect(this.channel.deviceId)
    clearChannel()
  },
  methods: {
    fmt(v) {
      if (v == null) return '—'
      return v < 0 ? '−' + Math.abs(v) : '' + v
    },
    async refresh() {
      if (!this.channel) return
      try {
        await requestStatus(this.channel)
        await requestConfig(this.channel)
      } catch (e) {
        this.connected = false
      }
    },
    onStatus(s) {
      this.connected = true
      if (s.kind === 'status') {
        this.curC = s.currentTemp
        this.mode = s.mode
        this.voltage = s.voltage
        if (s.battery != null && !isNaN(s.battery)) this.battery = s.battery
        this.alarms = s.alarms || []
      } else if (s.kind === 'config') {
        this.setC = s.setTemp
        this.mode = s.mode
      } else if (s.kind === 'name') {
        this.deviceName = s.name
      }
      this.drawDial()
    },
    queueSend() {
      // Debounce rapid +/- taps into one write.
      if (this.sendTimer) clearTimeout(this.sendTimer)
      this.sendTimer = setTimeout(() => {
        if (this.channel) setTemperature(this.channel, this.setC).catch(() => {})
      }, 450)
    },
    dec() {
      if (!this.on) return
      this.setC = Math.max(-20, this.setC - 1)
      this.drawDial()
      this.queueSend()
    },
    inc() {
      if (!this.on) return
      this.setC = Math.min(30, this.setC + 1)
      this.drawDial()
      this.queueSend()
    },
    pickMode(i) {
      this.mode = i
      if (this.channel) setMode(this.channel, i).catch(() => {})
    },
    togglePower() {
      this.on = !this.on
      if (this.channel) setPower(this.channel, this.on).catch(() => {})
      this.drawDial()
    },
    toggleUnit() {
      this.unit = this.unit === 'C' ? 'F' : 'C'
      if (this.channel) setUnit(this.channel, this.unit === 'F').catch(() => {})
    },
    goSettings() {
      uni.navigateTo({ url: '/pages/settings/settings' })
    },
    back() {
      uni.navigateBack()
    },
    drawDial() {
      const ctx = uni.createCanvasContext('dial', this)
      const size = 300
      const cx = size / 2
      const cy = size / 2
      const r = 128
      const start = Math.PI * 0.75
      const sweep = Math.PI * 1.5
      const min = -20
      const max = 30
      const f = Math.max(0.001, Math.min(1, (this.setC - min) / (max - min)))

      ctx.clearRect(0, 0, size, size)
      // track
      ctx.beginPath()
      ctx.setLineWidth(10)
      ctx.setLineCap('round')
      ctx.setStrokeStyle('rgba(255,255,255,0.08)')
      ctx.arc(cx, cy, r, start, start + sweep)
      ctx.stroke()
      // value
      ctx.beginPath()
      ctx.setLineWidth(10)
      ctx.setLineCap('round')
      ctx.setStrokeStyle(this.on ? '#8ACBFF' : '#5E6268')
      ctx.arc(cx, cy, r, start, start + sweep * f)
      ctx.stroke()
      // knob
      const ang = start + sweep * f
      const kx = cx + r * Math.cos(ang)
      const ky = cy + r * Math.sin(ang)
      ctx.beginPath()
      ctx.setFillStyle('#F4F1EA')
      ctx.arc(kx, ky, 11, 0, Math.PI * 2)
      ctx.fill()
      ctx.draw()
    },
  },
}
</script>

<style scoped>
.page {
  min-height: 100vh;
  background-color: #0b0c0e;
}
.scroll {
  height: 100vh;
}
.wrap {
  padding: 24rpx 40rpx 48rpx;
}
.top {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
}
.icon-btn {
  width: 88rpx;
  height: 88rpx;
  border-radius: 44rpx;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background-color: #15171a;
  display: flex;
  align-items: center;
  justify-content: center;
}
.chev {
  color: #f4f1ea;
  font-size: 48rpx;
}
.gear {
  color: #f4f1ea;
  font-size: 36rpx;
}
.title-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.dname {
  font-size: 32rpx;
  font-weight: 600;
  color: #f4f1ea;
  max-width: 420rpx;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.link-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  margin-top: 6rpx;
}
.dot {
  width: 12rpx;
  height: 12rpx;
  border-radius: 6rpx;
  margin-right: 10rpx;
}
.link-text {
  font-size: 22rpx;
  color: #a3a6ac;
}
.vitals {
  display: flex;
  flex-direction: row;
  margin-top: 28rpx;
}
.vital {
  flex: 1;
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 24rpx;
  padding: 20rpx 24rpx;
  margin-right: 16rpx;
}
.vital:last-child {
  margin-right: 0;
}
.vlabel {
  display: block;
  font-size: 20rpx;
  letter-spacing: 1rpx;
  color: #a3a6ac;
  text-transform: uppercase;
}
.vval {
  display: block;
  font-size: 32rpx;
  font-weight: 600;
  color: #f4f1ea;
  margin-top: 8rpx;
}
.dial {
  width: 600rpx;
  height: 600rpx;
  margin: 24rpx auto 0;
  position: relative;
}
.dial-canvas {
  width: 600rpx;
  height: 600rpx;
}
.dial-center {
  position: absolute;
  left: 0;
  top: 0;
  width: 600rpx;
  height: 600rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.dial-cap {
  font-size: 24rpx;
  letter-spacing: 3rpx;
  color: #a3a6ac;
  text-transform: uppercase;
}
.dial-temp {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  margin: 6rpx 0;
}
.dial-val {
  font-size: 150rpx;
  line-height: 1;
  font-weight: 300;
  color: #f4f1ea;
  letter-spacing: -6rpx;
}
.dial-unit {
  font-size: 44rpx;
  color: #a3a6ac;
  margin-top: 20rpx;
}
.dial-stat {
  font-size: 26rpx;
  font-weight: 500;
}
.stepper {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 44rpx;
  padding: 16rpx;
  margin-top: 8rpx;
}
.step-btn {
  width: 112rpx;
  height: 112rpx;
  border-radius: 32rpx;
  background-color: #1f2226;
  display: flex;
  align-items: center;
  justify-content: center;
}
.sign {
  color: #f4f1ea;
  font-size: 48rpx;
}
.step-mid {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.step-cap {
  font-size: 22rpx;
  letter-spacing: 2rpx;
  color: #a3a6ac;
  text-transform: uppercase;
}
.step-val {
  font-size: 52rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.section {
  display: block;
  font-size: 22rpx;
  letter-spacing: 2rpx;
  color: #a3a6ac;
  text-transform: uppercase;
  margin: 32rpx 0 16rpx 8rpx;
}
.modes {
  display: flex;
  flex-direction: row;
}
.mode {
  flex: 1;
  height: 128rpx;
  border-radius: 28rpx;
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-right: 16rpx;
}
.mode:last-child {
  margin-right: 0;
}
.mode.on {
  background-color: #f4f1ea;
  border-color: #f4f1ea;
}
.mode-name {
  font-size: 30rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.mode-name.on {
  color: #0b0c0e;
}
.mode-hint {
  font-size: 22rpx;
  color: #a3a6ac;
  margin-top: 4rpx;
}
.mode-hint.on {
  color: #4a4d52;
}
.actions {
  display: flex;
  flex-direction: row;
  margin-top: 28rpx;
}
.act {
  flex: 1;
  height: 112rpx;
  border-radius: 36rpx;
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  margin-right: 16rpx;
}
.act:last-child {
  margin-right: 0;
}
.act.danger {
  background-color: #d52b1e;
  border-color: #d52b1e;
}
.act-text {
  font-size: 30rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.act-text.danger {
  color: #ffffff;
}
.unit-c,
.unit-f {
  font-size: 30rpx;
  font-weight: 600;
  color: #6e7279;
}
.unit-c.on,
.unit-f.on {
  color: #f4f1ea;
}
.unit-sep {
  color: #5e6268;
  font-size: 30rpx;
}
.alarm {
  margin-top: 24rpx;
  background-color: rgba(213, 43, 30, 0.12);
  border: 1px solid rgba(213, 43, 30, 0.45);
  border-radius: 24rpx;
  padding: 24rpx;
}
.alarm-text {
  font-size: 26rpx;
  color: #ff6b5e;
}
</style>
