<template>
  <view class="page">
    <view class="status" :style="{ height: statusBar + 'px' }"></view>
    <scroll-view scroll-y class="scroll">
      <view class="wrap">
        <view class="top">
          <button class="icon-btn" @tap="back"><text class="chev">‹</text></button>
        </view>

        <text class="title">Create your account</text>
        <text class="sub">For the security of your account, please verify your email.</text>

        <view class="field">
          <text class="flabel">Email</text>
          <input class="input" v-model="email" placeholder="you@example.com" placeholder-class="ph" />
        </view>

        <view class="field">
          <text class="flabel">Verification code</text>
          <view class="coderow">
            <input class="input flex1" v-model="code" placeholder="6-digit code" placeholder-class="ph" />
            <button class="codebtn" :disabled="cooldown > 0" @tap="sendCode">
              {{ cooldown > 0 ? cooldown + 's' : 'Send code' }}
            </button>
          </view>
        </view>

        <view class="field">
          <text class="flabel">Password</text>
          <input class="input" :password="true" v-model="password" placeholder="6–12 characters" placeholder-class="ph" />
        </view>

        <button class="primary" :loading="loading" @tap="onRegister">Create account</button>

        <view class="foot">
          <text class="foot-text">Already have an account? </text>
          <text class="foot-link" @tap="back">Sign in</text>
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
      code: '',
      password: '',
      loading: false,
      cooldown: 0,
      timer: null,
    }
  },
  onLoad() {
    try {
      this.statusBar = uni.getSystemInfoSync().statusBarHeight || 20
    } catch (e) {}
  },
  onUnload() {
    if (this.timer) clearInterval(this.timer)
  },
  methods: {
    toast(m) {
      uni.showToast({ title: m, icon: 'none' })
    },
    isEmail(v) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)
    },
    async sendCode() {
      if (!this.isEmail(this.email)) return this.toast('Enter a valid email first')
      try {
        await api.sendCode(this.email.trim())
        this.toast('Code sent')
        this.cooldown = 60
        this.timer = setInterval(() => {
          this.cooldown -= 1
          if (this.cooldown <= 0) clearInterval(this.timer)
        }, 1000)
      } catch (e) {
        this.toast(e.message || 'Could not send code')
      }
    },
    async onRegister() {
      if (!this.isEmail(this.email)) return this.toast('Enter a valid email')
      if (!this.code) return this.toast('Enter the verification code')
      if (!this.password || this.password.length < 6) return this.toast('Password must be at least 6 characters')
      this.loading = true
      try {
        const data = await api.register(this.email.trim(), this.password, this.code)
        if (data && data.token) setToken(data.token)
        this.promptWarranty()
      } catch (e) {
        this.toast(e.message || 'Registration failed')
      } finally {
        this.loading = false
      }
    },
    promptWarranty() {
      uni.showModal({
        title: 'Register your warranty',
        content: 'Welcome aboard! Register your fridge now to activate its warranty — it only takes a moment.',
        confirmText: 'Register now',
        cancelText: 'Later',
        confirmColor: '#D52B1E',
        success: (r) => {
          if (r.confirm) uni.reLaunch({ url: '/pages/warranty/warranty' })
          else uni.reLaunch({ url: '/pages/home/home' })
        },
      })
    },
    back() {
      const pages = getCurrentPages()
      if (pages.length > 1) uni.navigateBack()
      else uni.reLaunch({ url: '/pages/login/login' })
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
  padding: 24rpx 48rpx 64rpx;
}
.top {
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
}
.chev {
  color: #f4f1ea;
  font-size: 48rpx;
}
.title {
  display: block;
  font-size: 56rpx;
  font-weight: 600;
  color: #f4f1ea;
  letter-spacing: -1rpx;
  margin-top: 16rpx;
}
.sub {
  display: block;
  font-size: 28rpx;
  color: #a3a6ac;
  margin-top: 14rpx;
  margin-bottom: 24rpx;
}
.field {
  margin-top: 24rpx;
}
.flabel {
  display: block;
  font-size: 26rpx;
  color: #a3a6ac;
  margin-bottom: 16rpx;
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
.coderow {
  display: flex;
  flex-direction: row;
  align-items: center;
}
.flex1 {
  flex: 1;
  margin-right: 16rpx;
}
.codebtn {
  height: 104rpx;
  padding: 0 28rpx;
  border-radius: 28rpx;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background-color: #15171a;
  color: #8acbff;
  font-size: 26rpx;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
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
.foot {
  margin-top: 40rpx;
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
