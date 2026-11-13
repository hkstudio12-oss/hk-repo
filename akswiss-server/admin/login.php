<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../core/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = isset($_POST['username']) ? $_POST['username'] : '';
  $p = isset($_POST['password']) ? $_POST['password'] : '';
  if (hash_equals(ADMIN_USER, $u) && hash_equals(ADMIN_PASS, $p)) {
    session_regenerate_id(true);
    $_SESSION['ak_admin'] = true;
    header('Location: index.php');
    exit;
  }
  $error = 'Wrong username or password';
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AK SWISS Admin — Sign in</title>
<style>
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0b0c0e;color:#f4f1ea;font-family:-apple-system,"Inter Tight",Segoe UI,Roboto,system-ui,sans-serif}
  .box{width:340px;padding:36px 28px;background:#15171a;border:1px solid rgba(255,255,255,.08);border-radius:22px}
  .logo{font-weight:700;letter-spacing:.5px;margin-bottom:6px}
  .logo .r{color:#d52b1e}
  .sub{color:#a3a6ac;font-size:13px;margin-bottom:22px}
  label{display:block;font-size:13px;color:#a3a6ac;margin:14px 0 6px}
  input{width:100%;background:#0e0f12;border:1px solid rgba(255,255,255,.1);color:#f4f1ea;border-radius:12px;padding:12px;font-size:15px}
  button{width:100%;margin-top:22px;background:#d52b1e;color:#fff;border:none;border-radius:26px;padding:13px;font-weight:600;font-size:15px;cursor:pointer}
  .err{color:#ff6b5e;font-size:13px;margin-top:14px}
</style>
</head>
<body>
<form class="box" method="post">
  <div class="logo">AK <span class="r">SWISS</span> · Admin</div>
  <div class="sub">Sign in to manage users and warranties.</div>
  <label for="u">Username</label>
  <input id="u" name="username" autocomplete="username" autofocus>
  <label for="p">Password</label>
  <input id="p" name="password" type="password" autocomplete="current-password">
  <button type="submit">Sign in</button>
  <?php if ($error): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
</form>
</body>
</html>
