<?php
/**
 * Copy this file to config.php and fill in your values.
 * In hPanel: create the MySQL database + user first (Databases -> Management),
 * then paste the credentials here.
 *
 * Keep config.php OUT of the browser's reach — it lives in /core which is
 * blocked by core/.htaccess. Never commit your real config.php.
 */

// --- Database (from hPanel -> Databases) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'uXXXXXX_akswiss');
define('DB_USER', 'uXXXXXX_akswiss');
define('DB_PASS', 'your-db-password');

// --- Security ---
// Long random string. Generate one, then keep it secret. Changing it logs
// everyone out of the app.
define('JWT_SECRET', 'change-this-to-a-long-random-string');
define('JWT_TTL_DAYS', 30);

// --- Admin dashboard login (you, not app users) ---
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'change-this-admin-password');

// --- Email (verification codes) ---
define('MAIL_FROM', 'no-reply@yourdomain.com');
define('MAIL_FROM_NAME', 'AK SWISS');

// Testing only: when true, /auth/send-code returns the code in the response
// so you can test sign-up without email delivery. MUST be false in production.
define('DEV_RETURN_CODE', false);
