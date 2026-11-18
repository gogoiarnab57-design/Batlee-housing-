<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/product_card.php';

$id = (int) ($_GET['id'] ?? 0);
$p = q_one('SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p JOIN categories c ON c.id = p.category_id
            WHERE p.id = ? AND p.is_active = 1', [$id]);
if (!$p) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty"><h1>Product not found</h1><a class="btn btn-dark" href="' . e(url('shop.php')) . '">Back to shop</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ---- Review submission (logged-in customers only; admin approves before it shows) ----
if (is_post() && post('action') === 'review') {
    require_post();
    $u = require_login();
    $rating = (int) post('rating');
    $comment = mb_substr(post('comment'), 0, 1000);
    if ($rating < 1 || $rating > 5) {
        flash('Please choose a rating from 1 to 5.', 'err');
    } elseif (q_val('SELECT COUNT(*) FROM reviews WHERE product_id = ? AND user_id = ?', [$id, $u['id']])) {
        flash('You have already reviewed this product.', 'err');
    } else {
        db_insert('reviews', [
            'product_id' => $id, 'user_id' => $u['id'], 'rating' => $rating, 'comment' => $comment,
            'status' => 'pending', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        flash('Thank you! Your review will appear after our team approves it.');
    }
    redirect(url('product.php?id=' . $id . '#reviews'));
}

$images = q_all('SELECT filename FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$id]);
$reviews = q_all("SELECT r.rating, r.comment, r.created_at, u.name
                  FROM reviews r JOIN users u ON u.id = r.user_id
                  WHERE r.product_id = ? AND r.status = 'approved' ORDER BY r.id DESC", [$id]);
$avg = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
$related = q_all(product_select_sql() . ' WHERE p.is_active = 1 AND p.category_id = ? AND p.id <> ? ORDER BY p.id DESC LIMIT 4',
                 [(int) $p['category_id'], $id]);

$mrp = $p['mrp'] !== null && (float) $p['mrp'] > (float) $p['price'] ? (float) $p['mrp'] : 0.0;
$off = $mrp ? (int) round((1 - (float) $p['price'] / $mrp) * 100) : 0;
$stock = (int) $p['stock'];

$pageTitle = $p['name'];
$metaDesc  = mb_substr(strip_tags((string) $p['description']), 0, 160);
require __DIR__ . '/includes/header.php';
?>
<nav class="crumbs" aria-label="Breadcrumb">
  <a href="<?= e(url('index.php')) ?>">Home</a> /
  <a href="<?= e(url('shop.php?cat=' . $p['category_slug'])) ?>"><?= e($p['category_name']) ?></a> /
  <span><?= e($p['name']) ?></span>
</nav>

<section class="product">
  <div class="gallery">
    <div class="gallery-main">
      <?php if ($images): ?>
        <img id="main-img" src="<?= e(product_image_url($images[0]['filename'])) ?>" alt="<?= e($p['name']) ?>" width="600" height="600">
      <?php else: ?>
        <span class="img-ph big" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($p['name'], 0, 1))) ?></span>
      <?php endif; ?>
    </div>
    <?php if (count($images) > 1): ?>
      <div class="thumbs">
        <?php foreach ($images as $i => $im): ?>
          <button type="button" class="thumb js-thumb<?= $i === 0 ? ' active' : '' ?>" data-src="<?= e(product_image_url($im['filename'])) ?>" aria-label="Photo <?= $i + 1 ?>">
            <img src="<?= e(product_image_url($im['filename'])) ?>" alt="" width="80" height="80" loading="lazy">
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="product-info">
    <h1><?= e($p['name']) ?></h1>
    <?php if ($reviews): ?>
      <p class="rating"><span class="stars" aria-hidden="true"><?= e(stars($avg)) ?></span> <?= e(number_format($avg, 1)) ?> (<?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?>)</p>
    <?php endif; ?>
    <div class="price-row big">
      <span class="price"><?= e(money($p['price'])) ?></span>
      <?php if ($mrp): ?><span class="mrp"><?= e(money($mrp)) ?></span><span class="tag tag-off inline"><?= $off ?>% off</span><?php endif; ?>
    </div>
    <p class="stock <?= $stock > 0 ? 'in' : 'out' ?>">
      <?= $stock <= 0 ? 'Out of stock' : ($stock <= 5 ? 'Only ' . $stock . ' left' : 'In stock') ?>
    </p>

    <?php if ($stock > 0): ?>
    <form method="post" action="<?= e(url('cart.php')) ?>" class="buy-form js-add-cart">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
      <input type="hidden" name="next" value="<?= e('/product.php?id=' . (int) $p['id']) ?>">
      <label class="qty">Qty
        <input type="number" name="qty" value="1" min="1" max="<?= (int) min($stock, CART_MAX_QTY) ?>" inputmode="numeric">
      </label>
      <button class="btn btn-dark" type="submit">Add to cart</button>
      <button class="btn btn-gold" type="submit" name="buy_now" value="1">Buy now</button>
    </form>
    <?php endif; ?>

    <div class="desc">
      <h2>Description</h2>
      <p><?= nl2br(e((string) $p['description'])) ?></p>
    </div>
  </div>
</section>

<section class="section" id="reviews">
  <h2 class="section-title">Customer reviews</h2>
  <?php if ($reviews): foreach ($reviews as $r): ?>
    <div class="review">
      <div><span class="stars" aria-hidden="true"><?= e(stars((float) $r['rating'])) ?></span> <strong><?= e($r['name']) ?></strong>
        <small class="muted"><?= e(date('d M Y', strtotime($r['created_at']))) ?></small></div>
      <?php if (trim((string) $r['comment']) !== ''): ?><p><?= nl2br(e($r['comment'])) ?></p><?php endif; ?>
    </div>
  <?php endforeach; else: ?>
    <p class="muted">No reviews yet.</p>
  <?php endif; ?>

  <?php if (current_user() && !is_admin()): ?>
    <form method="post" class="form-card review-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="review">
      <h3>Write a review</h3>
      <label>Rating
        <select name="rating" required>
          <option value="">Choose...</option>
          <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> - <?= e(stars((float) $i)) ?></option><?php endfor; ?>
        </select>
      </label>
      <label>Your review <textarea name="comment" rows="3" maxlength="1000"></textarea></label>
      <button class="btn btn-dark" type="submit">Submit review</button>
    </form>
  <?php elseif (!current_user()): ?>
    <p><a href="<?= e(url('login.php?next=' . rawurlencode('/product.php?id=' . $id))) ?>">Log in</a> to write a review.</p>
  <?php endif; ?>
</section>

<?php if ($related): ?>
<section class="section">
  <h2 class="section-title">You may also like</h2>
  <div class="grid"><?php foreach ($related as $r) { echo product_card($r); } ?></div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
