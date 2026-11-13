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
            <text class="vval" :style="{ color: batteryLow ? '#FF6B5E' : '#F4F1EA' }">{{ battery != null ? battery + '%' : '—' }}</text>
          </view>
          <view class="vital">
            <text class="vlabel">Input</text>
            <text class="vval">{{ voltage || '—' }} V</text>
          </view>
          <view class="vital">
            <text class="vlabel">Mode</text>
            <text class="vval" :style="{ color: modeColors[mode] }">{{ modeName }}</text>
          </view>
        </view>

        <!-- Dial -->
        <view class="dial" :style="{ opacity: on ? 1 : 0.45 }">
          <canvas canvas-id="dial" id="dial" class="dial-canvas"></canvas>
          <view class="aura" :style="auraStyle"></view>
          <view class="dial-center">
            <text class="dial-cap">Inside now</text>
            <view class="dial-temp">
              <text class="dial-val">{{ fmt(currentDisplay) }}</text>
              <text class="dial-unit">{{ unitSym }}</text>
            </view>
            <text class="dial-stat" :style="{ color: statusColor }">{{ statusLabel }}</text>
          </view>
          <view
            class="dial-touch"
            @touchstart.stop.prevent="startDial"
            @touchmove.stop.prevent="onDialTouch"
          ></view>
        </view>
        <view class="range">
          <text class="rt">{{ fmt(rangeMin) }}°</text>
          <text class="rt">{{ fmt(rangeMax) }}°</text>
        </view>

        <!-- Runtime estimate -->
        <view v-if="battery != null" class="runtime">
          <text class="runtime-r">{{ runtimeText }}</text>
          <text class="runtime-tag">estimate</text>
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

        <!-- Live temperature graph -->
        <view class="card">
          <text class="card-h">Temperature history</text>
          <text class="card-sub">Live · last few minutes</text>
          <canvas canvas-id="graph" id="graph" class="graph-canvas"></canvas>
          <view class="glabel">
            <text class="glabel-t">set {{ fmt(setDisplay) }}°</text>
            <text class="glabel-t" :style="{ color: on ? auraColor(dispCur) : '#8B8F96' }">now {{ fmt(currentDisplay) }}°</text>
          </view>
        </view>

        <!-- Battery panel -->
        <view class="card">
          <view class="bat-line">
            <text class="card-h">Battery &amp; power</text>
            <text class="bat-pct" :style="{ color: batteryLow ? '#FF6B5E' : '#F4F1EA' }">{{ battery != null ? battery + '%' : '—' }}</text>
          </view>
          <view class="bar">
            <view class="bar-fill" :style="{ width: (battery || 0) + '%', background: batteryLow ? '#FF6B5E' : 'linear-gradient(90deg,#5BD69A,#8ACBFF)' }"></view>
          </view>
          <text class="charge">{{ voltage ? (voltage + ' V input') : 'Waiting for reading…' }}</text>
        </view>

        <!-- Modes -->
        <text class="section">Cooling mode</text>
        <view class="modes">
          <button
            v-for="m in modeList"
            :key="m.i"
            class="mode"
            :style="mode === m.i ? { background: modeColors[m.i], borderColor: modeColors[m.i] } : {}"
            @tap="pickMode(m.i)"
          >
            <text class="mode-name" :style="{ color: mode === m.i ? '#0B0C0E' : '#F4F1EA' }">{{ m.name }}</text>
            <text class="mode-hint" :style="{ color: mode === m.i ? 'rgba(11,12,14,0.55)' : '#A3A6AC' }">{{ m.hint }}</text>
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
      unit: 'C',
      setC: -6,
      curC: -4,
      dispCur: -4,
      mode: 1,
      battery: null,
      voltage: '',
      alarms: [],
      history: [],
      channel: null,
      stopListen: null,
      poll: null,
      anim: null,
      sendTimer: null,
      dialRect: null,
      dialSize: 300,
      graphW: 0,
      graphH: 0,
      modeColors: ['#8ACBFF', '#5BD69A', '#F4F1EA'],
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
    currentDisplay() {
      const v = Math.round(this.dispCur)
      return this.unit === 'C' ? v : Math.round(v * 1.8 + 32)
    },
    setDisplay() {
      return this.unit === 'C' ? this.setC : Math.round(this.setC * 1.8 + 32)
    },
    rangeMin() {
      return this.unit === 'C' ? -20 : -4
    },
    rangeMax() {
      return this.unit === 'C' ? 30 : 86
    },
    batteryLow() {
      return this.battery != null && this.battery <= 20
    },
    runtimeText() {
      if (this.battery == null) return ''
      const total = (this.battery / 100) * 21 // rough hours at full
      const h = Math.floor(total)
      const m = Math.round((total - h) * 60)
      return '~' + h + 'h ' + (m < 10 ? '0' : '') + m + 'm left'
    },
    statusLabel() {
      if (!this.on) return 'Cooling is off'
      const diff = Math.max(0, Math.round(this.curC - this.setC))
      return diff > 0 ? 'Cooling · ' + diff + '° to go' : 'At set temperature'
    },
    statusColor() {
      if (!this.on) return '#A3A6AC'
      return Math.round(this.curC - this.setC) > 0 ? this.auraColor(this.dispCur) : '#A3A6AC'
    },
    auraStyle() {
      const c = this.on ? this.auraColor(this.dispCur) : 'rgb(120,120,120)'
      return 'background: radial-gradient(circle, ' + c + ' 0%, rgba(0,0,0,0) 70%); opacity: ' + (this.on ? 0.5 : 0.15) + ';'
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
    // smooth animation + live graph feed
    this.anim = setInterval(() => this.tick(), 500)
  },
  onReady() {
    this.$nextTick(() => {
      this.measure()
      setTimeout(() => {
        this.drawDial()
        this.drawGraph()
      }, 60)
    })
  },
  onUnload() {
    if (this.poll) clearInterval(this.poll)
    if (this.anim) clearInterval(this.anim)
    if (this.stopListen) this.stopListen()
    if (this.channel) disconnect(this.channel.deviceId)
    clearChannel()
  },
  methods: {
    fmt(v) {
      if (v == null) return '—'
      return v < 0 ? '−' + Math.abs(v) : '' + v
    },
    // temperature -> aura/accent colour
    auraColor(t) {
      const stops = [
        [-20, [46, 91, 255]],
        [-6, [138, 203, 255]],
        [5, [91, 214, 154]],
        [15, [245, 166, 35]],
        [30, [255, 90, 60]],
      ]
      if (t <= stops[0][0]) t = stops[0][0]
      if (t >= stops[stops.length - 1][0]) t = stops[stops.length - 1][0]
      for (let i = 0; i < stops.length - 1; i++) {
        if (t >= stops[i][0] && t <= stops[i + 1][0]) {
          const f = (t - stops[i][0]) / (stops[i + 1][0] - stops[i][0])
          const c = [0, 1, 2].map((k) => Math.round(stops[i][1][k] + (stops[i + 1][1][k] - stops[i][1][k]) * f))
          return 'rgb(' + c[0] + ',' + c[1] + ',' + c[2] + ')'
        }
      }
      return 'rgb(138,203,255)'
    },
    measure() {
      uni
        .createSelectorQuery()
        .in(this)
        .select('.dial')
        .boundingClientRect((r) => {
          if (r) {
            this.dialRect = r
            this.dialSize = Math.min(r.width, r.height)
          }
        })
        .exec()
      uni
        .createSelectorQuery()
        .in(this)
        .select('.graph-canvas')
        .boundingClientRect((r) => {
          if (r) {
            this.graphW = r.width
            this.graphH = r.height
          }
        })
        .exec()
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
    },
    // eased display value + feed graph + redraw
    tick() {
      this.dispCur += (this.curC - this.dispCur) * 0.3
      this.history.push({ cur: this.dispCur, set: this.setC })
      if (this.history.length > 90) this.history.shift()
      this.drawDial()
      this.drawGraph()
    },
    drawDial() {
      const ctx = uni.createCanvasContext('dial', this)
      const size = this.dialSize || 300
      const cx = size / 2
      const cy = size / 2
      const r = size * (128 / 300)
      const start = Math.PI * 0.75
      const sweep = Math.PI * 1.5
      const f = Math.max(0.001, Math.min(1, (this.setC - -20) / (30 - -20)))
      const col = this.on ? this.auraColor(this.setC) : '#5E6268'

      ctx.clearRect(0, 0, size, size)
      ctx.beginPath()
      ctx.setLineWidth(10)
      ctx.setLineCap('round')
      ctx.setStrokeStyle('rgba(255,255,255,0.08)')
      ctx.arc(cx, cy, r, start, start + sweep)
      ctx.stroke()
      ctx.beginPath()
      ctx.setLineWidth(10)
      ctx.setLineCap('round')
      ctx.setStrokeStyle(col)
      ctx.arc(cx, cy, r, start, start + sweep * f)
      ctx.stroke()
      const ang = start + sweep * f
      const kx = cx + r * Math.cos(ang)
      const ky = cy + r * Math.sin(ang)
      ctx.beginPath()
      ctx.setFillStyle('#F4F1EA')
      ctx.arc(kx, ky, 11, 0, Math.PI * 2)
      ctx.fill()
      ctx.beginPath()
      ctx.setFillStyle(col)
      ctx.arc(kx, ky, 4, 0, Math.PI * 2)
      ctx.fill()
      ctx.draw()
    },
    drawGraph() {
      if (!this.graphW || this.history.length < 2) return
      const ctx = uni.createCanvasContext('graph', this)
      const W = this.graphW
      const H = this.graphH
      const pad = 6
      const vals = []
      this.history.forEach((h) => {
        vals.push(h.cur, h.set)
      })
      let lo = Math.min.apply(null, vals) - 1
      let hi = Math.max.apply(null, vals) + 1
      if (hi - lo < 4) hi = lo + 4
      const n = this.history.length
      const X = (i) => pad + ((W - 2 * pad) * i) / (n - 1)
      const Y = (v) => pad + (H - 2 * pad) * (1 - (v - lo) / (hi - lo))
      const col = this.on ? this.auraColor(this.dispCur) : '#787878'

      ctx.clearRect(0, 0, W, H)
      // gridlines
      ctx.setStrokeStyle('rgba(255,255,255,0.05)')
      ctx.setLineWidth(1)
      for (let g = 1; g <= 2; g++) {
        const y = (H * g) / 3
        ctx.beginPath()
        ctx.moveTo(0, y)
        ctx.lineTo(W, y)
        ctx.stroke()
      }
      // line
      ctx.beginPath()
      ctx.setLineWidth(2)
      ctx.setStrokeStyle(col)
      ctx.setLineJoin('round')
      this.history.forEach((h, i) => {
        const x = X(i)
        const y = Y(h.cur)
        if (i === 0) ctx.moveTo(x, y)
        else ctx.lineTo(x, y)
      })
      ctx.stroke()
      // dot
      const lx = X(n - 1)
      const ly = Y(this.history[n - 1].cur)
      ctx.beginPath()
      ctx.setFillStyle(col)
      ctx.arc(lx, ly, 3, 0, Math.PI * 2)
      ctx.fill()
      ctx.draw()
    },
    // draggable dial
    applyDialTouch(t) {
      if (!this.on || !t || !this.dialRect) return
      const cx = this.dialRect.left + this.dialRect.width / 2
      const cy = this.dialRect.top + this.dialRect.height / 2
      const x = t.clientX != null ? t.clientX : t.pageX
      const y = t.clientY != null ? t.clientY : t.pageY
      let deg = (Math.atan2(y - cy, x - cx) * 180) / Math.PI
      deg = (deg + 360) % 360
      const delta = (deg - 135 + 360) % 360
      if (delta > 270) return
      const temp = Math.round(-20 + (delta / 270) * 50)
      const clamped = Math.max(-20, Math.min(30, temp))
      if (clamped !== this.setC) {
        this.setC = clamped
        this.drawDial()
        this.queueSend()
      }
    },
    startDial(e) {
      this.measure()
      const t = e.touches && e.touches[0]
      if (t) this.applyDialTouch(t)
    },
    onDialTouch(e) {
      const t = e.touches && e.touches[0]
      if (t) this.applyDialTouch(t)
    },
    queueSend() {
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
.aura {
  position: absolute;
  left: 50%;
  top: 50%;
  width: 440rpx;
  height: 440rpx;
  transform: translate(-50%, -50%);
  border-radius: 50%;
  filter: blur(40rpx);
  pointer-events: none;
}
.dial-touch {
  position: absolute;
  left: 0;
  top: 0;
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
.range {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  width: 470rpx;
  margin: -40rpx auto 0;
}
.rt {
  font-size: 22rpx;
  color: #8b8f96;
}
.runtime {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 999rpx;
  padding: 12rpx 24rpx;
  margin: 20rpx auto 0;
  width: max-content;
}
.runtime-r {
  font-size: 26rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.runtime-tag {
  font-size: 20rpx;
  color: #8b8f96;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8rpx;
  padding: 2rpx 10rpx;
  margin-left: 8rpx;
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
  margin-top: 20rpx;
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
.card {
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 28rpx;
  padding: 24rpx;
  margin-top: 20rpx;
}
.card-h {
  display: block;
  font-size: 28rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.card-sub {
  display: block;
  font-size: 22rpx;
  color: #a3a6ac;
  margin-top: 4rpx;
  margin-bottom: 14rpx;
}
.graph-canvas {
  width: 100%;
  height: 100rpx;
}
.glabel {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  margin-top: 10rpx;
}
.glabel-t {
  font-size: 20rpx;
  color: #8b8f96;
}
.bat-line {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: baseline;
}
.bat-pct {
  font-size: 40rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.bar {
  height: 20rpx;
  border-radius: 12rpx;
  background-color: rgba(255, 255, 255, 0.08);
  overflow: hidden;
  margin-top: 16rpx;
}
.bar-fill {
  height: 20rpx;
  border-radius: 12rpx;
  background-color: #8acbff;
}
.charge {
  display: block;
  font-size: 22rpx;
  color: #a3a6ac;
  margin-top: 14rpx;
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
.mode-name {
  font-size: 30rpx;
  font-weight: 600;
}
.mode-hint {
  font-size: 22rpx;
  margin-top: 4rpx;
}
.actions {
  display: flex;
  flex-direction: row;
  margin-top: 20rpx;
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
