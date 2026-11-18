</main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div>
      <div class="foot-brand">
        <img src="<?= e(url('assets/img/logo.jpg')) ?>" alt="" width="44" height="42">
        <strong><?= e(setting('site_name', 'Batlee Housing Solution')) ?></strong>
      </div>
      <p class="muted-light"><?= e(setting('tagline')) ?></p>
    </div>
    <div>
      <h3>Shop</h3>
      <ul class="plain">
        <?php foreach ($cats as $c): ?>
          <li><a href="<?= e(url('shop.php?cat=' . $c['slug'])) ?>"><?= e($c['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h3>Contact us</h3>
      <ul class="plain">
        <?php if (setting('phone') !== ''): ?><li>Phone: <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('phone'))) ?>"><?= e(setting('phone')) ?></a></li><?php endif; ?>
        <?php if (setting('email') !== ''): ?><li>Email: <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></li><?php endif; ?>
        <?php if (setting('address') !== ''): ?><li><?= nl2br(e(setting('address'))) ?></li><?php endif; ?>
        <?php if (setting('hours') !== ''): ?><li><?= e(setting('hours')) ?></li><?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="wrap">&copy; <?= date('Y') ?> <?= e(setting('site_name', 'Batlee Housing Solution')) ?> &middot; A Batlee Group of Associates company</div>
  </div>
</footer>

<?php require __DIR__ . '/chatbox.php'; ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
