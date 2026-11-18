<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
admin_start('Reviews', 'reviews');

if (is_post()) {
    require_post();
    $id = (int) post('id');
    $action = post('action');
    if ($action === 'approve') {
        q("UPDATE reviews SET status = 'approved' WHERE id = ?", [$id]);
        flash('Review approved and now visible on the product page.');
    } elseif ($action === 'hide') {
        q("UPDATE reviews SET status = 'pending' WHERE id = ?", [$id]);
        flash('Review hidden from the shop.');
    } elseif ($action === 'delete') {
        q('DELETE FROM reviews WHERE id = ?', [$id]);
        flash('Review deleted.');
    }
    redirect(url('admin/reviews.php'));
}

$rows = q_all('SELECT r.id, r.rating, r.comment, r.status, r.created_at, p.name AS product, u.name AS customer, u.username
               FROM reviews r JOIN products p ON p.id = r.product_id JOIN users u ON u.id = r.user_id
               ORDER BY (r.status = \'pending\') DESC, r.id DESC LIMIT 300');
admin_head();
?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Review</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['product']) ?></td>
      <td><?= e($r['customer']) ?><br><small class="muted"><?= e(date('d M Y', strtotime($r['created_at']))) ?></small></td>
      <td><span class="stars"><?= e(stars((float) $r['rating'])) ?></span></td>
      <td><?= nl2br(e((string) $r['comment'])) ?: '<span class="muted">(no comment)</span>' ?></td>
      <td><span class="badge st-<?= e($r['status']) ?>"><?= $r['status'] === 'approved' ? 'Visible' : 'Waiting' ?></span></td>
      <td><div class="inline-actions">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <?php if ($r['status'] === 'pending'): ?><input type="hidden" name="action" value="approve"><button class="btn btn-gold btn-sm" type="submit">Approve</button>
          <?php else: ?><input type="hidden" name="action" value="hide"><button class="btn btn-ghost btn-sm" type="submit">Hide</button><?php endif; ?></form>
        <form method="post" data-confirm="Delete this review?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
      </div></td>
    </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="muted">No reviews yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php admin_foot(); ?>
