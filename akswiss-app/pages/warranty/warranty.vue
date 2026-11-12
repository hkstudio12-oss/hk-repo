<template>
  <view class="page">
    <view class="status" :style="{ height: statusBar + 'px' }"></view>
    <scroll-view scroll-y class="scroll">
      <view class="wrap">
        <view class="top">
          <button class="icon-btn" @tap="back"><text class="chev">‹</text></button>
          <text class="h1">Warranty</text>
        </view>

        <text class="lead">Register your fridge to activate its warranty. Scan the QR or barcode on the unit, or type the serial number.</text>

        <!-- Scan tile -->
        <button class="scan" @tap="scan">
          <view class="scan-ic"><text class="scan-glyph">⛶</text></view>
          <view class="scan-txt">
            <text class="scan-title">Scan warranty code</text>
            <text class="scan-sub">Point your camera at the QR / barcode</text>
          </view>
        </button>

        <!-- Serial -->
        <text class="section">Serial number</text>
        <input class="input" v-model="serial" placeholder="e.g. AKS-2024-000000" placeholder-class="ph" />

        <!-- Purchase date -->
        <text class="section">Date of purchase</text>
        <picker mode="date" :value="purchaseDate" @change="purchaseDate = $event.detail.value">
          <view class="input picker">
            <text :class="{ dim: !purchaseDate }">{{ purchaseDate || 'Select date' }}</text>
            <text class="chev-sm">›</text>
          </view>
        </picker>

        <!-- Seller -->
        <text class="section">Where did you buy it?</text>
        <input class="input" v-model="seller" placeholder="Store or seller name" placeholder-class="ph" />

        <button class="primary" :loading="saving" @tap="submit">Register warranty</button>

        <!-- Existing -->
        <view v-if="items.length" class="existing">
          <text class="section">Your warranties</text>
          <view v-for="(w, i) in items" :key="i" class="wcard">
            <view class="wrow">
              <text class="wserial">{{ w.serial }}</text>
              <view class="wbadge" :class="{ expired: w.expired }">
                <text class="wbadge-text">{{ w.expired ? 'Expired' : 'Active' }}</text>
              </view>
            </view>
            <text class="wmeta">{{ w.model || 'Sports Utility Refrigerator' }} · covered until {{ w.until || '—' }}</text>
          </view>
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<script>
import { api, getToken } from '@/utils/request.js'

export default {
  data() {
    return {
      statusBar: 20,
      serial: '',
      purchaseDate: '',
      seller: '',
      saving: false,
      items: [],
    }
  },
  onLoad(q) {
    try {
      this.statusBar = uni.getSystemInfoSync().statusBarHeight || 20
    } catch (e) {}
    if (q && q.serial) this.serial = q.serial
    this.load()
  },
  methods: {
    toast(m) {
      uni.showToast({ title: m, icon: 'none' })
    },
    async load() {
      if (!getToken()) return
      try {
        const list = await api.warrantyList()
        const rows = Array.isArray(list) ? list : (list && list.list) || []
        this.items = rows.map((w) => ({
          serial: w.serial || w.sn || '—',
          model: w.model,
          until: w.until || w.expires_at,
          expired: !!w.expired,
        }))
      } catch (e) {
        this.items = []
      }
    },
    scan() {
      uni.scanCode({
        scanType: ['qrCode', 'barCode'],
        success: (r) => {
          if (r && r.result) {
            this.serial = r.result
            this.toast('Code captured')
          }
        },
        fail: () => this.toast('Scan cancelled'),
      })
    },
    async submit() {
      if (!this.serial.trim()) return this.toast('Enter or scan the serial number')
      if (!getToken()) {
        // Keep the scanned serial and ask them to sign in first.
        uni.showModal({
          title: 'Sign in to save',
          content: 'Please sign in so we can register the warranty to your account.',
          confirmText: 'Sign in',
          success: (r) => {
            if (r.confirm) uni.reLaunch({ url: '/pages/login/login' })
          },
        })
        return
      }
      this.saving = true
      try {
        await api.registerWarranty({
          serial: this.serial.trim(),
          purchaseDate: this.purchaseDate,
          seller: this.seller,
        })
        uni.showToast({ title: 'Warranty registered', icon: 'success' })
        this.serial = ''
        this.purchaseDate = ''
        this.seller = ''
        this.load()
      } catch (e) {
        this.toast(e.message || 'Could not register warranty')
      } finally {
        this.saving = false
      }
    },
    back() {
      const pages = getCurrentPages()
      if (pages.length > 1) uni.navigateBack()
      else uni.reLaunch({ url: '/pages/home/home' })
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
  padding: 24rpx 40rpx 64rpx;
}
.top {
  display: flex;
  flex-direction: row;
  align-items: center;
  margin-bottom: 16rpx;
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
.lead {
  display: block;
  font-size: 28rpx;
  line-height: 1.5;
  color: #a3a6ac;
  margin: 8rpx 8rpx 28rpx;
}
.scan {
  display: flex;
  flex-direction: row;
  align-items: center;
  background-color: #15171a;
  border: 1px dashed rgba(138, 203, 255, 0.4);
  border-radius: 28rpx;
  padding: 28rpx;
}
.scan-ic {
  width: 88rpx;
  height: 88rpx;
  border-radius: 22rpx;
  background-color: #1f2226;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 24rpx;
}
.scan-glyph {
  font-size: 44rpx;
  color: #8acbff;
}
.scan-txt {
  display: flex;
  flex-direction: column;
}
.scan-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.scan-sub {
  font-size: 24rpx;
  color: #a3a6ac;
  margin-top: 6rpx;
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
.picker {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
}
.dim {
  color: #6e7279;
}
.chev-sm {
  color: #8b8f96;
  font-size: 34rpx;
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
  margin-top: 44rpx;
}
.existing {
  margin-top: 16rpx;
}
.wcard {
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 24rpx;
  padding: 26rpx 28rpx;
  margin-bottom: 16rpx;
}
.wrow {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
}
.wserial {
  font-size: 30rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.wbadge {
  background-color: rgba(91, 214, 154, 0.16);
  border-radius: 999rpx;
  padding: 8rpx 18rpx;
}
.wbadge.expired {
  background-color: rgba(213, 43, 30, 0.16);
}
.wbadge-text {
  font-size: 22rpx;
  font-weight: 600;
  color: #5bd69a;
}
.wbadge.expired .wbadge-text {
  color: #ff6b5e;
}
.wmeta {
  display: block;
  font-size: 24rpx;
  color: #a3a6ac;
  margin-top: 10rpx;
}
</style>
