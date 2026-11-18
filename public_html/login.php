<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect(is_admin() ? url('admin/') : url('account.php'));
}

$next = (string) ($_GET['next'] ?? $_POST['next'] ?? '');
$error = '';
$username = '';

if (is_post()) {
    require_post();
    $username = mb_substr(post('username'), 0, 60);
    $password = (string) ($_POST['password'] ?? '');

    if (login_locked($username)) {
        $error = 'Too many failed attempts. Please wait ' . LOGIN_WINDOW_MINUTES . ' minutes and try again.';
    } else {
        $row = q_one('SELECT id, password_hash, role FROM users WHERE username = ?', [$username]);
        // Always run a hash check so response time does not reveal whether the username exists.
        $hash = $row['password_hash'] ?? '$2y$10$UGW/gQvGBVa82EECEWEG3uEn8DtKzCgnHsFHFcMZNcTGT95MyovCW';
        $ok = password_verify($password, $hash) && $row;
        if ($ok) {
            login_clear($username);
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $row['id']]);
            }
            login_user((int) $row['id']);
            if ($row['role'] === 'admin') {
                redirect(url('admin/'));
            }
            redirect(safe_next($next, url('account.php')));
        }
        login_failed($username);
        $error = 'Incorrect username or password.';
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <form method="post" class="form-card auth-card">
    <h1>Login</h1>
    <p class="muted">Customers and store admin can log in here.</p>
    <?php if ($error): ?><div class="alert alert-err" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label>Username
      <input type="text" name="username" value="<?= e($username) ?>" required maxlength="60" autocomplete="username" autocapitalize="none" autofocus>
    </label>
    <label>Password
      <input type="password" name="password" required maxlength="200" autocomplete="current-password">
    </label>
    <button class="btn btn-gold btn-block" type="submit">Login</button>
    <p class="muted center">New customer? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
