<?php
require __DIR__ . '/_guard.php';
require __DIR__ . '/_layout.php';

$rows = db()->query(
  "SELECT a.*, u.email FROM device_alarms a JOIN users u ON u.id = a.user_id
   ORDER BY a.id DESC LIMIT 300"
)->fetchAll();

render_header('alarms', 'Alerts — AK SWISS Admin');
?>
<h1>Device alerts</h1>
<p class="sub">Faults and status events reported by fridges.</p>
<?php if ($rows): ?>
<table>
  <tr><th>#</th><th>User</th><th>Title</th><th>Message</th><th>Severity</th><th>When</th></tr>
  <?php foreach ($rows as $a): ?>
  <tr>
    <td>#<?php echo (int) $a['id']; ?></td>
    <td><a href="users.php?id=<?php echo (int) $a['user_id']; ?>"><?php echo e($a['email']); ?></a></td>
    <td><?php echo e($a['title'] ?: '—'); ?></td>
    <td class="muted" style="max-width:300px"><?php echo e($a['message']); ?></td>
    <td><?php echo badge($a['severity']); ?></td>
    <td class="muted"><?php echo e($a['created_at']); ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">No alerts recorded.</div><?php endif; ?>
<?php render_footer(); ?>
