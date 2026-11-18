<?php
/** Storefront header. Usage: $pageTitle = '...'; require 'includes/header.php'; */
$user      = current_user();
$siteName  = setting('site_name', 'Batlee Housing Solution');
$cats      = q_all('SELECT id, name, slug FROM categories ORDER BY sort_order, name');
$cartCount = cart_count();
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . $siteName : $siteName;
$metaDesc  = $metaDesc ?? setting('tagline');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="theme-color" content="#0b0b0c">
<link rel="icon" type="image/jpeg" href="<?= e(url('assets/img/logo.jpg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<header class="site-header">
  <div class="wrap header-row">
    <button class="icon-btn nav-toggle" type="button" aria-label="Menu" aria-expanded="false" aria-controls="main-nav">
      <span></span><span></span><span></span>
    </button>
    <a class="brand" href="<?= e(url('index.php')) ?>">
      <img src="<?= e(url('assets/img/logo.jpg')) ?>" alt="BGA logo" width="52" height="49">
      <span class="brand-text"><strong><?= e($siteName) ?></strong><small>Batlee Group of Associates</small></span>
    </a>
    <form class="search" action="<?= e(url('shop.php')) ?>" method="get" role="search">
      <input type="search" name="q" placeholder="Search products..." value="<?= e($_GET['q'] ?? '') ?>" aria-label="Search products" maxlength="80">
      <button type="submit" aria-label="Search">Search</button>
    </form>
    <div class="header-actions">
      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?>
          <a class="hlink" href="<?= e(url('admin/')) ?>">Admin Panel</a>
        <?php else: ?>
          <a class="hlink" href="<?= e(url('account.php')) ?>">Hi, <?= e(explode(' ', $user['name'])[0]) ?></a>
        <?php endif; ?>
        <form method="post" action="<?= e(url('logout.php')) ?>" class="inline-form"><?= csrf_field() ?><button class="hlink linklike" type="submit">Logout</button></form>
      <?php else: ?>
        <a class="hlink" href="<?= e(url('login.php')) ?>">Login</a>
        <a class="hlink hlink-strong" href="<?= e(url('register.php')) ?>">Sign up</a>
      <?php endif; ?>
      <a class="cart-link" href="<?= e(url('cart.php')) ?>" aria-label="Cart, <?= (int) $cartCount ?> items">
        <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM3 2v2h2l3.6 7.6-1.4 2.4A2 2 0 0 0 9 17h11v-2H9.4l1.1-2h7.4a2 2 0 0 0 1.7-1l3.6-6.5A1 1 0 0 0 22.3 4H6.2l-.9-2H3Z"/></svg>
        <span class="cart-badge" id="cart-badge"<?= $cartCount ? '' : ' hidden' ?>><?= (int) $cartCount ?></span>
      </a>
    </div>
  </div>
  <nav id="main-nav" class="main-nav" aria-label="Categories">
    <div class="wrap nav-row">
      <a href="<?= e(url('index.php')) ?>">Home</a>
      <a href="<?= e(url('shop.php')) ?>">All Products</a>
      <?php foreach ($cats as $c): ?>
        <a href="<?= e(url('shop.php?cat=' . $c['slug'])) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </nav>
</header>
<main class="wrap main">
<?= render_flash() ?>
