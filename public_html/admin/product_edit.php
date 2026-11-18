<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
admin_start('Product', 'products');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$product = $id ? q_one('SELECT * FROM products WHERE id = ?', [$id]) : null;
if ($id && !$product) {
    flash('Product not found.', 'err');
    redirect(url('admin/products.php'));
}
$cats = q_all('SELECT id, name FROM categories ORDER BY sort_order, name');
$errors = [];

if (is_post()) {
    require_post();
    $name  = mb_substr(post('name'), 0, 200);
    $catId = (int) post('category_id');
    $desc  = mb_substr(post('description'), 0, 5000);
    $price = post('price');
    $mrp   = post('mrp');
    $stock = post('stock');
    $active = post('is_active') === '1' ? 1 : 0;

    if ($name === '') $errors[] = 'Product name is required.';
    if (!q_val('SELECT COUNT(*) FROM categories WHERE id = ?', [$catId])) $errors[] = 'Please choose a category.';
    if (!is_numeric($price) || (float) $price < 0 || (float) $price > 9999999) $errors[] = 'Enter a valid selling price.';
    if ($mrp !== '' && (!is_numeric($mrp) || (float) $mrp < 0 || (float) $mrp > 9999999)) $errors[] = 'Enter a valid MRP or leave it empty.';
    if (!preg_match('/^[0-9]{1,7}$/', $stock)) $errors[] = 'Stock must be a whole number.';

    // Validate + store new images first (re-encoded by GD).
    $newFiles = [];
    foreach (files_list('images') as $file) {
        [$saved, $err] = save_uploaded_image($file);
        if ($err) { $errors[] = $err; }
        if ($saved) { $newFiles[] = $saved; }
    }

    if ($errors) {
        foreach ($newFiles as $nf) { delete_image_file($nf); }
    } else {
        $data = [
            'category_id' => $catId, 'name' => $name, 'description' => $desc,
            'price' => round((float) $price, 2), 'mrp' => $mrp !== '' ? round((float) $mrp, 2) : null,
            'stock' => (int) $stock, 'is_active' => $active,
        ];
        if ($product) {
            q('UPDATE products SET category_id=?, name=?, description=?, price=?, mrp=?, stock=?, is_active=? WHERE id=?',
              [$data['category_id'], $data['name'], $data['description'], $data['price'], $data['mrp'], $data['stock'], $data['is_active'], $id]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = db_insert('products', $data);
        }
        // Remove images the admin ticked
        foreach ((array) ($_POST['remove_img'] ?? []) as $imgId) {
            $im = q_one('SELECT filename FROM product_images WHERE id = ? AND product_id = ?', [(int) $imgId, $id]);
            if ($im) {
                q('DELETE FROM product_images WHERE id = ?', [(int) $imgId]);
                delete_image_file($im['filename']);
            }
        }
        $order = (int) q_val('SELECT COALESCE(MAX(sort_order),0) FROM product_images WHERE product_id = ?', [$id]);
        foreach ($newFiles as $nf) {
            db_insert('product_images', ['product_id' => $id, 'filename' => $nf, 'sort_order' => ++$order]);
        }
        flash('Product saved.');
        redirect(url('admin/products.php'));
    }
    // fall through to re-show the form with the entered values
    $product = ['id' => $id, 'name' => $name, 'category_id' => $catId, 'description' => $desc, 'price' => $price,
                'mrp' => $mrp, 'stock' => $stock, 'is_active' => $active];
}

$images = $id ? q_all('SELECT id, filename FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$id]) : [];
$p = $product ?: ['name' => '', 'category_id' => $cats[0]['id'] ?? 0, 'description' => '', 'price' => '', 'mrp' => '', 'stock' => '10', 'is_active' => 1];

$GLOBALS['admin_title'] = $id ? 'Edit product' : 'Add product';
admin_head();
?>
<?php foreach ($errors as $er): ?><div class="alert alert-err" role="alert"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="form-card" style="max-width:760px" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $id ?>">
  <label>Product name * <input type="text" name="name" value="<?= e($p['name']) ?>" required maxlength="200"></label>
  <div class="form-grid">
    <label>Category *
      <select name="category_id" required>
        <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $p['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Stock quantity * <input type="number" name="stock" value="<?= e((string) $p['stock']) ?>" min="0" step="1" required inputmode="numeric"></label>
    <label>Selling price (₹) * <input type="number" name="price" value="<?= e((string) $p['price']) ?>" min="0" step="0.01" required inputmode="decimal"></label>
    <label>MRP / old price (₹) - optional <input type="number" name="mrp" value="<?= e((string) ($p['mrp'] ?? '')) ?>" min="0" step="0.01" inputmode="decimal"></label>
  </div>
  <label>Description <textarea name="description" rows="5" maxlength="5000"><?= e((string) $p['description']) ?></textarea></label>

  <?php if ($images): ?>
    <p><strong>Current photos</strong> <span class="muted small">(tick to remove; the first photo is the main one)</span></p>
    <div class="img-edit">
      <?php foreach ($images as $im): ?>
        <figure><img src="<?= e(product_image_url($im['filename'])) ?>" alt="" width="90" height="90">
          <label class="checkline"><input type="checkbox" name="remove_img[]" value="<?= (int) $im['id'] ?>"> Remove</label></figure>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <label>Add photos (JPG, PNG or WEBP, up to <?= (int) (MAX_UPLOAD_BYTES / 1048576) ?> MB each)
    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
  </label>

  <label class="checkline"><input type="checkbox" name="is_active" value="1" <?= (int) $p['is_active'] ? 'checked' : '' ?>> Visible in the shop</label>
  <div class="inline-actions" style="margin-top:14px">
    <button class="btn btn-gold" type="submit">Save product</button>
    <a class="btn btn-ghost" href="<?= e(url('admin/products.php')) ?>">Cancel</a>
  </div>
</form>
<?php admin_foot(); ?>
