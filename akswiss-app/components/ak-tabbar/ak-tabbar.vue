<template>
  <view class="tabbar" :style="{ paddingBottom: safeBottom + 'px' }">
    <view
      v-for="item in items"
      :key="item.key"
      class="tab"
      hover-class="none"
      @tap="go(item)"
    >
      <image class="icon" :src="item.icon" mode="aspectFit" :style="{ opacity: active === item.key ? 1 : 0.55 }" />
      <text class="label" :class="{ on: active === item.key }">{{ item.text }}</text>
    </view>
  </view>
</template>

<script>
export default {
  name: 'ak-tabbar',
  props: {
    active: { type: String, default: 'devices' },
  },
  data() {
    return {
      safeBottom: 20,
      items: [
        { key: 'devices', text: 'Devices', icon: '/static/tab/devices.png', url: '/pages/home/home' },
        { key: 'shop', text: 'Shop', icon: '/static/tab/shop.png', url: '/pages/shop/shop' },
        { key: 'alerts', text: 'Alerts', icon: '/static/tab/alerts.png', url: '/pages/alerts/alerts' },
        { key: 'account', text: 'Account', icon: '/static/tab/account.png', url: '/pages/account/account' },
      ],
    }
  },
  created() {
    try {
      const sys = uni.getSystemInfoSync()
      this.safeBottom = (sys.safeAreaInsets && sys.safeAreaInsets.bottom) || 20
    } catch (e) {}
  },
  methods: {
    go(item) {
      if (item.key === this.active) return
      uni.reLaunch({ url: item.url })
    },
  },
}
</script>

<style scoped>
.tabbar {
  display: flex;
  flex-direction: row;
  padding: 10px 12px 0;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  background-color: #0e0f12;
}
.tab {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 6px 0;
}
.icon {
  width: 24px;
  height: 24px;
  margin-bottom: 4px;
}
.label {
  font-size: 11px;
  color: #8b8f96;
}
.label.on {
  color: #f4f1ea;
  font-weight: 600;
}
</style>
