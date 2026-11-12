# AK SWISS — app v2 (rebuilt UI)

A fresh, premium rebuild of the AK SWISS portable-fridge app. Same stack as the
original (**uni-app + Vue 3**) so it still talks to the existing fridges over
Bluetooth, but with a new dark, premium UI and a clean codebase you own.

Built so you can move **off the old developer entirely**:

- **Fridge control (Bluetooth)** — reconstructed faithfully from the original
  app, byte-for-byte. Works with the fridges already in the field. See
  [BLE protocol](#ble-protocol) below.
- **Accounts, alerts, shop** — point at **your own new backend**. The old
  `akssz.com` server is never contacted. You implement the small API in
  [Backend API](#backend-api) and set `BASE_URL`.

## Screens

| Screen | File | Notes |
|---|---|---|
| Sign in | `pages/login/login.vue` | email/password + Google/Apple hooks |
| Devices (home) | `pages/home/home.vue` | device list, battery/temp at a glance |
| Connect | `pages/connect/connect.vue` | BLE scan + connect |
| Control | `pages/control/control.vue` | the core — **draggable** temperature dial, set temp, modes, power, °C/°F |
| Register | `pages/register/register.vue` | create account, then warranty popup |
| Warranty | `pages/warranty/warranty.vue` | scan/enter serial to register warranty |
| Device settings | `pages/settings/settings.vue` | name, battery protection, schedule, light/fan |
| Alerts | `pages/alerts/alerts.vue` | fault + status notifications |
| Shop | `pages/shop/shop.vue` | product grid |
| Account | `pages/account/account.vue` | profile, help, sign out |

## Project layout

```
akswiss-app/
  config/index.js     # BASE_URL + theme  ← set your server here
  utils/
    modbus.js         # Modbus RTU framing + CRC16 (faithful reconstruction)
    ble.js            # scan/connect/listen/send + fridge operations + register map
    request.js        # HTTP wrapper + api.* surface
    session.js        # holds the active BLE channel between screens
  components/ak-tabbar # shared bottom tab bar
  pages/...           # the 8 screens above
  static/             # logo + tab icons
  pages.json manifest.json main.js App.vue uni.scss
```

## Build & run

This is a uni-app project. Two ways to build it to an Android APK:

**HBuilderX (easiest)**
1. Open HBuilderX → *File → Open Directory* → select `akswiss-app/`.
2. Set an `appid` in `manifest.json` (*Run → Create app id*, or paste your own).
3. *Run → Run to Phone/Emulator* to test, or *Publish → Native App (Cloud)* to
   get a signed APK.

**CLI (uni-app CLI / HBuilderX cloud)**
- uni-app's App build still needs HBuilderX or the DCloud cloud packager for the
  native APK. For quick UI testing you can run the H5 target:
  `npx @dcloudio/uvm` then `npm run dev:h5` in a CLI-created uni-app project that
  includes these files.

> Bluetooth only works in a real app build on a physical device, not in H5.

## Configure your backend

Edit `config/index.js`:

```js
export const BASE_URL = 'https://api.yourdomain.com'
```

That is the only change needed to leave `akssz.com` behind.

## Backend API

Implement these on your server. The app expects JSON `{ code, message, data }`
(code `0`/`200` = success) — adjust `utils/request.js` if your shape differs.
Auth is a bearer token returned by `/auth/login` as `data.token`.

| Method | Path | Body | Returns |
|---|---|---|---|
| POST | `/auth/login` | `{ email, password }` | `{ token, ...user }` |
| POST | `/auth/register` | `{ email, password, code }` | `{ token }` |
| POST | `/auth/send-code` | `{ email }` | ok |
| POST | `/auth/change-password` | `{ email, password, code }` | ok |
| POST | `/auth/googleLogin` | `{ idToken }` | `{ token }` |
| POST | `/auth/appleLogin` | `{ idToken }` | `{ token }` |
| GET | `/user/profile` | — | `{ name, email, avatar }` |
| POST | `/auth/update-profile` | `{ name, ... }` | ok |
| GET | `/site/all-config` | — | app config |
| GET | `/device/list` | — | `[{ id, name, online, on, currentTemp, setTemp, battery, voltage, modeLabel }]` |
| POST | `/device/add` | `{ id, name }` | ok |
| GET | `/device-alarm/list` | — | `[{ title, message, time, severity }]` |
| POST | `/device-alarm/create` | `{ deviceId, code }` | ok |
| GET | `/product/list` | — | `[{ id, name, price, image }]` |
| GET | `/warranty/list` | — | `[{ serial, model, until, expired }]` |
| POST | `/warranty/register` | `{ serial, purchaseDate, seller }` | ok |

`severity` is one of `info` \| `danger` \| `good` (controls the alert icon/colour).

### Warranty

Warranty registration runs through `pages/warranty/warranty.vue`:
- reachable from the **login screen** ("Scan your warranty"), the **account**
  screen ("Warranty" section), and as a **popup right after registration**.
- scans the unit's QR/barcode via `uni.scanCode`, or accepts a typed serial,
  plus purchase date and seller, then calls `POST /warranty/register`.
- a guest who scans before signing in keeps the serial and is prompted to sign in.

## BLE protocol

The fridge speaks **Modbus RTU over BLE**. This is firmware-defined — the app
must match it exactly, so `utils/modbus.js` and the register map in
`utils/ble.js` are reconstructed verbatim from the shipped app. Change the UI
freely; do not change these bytes without a real fridge to test against.

- **Service UUID:** `0000FFE0-0000-1000-8000-00805F9B34FB`
- **Framing:** standard Modbus RTU, CRC-16/MODBUS (poly `0xA001`, init `0xFFFF`),
  appended low byte then high byte.
- **Registers:** `30001–39999` → address `reg−30001` (input), `40001–49999` →
  address `reg−40001` (holding).
- **Write (Android):** `writeNoResponse`, ~300 ms settle, up to 5 retries.

Commands (slave address `1`):

| Action | Frame |
|---|---|
| Set temperature | fc `06` → reg `40001` = value (signed °) |
| Set mode | fc `06` → reg `40002` = `0` Turbo / `1` Eco / `2` Keep |
| Power on/off | fc `06` → reg `40003` = `1` / `0` |
| °C / °F | fc `06` → reg `40005` = `0` / `1` |
| Read live status | fc `04` → reg `30001`, qty `11` |
| Read config | fc `03` → reg `40001`, qty `28` |

Status frame (fc `04`, 27 bytes): current temp = `bytes[3..4]` signed16,
mode = `bytes[6]`, input voltage = `bytes[8] / 10`. Device name is set via
`AT+BLUFINAME=<name>` then `AT+BLEWF=1`.

## Notes

- Sample/placeholder values (e.g. battery %, product prices) show only until a
  real device/backend is connected; nothing is hardcoded as fact.
- The old developer's server is intentionally not referenced anywhere in this
  codebase.
