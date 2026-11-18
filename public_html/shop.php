<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/product_card.php';

$slug = (string) ($_GET['cat'] ?? '');
$term = trim((string) ($_GET['q'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'new');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per  = 12;

$category = $slug !== '' ? q_one('SELECT id, name FROM categories WHERE slug = ?', [$slug]) : null;

$where  = ['p.is_active = 1'];
$params = [];
if ($category) {
    $where[]  = 'p.category_id = ?';
    $params[] = (int) $category['id'];
}
if ($term !== '') {
    $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_substr($term, 0, 80)) . '%';
    $where[]  = "(p.name LIKE ? ESCAPE '!' OR p.description LIKE ? ESCAPE '!')";
    $params[] = $like;
    $params[] = $like;
}
$orderBy = ['new' => 'p.id DESC', 'low' => 'p.price ASC', 'high' => 'p.price DESC', 'name' => 'p.name ASC'][$sort] ?? 'p.id DESC';
$whereSql = implode(' AND ', $where);

$total = (int) q_val("SELECT COUNT(*) FROM products p WHERE $whereSql", $params);
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$offset = ($page - 1) * $per;
$items = q_all(product_select_sql() . " WHERE $whereSql ORDER BY $orderBy LIMIT " . (int) $per . ' OFFSET ' . (int) $offset, $params);

$heading = $category ? $category['name'] : ($term !== '' ? 'Search results' : 'All products');
$pageTitle = $heading;

function shop_link(array $over): string
{
    $q = array_merge(['cat' => $_GET['cat'] ?? '', 'q' => $_GET['q'] ?? '', 'sort' => $_GET['sort'] ?? '', 'page' => ''], $over);
    return url('shop.php?' . http_build_query(array_filter($q, fn($v) => $v !== '' && $v !== null)));
}

require __DIR__ . '/includes/header.php';
?>
<div class="section-head">
  <div>
    <h1 class="page-title"><?= e($heading) ?></h1>
    <p class="muted"><?= $total ?> product<?= $total === 1 ? '' : 's' ?><?= $term !== '' ? ' for &ldquo;' . e($term) . '&rdquo;' : '' ?></p>
  </div>
  <form method="get" class="sort-form">
    <?php if ($slug !== ''): ?><input type="hidden" name="cat" value="<?= e($slug) ?>"><?php endif; ?>
    <?php if ($term !== ''): ?><input type="hidden" name="q" value="<?= e($term) ?>"><?php endif; ?>
    <label>Sort by
      <select name="sort" class="js-autosubmit">
        <option value="new"  <?= $sort === 'new'  ? 'selected' : '' ?>>Newest</option>
        <option value="low"  <?= $sort === 'low'  ? 'selected' : '' ?>>Price: low to high</option>
        <option value="high" <?= $sort === 'high' ? 'selected' : '' ?>>Price: high to low</option>
        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
      </select>
    </label>
    <noscript><button class="btn btn-dark" type="submit">Apply</button></noscript>
  </form>
</div>

<?php if ($items): ?>
  <div class="grid"><?php foreach ($items as $p) { echo product_card($p); } ?></div>
  <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Pages">
      <?php if ($page > 1): ?><a href="<?= e(shop_link(['page' => $page - 1])) ?>">&larr; Prev</a><?php endif; ?>
      <span>Page <?= $page ?> of <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= e(shop_link(['page' => $page + 1])) ?>">Next &rarr;</a><?php endif; ?>
    </nav>
  <?php endif; ?>
<?php else: ?>
  <div class="empty"><p>No products found.</p><a class="btn btn-dark" href="<?= e(url('shop.php')) ?>">Browse all products</a></div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
