<?php
/** Renders one product card. $p needs: id, name, price, mrp, stock, image, category_name (optional). */
function product_card(array $p): string
{
    $img   = product_image_url($p['image'] ?? null);
    $mrp   = $p['mrp'] !== null && (float) $p['mrp'] > (float) $p['price'] ? (float) $p['mrp'] : 0.0;
    $off   = $mrp ? (int) round((1 - (float) $p['price'] / $mrp) * 100) : 0;
    $out   = (int) $p['stock'] <= 0;
    $href  = url('product.php?id=' . (int) $p['id']);
    ob_start(); ?>
    <article class="card">
      <a class="card-img" href="<?= e($href) ?>">
        <?php if ($img): ?>
          <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" loading="lazy" width="400" height="400">
        <?php else: ?>
          <span class="img-ph" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($p['name'], 0, 1))) ?></span>
        <?php endif; ?>
        <?php if ($off > 0 && !$out): ?><span class="tag tag-off"><?= $off ?>% off</span><?php endif; ?>
        <?php if ($out): ?><span class="tag tag-out">Out of stock</span><?php endif; ?>
      </a>
      <div class="card-body">
        <?php if (!empty($p['category_name'])): ?><div class="card-cat"><?= e($p['category_name']) ?></div><?php endif; ?>
        <h3 class="card-title"><a href="<?= e($href) ?>"><?= e($p['name']) ?></a></h3>
        <div class="price-row">
          <span class="price"><?= e(money($p['price'])) ?></span>
          <?php if ($mrp): ?><span class="mrp"><?= e(money($mrp)) ?></span><?php endif; ?>
        </div>
        <?php if (!$out): ?>
        <form method="post" action="<?= e(url('cart.php')) ?>" class="js-add-cart">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
          <input type="hidden" name="qty" value="1">
          <input type="hidden" name="next" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
          <button class="btn btn-dark btn-block" type="submit">Add to cart</button>
        </form>
        <?php else: ?>
          <a class="btn btn-ghost btn-block" href="<?= e($href) ?>">View details</a>
        <?php endif; ?>
      </div>
    </article>
    <?php
    return (string) ob_get_clean();
}

/** Base SELECT used for product listings. */
function product_select_sql(): string
{
    return "SELECT p.id, p.name, p.price, p.mrp, p.stock, c.name AS category_name,
                   (SELECT filename FROM product_images i WHERE i.product_id = p.id ORDER BY i.sort_order, i.id LIMIT 1) AS image
            FROM products p JOIN categories c ON c.id = p.category_id";
}
