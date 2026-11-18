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

$user = current_user();
$old  = $_SESSION['checkout'] ?? [];
$f = [
    'name'     => $old['name']     ?? ($user['name'] ?? ''),
    'phone'    => $old['phone']    ?? ($user['phone'] ?? ''),
    'email'    => $old['email']    ?? ($user['email'] ?? ''),
    'address'  => $old['address']  ?? '',
    'landmark' => $old['landmark'] ?? '',
    'city'     => $old['city']     ?? '',
    'state'    => $old['state']    ?? '',
    'pincode'  => $old['pincode']  ?? '',
    'notes'    => $old['notes']    ?? '',
    'lat'      => $old['lat']      ?? '',
    'lng'      => $old['lng']      ?? '',
];
$errors = [];

if (is_post()) {
    require_post();
    foreach (['name', 'phone', 'email', 'address', 'landmark', 'city', 'state', 'pincode', 'notes', 'lat', 'lng'] as $k) {
        $f[$k] = post($k);
    }
    $f['name']     = mb_substr($f['name'], 0, 100);
    $f['address']  = mb_substr($f['address'], 0, 400);
    $f['landmark'] = mb_substr($f['landmark'], 0, 150);
    $f['city']     = mb_substr($f['city'], 0, 80);
    $f['state']    = mb_substr($f['state'], 0, 80);
    $f['notes']    = mb_substr($f['notes'], 0, 500);

    if ($f['name'] === '')                                         $errors['name'] = 'Please enter your full name.';
    if (!valid_phone($f['phone']))                                 $errors['phone'] = 'Please enter a valid contact number.';
    if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    if (mb_strlen($f['address']) < 6)                              $errors['address'] = 'Please enter your full delivery address.';
    if ($f['city'] === '')                                         $errors['city'] = 'Please enter your city.';
    if ($f['state'] === '')                                        $errors['state'] = 'Please enter your state.';
    if (!preg_match('/^[0-9]{6}$/', $f['pincode']))                $errors['pincode'] = 'Please enter a 6-digit PIN code.';

    // Optional GPS location: keep only if both are valid coordinates.
    if (!(is_numeric($f['lat']) && is_numeric($f['lng']) && abs((float) $f['lat']) <= 90 && abs((float) $f['lng']) <= 180)) {
        $f['lat'] = $f['lng'] = '';
    }

    if (!$errors) {
        $_SESSION['checkout'] = $f;
        redirect(url('payment.php'));
    }
}

$pageTitle = 'Checkout';
require __DIR__ . '/includes/header.php';

function field_err(array $errors, string $k): string
{
    return isset($errors[$k]) ? '<span class="field-err">' . e($errors[$k]) . '</span>' : '';
}
?>
<h1 class="page-title">Delivery details</h1>
<div class="cart-layout">
  <form method="post" class="form-card" novalidate>
    <?= csrf_field() ?>
    <div class="form-grid">
      <label>Full name *
        <input type="text" name="name" value="<?= e($f['name']) ?>" required maxlength="100" autocomplete="name">
        <?= field_err($errors, 'name') ?>
      </label>
      <label>Contact number *
        <input type="tel" name="phone" value="<?= e($f['phone']) ?>" required maxlength="20" inputmode="tel" autocomplete="tel">
        <?= field_err($errors, 'phone') ?>
      </label>
      <label class="span2">Email (optional)
        <input type="email" name="email" value="<?= e($f['email']) ?>" maxlength="150" autocomplete="email">
        <?= field_err($errors, 'email') ?>
      </label>
      <label class="span2">Full address (house no., street, area) *
        <textarea name="address" rows="3" required maxlength="400" autocomplete="street-address"><?= e($f['address']) ?></textarea>
        <?= field_err($errors, 'address') ?>
      </label>
      <label class="span2">Landmark (optional)
        <input type="text" name="landmark" value="<?= e($f['landmark']) ?>" maxlength="150">
      </label>
      <label>City *
        <input type="text" name="city" value="<?= e($f['city']) ?>" required maxlength="80" autocomplete="address-level2">
        <?= field_err($errors, 'city') ?>
      </label>
      <label>State *
        <input type="text" name="state" value="<?= e($f['state']) ?>" required maxlength="80" autocomplete="address-level1">
        <?= field_err($errors, 'state') ?>
      </label>
      <label>PIN code *
        <input type="text" name="pincode" value="<?= e($f['pincode']) ?>" required maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="postal-code">
        <?= field_err($errors, 'pincode') ?>
      </label>
      <label class="span2">Delivery notes (optional)
        <textarea name="notes" rows="2" maxlength="500" placeholder="Preferred time, gate code, etc."><?= e($f['notes']) ?></textarea>
      </label>
    </div>

    <div class="loc-box">
      <button class="btn btn-ghost" type="button" id="use-location">📍 Share my current location (optional)</button>
      <span class="muted small" id="loc-status"><?= $f['lat'] !== '' ? 'Location attached.' : 'Helps our delivery team find you faster. Asked only when you tap the button.' ?></span>
      <input type="hidden" name="lat" id="lat" value="<?= e($f['lat']) ?>">
      <input type="hidden" name="lng" id="lng" value="<?= e($f['lng']) ?>">
    </div>

    <button class="btn btn-gold btn-block" type="submit">Continue to payment</button>
  </form>

  <aside class="summary">
    <h2>Your order</h2>
    <ul class="plain mini-lines">
      <?php foreach ($cart['lines'] as $l): ?>
        <li><span><?= e($l['name']) ?> &times; <?= (int) $l['qty'] ?></span><span><?= e(money($l['line'])) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <dl>
      <dt>Subtotal</dt><dd><?= e(money($cart['subtotal'])) ?></dd>
      <dt>Delivery</dt><dd><?= $cart['shipping'] > 0 ? e(money($cart['shipping'])) : 'Free' ?></dd>
      <dt class="total">Total</dt><dd class="total"><?= e(money($cart['total'])) ?></dd>
    </dl>
    <a class="btn btn-ghost btn-block" href="<?= e(url('cart.php')) ?>">Edit cart</a>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
