<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/admin_layout.php';
admin_start('Sales records', 'sales');

function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

$to   = (string) ($_GET['to'] ?? date('Y-m-d'));
$from = (string) ($_GET['from'] ?? date('Y-m-d', strtotime('-29 days')));
if (!valid_date($to))   $to = date('Y-m-d');
if (!valid_date($from)) $from = date('Y-m-d', strtotime('-29 days'));
if ($from > $to) { [$from, $to] = [$to, $from]; }
$start = $from . ' 00:00:00';
$end   = $to . ' 23:59:59';

// Cancelled orders are not counted as sales.
$rows = q_all("SELECT order_no, cust_name, phone, city, total, payment_method, payment_status, status, created_at
               FROM orders WHERE status <> 'cancelled' AND created_at BETWEEN ? AND ? ORDER BY id DESC", [$start, $end]);

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sales-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Order', 'Date', 'Customer', 'Phone', 'City', 'Total', 'Payment method', 'Payment status', 'Order status']);
    foreach ($rows as $r) {
        // Guard against spreadsheet formula injection from customer-typed text.
        $safe = fn($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : $v;
        fputcsv($out, [$r['order_no'], $r['created_at'], $safe($r['cust_name']), $safe($r['phone']), $safe($r['city']),
            $r['total'], payment_method_label($r['payment_method']), $r['payment_status'], $r['status']]);
    }
    fclose($out);
    exit;
}

$revenue = array_sum(array_column($rows, 'total'));
$paid = 0.0; $pending = 0.0;
foreach ($rows as $r) {
    if ($r['payment_status'] === 'paid') $paid += (float) $r['total']; else $pending += (float) $r['total'];
}
$daily = q_all("SELECT SUBSTR(created_at, 1, 10) AS d, COUNT(*) AS n, SUM(total) AS t
                FROM orders WHERE status <> 'cancelled' AND created_at BETWEEN ? AND ?
                GROUP BY SUBSTR(created_at, 1, 10) ORDER BY d DESC", [$start, $end]);
$top = q_all("SELECT oi.product_name, SUM(oi.qty) AS qty, SUM(oi.line_total) AS amt
              FROM order_items oi JOIN orders o ON o.id = oi.order_id
              WHERE o.status <> 'cancelled' AND o.created_at BETWEEN ? AND ?
              GROUP BY oi.product_name ORDER BY amt DESC LIMIT 10", [$start, $end]);

admin_head();
?>
<form method="get" class="toolbar">
  <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
  <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
  <button class="btn btn-dark" type="submit">Show</button>
  <a class="btn btn-ghost" href="<?= e(url('admin/sales.php?' . http_build_query(['from' => $from, 'to' => $to, 'export' => 'csv']))) ?>">Download CSV</a>
</form>

<div class="stat-grid">
  <div class="stat"><small>Orders</small><strong><?= count($rows) ?></strong></div>
  <div class="stat"><small>Total sales</small><strong><?= e(money($revenue)) ?></strong></div>
  <div class="stat"><small>Paid</small><strong><?= e(money($paid)) ?></strong></div>
  <div class="stat"><small>Payment pending</small><strong><?= e(money($pending)) ?></strong></div>
</div>

<div class="detail-grid">
  <section>
    <h2>Day by day</h2>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Date</th><th class="num">Orders</th><th class="num">Sales</th></tr></thead>
      <tbody>
      <?php foreach ($daily as $d): ?>
        <tr><td><?= e(date('d M Y', strtotime($d['d']))) ?></td><td class="num"><?= (int) $d['n'] ?></td><td class="num"><?= e(money($d['t'])) ?></td></tr>
      <?php endforeach; if (!$daily): ?><tr><td colspan="3" class="muted">No sales in this period.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>
  <section>
    <h2>Top products</h2>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Product</th><th class="num">Qty</th><th class="num">Sales</th></tr></thead>
      <tbody>
      <?php foreach ($top as $t): ?>
        <tr><td><?= e($t['product_name']) ?></td><td class="num"><?= (int) $t['qty'] ?></td><td class="num"><?= e(money($t['amt'])) ?></td></tr>
      <?php endforeach; if (!$top): ?><tr><td colspan="3" class="muted">Nothing sold yet.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </section>
</div>

<h2 style="margin-top:24px">All sales in this period</h2>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th class="num">Total</th><th>Payment</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e($r['order_no']) ?></td><td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td><td><?= e($r['cust_name']) ?></td>
      <td class="num"><?= e(money($r['total'])) ?></td><td><span class="badge pay-<?= e($r['payment_status']) ?>"><?= e(ucfirst($r['payment_status'])) ?></span></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="5" class="muted">No sales in this period.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php admin_foot(); ?>
