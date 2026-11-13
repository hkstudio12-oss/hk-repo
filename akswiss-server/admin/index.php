<?php
require __DIR__ . '/_guard.php';
require __DIR__ . '/_layout.php';

$users = db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$devices = db()->query('SELECT COUNT(*) FROM devices')->fetchColumn();
$warranties = db()->query('SELECT COUNT(*) FROM warranties')->fetchColumn();
$pending = db()->query("SELECT COUNT(*) FROM warranty_claims WHERE status = 'pending'")->fetchColumn();

$recent = db()->query(
  "SELECT c.id, c.status, c.created_at, w.serial, u.email
   FROM warranty_claims c
   JOIN warranties w ON w.id = c.warranty_id
   JOIN users u ON u.id = c.user_id
   ORDER BY c.id DESC LIMIT 8"
)->fetchAll();

render_header('index', 'Overview — AK SWISS Admin');
?>
<h1>Overview</h1>
<p class="sub">At a glance across your fleet.</p>

<div class="cards">
  <div class="card"><div class="n"><?php echo (int) $users; ?></div><div class="l">Registered users</div></div>
  <div class="card"><div class="n"><?php echo (int) $devices; ?></div><div class="l">Connected fridges</div></div>
  <div class="card"><div class="n"><?php echo (int) $warranties; ?></div><div class="l">Warranties</div></div>
  <div class="card"><div class="n"><?php echo (int) $pending; ?></div><div class="l">Pending claims</div></div>
</div>

<h1 style="font-size:18px">Latest warranty claims</h1>
<p class="sub">Newest first.</p>
<?php if ($recent): ?>
<table>
  <tr><th>#</th><th>User</th><th>Serial</th><th>Status</th><th>Submitted</th><th></th></tr>
  <?php foreach ($recent as $r): ?>
  <tr>
    <td>#<?php echo (int) $r['id']; ?></td>
    <td><?php echo e($r['email']); ?></td>
    <td><?php echo e($r['serial']); ?></td>
    <td><?php echo badge($r['status']); ?></td>
    <td class="muted"><?php echo e($r['created_at']); ?></td>
    <td><a class="btn btn-ghost" href="claims.php#c<?php echo (int) $r['id']; ?>">Review</a></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?>
<div class="empty">No warranty claims yet.</div>
<?php endif; ?>
<?php render_footer(); ?>
