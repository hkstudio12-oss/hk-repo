<?php
/** Shared dark-theme chrome for the admin dashboard. */
function render_header($active = '', $title = 'AK SWISS Admin') {
  $nav = [
    'index' => 'Overview',
    'users' => 'Users',
    'warranties' => 'Warranties',
    'claims' => 'Claims',
    'products' => 'Products',
    'alarms' => 'Alerts',
  ];
  ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($title); ?></title>
<style>
  :root{--bg:#0b0c0e;--surface:#15171a;--line:rgba(255,255,255,.08);--text:#f4f1ea;--dim:#a3a6ac;--mute:#8b8f96;--accent:#8acbff;--brand:#d52b1e;--good:#5bd69a;--warn:#ff6b5e}
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font-family:-apple-system,"Inter Tight",Segoe UI,Roboto,system-ui,sans-serif;font-size:14px}
  a{color:var(--text);text-decoration:none}
  .wrap{max-width:1100px;margin:0 auto;padding:0 20px}
  header{border-bottom:1px solid var(--line);background:#0e0f12;position:sticky;top:0;z-index:5}
  .bar{display:flex;align-items:center;gap:20px;height:60px}
  .logo{font-weight:700;letter-spacing:.5px}
  .logo .r{color:var(--brand)}
  nav{display:flex;gap:4px;flex-wrap:wrap;flex:1}
  nav a{padding:8px 12px;border-radius:10px;color:var(--dim);font-weight:500}
  nav a.on{background:var(--surface);color:var(--text)}
  .out{color:var(--mute);font-size:13px}
  main{padding:28px 0 60px}
  h1{font-size:26px;margin:0 0 4px;letter-spacing:-.3px}
  .sub{color:var(--dim);margin:0 0 24px}
  .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:28px}
  .card{background:var(--surface);border:1px solid var(--line);border-radius:18px;padding:18px}
  .card .n{font-size:30px;font-weight:600}
  .card .l{color:var(--dim);font-size:13px;margin-top:4px}
  table{width:100%;border-collapse:collapse;background:var(--surface);border:1px solid var(--line);border-radius:16px;overflow:hidden}
  th,td{text-align:left;padding:12px 14px;border-bottom:1px solid var(--line);vertical-align:middle}
  th{color:var(--dim);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px}
  tr:last-child td{border-bottom:none}
  .badge{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600}
  .b-good{background:rgba(91,214,154,.16);color:var(--good)}
  .b-warn{background:rgba(255,107,94,.16);color:var(--warn)}
  .b-info{background:rgba(138,203,255,.16);color:var(--accent)}
  .b-mute{background:rgba(255,255,255,.08);color:var(--dim)}
  input,select,textarea{background:#0e0f12;border:1px solid var(--line);color:var(--text);border-radius:10px;padding:9px 12px;font-size:14px;font-family:inherit}
  button,.btn{background:var(--brand);color:#fff;border:none;border-radius:10px;padding:9px 16px;font-weight:600;cursor:pointer;font-size:14px}
  .btn-ghost{background:var(--surface);border:1px solid var(--line);color:var(--text)}
  .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:18px}
  .muted{color:var(--mute)}
  .empty{padding:50px;text-align:center;color:var(--mute)}
  form.inline{display:inline}
</style>
</head>
<body>
<header><div class="wrap bar">
  <span class="logo">AK <span class="r">SWISS</span> · Admin</span>
  <nav>
    <?php foreach ($nav as $k => $label): ?>
      <a href="<?php echo $k; ?>.php" class="<?php echo $active === $k ? 'on' : ''; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
  </nav>
  <a class="out" href="logout.php">Sign out</a>
</div></header>
<main><div class="wrap"><?php
}

function render_footer() {
  ?></div></main></body></html><?php
}

function badge($status) {
  $map = [
    'active' => 'b-good', 'approved' => 'b-good', 'completed' => 'b-good', 'good' => 'b-good',
    'expired' => 'b-warn', 'rejected' => 'b-warn', 'danger' => 'b-warn',
    'pending' => 'b-info', 'info' => 'b-info',
  ];
  $cls = isset($map[$status]) ? $map[$status] : 'b-mute';
  return '<span class="badge ' . $cls . '">' . e(ucfirst($status)) . '</span>';
}
