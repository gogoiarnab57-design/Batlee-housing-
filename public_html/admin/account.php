<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
$admin = admin_start('My login & password', 'account');

if (is_post()) {
    require_post();
    $row = q_one('SELECT password_hash FROM users WHERE id = ?', [$admin['id']]);
    $current  = (string) ($_POST['current_password'] ?? '');
    $username = post('username');
    $new      = (string) ($_POST['new_password'] ?? '');
    $new2     = (string) ($_POST['new_password2'] ?? '');
    $err = null;

    if (!password_verify($current, $row['password_hash'])) {
        $err = 'Your current password is incorrect.';
    } elseif (!preg_match('/^[A-Za-z0-9_.@-]{3,40}$/', $username)) {
        $err = 'Username must be 3-40 characters: letters, numbers and _ . @ - only.';
    } elseif (q_val('SELECT COUNT(*) FROM users WHERE LOWER(username) = LOWER(?) AND id <> ?', [$username, $admin['id']])) {
        $err = 'That username is already used by someone else.';
    } elseif ($new !== '' && ($msg = valid_password($new))) {
        $err = $msg;
    } elseif ($new !== $new2) {
        $err = 'The two new passwords do not match.';
    }

    if ($err) {
        flash($err, 'err');
    } else {
        q('UPDATE users SET username = ? WHERE id = ?', [$username, $admin['id']]);
        if ($new !== '') {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
        }
        session_regenerate_id(true);
        flash('Your login details were updated.');
    }
    redirect(url('admin/account.php'));
}

$me = q_one('SELECT username FROM users WHERE id = ?', [$admin['id']]);
admin_head();
?>
<form method="post" class="form-card" style="max-width:480px">
  <?= csrf_field() ?>
  <p class="muted">Change the admin username and/or password. Leave the new password empty to keep the current one.</p>
  <label>Username <input type="text" name="username" value="<?= e($me['username']) ?>" required maxlength="40" autocomplete="username" autocapitalize="none"></label>
  <label>New password (min 8 characters) <input type="password" name="new_password" minlength="8" maxlength="200" autocomplete="new-password"></label>
  <label>Confirm new password <input type="password" name="new_password2" minlength="8" maxlength="200" autocomplete="new-password"></label>
  <hr style="border:0;border-top:1px solid var(--line);margin:14px 0">
  <label>Current password (required to save) <input type="password" name="current_password" required maxlength="200" autocomplete="current-password"></label>
  <button class="btn btn-gold" type="submit">Save changes</button>
</form>
<?php admin_foot(); ?>
