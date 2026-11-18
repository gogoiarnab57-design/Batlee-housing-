<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
admin_start('Orders', 'orders');

$status = (string) ($_GET['status'] ?? '');
$term   = trim((string) ($_GET['q'] ?? ''));
$statuses = ['new', 'confirmed', 'packed', 'shipped', 'delivered', 'cancelled'];

$where = ['1=1'];
$params = [];
if (in_array($status, $statuses, true)) {
    $where[] = 'status = ?';
    $params[] = $status;
}
if ($term !== '') {
    $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_substr($term, 0, 60)) . '%';
    $where[] = "(order_no LIKE ? ESCAPE '!' OR cust_name LIKE ? ESCAPE '!' OR phone LIKE ? ESCAPE '!')";
    array_push($params, $like, $like, $like);
}
$orders = q_all('SELECT id, order_no, cust_name, phone, city, total, status, payment_method, payment_status, created_at
                 FROM orders WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 300', $params);

admin_head();
?>
<form method="get" class="toolbar">
  <label>Status
    <select name="status">
      <option value="">All</option>
      <?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(order_status_label($s)) ?></option><?php endforeach; ?>
    </select>
  </label>
  <label>Search <input type="search" name="q" value="<?= e($term) ?>" placeholder="Order no, name or phone"></label>
  <button class="btn btn-dark" type="submit">Filter</button>
</form>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th class="num">Total</th><th>Payment</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><a href="<?= e(url('admin/order_view.php?id=' . (int) $o['id'])) ?>"><?= e($o['order_no']) ?></a></td>
      <td><?= e($o['cust_name']) ?><br><small class="muted"><?= e($o['phone']) ?> &middot; <?= e($o['city']) ?></small></td>
      <td><?= e(date('d M Y, h:i A', strtotime($o['created_at']))) ?></td>
      <td class="num"><?= e(money($o['total'])) ?></td>
      <td><?= e(payment_method_label($o['payment_method'])) ?><br><span class="badge pay-<?= e($o['payment_status']) ?>"><?= e(ucfirst($o['payment_status'])) ?></span></td>
      <td><span class="badge st-<?= e($o['status']) ?>"><?= e(order_status_label($o['status'])) ?></span></td>
    </tr>
  <?php endforeach; if (!$orders): ?><tr><td colspan="6" class="muted">No orders found.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php admin_foot(); ?>
