<template>
  <view class="page">
    <view class="status" :style="{ height: statusBar + 'px' }"></view>
    <scroll-view scroll-y class="scroll">
      <view class="wrap">
        <view class="head">
          <image class="wordmark" src="/static/logo-wordmark.png" mode="widthFix" />
          <text class="eyebrow">SINCE 1881</text>
          <text class="title">Cold, wherever{{ '\n' }}the road goes.</text>
          <text class="sub">Sign in to control your fridge.</text>
        </view>

        <view class="field">
          <text class="flabel">Email</text>
          <input
            class="input"
            type="text"
            v-model="email"
            placeholder="you@example.com"
            placeholder-class="ph"
          />
        </view>

        <view class="field">
          <view class="frow">
            <text class="flabel">Password</text>
            <text class="link" @tap="forgot">Forgot?</text>
          </view>
          <view class="pwwrap">
            <input
              class="input"
              :password="!showPw"
              v-model="password"
              placeholder="6–12 characters"
              placeholder-class="ph"
            />
            <text class="toggle" @tap="showPw = !showPw">{{ showPw ? 'Hide' : 'Show' }}</text>
          </view>
        </view>

        <view class="agree" @tap="agree = !agree">
          <view class="box" :class="{ on: agree }">
            <text v-if="agree" class="tick">✓</text>
          </view>
          <text class="agree-text">I agree to the User Agreement and Privacy Policy</text>
        </view>

        <button class="primary" :loading="loading" @tap="onLogin">Sign in</button>

        <button class="warranty" @tap="scanWarranty">
          <text class="warranty-glyph">⛶</text>
          <text class="warranty-text">Scan your warranty</text>
        </button>

        <view class="or">
          <view class="rule"></view>
          <text class="or-text">or</text>
          <view class="rule"></view>
        </view>

        <button class="guest" @tap="guestLogin">Continue as guest</button>

        <view class="foot">
          <text class="foot-text">New here? </text>
          <text class="foot-link" @tap="register">Create an account</text>
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<script>
import { api, setToken } from '@/utils/request.js'

export default {
  data() {
    return {
      statusBar: 20,
      email: '',
      password: '',
      showPw: false,
      agree: true,
      loading: false,
    }
  },
  onLoad() {
    try {
      this.statusBar = uni.getSystemInfoSync().statusBarHeight || 20
    } catch (e) {}
  },
  methods: {
    toast(msg) {
      uni.showToast({ title: msg, icon: 'none' })
    },
    async onLogin() {
      if (!this.agree) return this.toast('Please agree to the terms to continue')
      if (!this.email) return this.toast('Please enter your email address')
      if (!this.password) return this.toast('Please enter your password')
      this.loading = true
      try {
        const data = await api.login(this.email.trim(), this.password)
        if (data && data.token) setToken(data.token)
        uni.reLaunch({ url: '/pages/home/home' })
      } catch (e) {
        this.toast(e.message || 'Sign in failed')
      } finally {
        this.loading = false
      }
    },
    guestLogin() {
      try {
        uni.setStorageSync('ak_guest', '1')
      } catch (e) {}
      uni.reLaunch({ url: '/pages/home/home' })
    },
    forgot() {
      uni.navigateTo({ url: '/pages/reset/reset' })
    },
    register() {
      uni.navigateTo({ url: '/pages/register/register' })
    },
    scanWarranty() {
      uni.navigateTo({ url: '/pages/warranty/warranty' })
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
  padding: 48rpx 48rpx 64rpx;
  display: flex;
  flex-direction: column;
}
.head {
  margin-top: 40rpx;
  margin-bottom: 56rpx;
}
.wordmark {
  width: 320rpx;
  margin-bottom: 36rpx;
}
.eyebrow {
  display: block;
  font-size: 22rpx;
  letter-spacing: 4rpx;
  color: #d52b1e;
  font-weight: 600;
  margin-bottom: 16rpx;
}
.title {
  display: block;
  font-size: 64rpx;
  line-height: 1.12;
  font-weight: 600;
  color: #f4f1ea;
  white-space: pre-line;
  letter-spacing: -1rpx;
}
.sub {
  display: block;
  font-size: 28rpx;
  color: #a3a6ac;
  margin-top: 16rpx;
}
.field {
  margin-bottom: 28rpx;
}
.frow {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: baseline;
}
.flabel {
  font-size: 26rpx;
  color: #a3a6ac;
  margin-bottom: 16rpx;
  display: block;
}
.link {
  font-size: 26rpx;
  color: #f4f1ea;
  font-weight: 500;
}
.input {
  height: 104rpx;
  background-color: #15171a;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 28rpx;
  padding: 0 32rpx;
  font-size: 32rpx;
  color: #f4f1ea;
}
.ph {
  color: #6e7279;
}
.pwwrap {
  position: relative;
}
.toggle {
  position: absolute;
  right: 28rpx;
  top: 36rpx;
  font-size: 26rpx;
  color: #a3a6ac;
}
.agree {
  display: flex;
  flex-direction: row;
  align-items: center;
  margin: 8rpx 0 36rpx;
}
.box {
  width: 36rpx;
  height: 36rpx;
  border-radius: 10rpx;
  border: 1px solid rgba(255, 255, 255, 0.25);
  margin-right: 18rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}
.box.on {
  background-color: #d52b1e;
  border-color: #d52b1e;
}
.tick {
  color: #fff;
  font-size: 24rpx;
}
.agree-text {
  flex: 1;
  font-size: 24rpx;
  color: #a3a6ac;
  line-height: 1.5;
}
.primary {
  height: 104rpx;
  border-radius: 52rpx;
  background-color: #d52b1e;
  color: #ffffff;
  font-size: 32rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}
.warranty {
  height: 104rpx;
  margin-top: 20rpx;
  border-radius: 52rpx;
  border: 1px solid rgba(138, 203, 255, 0.35);
  background-color: #15171a;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
}
.warranty-glyph {
  font-size: 34rpx;
  color: #8acbff;
  margin-right: 14rpx;
}
.warranty-text {
  font-size: 30rpx;
  font-weight: 600;
  color: #f4f1ea;
}
.or {
  display: flex;
  flex-direction: row;
  align-items: center;
  margin: 44rpx 0;
}
.rule {
  flex: 1;
  height: 1px;
  background-color: rgba(255, 255, 255, 0.08);
}
.or-text {
  font-size: 24rpx;
  color: #8b8f96;
  margin: 0 20rpx;
}
.guest {
  height: 104rpx;
  border-radius: 52rpx;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background-color: #15171a;
  color: #f4f1ea;
  font-size: 30rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}
.foot {
  margin-top: 56rpx;
  display: flex;
  flex-direction: row;
  justify-content: center;
}
.foot-text {
  font-size: 28rpx;
  color: #a3a6ac;
}
.foot-link {
  font-size: 28rpx;
  color: #f4f1ea;
  font-weight: 600;
}
</style>
