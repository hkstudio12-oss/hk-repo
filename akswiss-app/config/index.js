/**
 * App configuration.
 *
 * IMPORTANT: this app no longer uses the old developer's server (akssz.com).
 * Point BASE_URL at YOUR OWN backend. The endpoints the app calls are listed
 * in README.md under "Backend API" — implement those on your server and the
 * app works unchanged.
 */

// TODO: replace with your own backend base URL (https strongly recommended).
export const BASE_URL = 'https://api.example.com'

// Where product / content images are served from on your backend.
export const STORAGE_URL = BASE_URL + '/storage/'

// Brand palette (kept in one place so screens stay consistent).
export const THEME = {
  bg: '#0B0C0E',
  surface: '#15171A',
  surfaceAlt: '#1F2226',
  line: 'rgba(255,255,255,0.07)',
  text: '#F4F1EA',
  textDim: '#A3A6AC',
  textMute: '#8B8F96',
  accent: '#8ACBFF', // cold blue, used for temperature
  brand: '#D52B1E', // AK SWISS red, used for primary actions
  good: '#5BD69A',
  warn: '#FF6B5E',
}
