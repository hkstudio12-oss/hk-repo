<?php
require __DIR__ . '/_guard.php';
require __DIR__ . '/_layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id) {
  $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
  $stmt->execute([$id]);
  $user = $stmt->fetch();
  if (!$user) { render_header('users'); echo '<div class="empty">User not found.</div>'; render_footer(); exit; }

  $dev = db()->prepare('SELECT * FROM devices WHERE user_id = ? ORDER BY id DESC');
  $dev->execute([$id]);
  $devices = $dev->fetchAll();

  $war = db()->prepare('SELECT * FROM warranties WHERE user_id = ? ORDER BY id DESC');
  $war->execute([$id]);
  $warranties = $war->fetchAll();

  render_header('users', 'User — AK SWISS Admin');
  ?>
  <div class="row"><a class="btn btn-ghost" href="users.php">&larr; All users</a></div>
  <h1><?php echo e($user['name'] ?: 'No name'); ?></h1>
  <p class="sub"><?php echo e($user['email']); ?> · joined <?php echo e($user['created_at']); ?> · <?php echo e($user['provider']); ?></p>

  <h1 style="font-size:18px">Fridges</h1>
  <p class="sub"></p>
  <?php if ($devices): ?>
  <table><tr><th>#</th><th>Name</th><th>Device ID</th><th>Last temp</th><th>Last seen</th></tr>
  <?php foreach ($devices as $d): ?>
    <tr><td>#<?php echo (int) $d['id']; ?></td><td><?php echo e($d['name']); ?></td>
    <td class="muted"><?php echo e($d['device_uid']); ?></td>
    <td><?php echo $d['last_temp'] === null ? '—' : e($d['last_temp']) . '°C'; ?></td>
    <td class="muted"><?php echo e($d['last_seen'] ?: '—'); ?></td></tr>
  <?php endforeach; ?></table>
  <?php else: ?><div class="empty">No fridges linked.</div><?php endif; ?>

  <h1 style="font-size:18px;margin-top:28px">Warranties</h1>
  <p class="sub"></p>
  <?php if ($warranties): ?>
  <table><tr><th>Serial</th><th>Purchased</th><th>Covered until</th><th>Status</th></tr>
  <?php foreach ($warranties as $w): ?>
    <tr><td><?php echo e($w['serial']); ?></td><td><?php echo e($w['purchase_date'] ?: '—'); ?></td>
    <td><?php echo e($w['expires_at'] ?: '—'); ?></td>
    <td><?php echo badge($w['expires_at'] && $w['expires_at'] < date('Y-m-d') ? 'expired' : 'active'); ?></td></tr>
  <?php endforeach; ?></table>
  <?php else: ?><div class="empty">No warranties registered.</div><?php endif; ?>
  <?php
  render_footer();
  exit;
}

// List view
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q !== '') {
  $stmt = db()->prepare("SELECT u.*, (SELECT COUNT(*) FROM warranties w WHERE w.user_id = u.id) AS wcount
    FROM users u WHERE u.email LIKE ? OR u.name LIKE ? ORDER BY u.id DESC LIMIT 300");
  $like = '%' . $q . '%';
  $stmt->execute([$like, $like]);
} else {
  $stmt = db()->query("SELECT u.*, (SELECT COUNT(*) FROM warranties w WHERE w.user_id = u.id) AS wcount
    FROM users u ORDER BY u.id DESC LIMIT 300");
}
$rows = $stmt->fetchAll();

render_header('users', 'Users — AK SWISS Admin');
?>
<h1>Users</h1>
<p class="sub"><?php echo count($rows); ?> shown.</p>
<form class="row" method="get">
  <input name="q" placeholder="Search email or name" value="<?php echo e($q); ?>" style="min-width:260px">
  <button type="submit">Search</button>
  <?php if ($q): ?><a class="btn btn-ghost" href="users.php">Clear</a><?php endif; ?>
</form>
<?php if ($rows): ?>
<table>
  <tr><th>#</th><th>Email</th><th>Name</th><th>Warranties</th><th>Joined</th><th></th></tr>
  <?php foreach ($rows as $u): ?>
  <tr>
    <td>#<?php echo (int) $u['id']; ?></td>
    <td><?php echo e($u['email']); ?></td>
    <td><?php echo e($u['name'] ?: '—'); ?></td>
    <td><?php echo (int) $u['wcount']; ?></td>
    <td class="muted"><?php echo e($u['created_at']); ?></td>
    <td><a class="btn btn-ghost" href="users.php?id=<?php echo (int) $u['id']; ?>">View</a></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">No users found.</div><?php endif; ?>
<?php render_footer(); ?>
