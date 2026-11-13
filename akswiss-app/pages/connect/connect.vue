<template>
  <view class="page">
    <view class="status" :style="{ height: statusBar + 'px' }"></view>
    <view class="wrap">
      <view class="top">
        <button class="icon-btn" @tap="close"><text class="x">✕</text></button>
        <text class="step">Searching</text>
        <view class="spacer"></view>
      </view>

      <view class="intro">
        <text class="title">Looking for your fridge</text>
        <text class="sub">Make sure the fridge is switched on and within 5 metres. Bluetooth must be on.</text>
      </view>

      <!-- Radar -->
      <view class="radar">
        <view class="ring r1"></view>
        <view class="ring r2"></view>
        <view class="ring r3"></view>
        <view class="radar-core"><image class="bt-ic" src="/static/logo-mark.png" mode="aspectFit" /></view>
      </view>

      <view class="found-head">
        <text class="found-label">{{ scanning ? 'Scanning…' : 'Found nearby' }} · {{ devices.length }}</text>
      </view>

      <scroll-view scroll-y class="list">
        <view v-for="d in devices" :key="d.deviceId" class="row">
          <view class="row-ic"><text class="row-bt">❄</text></view>
          <view class="row-mid">
            <text class="row-name">{{ d.name || d.localName || 'Fridge' }}</text>
            <text class="row-sub">{{ signal(d.RSSI) }} · {{ shortId(d.deviceId) }}</text>
          </view>
          <button class="connect" :loading="connectingId === d.deviceId" @tap="pick(d)">Connect</button>
        </view>

        <view v-if="!devices.length" class="empty">
          <text class="empty-text">{{ scanning ? 'Searching for nearby fridges…' : 'No fridges found yet.' }}</text>
        </view>
      </scroll-view>

      <button class="help" @tap="rescan">Can’t find your fridge? Scan again</button>
    </view>
  </view>
</template>

<script>
import { openAdapter, startScan, connect } from '@/utils/ble.js'
import { setChannel } from '@/utils/session.js'

export default {
  data() {
    return {
      statusBar: 20,
      devices: [],
      scanning: false,
      connectingId: '',
      stopScan: null,
    }
  },
  onLoad() {
    try {
      this.statusBar = uni.getSystemInfoSync().statusBarHeight || 20
    } catch (e) {}
    this.begin()
  },
  onUnload() {
    if (this.stopScan) this.stopScan()
  },
  methods: {
    toast(m) {
      uni.showToast({ title: m, icon: 'none' })
    },
    async begin() {
      try {
        await openAdapter()
      } catch (e) {
        this.toast('Please turn on Bluetooth')
        return
      }
      this.scanning = true
      const seen = {}
      this.stopScan = startScan((d) => {
        if (seen[d.deviceId]) return
        seen[d.deviceId] = true
        this.devices.push(d)
      })
      // Stop scanning after 12s to save battery.
      setTimeout(() => {
        this.scanning = false
        if (this.stopScan) this.stopScan()
      }, 12000)
    },
    rescan() {
      this.devices = []
      if (this.stopScan) this.stopScan()
      this.begin()
    },
    async pick(d) {
      this.connectingId = d.deviceId
      if (this.stopScan) this.stopScan()
      try {
        const channel = await connect(d.deviceId)
        setChannel(channel, d)
        uni.redirectTo({ url: '/pages/control/control' })
      } catch (e) {
        this.toast(e.message || 'Connection failed')
      } finally {
        this.connectingId = ''
      }
    },
    signal(rssi) {
      if (rssi == null) return 'Signal'
      if (rssi > -60) return 'Strong signal'
      if (rssi > -80) return 'Good signal'
      return 'Weak signal'
    },
    shortId(id) {
      if (!id) return ''
      return 'ID ' + id.replace(/:/g, '').slice(-4).toUpperCase()
    },
    close() {
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
.wrap {
  padding: 24rpx 40rpx 48rpx;
  display: flex;
  flex-direction: column;
  height: 100vh;
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
.x {
  color: #f4f1ea;
  font-size: 32rpx;
}
.step {
  font-size: 26rpx;
  color: #a3a6ac;
}
.spacer {
  width: 88rpx;
}
.intro {
  margin-top: 24rpx;
}
.title {
  display: block;
  font-size: 52rpx;
  font-weight: 600;
  color: #f4f1ea;
  letter-spacing: -1rpx;
}
.sub {
  display: block;
  font-size: 28rpx;
  line-height: 1.5;
  color: #a3a6ac;
  margin-top: 14rpx;
}
.radar {
  width: 440rpx;
  height: 440rpx;
  margin: 32rpx auto;
  position: relative;
}
.ring {
  position: absolute;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.06);
}
.r1 {
  width: 440rpx;
  height: 440rpx;
  left: 0;
  top: 0;
}
.r2 {
  width: 320rpx;
  height: 320rpx;
  left: 60rpx;
  top: 60rpx;
}
.r3 {
  width: 200rpx;
  height: 200rpx;
  left: 120rpx;
  top: 120rpx;
  border-color: rgba(138, 203, 255, 0.3);
}
.radar-core {
  position: absolute;
  width: 112rpx;
  height: 112rpx;
  left: 164rpx;
  top: 164rpx;
  border-radius: 56rpx;
  background-color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
}
.bt-ic {
  width: 80rpx;
  height: 80rpx;
}
.found-head {
  margin-bottom: 16rpx;
}
.found-label {
  font-size: 22rpx;
  letter-spacing: 2rpx;
  color: #a3a6ac;
  text-transform: uppercase;
}
.list {
  flex: 1;
}
.row {
  display: flex;
  flex-direction: row;
  align-items: center;
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 28rpx;
  padding: 24rpx;
  margin-bottom: 18rpx;
}
.row-ic {
  width: 80rpx;
  height: 80rpx;
  border-radius: 20rpx;
  background-color: #1f2226;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 24rpx;
}
.row-bt {
  color: #8acbff;
  font-size: 34rpx;
}
.row-mid {
  flex: 1;
}
.row-name {
  display: block;
  font-size: 30rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.row-sub {
  display: block;
  font-size: 24rpx;
  color: #a3a6ac;
  margin-top: 6rpx;
}
.connect {
  height: 76rpx;
  padding: 0 32rpx;
  border-radius: 20rpx;
  background-color: #d52b1e;
  color: #ffffff;
  font-size: 28rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}
.empty {
  padding: 60rpx 0;
  display: flex;
  justify-content: center;
}
.empty-text {
  font-size: 26rpx;
  color: #8b8f96;
}
.help {
  height: 96rpx;
  background-color: transparent;
  color: #a3a6ac;
  font-size: 28rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
