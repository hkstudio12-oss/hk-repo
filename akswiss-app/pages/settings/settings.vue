<template>
  <view class="page">
    <view class="status" :style="{ height: statusBar + 'px' }"></view>
    <scroll-view scroll-y class="scroll">
      <view class="wrap">
        <view class="top">
          <button class="icon-btn" @tap="back"><text class="chev">‹</text></button>
          <text class="h1">Device settings</text>
        </view>

        <text class="section">Device name</text>
        <input class="input" v-model="name" placeholder="Device name" placeholder-class="ph" />

        <text class="section">Car battery protection</text>
        <view class="seg">
          <button class="seg-btn" :class="{ on: level === 0 }" @tap="level = 0">
            <text class="seg-text" :class="{ on: level === 0 }">High</text>
          </button>
          <button class="seg-btn" :class="{ on: level === 1 }" @tap="level = 1">
            <text class="seg-text" :class="{ on: level === 1 }">Low</text>
          </button>
        </view>
        <text class="hint">{{ levelHint }}</text>

        <text class="section">Schedule &amp; interior</text>
        <view class="group">
          <view class="line">
            <text class="line-label">Turn on at</text>
            <text class="line-val">07:00 ›</text>
          </view>
          <view class="line">
            <text class="line-label">Turn off at</text>
            <text class="line-val">Not set ›</text>
          </view>
          <view class="line">
            <text class="line-label">Interior light</text>
            <switch :checked="light" color="#8ACBFF" @change="light = $event.detail.value" />
          </view>
          <view class="line no-border">
            <text class="line-label">Interior fan</text>
            <switch :checked="fan" color="#8ACBFF" @change="fan = $event.detail.value" />
          </view>
        </view>

        <button class="primary" :loading="saving" @tap="save">Save changes</button>
      </view>
    </scroll-view>
  </view>
</template>

<script>
import { getChannel } from '@/utils/session.js'
import { setDeviceName } from '@/utils/ble.js'

export default {
  data() {
    return {
      statusBar: 20,
      name: 'Sports Utility Refrigerator',
      level: 0,
      light: true,
      fan: true,
      saving: false,
    }
  },
  computed: {
    levelHint() {
      return this.level === 0
        ? 'Stops cooling earlier to protect the car battery. Best when the engine is off.'
        : 'Keeps cooling longer before cutting out. Best when the engine is running.'
    },
  },
  onLoad() {
    try {
      this.statusBar = uni.getSystemInfoSync().statusBarHeight || 20
    } catch (e) {}
  },
  methods: {
    async save() {
      this.saving = true
      try {
        const ch = getChannel()
        if (ch && this.name) await setDeviceName(ch, this.name)
        uni.showToast({ title: 'Saved', icon: 'success' })
        setTimeout(() => uni.navigateBack(), 800)
      } catch (e) {
        uni.showToast({ title: e.message || 'Save failed', icon: 'none' })
      } finally {
        this.saving = false
      }
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
  margin-bottom: 8rpx;
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
  margin-right: 20rpx;
}
.chev {
  color: #f4f1ea;
  font-size: 48rpx;
}
.h1 {
  font-size: 44rpx;
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
.input {
  height: 104rpx;
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 28rpx;
  padding: 0 32rpx;
  font-size: 30rpx;
  color: #f4f1ea;
}
.ph {
  color: #6e7279;
}
.seg {
  display: flex;
  flex-direction: row;
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 24rpx;
  padding: 10rpx;
}
.seg-btn {
  flex: 1;
  height: 88rpx;
  border-radius: 18rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}
.seg-btn.on {
  background-color: #f4f1ea;
}
.seg-text {
  font-size: 30rpx;
  font-weight: 600;
  color: #a3a6ac;
}
.seg-text.on {
  color: #0b0c0e;
}
.hint {
  display: block;
  font-size: 24rpx;
  line-height: 1.5;
  color: #a3a6ac;
  margin: 14rpx 8rpx 0;
}
.group {
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 28rpx;
}
.line {
  height: 112rpx;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  padding: 0 32rpx;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}
.no-border {
  border-bottom: none;
}
.line-label {
  font-size: 30rpx;
  color: #f4f1ea;
}
.line-val {
  font-size: 28rpx;
  color: #a3a6ac;
}
.primary {
  height: 112rpx;
  border-radius: 56rpx;
  background-color: #d52b1e;
  color: #ffffff;
  font-size: 32rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 48rpx;
}
</style>
