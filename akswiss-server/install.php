<?php
/**
 * One-time web installer for the AK SWISS backend.
 *
 * Upload the whole akswiss-server folder to public_html, then open
 * https://yourdomain.com/install.php in your browser and fill the form.
 * It tests the database, creates the tables, and writes core/config.php for you.
 *
 * DELETE this file after a successful install.
 */
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$CONFIG_PATH = __DIR__ . '/core/config.php';
$SCHEMA_PATH = __DIR__ . '/schema.sql';

$alreadyInstalled = file_exists($CONFIG_PATH);
$done = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled) {
  $dbHost = trim($_POST['db_host'] ?? 'localhost');
  $dbName = trim($_POST['db_name'] ?? '');
  $dbUser = trim($_POST['db_user'] ?? '');
  $dbPass = (string) ($_POST['db_pass'] ?? '');
  $adminUser = trim($_POST['admin_user'] ?? '');
  $adminPass = (string) ($_POST['admin_pass'] ?? '');
  $mailFrom = trim($_POST['mail_from'] ?? '');

  if ($dbName === '' || $dbUser === '') $errors[] = 'Database name and user are required.';
  if ($adminUser === '' || strlen($adminPass) < 6) $errors[] = 'Admin username and a password (6+ chars) are required.';

  $pdo = null;
  if (!$errors) {
    try {
      $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      ]);
    } catch (PDOException $e) {
      $errors[] = 'Could not connect to the database. Check the host, name, user and password.';
    }
  }

  if (!$errors && $pdo) {
    try {
      $sql = file_get_contents($SCHEMA_PATH);
      if ($sql === false) throw new Exception('schema.sql is missing next to install.php.');
      $pdo->exec($sql);
    } catch (Throwable $e) {
      $errors[] = 'Could not create the tables: ' . $e->getMessage();
    }
  }

  if (!$errors) {
    $jwt = bin2hex(random_bytes(32));
    $tpl = "<?php\n"
      . "define('DB_HOST', " . var_export($dbHost, true) . ");\n"
      . "define('DB_NAME', " . var_export($dbName, true) . ");\n"
      . "define('DB_USER', " . var_export($dbUser, true) . ");\n"
      . "define('DB_PASS', " . var_export($dbPass, true) . ");\n"
      . "define('JWT_SECRET', " . var_export($jwt, true) . ");\n"
      . "define('JWT_TTL_DAYS', 30);\n"
      . "define('ADMIN_USER', " . var_export($adminUser, true) . ");\n"
      . "define('ADMIN_PASS', " . var_export($adminPass, true) . ");\n"
      . "define('MAIL_FROM', " . var_export($mailFrom ?: 'no-reply@example.com', true) . ");\n"
      . "define('MAIL_FROM_NAME', 'AK SWISS');\n"
      . "define('DEV_RETURN_CODE', false);\n";
    if (@file_put_contents($CONFIG_PATH, $tpl) === false) {
      $errors[] = 'Could not write core/config.php. Create it manually from config.sample.php.';
    } else {
      $done = true;
    }
  }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AK SWISS — Installer</title>
<style>
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0b0c0e;color:#f4f1ea;font-family:-apple-system,"Inter Tight",Segoe UI,Roboto,system-ui,sans-serif;padding:24px}
  .box{width:420px;max-width:100%;padding:32px 28px;background:#15171a;border:1px solid rgba(255,255,255,.08);border-radius:22px}
  .logo{font-weight:700;letter-spacing:.5px;margin-bottom:4px}.logo .r{color:#d52b1e}
  .sub{color:#a3a6ac;font-size:13px;margin-bottom:20px}
  label{display:block;font-size:13px;color:#a3a6ac;margin:14px 0 6px}
  input{width:100%;background:#0e0f12;border:1px solid rgba(255,255,255,.1);color:#f4f1ea;border-radius:12px;padding:11px;font-size:15px}
  button{width:100%;margin-top:22px;background:#d52b1e;color:#fff;border:none;border-radius:26px;padding:13px;font-weight:600;font-size:15px;cursor:pointer}
  .err{background:rgba(255,107,94,.12);border:1px solid rgba(255,107,94,.4);color:#ff6b5e;font-size:13px;padding:12px;border-radius:12px;margin-bottom:16px}
  .ok{background:rgba(91,214,154,.12);border:1px solid rgba(91,214,154,.4);color:#5bd69a;font-size:14px;padding:16px;border-radius:14px;line-height:1.6}
  .two{display:flex;gap:10px}.two>div{flex:1}
  code{background:#0e0f12;padding:2px 6px;border-radius:6px}
  a{color:#8acbff}
</style>
</head>
<body>
<div class="box">
  <div class="logo">AK <span class="r">SWISS</span> · Installer</div>
  <div class="sub">One-time setup. Delete this file when you’re done.</div>

  <?php if ($alreadyInstalled): ?>
    <div class="ok">Already installed — <code>core/config.php</code> exists.<br>
      For safety, <b>delete install.php</b> now, then open
      <a href="admin/">the admin dashboard</a>.</div>
  <?php elseif ($done): ?>
    <div class="ok">
      ✓ Database connected<br>✓ Tables created<br>✓ Config saved<br><br>
      <b>Now delete install.php</b> from the server, then open
      <a href="admin/">your admin dashboard</a> and sign in.<br><br>
      In the app, set <code>BASE_URL</code> to <code>https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'yourdomain.com'); ?>/api</code>.
    </div>
  <?php else: ?>
    <?php foreach ($errors as $er): ?><div class="err"><?php echo htmlspecialchars($er); ?></div><?php endforeach; ?>
    <form method="post">
      <label>Database host</label>
      <input name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>">
      <label>Database name</label>
      <input name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" placeholder="u123456_akswiss" required>
      <label>Database user</label>
      <input name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" placeholder="u123456_akswiss" required>
      <label>Database password</label>
      <input name="db_pass" type="password">
      <div class="two">
        <div><label>Admin username</label><input name="admin_user" value="<?php echo htmlspecialchars($_POST['admin_user'] ?? 'admin'); ?>" required></div>
        <div><label>Admin password</label><input name="admin_pass" type="password" required></div>
      </div>
      <label>From email (optional)</label>
      <input name="mail_from" value="<?php echo htmlspecialchars($_POST['mail_from'] ?? ''); ?>" placeholder="no-reply@yourdomain.com">
      <button type="submit">Install</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
