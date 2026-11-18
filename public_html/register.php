<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(url('account.php'));
}

$f = ['name' => '', 'username' => '', 'phone' => '', 'email' => ''];
$errors = [];

if (is_post()) {
    require_post();
    foreach ($f as $k => $_) {
        $f[$k] = post($k);
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['password2'] ?? '');

    if ($f['name'] === '' || mb_strlen($f['name']) > 100)           $errors[] = 'Please enter your name.';
    if (!preg_match('/^[A-Za-z0-9_.@-]{3,40}$/', $f['username']))    $errors[] = 'Username must be 3-40 characters: letters, numbers and _ . @ - only.';
    if (!valid_phone($f['phone']))                                   $errors[] = 'Please enter a valid phone number.';
    if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($msg = valid_password($password))                            $errors[] = $msg;
    if ($password !== $confirm)                                      $errors[] = 'The two passwords do not match.';
    if (!$errors && q_val('SELECT COUNT(*) FROM users WHERE LOWER(username) = LOWER(?)', [$f['username']])) {
        $errors[] = 'That username is already taken.';
    }

    if (!$errors) {
        $id = db_insert('users', [
            'username' => $f['username'], 'name' => $f['name'],
            'email' => $f['email'] !== '' ? $f['email'] : null, 'phone' => $f['phone'],
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'customer', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $cart = $_SESSION['cart'] ?? [];
        login_user($id);
        $_SESSION['cart'] = $cart; // keep the guest's cart after sign-up
        flash('Welcome, ' . $f['name'] . '! Your account is ready.');
        redirect(url('index.php'));
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <form method="post" class="form-card auth-card" novalidate>
    <h1>Create account</h1>
    <?php foreach ($errors as $er): ?><div class="alert alert-err" role="alert"><?= e($er) ?></div><?php endforeach; ?>
    <?= csrf_field() ?>
    <label>Full name <input type="text" name="name" value="<?= e($f['name']) ?>" required maxlength="100" autocomplete="name"></label>
    <label>Username <input type="text" name="username" value="<?= e($f['username']) ?>" required maxlength="40" autocomplete="username" autocapitalize="none"></label>
    <label>Phone <input type="tel" name="phone" value="<?= e($f['phone']) ?>" required maxlength="20" inputmode="tel" autocomplete="tel"></label>
    <label>Email (optional) <input type="email" name="email" value="<?= e($f['email']) ?>" maxlength="150" autocomplete="email"></label>
    <label>Password (min 8 characters) <input type="password" name="password" required minlength="8" maxlength="200" autocomplete="new-password"></label>
    <label>Confirm password <input type="password" name="password2" required minlength="8" maxlength="200" autocomplete="new-password"></label>
    <button class="btn btn-gold btn-block" type="submit">Sign up</button>
    <p class="muted center">Already registered? <a href="<?= e(url('login.php')) ?>">Login</a></p>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
