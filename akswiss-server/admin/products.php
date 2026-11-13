<?php
require __DIR__ . '/_guard.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $action = $_POST['action'] ?? '';
  if ($action === 'save') {
    $pid = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = ($_POST['price'] === '') ? null : (float) $_POST['price'];
    $image = trim($_POST['image'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;
    if ($name !== '') {
      if ($pid) {
        $s = db()->prepare('UPDATE products SET name=?, price=?, image=?, category=?, description=?, is_active=? WHERE id=?');
        $s->execute([$name, $price, $image, $category, $desc, $active, $pid]);
      } else {
        $s = db()->prepare('INSERT INTO products (name, price, image, category, description, is_active) VALUES (?,?,?,?,?,?)');
        $s->execute([$name, $price, $image, $category, $desc, $active]);
      }
    }
  } elseif ($action === 'delete') {
    db()->prepare('DELETE FROM products WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
  }
  header('Location: products.php');
  exit;
}

$rows = db()->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
$edit = null;
if (isset($_GET['edit'])) {
  $s = db()->prepare('SELECT * FROM products WHERE id = ?');
  $s->execute([(int) $_GET['edit']]);
  $edit = $s->fetch();
}

render_header('products', 'Products — AK SWISS Admin');
?>
<h1>Products</h1>
<p class="sub">These feed the Shop tab in the app.</p>

<div class="card" style="margin-bottom:24px">
  <form method="post" class="row" style="margin:0">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?php echo $edit ? (int) $edit['id'] : 0; ?>">
    <input name="name" placeholder="Product name" value="<?php echo e($edit['name'] ?? ''); ?>" required style="min-width:220px">
    <input name="price" placeholder="Price" value="<?php echo e($edit['price'] ?? ''); ?>" style="width:110px">
    <input name="category" placeholder="Category" value="<?php echo e($edit['category'] ?? ''); ?>" style="width:140px">
    <input name="image" placeholder="Image URL or filename" value="<?php echo e($edit['image'] ?? ''); ?>" style="min-width:220px">
    <input name="description" placeholder="Description" value="<?php echo e($edit['description'] ?? ''); ?>" style="min-width:240px">
    <label class="muted"><input type="checkbox" name="is_active" <?php echo (!$edit || $edit['is_active']) ? 'checked' : ''; ?>> Active</label>
    <button type="submit"><?php echo $edit ? 'Update' : 'Add product'; ?></button>
    <?php if ($edit): ?><a class="btn btn-ghost" href="products.php">Cancel</a><?php endif; ?>
  </form>
</div>

<?php if ($rows): ?>
<table>
  <tr><th>#</th><th>Name</th><th>Price</th><th>Category</th><th>Active</th><th></th></tr>
  <?php foreach ($rows as $p): ?>
  <tr>
    <td>#<?php echo (int) $p['id']; ?></td>
    <td><?php echo e($p['name']); ?></td>
    <td><?php echo $p['price'] === null ? '—' : 'RM ' . e(number_format($p['price'], 2)); ?></td>
    <td class="muted"><?php echo e($p['category'] ?: '—'); ?></td>
    <td><?php echo $p['is_active'] ? badge('active') : badge('mute'); ?></td>
    <td class="row" style="margin:0;gap:6px">
      <a class="btn btn-ghost" href="products.php?edit=<?php echo (int) $p['id']; ?>">Edit</a>
      <form method="post" class="inline" onsubmit="return confirm('Delete this product?')">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">
        <button type="submit" class="btn-ghost" style="color:#ff6b5e">Delete</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?><div class="empty">No products yet. Add one above.</div><?php endif; ?>
<?php render_footer(); ?>
