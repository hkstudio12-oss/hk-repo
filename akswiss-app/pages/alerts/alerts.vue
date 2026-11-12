<template>
  <view class="page">
    <view class="status" :style="{ height: statusBar + 'px' }"></view>
    <scroll-view scroll-y class="scroll">
      <view class="wrap">
        <view class="head">
          <text class="h1">Alerts</text>
          <button class="mark" @tap="markAll">Mark all read</button>
        </view>

        <view v-if="items.length">
          <view
            v-for="(a, idx) in items"
            :key="idx"
            class="card"
            :class="{ danger: a.severity === 'danger' }"
          >
            <view class="row">
              <view class="ic" :class="a.severity">
                <text class="ic-text">{{ a.severity === 'danger' ? '⚠' : a.severity === 'good' ? '✓' : '⚡' }}</text>
              </view>
              <view class="mid">
                <view class="titleline">
                  <text class="a-title">{{ a.title }}</text>
                  <text class="a-time">{{ a.time }}</text>
                </view>
                <text class="a-body">{{ a.body }}</text>
              </view>
            </view>
          </view>
        </view>

        <view v-else class="empty">
          <text class="empty-text">No alerts. Your fridge is running fine.</text>
        </view>
      </view>
    </scroll-view>

    <ak-tabbar active="alerts" />
  </view>
</template>

<script>
import { api } from '@/utils/request.js'

export default {
  data() {
    return { statusBar: 20, items: [] }
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
    async load() {
      try {
        const list = await api.alarms()
        const rows = Array.isArray(list) ? list : (list && list.list) || []
        this.items = rows.map((r) => ({
          title: r.title || r.name || 'Alert',
          body: r.message || r.body || '',
          time: r.time || r.created_at || '',
          severity: r.severity || 'info',
        }))
      } catch (e) {
        this.items = []
      }
    },
    markAll() {
      uni.showToast({ title: 'All marked read', icon: 'none' })
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
  align-items: flex-end;
  justify-content: space-between;
  margin-bottom: 28rpx;
}
.h1 {
  font-size: 60rpx;
  font-weight: 600;
  color: #f4f1ea;
  letter-spacing: -1rpx;
}
.mark {
  height: 68rpx;
  padding: 0 24rpx;
  border-radius: 34rpx;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background-color: transparent;
  color: #f4f1ea;
  font-size: 24rpx;
  display: flex;
  align-items: center;
}
.card {
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 28rpx;
  padding: 28rpx;
  margin-bottom: 18rpx;
}
.card.danger {
  border-color: rgba(213, 43, 30, 0.45);
}
.row {
  display: flex;
  flex-direction: row;
}
.ic {
  width: 72rpx;
  height: 72rpx;
  border-radius: 20rpx;
  background-color: #1f2226;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 22rpx;
}
.ic.danger {
  background-color: rgba(213, 43, 30, 0.16);
}
.ic-text {
  font-size: 30rpx;
  color: #a3a6ac;
}
.ic.danger .ic-text {
  color: #ff6b5e;
}
.ic.good .ic-text {
  color: #5bd69a;
}
.mid {
  flex: 1;
}
.titleline {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: baseline;
}
.a-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.a-time {
  font-size: 24rpx;
  color: #a3a6ac;
}
.a-body {
  display: block;
  font-size: 26rpx;
  line-height: 1.5;
  color: #a3a6ac;
  margin-top: 8rpx;
}
.empty {
  padding: 120rpx 0;
  display: flex;
  justify-content: center;
}
.empty-text {
  font-size: 28rpx;
  color: #8b8f96;
}
</style>
