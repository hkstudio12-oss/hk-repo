<?php
require __DIR__ . '/_guard.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $cid = (int) ($_POST['id'] ?? 0);
  $status = $_POST['status'] ?? 'pending';
  $note = $_POST['admin_note'] ?? '';
  $allowed = ['pending', 'approved', 'rejected', 'completed'];
  if (!in_array($status, $allowed, true)) $status = 'pending';
  $upd = db()->prepare('UPDATE warranty_claims SET status = ?, admin_note = ?, updated_at = NOW() WHERE id = ?');
  $upd->execute([$status, $note, $cid]);
  header('Location: claims.php');
  exit;
}

$filter = isset($_GET['status']) ? $_GET['status'] : '';
$sql = "SELECT c.*, w.serial, w.model, u.email
        FROM warranty_claims c
        JOIN warranties w ON w.id = c.warranty_id
        JOIN users u ON u.id = c.user_id WHERE 1=1";
$args = [];
if (in_array($filter, ['pending', 'approved', 'rejected', 'completed'], true)) { $sql .= ' AND c.status = ?'; $args[] = $filter; }
$sql .= ' ORDER BY c.id DESC LIMIT 400';
$stmt = db()->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

render_header('claims', 'Claims — AK SWISS Admin');
?>
<h1>Warranty claims</h1>
<p class="sub"><?php echo count($rows); ?> shown.</p>
<form class="row" method="get">
  <select name="status" onchange="this.form.submit()">
    <option value="">All statuses</option>
    <?php foreach (['pending', 'approved', 'rejected', 'completed'] as $s): ?>
      <option value="<?php echo $s; ?>" <?php echo $filter === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
    <?php endforeach; ?>
  </select>
</form>
<?php if ($rows): ?>
<table>
  <tr><th>#</th><th>User</th><th>Serial</th><th>Issue</th><th>Status</th><th>Update</th></tr>
  <?php foreach ($rows as $c): ?>
  <tr id="c<?php echo (int) $c['id']; ?>">
    <td>#<?php echo (int) $c['id']; ?></td>
    <td><a href="users.php?id=<?php echo (int) $c['user_id']; ?>"><?php echo e($c['email']); ?></a></td>
    <td><?php echo e($c['serial']); ?></td>
    <td style="max-width:260px"><?php echo e($c['issue'] ?: '—'); ?>
      <?php if ($c['admin_note']): ?><div class="muted" style="margin-top:4px">Note: <?php echo e($c['admin_note']); ?></div><?php endif; ?>
    </td>
    <td><?php echo badge($c['status']); ?></td>
    <td>
      <form method="post" class="row" style="margin:0;gap:6px">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
        <select name="status">
          <?php foreach (['pending', 'approved', 'rejected', 'completed'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo $c['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
          <?php endforeach; ?>
        </select>
        <input name="admin_note" placeholder="Note" value="<?php echo e($c['admin_note']); ?>" style="width:120px">
        <button type="submit">Save</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">No claims found.</div><?php endif; ?>
<?php render_footer(); ?>
