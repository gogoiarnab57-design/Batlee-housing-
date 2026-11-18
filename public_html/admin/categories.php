<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
admin_start('Categories', 'categories');

if (is_post()) {
    require_post();
    $action = post('action');
    $id = (int) post('id');
    $name = mb_substr(post('name'), 0, 100);

    if ($action === 'add' || $action === 'rename') {
        if ($name === '') {
            flash('Please enter a category name.', 'err');
        } else {
            $slug = slugify($name);
            $base = $slug; $n = 2;
            while (q_val('SELECT COUNT(*) FROM categories WHERE slug = ? AND id <> ?', [$slug, $id])) {
                $slug = $base . '-' . $n++;
            }
            if ($action === 'add') {
                $max = (int) q_val('SELECT COALESCE(MAX(sort_order),0) FROM categories');
                db_insert('categories', ['name' => $name, 'slug' => $slug, 'sort_order' => $max + 1]);
                flash('Category added.');
            } else {
                q('UPDATE categories SET name = ?, slug = ? WHERE id = ?', [$name, $slug, $id]);
                flash('Category renamed.');
            }
        }
    } elseif ($action === 'delete') {
        if ((int) q_val('SELECT COUNT(*) FROM products WHERE category_id = ?', [$id]) > 0) {
            flash('This category still has products. Move or delete them first.', 'err');
        } else {
            q('DELETE FROM categories WHERE id = ?', [$id]);
            flash('Category deleted.');
        }
    }
    redirect(url('admin/categories.php'));
}

$cats = q_all('SELECT c.id, c.name, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS n FROM categories c ORDER BY c.sort_order, c.name');
admin_head();
?>
<form method="post" class="form-card toolbar" style="max-width:520px">
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <label style="flex:1;min-width:200px">New category <input type="text" name="name" required maxlength="100" style="width:100%"></label>
  <button class="btn btn-gold" type="submit">Add</button>
</form>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Name</th><th class="num">Products</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($cats as $c): ?>
    <tr>
      <td>
        <form method="post" class="inline-actions"><?= csrf_field() ?><input type="hidden" name="action" value="rename"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
          <input type="text" name="name" value="<?= e($c['name']) ?>" maxlength="100" style="margin:0;min-width:180px" aria-label="Category name">
          <button class="btn btn-ghost btn-sm" type="submit">Rename</button></form>
      </td>
      <td class="num"><?= (int) $c['n'] ?></td>
      <td><form method="post" data-confirm="Delete this category?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php admin_foot(); ?>
