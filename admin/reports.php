<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_role('Administrator');

$from = is_string($_GET['from'] ?? null) ? $_GET['from'] : date('Y-m-01');
$to = is_string($_GET['to'] ?? null) ? $_GET['to'] : date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || $to < $from) $to = date('Y-m-d');
$params = [$from . ' 00:00:00', $to . ' 23:59:59'];
$summary = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) AS sales, COUNT(*) AS orders, SUM(status = 'Completed') AS completed, SUM(status = 'Cancelled') AS cancelled FROM orders WHERE created_at BETWEEN ? AND ?");
$summary->execute($params);
$summary = $summary->fetch();
$by_status = $pdo->prepare('SELECT status, COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS amount FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY status ORDER BY order_count DESC');
$by_status->execute($params);
$top_items = $pdo->prepare("SELECT oi.item_name, SUM(oi.quantity) AS quantity, SUM(oi.line_total) AS sales FROM order_items oi JOIN orders o ON o.order_id = oi.order_id WHERE o.created_at BETWEEN ? AND ? AND o.status <> 'Cancelled' GROUP BY oi.item_id, oi.item_name ORDER BY quantity DESC, sales DESC LIMIT 10");
$top_items->execute($params);
$page_title = 'Sales Reports';
include '../includes/header.php';
?>
<section class="order-page"><div class="order-page-heading"><span class="menu-eyebrow">ADMINISTRATION</span><h1>Sales <span>reports</span></h1><p>Review revenue and order activity for a selected period.</p></div><form class="card report-filter" method="get"><label>From <input type="date" name="from" value="<?= e($from) ?>"></label><label>To <input type="date" name="to" value="<?= e($to) ?>"></label><button class="btn-primary" type="submit">Run report</button></form><div class="report-stat-grid"><div class="card"><span>Total sales</span><strong><?= money((float)$summary['sales']) ?></strong></div><div class="card"><span>Total orders</span><strong><?= (int)$summary['orders'] ?></strong></div><div class="card"><span>Completed</span><strong><?= (int)$summary['completed'] ?></strong></div><div class="card"><span>Cancelled</span><strong><?= (int)$summary['cancelled'] ?></strong></div></div><div class="order-layout"><div class="card"><h2>Orders by status</h2><div class="order-table-wrap"><table class="data-table"><thead><tr><th>Status</th><th>Orders</th><th>Sales</th></tr></thead><tbody><?php foreach ($by_status as $row): ?><tr><td><?= e($row['status']) ?></td><td><?= (int)$row['order_count'] ?></td><td><?= money((float)$row['amount']) ?></td></tr><?php endforeach; ?></tbody></table></div></div><div class="card"><h2>Top-selling dishes</h2><div class="order-table-wrap"><table class="data-table"><thead><tr><th>Dish</th><th>Qty</th><th>Sales</th></tr></thead><tbody><?php foreach ($top_items as $row): ?><tr><td><?= e($row['item_name']) ?></td><td><?= (int)$row['quantity'] ?></td><td><?= money((float)$row['sales']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></section>
<?php include '../includes/footer.php'; ?>
