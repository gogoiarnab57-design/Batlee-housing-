<?php
declare(strict_types=1);

/** Every admin page starts with admin_start('Title', 'nav-key'). It blocks anyone who is not the admin. */
function admin_start(string $title, string $active): array
{
    $admin = require_admin();
    header('Cache-Control: no-store');   // admin pages hold customer data - never cache them
    $GLOBALS['admin_title'] = $title;
    $GLOBALS['admin_active'] = $active;
    return $admin;
}

function admin_head(): void
{
    $title  = $GLOBALS['admin_title'] ?? 'Admin';
    $active = $GLOBALS['admin_active'] ?? '';
    $newOrders = (int) q_val("SELECT COUNT(*) FROM orders WHERE status = 'new'");
    $pendingRev = (int) q_val("SELECT COUNT(*) FROM reviews WHERE status = 'pending'");
    $unreadMsg = (int) q_val('SELECT COUNT(*) FROM messages WHERE is_read = 0');
    $nav = [
        'dashboard'  => ['Dashboard', 'admin/index.php', 0],
        'orders'     => ['Orders', 'admin/orders.php', $newOrders],
        'products'   => ['Products', 'admin/products.php', 0],
        'categories' => ['Categories', 'admin/categories.php', 0],
        'sales'      => ['Sales records', 'admin/sales.php', 0],
        'reviews'    => ['Reviews', 'admin/reviews.php', $pendingRev],
        'messages'   => ['Helpline messages', 'admin/messages.php', $unreadMsg],
        'settings'   => ['Site & contact settings', 'admin/settings.php', 0],
        'account'    => ['My login & password', 'admin/account.php', 0],
    ];
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($title) ?> | Admin</title>
<link rel="icon" type="image/jpeg" href="<?= e(url('assets/img/logo.jpg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="admin-body">
<div class="admin-bar">
  <button class="icon-btn nav-toggle" type="button" aria-label="Menu" aria-expanded="false" aria-controls="admin-nav"><span></span><span></span><span></span></button>
  <a class="brand" href="<?= e(url('admin/index.php')) ?>">
    <img src="<?= e(url('assets/img/logo.jpg')) ?>" alt="" width="40" height="38">
    <span class="brand-text"><strong>Admin Panel</strong><small><?= e(setting('site_name')) ?></small></span>
  </a>
  <span class="spacer"></span>
  <a class="hlink" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View site</a>
  <form method="post" action="<?= e(url('logout.php')) ?>" class="inline-form"><?= csrf_field() ?><button class="hlink linklike" type="submit">Logout</button></form>
</div>
<div class="admin-shell">
  <nav class="admin-nav" id="admin-nav" aria-label="Admin">
    <?php foreach ($nav as $key => [$label, $href, $count]): ?>
      <a href="<?= e(url($href)) ?>" class="<?= $key === $active ? 'active' : '' ?>"><?= e($label) ?><?php if ($count): ?><span class="nav-count"><?= $count ?></span><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
  <main class="admin-main">
    <h1><?= e($title) ?></h1>
    <?= render_flash() ?>
<?php
}

function admin_foot(): void
{
    ?>
  </main>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}
