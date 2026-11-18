<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
admin_start('Products', 'products');

if (is_post()) {
    require_post();
    $id = (int) post('id');
    $action = post('action');
    if ($action === 'delete' && $id > 0) {
        $inOrders = (int) q_val('SELECT COUNT(*) FROM order_items WHERE product_id = ?', [$id]);
        $imgs = q_all('SELECT filename FROM product_images WHERE product_id = ?', [$id]);
        if ($inOrders > 0) {
            // Keep order history intact: hide the product instead of deleting it.
            q('UPDATE products SET is_active = 0 WHERE id = ?', [$id]);
            flash('This product is in past orders, so it was hidden from the shop instead of deleted.');
        } else {
            q('DELETE FROM product_images WHERE product_id = ?', [$id]);
            q('DELETE FROM reviews WHERE product_id = ?', [$id]);
            q('DELETE FROM products WHERE id = ?', [$id]);
            foreach ($imgs as $im) { delete_image_file($im['filename']); }
            flash('Product deleted.');
        }
    } elseif ($action === 'toggle' && $id > 0) {
        q('UPDATE products SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('Visibility updated.');
    }
    redirect(url('admin/products.php'));
}

$term = trim((string) ($_GET['q'] ?? ''));
$params = [];
$where = '1=1';
if ($term !== '') {
    $where = "p.name LIKE ? ESCAPE '!'";
    $params[] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_substr($term, 0, 80)) . '%';
}
$rows = q_all("SELECT p.id, p.name, p.price, p.stock, p.is_active, c.name AS category,
               (SELECT filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.sort_order, i.id LIMIT 1) AS image
               FROM products p JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY p.id DESC", $params);

admin_head();
?>
<div class="toolbar">
  <a class="btn btn-gold" href="<?= e(url('admin/product_edit.php')) ?>">+ Add product</a>
  <form method="get" class="toolbar"><label class="sr-only" for="pq">Search</label>
    <input id="pq" type="search" name="q" value="<?= e($term) ?>" placeholder="Search products">
    <button class="btn btn-ghost" type="submit">Search</button></form>
</div>
<div class="table-wrap"><table class="table">
  <thead><tr><th></th><th>Product</th><th>Category</th><th class="num">Price</th><th class="num">Stock</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $p): ?>
    <tr>
      <td><?php if ($p['image']): ?><img class="thumb-sm" src="<?= e(product_image_url($p['image'])) ?>" alt="" width="46" height="46"><?php else: ?><span class="thumb-sm ph"><?= e(mb_strtoupper(mb_substr($p['name'], 0, 1))) ?></span><?php endif; ?></td>
      <td><a href="<?= e(url('admin/product_edit.php?id=' . (int) $p['id'])) ?>"><?= e($p['name']) ?></a></td>
      <td><?= e($p['category']) ?></td>
      <td class="num"><?= e(money($p['price'])) ?></td>
      <td class="num"><?= (int) $p['stock'] ?></td>
      <td><span class="badge <?= (int) $p['is_active'] ? 'st-delivered' : 'st-cancelled' ?>"><?= (int) $p['is_active'] ? 'Visible' : 'Hidden' ?></span></td>
      <td><div class="inline-actions">
        <a class="btn btn-ghost btn-sm" href="<?= e(url('admin/product_edit.php?id=' . (int) $p['id'])) ?>">Edit</a>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit"><?= (int) $p['is_active'] ? 'Hide' : 'Show' ?></button></form>
        <form method="post" data-confirm="Delete this product permanently?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
      </div></td>
    </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="muted">No products yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php admin_foot(); ?>
