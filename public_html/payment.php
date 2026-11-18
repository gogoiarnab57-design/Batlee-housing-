<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$cart = cart_summary();
foreach ($cart['problems'] as $msg) {
    flash($msg, 'err');
}
if (!$cart['lines']) {
    redirect(url('cart.php'));
}
$co = $_SESSION['checkout'] ?? null;
if (!$co) {
    redirect(url('checkout.php'));
}

$methods = [];
foreach (['cod' => 'pay_cod', 'upi' => 'pay_upi', 'card' => 'pay_card', 'netbanking' => 'pay_netbanking'] as $code => $key) {
    if (setting($key, '1') === '1') {
        $methods[$code] = payment_method_label($code);
    }
}
$upiId = setting('upi_id');

if (is_post()) {
    require_post();
    $method = post('method');
    $ref    = mb_substr(post('payment_ref'), 0, 60);
    if (!isset($methods[$method])) {
        flash('Please choose a payment option.', 'err');
        redirect(url('payment.php'));
    }
    if ($ref !== '' && !preg_match('/^[A-Za-z0-9\-_.@ ]+$/', $ref)) {
        flash('The payment reference may contain only letters, numbers and - _ . @', 'err');
        redirect(url('payment.php'));
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();
        // Re-read the cart inside the transaction so prices and stock are final.
        $cart = cart_summary();
        if (!$cart['lines']) {
            throw new RuntimeException('Your cart is empty.');
        }
        $orderNo = 'BHS' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $user = current_user();
        $orderId = db_insert('orders', [
            'order_no' => $orderNo,
            'user_id' => $user ? $user['id'] : null,
            'cust_name' => $co['name'], 'phone' => $co['phone'],
            'email' => $co['email'] !== '' ? $co['email'] : null,
            'address' => $co['address'],
            'landmark' => $co['landmark'] !== '' ? $co['landmark'] : null,
            'city' => $co['city'], 'state' => $co['state'], 'pincode' => $co['pincode'],
            'notes' => $co['notes'] !== '' ? $co['notes'] : null,
            'lat' => $co['lat'] !== '' ? $co['lat'] : null,
            'lng' => $co['lng'] !== '' ? $co['lng'] : null,
            'subtotal' => $cart['subtotal'], 'shipping' => $cart['shipping'], 'total' => $cart['total'],
            'payment_method' => $method, 'payment_status' => 'pending',
            'payment_ref' => $ref !== '' ? $ref : null,
            'status' => 'new', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        foreach ($cart['lines'] as $l) {
            // Atomic stock check: fails if someone else bought the last units meanwhile.
            $st = q('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?', [$l['qty'], $l['id'], $l['qty']]);
            if ($st->rowCount() !== 1) {
                throw new RuntimeException('Sorry, "' . $l['name'] . '" just went out of stock. Please review your cart.');
            }
            db_insert('order_items', [
                'order_id' => $orderId, 'product_id' => $l['id'], 'product_name' => $l['name'],
                'price' => $l['price'], 'qty' => $l['qty'], 'line_total' => $l['line'],
            ]);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $shown = $ex instanceof RuntimeException ? $ex->getMessage() : 'We could not place your order. Please try again.';
        if (!($ex instanceof RuntimeException)) {
            error_log('Order failed: ' . $ex->getMessage());
        }
        flash($shown, 'err');
        redirect(url('cart.php'));
    }

    $_SESSION['cart'] = [];
    unset($_SESSION['checkout']);
    $_SESSION['last_order'] = $orderId;
    redirect(url('order_success.php'));
}

$pageTitle = 'Payment';
require __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">Payment</h1>
<p class="secure-note">🔒 Your delivery and payment details are private. Only our store administrator can view them. We never ask for or store your card number or bank password on this site.</p>

<div class="cart-layout">
  <form method="post" class="form-card" autocomplete="off" novalidate>
    <?= csrf_field() ?>
    <h2>Choose a payment option</h2>
    <?php if (!$methods): ?>
      <p class="alert alert-err">No payment options are enabled right now. Please call us to place your order.</p>
    <?php else: ?>
    <div class="pay-options">
      <?php $first = true; foreach ($methods as $code => $label): ?>
        <label class="pay-opt">
          <input type="radio" name="method" value="<?= e($code) ?>" <?= $first ? 'checked' : '' ?> required>
          <span>
            <strong><?= e($label) ?></strong>
            <small>
              <?php if ($code === 'cod'): ?>Pay in cash when your order is delivered.
              <?php elseif ($code === 'upi'): ?>Pay by any UPI app<?= $upiId !== '' ? ' to <b>' . e($upiId) . '</b>' : '' ?>, then enter the payment reference below.
              <?php elseif ($code === 'card'): ?>Our team will contact you to complete the card payment securely.
              <?php else: ?>Our team will contact you to complete the net banking payment securely.
              <?php endif; ?>
            </small>
          </span>
        </label>
      <?php $first = false; endforeach; ?>
    </div>
    <label class="upi-ref" id="upi-ref" hidden>UPI transaction reference (optional)
      <input type="text" name="payment_ref" maxlength="60" placeholder="e.g. 4123 5678 9012">
    </label>
    <button class="btn btn-gold btn-block" type="submit">Place order - <?= e(money($cart['total'])) ?></button>
    <?php endif; ?>
    <p class="muted small">By placing the order you confirm your delivery details are correct. <a href="<?= e(url('checkout.php')) ?>">Edit details</a></p>
  </form>

  <aside class="summary">
    <h2>Deliver to</h2>
    <p class="addr"><strong><?= e($co['name']) ?></strong><br>
      <?= nl2br(e($co['address'])) ?><br>
      <?= e($co['city']) ?>, <?= e($co['state']) ?> - <?= e($co['pincode']) ?><br>
      Phone: <?= e($co['phone']) ?></p>
    <h2>Order total</h2>
    <dl>
      <dt>Subtotal</dt><dd><?= e(money($cart['subtotal'])) ?></dd>
      <dt>Delivery</dt><dd><?= $cart['shipping'] > 0 ? e(money($cart['shipping'])) : 'Free' ?></dd>
      <dt class="total">Total</dt><dd class="total"><?= e(money($cart['total'])) ?></dd>
    </dl>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
