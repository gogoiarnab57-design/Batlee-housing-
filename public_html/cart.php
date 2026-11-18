<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// ---- Cart actions (all POST + CSRF protected) ----
if (is_post()) {
    require_post();
    $action = post('action');
    $pid    = (int) post('product_id');
    $next   = safe_next(post('next'), url('cart.php'));
    $wantsJson = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

    if ($action === 'add' && $pid > 0) {
        $p = q_one('SELECT id, name, stock FROM products WHERE id = ? AND is_active = 1', [$pid]);
        if (!$p || (int) $p['stock'] <= 0) {
            $msg = 'Sorry, that product is not available.';
            if ($wantsJson) { header('Content-Type: application/json'); echo json_encode(['ok' => false, 'message' => $msg, 'count' => cart_count()]); exit; }
            flash($msg, 'err');
            redirect($next);
        }
        $have = (int) (cart_ids()[$pid] ?? 0);
        $want = $have + max(1, (int) post('qty', '1'));
        $max  = min((int) $p['stock'], CART_MAX_QTY);
        $note = '';
        if ($want > $max) { $want = $max; $note = ' (maximum available quantity)'; }
        cart_set($pid, $want);
        $msg = '"' . $p['name'] . '" added to cart' . $note . '.';
        if ($wantsJson) { header('Content-Type: application/json'); echo json_encode(['ok' => true, 'message' => $msg, 'count' => cart_count()]); exit; }
        flash($msg);
        redirect(post('buy_now') === '1' ? url('cart.php') : $next);
    }

    if ($action === 'update' && $pid > 0 && isset(cart_ids()[$pid])) {
        cart_set($pid, (int) post('qty'));
        redirect(url('cart.php'));
    }
    if ($action === 'remove' && $pid > 0) {
        cart_set($pid, 0);
        flash('Item removed.');
        redirect(url('cart.php'));
    }
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        redirect(url('cart.php'));
    }
    redirect(url('cart.php'));
}

$cart = cart_summary();
foreach ($cart['problems'] as $msg) {
    flash($msg, 'err');
}
$free = (float) setting('free_shipping_above', '0');

$pageTitle = 'Your cart';
require __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Your cart</h1>
<?php if (!$cart['lines']): ?>
  <div class="empty"><p>Your cart is empty.</p><a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Continue shopping</a></div>
<?php else: ?>
<div class="cart-layout">
  <div class="cart-lines">
    <?php foreach ($cart['lines'] as $l): ?>
      <div class="cart-line">
        <a class="cart-thumb" href="<?= e(url('product.php?id=' . $l['id'])) ?>">
          <?php if ($l['image']): ?><img src="<?= e(product_image_url($l['image'])) ?>" alt="" width="90" height="90"><?php else: ?><span class="img-ph" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($l['name'], 0, 1))) ?></span><?php endif; ?>
        </a>
        <div class="cart-info">
          <a class="cart-name" href="<?= e(url('product.php?id=' . $l['id'])) ?>"><?= e($l['name']) ?></a>
          <div class="muted"><?= e(money($l['price'])) ?> each</div>
          <div class="cart-controls">
            <form method="post" class="qty-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="product_id" value="<?= (int) $l['id'] ?>">
              <label class="sr-only" for="q<?= (int) $l['id'] ?>">Quantity</label>
              <input id="q<?= (int) $l['id'] ?>" type="number" name="qty" value="<?= (int) $l['qty'] ?>" min="0" max="<?= (int) min($l['stock'], CART_MAX_QTY) ?>" inputmode="numeric">
              <button class="btn btn-ghost btn-sm" type="submit">Update</button>
            </form>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="product_id" value="<?= (int) $l['id'] ?>">
              <button class="linklike danger" type="submit">Remove</button>
            </form>
          </div>
        </div>
        <div class="cart-line-total"><?= e(money($l['line'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <aside class="summary">
    <h2>Order summary</h2>
    <dl>
      <dt>Subtotal</dt><dd><?= e(money($cart['subtotal'])) ?></dd>
      <dt>Delivery</dt><dd><?= $cart['shipping'] > 0 ? e(money($cart['shipping'])) : 'Free' ?></dd>
      <dt class="total">Total</dt><dd class="total"><?= e(money($cart['total'])) ?></dd>
    </dl>
    <?php if ($free > 0 && $cart['subtotal'] < $free): ?>
      <p class="muted small">Add <?= e(money($free - $cart['subtotal'])) ?> more for free delivery.</p>
    <?php endif; ?>
    <a class="btn btn-gold btn-block" href="<?= e(url('checkout.php')) ?>">Proceed to checkout</a>
    <a class="btn btn-ghost btn-block" href="<?= e(url('shop.php')) ?>">Continue shopping</a>
  </aside>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
