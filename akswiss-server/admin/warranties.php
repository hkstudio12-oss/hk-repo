<?php
require __DIR__ . '/_guard.php';
require __DIR__ . '/_layout.php';

$filter = isset($_GET['status']) ? $_GET['status'] : '';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT w.*, u.email,
        (SELECT COUNT(*) FROM warranty_claims c WHERE c.warranty_id = w.id) AS claims
        FROM warranties w JOIN users u ON u.id = w.user_id WHERE 1=1";
$args = [];
if ($q !== '') { $sql .= ' AND (w.serial LIKE ? OR u.email LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
if ($filter === 'active') $sql .= " AND (w.expires_at IS NULL OR w.expires_at >= CURDATE())";
if ($filter === 'expired') $sql .= " AND w.expires_at < CURDATE()";
$sql .= ' ORDER BY w.id DESC LIMIT 400';
$stmt = db()->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

render_header('warranties', 'Warranties — AK SWISS Admin');
?>
<h1>Warranties</h1>
<p class="sub"><?php echo count($rows); ?> shown.</p>
<form class="row" method="get">
  <input name="q" placeholder="Search serial or email" value="<?php echo e($q); ?>" style="min-width:240px">
  <select name="status">
    <option value="">All statuses</option>
    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active</option>
    <option value="expired" <?php echo $filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
  </select>
  <button type="submit">Filter</button>
  <?php if ($q || $filter): ?><a class="btn btn-ghost" href="warranties.php">Clear</a><?php endif; ?>
</form>
<?php if ($rows): ?>
<table>
  <tr><th>Serial</th><th>Owner</th><th>Model</th><th>Purchased</th><th>Until</th><th>Status</th><th>Claims</th></tr>
  <?php foreach ($rows as $w): $expired = $w['expires_at'] && $w['expires_at'] < date('Y-m-d'); ?>
  <tr>
    <td><?php echo e($w['serial']); ?></td>
    <td><a href="users.php?id=<?php echo (int) $w['user_id']; ?>"><?php echo e($w['email']); ?></a></td>
    <td class="muted"><?php echo e($w['model']); ?></td>
    <td><?php echo e($w['purchase_date'] ?: '—'); ?></td>
    <td><?php echo e($w['expires_at'] ?: '—'); ?></td>
    <td><?php echo badge($expired ? 'expired' : 'active'); ?></td>
    <td><?php echo (int) $w['claims']; ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">No warranties found.</div><?php endif; ?>
<?php render_footer(); ?>
