<template>
  <view class="page">
    <view class="status" :style="{ height: statusBar + 'px' }"></view>

    <scroll-view scroll-y class="scroll">
      <view class="wrap">
        <view class="head">
          <view>
            <text class="hi">Welcome back</text>
            <text class="h1">My devices</text>
          </view>
          <button class="add" @tap="goConnect">
            <text class="plus">+</text>
          </button>
        </view>

        <!-- Device cards -->
        <view v-for="d in devices" :key="d.id" class="card" @tap="openDevice(d)">
          <view class="card-top">
            <view>
              <text class="card-name">{{ d.name || 'Sports Utility Refrigerator' }}</text>
              <view class="status-row">
                <view class="dot" :style="{ backgroundColor: d.online ? '#5BD69A' : '#6E7279' }"></view>
                <text class="status-text">{{ d.online ? ('Connected · ' + (d.modeLabel || 'Eco mode')) : 'Offline' }}</text>
              </view>
            </view>
            <view class="pill" :class="{ off: !d.on }">
              <text class="pill-text">{{ d.on ? 'On' : 'Off' }}</text>
            </view>
          </view>

          <view class="temp-row">
            <view class="temp">
              <text class="temp-val">{{ fmt(d.currentTemp) }}</text>
              <text class="temp-unit">°C</text>
            </view>
            <view class="temp-meta">
              <text class="meta">Set to {{ fmt(d.setTemp) }}°C</text>
              <text class="meta">Battery {{ d.battery != null ? d.battery + '%' : '—' }} · {{ d.voltage || '—' }} V</text>
            </view>
          </view>

          <view class="bar">
            <view class="bar-fill" :style="{ width: (d.battery || 0) + '%' }"></view>
          </view>

          <view class="open">
            <text class="open-text">Open controls</text>
            <text class="chev">›</text>
          </view>
        </view>

        <!-- Add another -->
        <view class="dashed" @tap="goConnect">
          <view class="dashed-ic"><text class="bt">⌁</text></view>
          <view>
            <text class="dashed-title">Add another fridge</text>
            <text class="dashed-sub">Switch it on and keep your phone close</text>
          </view>
        </view>

        <view class="tip">
          <text class="tip-text">On a long drive, Eco mode keeps food cold while protecting your car battery.</text>
        </view>
      </view>
    </scroll-view>

    <ak-tabbar active="devices" />
  </view>
</template>

<script>
import { api } from '@/utils/request.js'

export default {
  data() {
    return {
      statusBar: 20,
      devices: [],
    }
  },
  onLoad() {
    try {
      this.statusBar = uni.getSystemInfoSync().statusBarHeight || 20
    } catch (e) {}
  },
  onShow() {
    this.load()
  },
  methods: {
    fmt(v) {
      if (v == null || v === '') return '—'
      return v < 0 ? '−' + Math.abs(v) : '' + v
    },
    async load() {
      try {
        const list = await api.deviceList()
        this.devices = Array.isArray(list) ? list : (list && list.list) || []
      } catch (e) {
        // Backend not reachable yet — leave list empty; the add card still shows.
        this.devices = []
      }
    },
    openDevice(d) {
      uni.navigateTo({ url: '/pages/control/control?id=' + encodeURIComponent(d.id || '') })
    },
    goConnect() {
      uni.navigateTo({ url: '/pages/connect/connect' })
    },
  },
}
</script>

<style scoped>
.page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: #0b0c0e;
}
.scroll {
  flex: 1;
}
.wrap {
  padding: 24rpx 40rpx 40rpx;
}
.head {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 40rpx;
}
.hi {
  display: block;
  font-size: 26rpx;
  color: #a3a6ac;
  margin-bottom: 10rpx;
}
.h1 {
  display: block;
  font-size: 60rpx;
  font-weight: 600;
  color: #f4f1ea;
  letter-spacing: -1rpx;
}
.add {
  width: 96rpx;
  height: 96rpx;
  border-radius: 48rpx;
  background-color: #d52b1e;
  display: flex;
  align-items: center;
  justify-content: center;
}
.plus {
  color: #fff;
  font-size: 52rpx;
  line-height: 52rpx;
}
.card {
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 40rpx;
  padding: 36rpx;
  margin-bottom: 28rpx;
}
.card-top {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: flex-start;
}
.card-name {
  display: block;
  font-size: 34rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.status-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  margin-top: 12rpx;
}
.dot {
  width: 12rpx;
  height: 12rpx;
  border-radius: 6rpx;
  margin-right: 12rpx;
}
.status-text {
  font-size: 24rpx;
  color: #a3a6ac;
}
.pill {
  background-color: #f4f1ea;
  border-radius: 999rpx;
  padding: 10rpx 22rpx;
}
.pill.off {
  background-color: #2a2d32;
}
.pill-text {
  font-size: 24rpx;
  font-weight: 600;
  color: #0b0c0e;
}
.pill.off .pill-text {
  color: #a3a6ac;
}
.temp-row {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: flex-end;
  margin: 32rpx 0 24rpx;
}
.temp {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
}
.temp-val {
  font-size: 128rpx;
  line-height: 0.9;
  font-weight: 300;
  color: #f4f1ea;
  letter-spacing: -4rpx;
}
.temp-unit {
  font-size: 36rpx;
  color: #a3a6ac;
  margin-top: 8rpx;
}
.temp-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}
.meta {
  font-size: 24rpx;
  color: #a3a6ac;
  margin-top: 8rpx;
}
.bar {
  height: 12rpx;
  border-radius: 6rpx;
  background-color: rgba(255, 255, 255, 0.08);
  overflow: hidden;
}
.bar-fill {
  height: 12rpx;
  border-radius: 6rpx;
  background-color: #8acbff;
}
.open {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  margin-top: 28rpx;
}
.open-text {
  font-size: 28rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.chev {
  font-size: 40rpx;
  color: #f4f1ea;
}
.dashed {
  display: flex;
  flex-direction: row;
  align-items: center;
  border: 1px dashed rgba(255, 255, 255, 0.18);
  border-radius: 32rpx;
  padding: 28rpx 32rpx;
  margin-bottom: 28rpx;
}
.dashed-ic {
  width: 80rpx;
  height: 80rpx;
  border-radius: 24rpx;
  background-color: #1f2226;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 24rpx;
}
.bt {
  color: #8acbff;
  font-size: 40rpx;
}
.dashed-title {
  display: block;
  font-size: 30rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.dashed-sub {
  display: block;
  font-size: 24rpx;
  color: #a3a6ac;
  margin-top: 6rpx;
}
.tip {
  background-color: #121417;
  border-radius: 28rpx;
  padding: 28rpx 32rpx;
}
.tip-text {
  font-size: 24rpx;
  line-height: 1.5;
  color: #a3a6ac;
}
</style>
