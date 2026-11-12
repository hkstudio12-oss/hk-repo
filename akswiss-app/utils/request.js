/**
 * Thin HTTP wrapper around uni.request.
 * Adds the bearer token, prefixes BASE_URL, and surfaces a normalized error.
 */
import { BASE_URL } from '@/config/index.js'

const TOKEN_KEY = 'ak_token'

export function getToken() {
  try {
    return uni.getStorageSync(TOKEN_KEY) || ''
  } catch (e) {
    return ''
  }
}
export function setToken(t) {
  try {
    uni.setStorageSync(TOKEN_KEY, t || '')
  } catch (e) {}
}
export function clearToken() {
  try {
    uni.removeStorageSync(TOKEN_KEY)
  } catch (e) {}
}

export function request(path, { method = 'GET', data = {}, auth = true, header = {} } = {}) {
  return new Promise((resolve, reject) => {
    const headers = { 'Content-Type': 'application/json', ...header }
    if (auth) {
      const token = getToken()
      if (token) headers.Authorization = 'Bearer ' + token
    }
    uni.request({
      url: BASE_URL + path,
      method,
      data,
      header: headers,
      timeout: 60000,
      success: (res) => {
        const body = res.data || {}
        if (res.statusCode === 401) {
          clearToken()
          reject(new Error('Please sign in again'))
          return
        }
        // Convention: backend returns { code, message, data }. Adjust to taste.
        if (res.statusCode >= 200 && res.statusCode < 300 && (body.code === undefined || body.code === 0 || body.code === 200)) {
          resolve(body.data !== undefined ? body.data : body)
        } else {
          reject(new Error(body.message || 'Request failed (' + res.statusCode + ')'))
        }
      },
      fail: (err) => reject(new Error(err.errMsg || 'Network error')),
    })
  })
}

// ---- API surface (implement these on your backend; see README) -------------
export const api = {
  login: (email, password) => request('/auth/login', { method: 'POST', data: { email, password }, auth: false }),
  register: (email, password, code) =>
    request('/auth/register', { method: 'POST', data: { email, password, code }, auth: false }),
  sendCode: (email) => request('/auth/send-code', { method: 'POST', data: { email }, auth: false }),
  changePassword: (email, password, code) =>
    request('/auth/change-password', { method: 'POST', data: { email, password, code }, auth: false }),
  googleLogin: (idToken) => request('/auth/googleLogin', { method: 'POST', data: { idToken }, auth: false }),
  appleLogin: (idToken) => request('/auth/appleLogin', { method: 'POST', data: { idToken }, auth: false }),

  profile: () => request('/user/profile'),
  updateProfile: (data) => request('/auth/update-profile', { method: 'POST', data }),

  config: () => request('/site/all-config', { auth: false }),

  deviceList: () => request('/device/list'),
  addDevice: (data) => request('/device/add', { method: 'POST', data }),

  alarms: () => request('/device-alarm/list'),
  createAlarm: (data) => request('/device-alarm/create', { method: 'POST', data }),

  products: () => request('/product/list', { auth: false }),
}
