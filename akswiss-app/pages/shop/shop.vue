<template>
  <view class="page">
    <view class="status" :style="{ height: statusBar + 'px' }"></view>
    <scroll-view scroll-y class="scroll">
      <view class="wrap">
        <text class="h1">Shop</text>

        <view v-if="products.length" class="grid">
          <view v-for="p in products" :key="p.id" class="prod" @tap="open(p)">
            <image class="prod-img" :src="p.image" mode="aspectFill" />
            <text class="prod-name">{{ p.name }}</text>
            <text class="prod-price">{{ p.price }}</text>
          </view>
        </view>

        <view v-else class="empty">
          <text class="empty-text">Products will appear here once your store is connected.</text>
        </view>
      </view>
    </scroll-view>

    <ak-tabbar active="shop" />
  </view>
</template>

<script>
import { api } from '@/utils/request.js'
import { STORAGE_URL } from '@/config/index.js'

export default {
  data() {
    return { statusBar: 20, products: [] }
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
        const list = await api.products()
        const rows = Array.isArray(list) ? list : (list && list.list) || []
        this.products = rows.map((r) => ({
          id: r.id,
          name: r.name || r.title || 'Product',
          price: r.price != null ? 'RM ' + r.price : '',
          image: r.image ? (r.image.startsWith('http') ? r.image : STORAGE_URL + r.image) : '',
        }))
      } catch (e) {
        this.products = []
      }
    },
    open(p) {
      uni.showToast({ title: p.name, icon: 'none' })
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
.grid {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: space-between;
}
.prod {
  width: 330rpx;
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 28rpx;
  padding: 18rpx 18rpx 24rpx;
  margin-bottom: 20rpx;
}
.prod-img {
  width: 294rpx;
  height: 240rpx;
  border-radius: 18rpx;
  background-color: #1b1e22;
}
.prod-name {
  display: block;
  font-size: 28rpx;
  font-weight: 600;
  color: #f4f1ea;
  margin-top: 16rpx;
}
.prod-price {
  display: block;
  font-size: 26rpx;
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
  text-align: center;
}
</style>
