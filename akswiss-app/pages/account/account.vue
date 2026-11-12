<template>
  <view class="page">
    <view class="status" :style="{ height: statusBar + 'px' }"></view>
    <scroll-view scroll-y class="scroll">
      <view class="wrap">
        <text class="h1">Account</text>

        <view class="profile">
          <view class="avatar"><text class="initial">{{ initial }}</text></view>
          <view class="pinfo">
            <text class="pname">{{ profile.name || 'Your name' }}</text>
            <text class="pmail">{{ profile.email || 'you@example.com' }}</text>
          </view>
          <text class="edit">Edit ›</text>
        </view>

        <text class="section">Warranty</text>
        <view class="group">
          <view class="line" @tap="goWarranty">
            <text class="line-label">My warranties</text>
            <view class="right">
              <text class="count">{{ warrantyCount }}</text>
              <text class="chev">›</text>
            </view>
          </view>
          <view class="line no-border" @tap="goWarranty">
            <text class="line-label">Register a warranty</text>
            <text class="chev">›</text>
          </view>
        </view>

        <text class="section">Help</text>
        <view class="group">
          <view class="line" @tap="soon">
            <text class="line-label">User manual</text>
            <text class="chev">›</text>
          </view>
          <view class="line" @tap="soon">
            <text class="line-label">Call customer service</text>
            <text class="chev">›</text>
          </view>
          <view class="line no-border" @tap="soon">
            <text class="line-label">Send feedback</text>
            <text class="chev">›</text>
          </view>
        </view>

        <text class="section">About</text>
        <view class="group">
          <view class="line" @tap="soon">
            <text class="line-label">About AK SWISS</text>
            <text class="chev">›</text>
          </view>
          <view class="line" @tap="soon">
            <text class="line-label">Privacy &amp; terms</text>
            <text class="chev">›</text>
          </view>
          <view class="line no-border">
            <text class="line-label">App version</text>
            <text class="line-val">2.0.0</text>
          </view>
        </view>

        <button class="signout" @tap="signOut">Sign out</button>
      </view>
    </scroll-view>

    <ak-tabbar active="account" />
  </view>
</template>

<script>
import { api, clearToken } from '@/utils/request.js'

export default {
  data() {
    return { statusBar: 20, profile: {}, warrantyCount: 0 }
  },
  computed: {
    initial() {
      const n = this.profile.name || this.profile.email || 'A'
      return n.trim().charAt(0).toUpperCase()
    },
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
        this.profile = (await api.profile()) || {}
      } catch (e) {
        this.profile = {}
      }
      try {
        const list = await api.warrantyList()
        const rows = Array.isArray(list) ? list : (list && list.list) || []
        this.warrantyCount = rows.length
      } catch (e) {
        this.warrantyCount = 0
      }
    },
    goWarranty() {
      uni.navigateTo({ url: '/pages/warranty/warranty' })
    },
    soon() {
      uni.showToast({ title: 'Coming soon', icon: 'none' })
    },
    signOut() {
      uni.showModal({
        title: 'Sign out',
        content: 'Are you sure you want to sign out?',
        confirmColor: '#D52B1E',
        success: (r) => {
          if (r.confirm) {
            clearToken()
            uni.reLaunch({ url: '/pages/login/login' })
          }
        },
      })
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
.h1 {
  display: block;
  font-size: 60rpx;
  font-weight: 600;
  color: #f4f1ea;
  letter-spacing: -1rpx;
  margin-bottom: 28rpx;
}
.profile {
  display: flex;
  flex-direction: row;
  align-items: center;
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 32rpx;
  padding: 28rpx;
}
.avatar {
  width: 104rpx;
  height: 104rpx;
  border-radius: 52rpx;
  background-color: #f4f1ea;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 24rpx;
}
.initial {
  font-size: 40rpx;
  font-weight: 600;
  color: #0b0c0e;
}
.pinfo {
  flex: 1;
}
.pname {
  display: block;
  font-size: 32rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.pmail {
  display: block;
  font-size: 26rpx;
  color: #a3a6ac;
  margin-top: 6rpx;
}
.edit {
  font-size: 26rpx;
  color: #a3a6ac;
}
.section {
  display: block;
  font-size: 22rpx;
  letter-spacing: 2rpx;
  color: #a3a6ac;
  text-transform: uppercase;
  margin: 32rpx 0 16rpx 8rpx;
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
.chev {
  font-size: 34rpx;
  color: #8b8f96;
}
.right {
  display: flex;
  flex-direction: row;
  align-items: center;
}
.count {
  font-size: 26rpx;
  color: #8acbff;
  font-weight: 600;
  margin-right: 12rpx;
  min-width: 28rpx;
  text-align: right;
}
.signout {
  height: 104rpx;
  border-radius: 52rpx;
  border: 1px solid rgba(213, 43, 30, 0.5);
  background-color: transparent;
  color: #ff6b5e;
  font-size: 30rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 36rpx;
}
</style>
