<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// Only the browser session that just placed the order can see this page.
$orderId = (int) ($_SESSION['last_order'] ?? 0);
$order = $orderId ? q_one('SELECT * FROM orders WHERE id = ?', [$orderId]) : null;
if (!$order) {
    redirect(url('index.php'));
}
$items = q_all('SELECT product_name, qty, line_total FROM order_items WHERE order_id = ?', [$orderId]);

$pageTitle = 'Order placed';
require __DIR__ . '/includes/header.php';
?>
<div class="success">
  <div class="tick" aria-hidden="true">✓</div>
  <h1>Thank you, <?= e(explode(' ', $order['cust_name'])[0]) ?>!</h1>
  <p>Your order <strong><?= e($order['order_no']) ?></strong> has been placed.</p>
  <p class="muted">Payment: <?= e(payment_method_label($order['payment_method'])) ?> &middot; Total: <strong><?= e(money($order['total'])) ?></strong></p>
  <ul class="plain mini-lines">
    <?php foreach ($items as $it): ?>
      <li><span><?= e($it['product_name']) ?> &times; <?= (int) $it['qty'] ?></span><span><?= e(money($it['line_total'])) ?></span></li>
    <?php endforeach; ?>
  </ul>
  <p>We will contact you on <strong><?= e($order['phone']) ?></strong> to confirm delivery.</p>
  <p><a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Continue shopping</a>
     <?php if (current_user()): ?><a class="btn btn-ghost" href="<?= e(url('account.php')) ?>">My orders</a><?php endif; ?></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
