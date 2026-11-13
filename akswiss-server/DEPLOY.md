# AK SWISS backend + admin — deploy on Hostinger (hPanel)

Plain PHP + MySQL. No Composer, no SSH, no framework — it runs on standard
Hostinger web hosting. You get two things on your own domain:

- **`/api`** — the backend the phone app talks to.
- **`/admin`** — a dashboard where **you** log in to see users, warranties and
  warranty claims.

```
https://yourdomain.com/api     ← app points here (BASE_URL)
https://yourdomain.com/admin   ← you log in here
```

## Fast path: the web installer (recommended)

Instead of editing files by hand (steps 2 & 4 below), you can use the one-page
installer:

1. Create the MySQL database + user in hPanel (step 1 below).
2. Upload this whole folder into `public_html` (step 3 below).
3. Open **`https://yourdomain.com/install.php`** in your browser.
4. Fill in the database details and an admin username/password → **Install**.
   It tests the connection, creates the tables, and writes `core/config.php`
   (with a random `JWT_SECRET`) for you.
5. **Delete `install.php`** from the server afterwards.
6. Turn on SSL (step 5) and point the app at `/api` (step 6).

The manual steps below do the same thing by hand if you prefer.

---

## 1. Create the database (hPanel)

1. hPanel → **Databases → MySQL Databases**.
2. Create a database + user (note the full names, usually prefixed like
   `u123456_akswiss`), and a strong password.
3. Add the user to the database with **All Privileges**.

## 2. Import the tables

1. hPanel → **phpMyAdmin** → open your database.
2. **Import** tab → choose `schema.sql` from this folder → **Go**.

## 3. Upload the files

Upload the contents of this `akswiss-server/` folder into your site so that:

```
public_html/
  api/      (from akswiss-server/api)
  admin/    (from akswiss-server/admin)
  core/     (from akswiss-server/core)
```

Use hPanel → **File Manager** (or FTP). You can delete `schema.sql`,
`DEPLOY.md` and `config.sample.php` from the server after setup.

## 4. Configure

1. Copy `core/config.sample.php` to `core/config.php`.
2. Fill in: database name/user/password, a long random `JWT_SECRET`, and your
   admin username/password for the dashboard.
3. Set `MAIL_FROM` to an address on your domain (e.g. `no-reply@yourdomain.com`)
   so verification emails aren’t marked as spam.

`core/` is blocked from the browser by `core/.htaccess`, so `config.php` stays
private.

## 5. Turn on HTTPS

hPanel → **Security → SSL** → install the free SSL for your domain, then force
HTTPS. Both iOS and Android require HTTPS.

## 6. Point the app at your server

In the app repo, edit `akswiss-app/config/index.js`:

```js
export const BASE_URL = 'https://yourdomain.com/api'
```

Rebuild the APK and it now uses your backend.

## 7. Test

- Visit `https://yourdomain.com/admin` → sign in with your admin credentials.
- Quick API check: `https://yourdomain.com/api/product/list` should return
  `{"code":0,"message":"ok","data":[]}`.
- To test sign-up without email during setup, set `DEV_RETURN_CODE` to `true`
  in `config.php` — `/auth/send-code` then returns the code in the response.
  **Set it back to `false` before going live.**

## Email note

Verification codes are sent with PHP `mail()`. On some Hostinger plans you must
send from a mailbox that exists on your domain (create one under
**Emails → Email Accounts**) for delivery to work. If emails don’t arrive,
create `no-reply@yourdomain.com` and set it as `MAIL_FROM`.

## What the admin dashboard shows

- **Overview** — counts + latest warranty claims.
- **Users** — search, open a user to see their fridges and warranties.
- **Warranties** — filter by active/expired, search by serial or email.
- **Claims** — change a claim’s status (pending → approved/rejected/completed)
  and add a note.
- **Products** — add/edit/remove items shown in the app’s Shop tab.
- **Alerts** — fridge fault/status events.

## Security checklist

- Strong `ADMIN_PASS` and a long random `JWT_SECRET`.
- `DEV_RETURN_CODE = false` in production.
- Keep `config.php` only on the server, never in git (it’s git-ignored).
- HTTPS forced.
