<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/product_card.php';

$pageTitle = 'Home';
$catTiles = q_all('SELECT c.id, c.name, c.slug, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS n
               FROM categories c ORDER BY c.sort_order, c.name');
$latest = q_all(product_select_sql() . ' WHERE p.is_active = 1 ORDER BY p.id DESC LIMIT 8');

require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="hero-text">
    <p class="eyebrow">Batlee Housing Solution</p>
    <h1><?= e(setting('hero_title', 'Everything your home needs')) ?></h1>
    <p><?= e(setting('hero_text')) ?></p>
    <div class="hero-cta">
      <a class="btn btn-gold" href="<?= e(url('shop.php')) ?>">Shop now</a>
      <?php if (!current_user()): ?><a class="btn btn-outline-light" href="<?= e(url('register.php')) ?>">Create account</a><?php endif; ?>
    </div>
  </div>
  <img class="hero-logo" src="<?= e(url('assets/img/logo.jpg')) ?>" alt="Batlee Group of Associates logo" width="288" height="272">
</section>

<section class="section">
  <h2 class="section-title">Shop by category</h2>
  <div class="cat-grid">
    <?php foreach ($catTiles as $c): ?>
      <a class="cat-tile" href="<?= e(url('shop.php?cat=' . $c['slug'])) ?>">
        <span class="cat-name"><?= e($c['name']) ?></span>
        <span class="cat-count"><?= (int) $c['n'] ?> product<?= (int) $c['n'] === 1 ? '' : 's' ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <h2 class="section-title">New arrivals</h2>
    <a href="<?= e(url('shop.php')) ?>">View all &rarr;</a>
  </div>
  <?php if ($latest): ?>
    <div class="grid"><?php foreach ($latest as $p) { echo product_card($p); } ?></div>
  <?php else: ?>
    <p class="muted">Products will appear here soon.</p>
  <?php endif; ?>
</section>

<section class="section perks">
  <div><strong>Secure checkout</strong><span>Your details are visible only to our store team.</span></div>
  <div><strong>Easy ordering</strong><span>Add to cart, enter your delivery details and place your order.</span></div>
  <div><strong>Helpline support</strong><span>Use the Helpline button to reach us any time.</span></div>
  <div><strong>Four home categories</strong><span>Bathroom, kitchen, hardware and utilities together.</span></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
