<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
$admin = admin_start('Dashboard', 'dashboard');

$today = date('Y-m-d');
$stats = [
    'Orders today'     => (int) q_val("SELECT COUNT(*) FROM orders WHERE created_at >= ?", [$today . ' 00:00:00']),
    'New orders'       => (int) q_val("SELECT COUNT(*) FROM orders WHERE status = 'new'"),
    'Total sales'      => money(q_val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled'")),
    'Products'         => (int) q_val('SELECT COUNT(*) FROM products'),
    'Reviews to check' => (int) q_val("SELECT COUNT(*) FROM reviews WHERE status = 'pending'"),
    'Unread messages'  => (int) q_val('SELECT COUNT(*) FROM messages WHERE is_read = 0'),
];
$lowStock = q_all('SELECT id, name, stock FROM products WHERE is_active = 1 AND stock <= 5 ORDER BY stock, name LIMIT 8');
$recent = q_all('SELECT id, order_no, cust_name, total, status, payment_status, created_at FROM orders ORDER BY id DESC LIMIT 8');

// Warn while the starting password (12345) is still in use.
$row = q_one('SELECT username, password_hash FROM users WHERE id = ?', [$admin['id']]);
$defaultPw = password_verify('12345', $row['password_hash']);

admin_head();
?>
<?php if ($defaultPw): ?>
  <div class="warn-bar"><strong>Please change your password.</strong> You are still using the starting password. <a href="<?= e(url('admin/account.php')) ?>">Change it now &rarr;</a></div>
<?php endif; ?>

<div class="stat-grid">
  <?php foreach ($stats as $label => $val): ?>
    <div class="stat"><small><?= e($label) ?></small><strong><?= e((string) $val) ?></strong></div>
  <?php endforeach; ?>
</div>

<h2>Recent orders</h2>
<?php if ($recent): ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th class="num">Total</th><th>Status</th><th>Payment</th></tr></thead>
  <tbody>
  <?php foreach ($recent as $o): ?>
    <tr>
      <td><a href="<?= e(url('admin/order_view.php?id=' . (int) $o['id'])) ?>"><?= e($o['order_no']) ?></a></td>
      <td><?= e($o['cust_name']) ?></td>
      <td><?= e(date('d M, h:i A', strtotime($o['created_at']))) ?></td>
      <td class="num"><?= e(money($o['total'])) ?></td>
      <td><span class="badge st-<?= e($o['status']) ?>"><?= e(order_status_label($o['status'])) ?></span></td>
      <td><span class="badge pay-<?= e($o['payment_status']) ?>"><?= e(ucfirst($o['payment_status'])) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?><p class="muted">No orders yet.</p><?php endif; ?>

<?php if ($lowStock): ?>
<h2 style="margin-top:24px">Low stock</h2>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Product</th><th class="num">In stock</th></tr></thead>
  <tbody>
  <?php foreach ($lowStock as $p): ?>
    <tr><td><a href="<?= e(url('admin/product_edit.php?id=' . (int) $p['id'])) ?>"><?= e($p['name']) ?></a></td><td class="num"><?= (int) $p['stock'] ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php admin_foot(); ?>
