<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
admin_start('Order', 'orders');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$o = q_one('SELECT * FROM orders WHERE id = ?', [$id]);
if (!$o) {
    flash('Order not found.', 'err');
    redirect(url('admin/orders.php'));
}

if (is_post()) {
    require_post();
    $newStatus = post('status');
    $newPay    = post('payment_status');
    $okStatus  = ['new', 'confirmed', 'packed', 'shipped', 'delivered', 'cancelled'];
    $okPay     = ['pending', 'paid', 'failed', 'refunded'];
    if (in_array($newStatus, $okStatus, true) && in_array($newPay, $okPay, true)) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Cancelling puts the items back in stock; un-cancelling takes them out again.
            if ($newStatus === 'cancelled' && $o['status'] !== 'cancelled') {
                foreach (q_all('SELECT product_id, qty FROM order_items WHERE order_id = ?', [$id]) as $it) {
                    if ($it['product_id']) q('UPDATE products SET stock = stock + ? WHERE id = ?', [$it['qty'], $it['product_id']]);
                }
            } elseif ($o['status'] === 'cancelled' && $newStatus !== 'cancelled') {
                foreach (q_all('SELECT product_id, qty FROM order_items WHERE order_id = ?', [$id]) as $it) {
                    if ($it['product_id']) q('UPDATE products SET stock = CASE WHEN stock >= ? THEN stock - ? ELSE 0 END WHERE id = ?', [$it['qty'], $it['qty'], $it['product_id']]);
                }
            }
            q('UPDATE orders SET status = ?, payment_status = ? WHERE id = ?', [$newStatus, $newPay, $id]);
            $pdo->commit();
            flash('Order updated.');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            error_log('Order update failed: ' . $ex->getMessage());
            flash('Could not update the order.', 'err');
        }
    }
    redirect(url('admin/order_view.php?id=' . $id));
}

$items = q_all('SELECT product_name, price, qty, line_total FROM order_items WHERE order_id = ?', [$id]);
$GLOBALS['admin_title'] = 'Order ' . $o['order_no'];
admin_head();
?>
<p><a href="<?= e(url('admin/orders.php')) ?>">&larr; All orders</a></p>
<div class="detail-grid">
  <section class="form-card">
    <h2>Customer &amp; delivery</h2>
    <dl class="kv">
      <dt>Name</dt><dd><?= e($o['cust_name']) ?></dd>
      <dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $o['phone'])) ?>"><?= e($o['phone']) ?></a></dd>
      <dt>Email</dt><dd><?= e($o['email'] ?: '-') ?></dd>
      <dt>Address</dt><dd><?= nl2br(e($o['address'])) ?></dd>
      <dt>Landmark</dt><dd><?= e($o['landmark'] ?: '-') ?></dd>
      <dt>City / State</dt><dd><?= e($o['city']) ?>, <?= e($o['state']) ?></dd>
      <dt>PIN code</dt><dd><?= e($o['pincode']) ?></dd>
      <dt>Notes</dt><dd><?= e($o['notes'] ?: '-') ?></dd>
      <dt>GPS location</dt><dd><?php if ($o['lat'] !== null && $o['lng'] !== null): ?><a href="<?= e(maps_link($o['lat'], $o['lng'])) ?>" target="_blank" rel="noopener noreferrer">Open in Google Maps</a><?php else: ?>Not shared<?php endif; ?></dd>
      <dt>Placed</dt><dd><?= e(date('d M Y, h:i A', strtotime($o['created_at']))) ?></dd>
    </dl>
  </section>

  <section class="form-card">
    <h2>Payment &amp; status</h2>
    <dl class="kv">
      <dt>Method</dt><dd><?= e(payment_method_label($o['payment_method'])) ?></dd>
      <dt>Reference</dt><dd><?= e($o['payment_ref'] ?: '-') ?></dd>
      <dt>Subtotal</dt><dd><?= e(money($o['subtotal'])) ?></dd>
      <dt>Delivery</dt><dd><?= e(money($o['shipping'])) ?></dd>
      <dt>Total</dt><dd><strong><?= e(money($o['total'])) ?></strong></dd>
    </dl>
    <form method="post" style="margin-top:14px">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>">
      <label>Order status
        <select name="status"><?php foreach (['new', 'confirmed', 'packed', 'shipped', 'delivered', 'cancelled'] as $s): ?>
          <option value="<?= e($s) ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= e(order_status_label($s)) ?></option><?php endforeach; ?></select>
      </label>
      <label>Payment status
        <select name="payment_status"><?php foreach (['pending', 'paid', 'failed', 'refunded'] as $s): ?>
          <option value="<?= e($s) ?>" <?= $o['payment_status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select>
      </label>
      <button class="btn btn-gold" type="submit">Update order</button>
    </form>
  </section>
</div>

<h2>Items</h2>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Product</th><th class="num">Price</th><th class="num">Qty</th><th class="num">Line total</th></tr></thead>
  <tbody>
  <?php foreach ($items as $it): ?>
    <tr><td><?= e($it['product_name']) ?></td><td class="num"><?= e(money($it['price'])) ?></td><td class="num"><?= (int) $it['qty'] ?></td><td class="num"><?= e(money($it['line_total'])) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php admin_foot(); ?>
