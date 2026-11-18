<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();
if ($user['role'] === 'admin') {
    redirect(url('admin/'));
}

if (is_post()) {
    require_post();
    $action = post('action');
    if ($action === 'profile') {
        $name = mb_substr(post('name'), 0, 100);
        $phone = post('phone');
        $email = post('email');
        if ($name === '' || !valid_phone($phone) || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            flash('Please check your name, phone number and email.', 'err');
        } else {
            q('UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ?', [$name, $phone, $email !== '' ? $email : null, $user['id']]);
            flash('Profile updated.');
        }
    } elseif ($action === 'password') {
        $row = q_one('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $row['password_hash'])) {
            flash('Your current password is incorrect.', 'err');
        } elseif ($msg = valid_password($new)) {
            flash($msg, 'err');
        } elseif ($new !== (string) ($_POST['new_password2'] ?? '')) {
            flash('The new passwords do not match.', 'err');
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            flash('Password changed.');
        }
    }
    redirect(url('account.php'));
}

// Customers only ever see THEIR OWN orders (filtered by their user id).
$orders = q_all('SELECT id, order_no, total, status, payment_method, payment_status, created_at
                 FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 50', [$user['id']]);
$viewId = (int) ($_GET['order'] ?? 0);
$view = $viewId ? q_one('SELECT * FROM orders WHERE id = ? AND user_id = ?', [$viewId, $user['id']]) : null;
$viewItems = $view ? q_all('SELECT product_name, price, qty, line_total FROM order_items WHERE order_id = ?', [$viewId]) : [];
$me = q_one('SELECT name, phone, email FROM users WHERE id = ?', [$user['id']]);

$pageTitle = 'My account';
require __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">My account</h1>
<div class="account-grid">
  <section>
    <h2>My orders</h2>
    <?php if ($view): ?>
      <div class="form-card">
        <h3>Order <?= e($view['order_no']) ?></h3>
        <p class="muted"><?= e(date('d M Y, h:i A', strtotime($view['created_at']))) ?> &middot; <span class="badge st-<?= e($view['status']) ?>"><?= e(order_status_label($view['status'])) ?></span></p>
        <ul class="plain mini-lines">
          <?php foreach ($viewItems as $it): ?>
            <li><span><?= e($it['product_name']) ?> &times; <?= (int) $it['qty'] ?></span><span><?= e(money($it['line_total'])) ?></span></li>
          <?php endforeach; ?>
          <li><span>Delivery</span><span><?= (float) $view['shipping'] > 0 ? e(money($view['shipping'])) : 'Free' ?></span></li>
          <li class="total"><span>Total</span><span><?= e(money($view['total'])) ?></span></li>
        </ul>
        <p class="muted">Payment: <?= e(payment_method_label($view['payment_method'])) ?> (<?= e(ucfirst($view['payment_status'])) ?>)</p>
        <p class="muted">Deliver to: <?= e($view['address']) ?>, <?= e($view['city']) ?>, <?= e($view['state']) ?> - <?= e($view['pincode']) ?></p>
        <a href="<?= e(url('account.php')) ?>">&larr; All orders</a>
      </div>
    <?php elseif ($orders): ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><a href="<?= e(url('account.php?order=' . (int) $o['id'])) ?>"><?= e($o['order_no']) ?></a></td>
            <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
            <td><?= e(money($o['total'])) ?></td>
            <td><span class="badge st-<?= e($o['status']) ?>"><?= e(order_status_label($o['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php else: ?>
      <p class="muted">You have not placed any orders yet.</p>
    <?php endif; ?>
  </section>

  <section>
    <form method="post" class="form-card">
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <h2>My details</h2>
      <label>Name <input type="text" name="name" value="<?= e($me['name']) ?>" required maxlength="100"></label>
      <label>Phone <input type="tel" name="phone" value="<?= e($me['phone']) ?>" required maxlength="20"></label>
      <label>Email <input type="email" name="email" value="<?= e($me['email']) ?>" maxlength="150"></label>
      <button class="btn btn-dark" type="submit">Save</button>
    </form>
    <form method="post" class="form-card">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <h2>Change password</h2>
      <label>Current password <input type="password" name="current_password" required autocomplete="current-password"></label>
      <label>New password <input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
      <label>Confirm new password <input type="password" name="new_password2" required minlength="8" autocomplete="new-password"></label>
      <button class="btn btn-dark" type="submit">Change password</button>
    </form>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
