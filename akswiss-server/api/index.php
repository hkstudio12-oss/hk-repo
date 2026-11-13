<?php
/**
 * AK SWISS API front controller.
 * Returns the { code, message, data } envelope the uni-app client expects.
 */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
require __DIR__ . '/../core/config.php';
require __DIR__ . '/../core/db.php';
require __DIR__ . '/../core/jwt.php';
require __DIR__ . '/../core/helpers.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Resolve the path after /api/
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = preg_replace('#^.*/api/#', '', $uri);
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'];
$route = $method . ' ' . $path;

try {
  switch ($route) {

    // ---- Auth ----
    case 'POST /auth/send-code': {
      $in = body();
      $email = strtolower(trim(field($in, 'email', '')));
      if (!valid_email($email)) fail('Please enter a valid email address');
      $code = gen_code();
      $stmt = db()->prepare('INSERT INTO verification_codes (email, code, purpose, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))');
      $stmt->execute([$email, $code, field($in, 'purpose', 'register')]);
      $sent = send_code_email($email, $code);
      $out = ['sent' => (bool) $sent];
      if (DEV_RETURN_CODE) $out['devCode'] = $code; // testing only
      ok($out);
    }

    case 'POST /auth/register': {
      $in = body();
      $email = strtolower(trim(field($in, 'email', '')));
      $password = (string) field($in, 'password', '');
      $code = trim((string) field($in, 'code', ''));
      if (!valid_email($email)) fail('Please enter a valid email address');
      if (strlen($password) < 6) fail('Password must be at least 6 characters');
      check_code($email, $code, 'register');
      $exists = db()->prepare('SELECT id FROM users WHERE email = ?');
      $exists->execute([$email]);
      if ($exists->fetch()) fail('This email is already registered', 409);
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $ins = db()->prepare('INSERT INTO users (email, password_hash, provider) VALUES (?, ?, "email")');
      $ins->execute([$email, $hash]);
      $uid = (int) db()->lastInsertId();
      ok(['token' => jwt_sign(['uid' => $uid]), 'id' => $uid, 'email' => $email]);
    }

    case 'POST /auth/login': {
      $in = body();
      $email = strtolower(trim(field($in, 'email', '')));
      $password = (string) field($in, 'password', '');
      if (!valid_email($email)) fail('Please enter a valid email address');
      $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
      $stmt->execute([$email]);
      $user = $stmt->fetch();
      if (!$user || !password_verify($password, $user['password_hash'])) fail('Email or password is incorrect', 401);
      ok([
        'token' => jwt_sign(['uid' => (int) $user['id']]),
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
      ]);
    }

    case 'POST /auth/change-password': {
      $in = body();
      $email = strtolower(trim(field($in, 'email', '')));
      $password = (string) field($in, 'password', '');
      $code = trim((string) field($in, 'code', ''));
      if (!valid_email($email)) fail('Please enter a valid email address');
      if (strlen($password) < 6) fail('Password must be at least 6 characters');
      check_code($email, $code, 'reset');
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $upd = db()->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
      $upd->execute([$hash, $email]);
      if ($upd->rowCount() === 0) fail('No account found for this email', 404);
      ok(['updated' => true]);
    }

    case 'POST /auth/googleLogin':
    case 'POST /auth/appleLogin':
      fail('Social sign-in is not configured yet', 501);

    // ---- Profile ----
    case 'GET /user/profile': {
      $uid = require_user();
      $stmt = db()->prepare('SELECT id, email, name, avatar FROM users WHERE id = ?');
      $stmt->execute([$uid]);
      $u = $stmt->fetch();
      if (!$u) fail('Account not found', 404);
      ok($u);
    }

    case 'POST /auth/update-profile': {
      $uid = require_user();
      $in = body();
      $name = trim((string) field($in, 'name', ''));
      $avatar = field($in, 'avatar', null);
      $upd = db()->prepare('UPDATE users SET name = COALESCE(NULLIF(?, ""), name), avatar = COALESCE(?, avatar) WHERE id = ?');
      $upd->execute([$name, $avatar, $uid]);
      ok(['updated' => true]);
    }

    // ---- Config ----
    case 'GET /site/all-config': {
      $rows = db()->query('SELECT cfg_key, cfg_value FROM app_config')->fetchAll();
      $cfg = [];
      foreach ($rows as $r) $cfg[$r['cfg_key']] = $r['cfg_value'];
      ok($cfg);
    }

    // ---- Devices ----
    case 'GET /device/list': {
      $uid = require_user();
      $stmt = db()->prepare('SELECT id, device_uid, name, last_temp, last_seen FROM devices WHERE user_id = ? ORDER BY id DESC');
      $stmt->execute([$uid]);
      ok($stmt->fetchAll());
    }

    case 'POST /device/add': {
      $uid = require_user();
      $in = body();
      $name = trim((string) field($in, 'name', 'Sports Utility Refrigerator'));
      $deviceUid = trim((string) field($in, 'id', field($in, 'device_uid', '')));
      $ins = db()->prepare('INSERT INTO devices (user_id, device_uid, name) VALUES (?, ?, ?)');
      $ins->execute([$uid, $deviceUid, $name]);
      ok(['id' => (int) db()->lastInsertId()]);
    }

    // ---- Alarms ----
    case 'GET /device-alarm/list': {
      $uid = require_user();
      $stmt = db()->prepare('SELECT id, code, title, message, severity, created_at AS time FROM device_alarms WHERE user_id = ? ORDER BY id DESC LIMIT 100');
      $stmt->execute([$uid]);
      ok($stmt->fetchAll());
    }

    case 'POST /device-alarm/create': {
      $uid = require_user();
      $in = body();
      $ins = db()->prepare('INSERT INTO device_alarms (user_id, device_id, code, title, message, severity) VALUES (?, ?, ?, ?, ?, ?)');
      $ins->execute([
        $uid,
        field($in, 'deviceId', null),
        field($in, 'code', null),
        field($in, 'title', 'Alert'),
        field($in, 'message', ''),
        field($in, 'severity', 'info'),
      ]);
      ok(['id' => (int) db()->lastInsertId()]);
    }

    // ---- Products ----
    case 'GET /product/list': {
      $rows = db()->query('SELECT id, name, price, image, category, description FROM products WHERE is_active = 1 ORDER BY id DESC')->fetchAll();
      ok($rows);
    }

    // ---- Warranty ----
    case 'GET /warranty/list': {
      $uid = require_user();
      $stmt = db()->prepare('SELECT id, serial, model, purchase_date, seller, registered_at, expires_at AS until, status FROM warranties WHERE user_id = ? ORDER BY id DESC');
      $stmt->execute([$uid]);
      $rows = $stmt->fetchAll();
      foreach ($rows as &$r) $r['expired'] = ($r['until'] && $r['until'] < date('Y-m-d'));
      ok($rows);
    }

    case 'POST /warranty/register': {
      $uid = require_user();
      $in = body();
      $serial = trim((string) field($in, 'serial', ''));
      if ($serial === '') fail('Serial number is required');
      $dup = db()->prepare('SELECT id FROM warranties WHERE serial = ?');
      $dup->execute([$serial]);
      if ($dup->fetch()) fail('This serial number is already registered', 409);
      $months = (int) (config_value('warranty_months') ?: 24);
      $purchase = field($in, 'purchaseDate', null) ?: date('Y-m-d');
      $expires = date('Y-m-d', strtotime($purchase . ' +' . $months . ' months'));
      $ins = db()->prepare('INSERT INTO warranties (user_id, serial, purchase_date, seller, expires_at) VALUES (?, ?, ?, ?, ?)');
      $ins->execute([$uid, $serial, $purchase, field($in, 'seller', null), $expires]);
      ok(['id' => (int) db()->lastInsertId(), 'expires_at' => $expires]);
    }

    case 'POST /warranty/claim': {
      $uid = require_user();
      $in = body();
      $warrantyId = (int) field($in, 'warrantyId', 0);
      $own = db()->prepare('SELECT id FROM warranties WHERE id = ? AND user_id = ?');
      $own->execute([$warrantyId, $uid]);
      if (!$own->fetch()) fail('Warranty not found', 404);
      $ins = db()->prepare('INSERT INTO warranty_claims (warranty_id, user_id, issue) VALUES (?, ?, ?)');
      $ins->execute([$warrantyId, $uid, field($in, 'issue', '')]);
      ok(['id' => (int) db()->lastInsertId()]);
    }

    default:
      fail('Not found: ' . $route, 404);
  }
} catch (Throwable $e) {
  fail('Server error', 500);
}

// ---- small helpers used above ----
function check_code($email, $code, $purpose) {
  if ($code === '') fail('Please enter the verification code');
  $stmt = db()->prepare('SELECT id FROM verification_codes WHERE email = ? AND code = ? AND purpose = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
  $stmt->execute([$email, $code, $purpose]);
  $row = $stmt->fetch();
  if (!$row) fail('The verification code is invalid or has expired');
  db()->prepare('UPDATE verification_codes SET used = 1 WHERE id = ?')->execute([$row['id']]);
}

function config_value($key) {
  $stmt = db()->prepare('SELECT cfg_value FROM app_config WHERE cfg_key = ?');
  $stmt->execute([$key]);
  $r = $stmt->fetch();
  return $r ? $r['cfg_value'] : null;
}
