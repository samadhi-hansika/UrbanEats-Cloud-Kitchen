<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

require_role('Administrator');

$today = new DateTimeImmutable('today');
$default_start = $today->modify('first day of this month');
$start_date = $default_start->format('Y-m-d');
$end_date = $today->format('Y-m-d');
$filter_error = '';

$parse_date = static function (string $value): ?DateTimeImmutable {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $date_errors = DateTimeImmutable::getLastErrors();
    if ($date === false || ($date_errors !== false && ($date_errors['warning_count'] > 0 || $date_errors['error_count'] > 0))) {
        return null;
    }

    return $date->format('Y-m-d') === $value ? $date : null;
};

if (isset($_GET['start_date']) || isset($_GET['end_date'])) {
    $requested_start = $parse_date((string)($_GET['start_date'] ?? ''));
    $requested_end = $parse_date((string)($_GET['end_date'] ?? ''));

    if ($requested_start === null || $requested_end === null) {
        $filter_error = 'Please choose valid start and end dates.';
    } elseif ($requested_start > $requested_end) {
        $filter_error = 'The start date must be on or before the end date.';
    } else {
        $start_date = $requested_start->format('Y-m-d');
        $end_date = $requested_end->format('Y-m-d');
    }
}

$summary = ['order_count' => 0, 'revenue' => 0, 'average_order' => 0];
$daily_sales = [];
$popular_dishes = [];
$recent_orders = [];

if ($filter_error === '') {
    $range_start = $start_date . ' 00:00:00';
    $range_end = (new DateTimeImmutable($end_date))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';

    $summary_statement = $pdo->prepare(
        'SELECT COUNT(*) AS order_count,
                COALESCE(SUM(total_amount), 0) AS revenue,
                COALESCE(AVG(total_amount), 0) AS average_order
         FROM orders
         WHERE created_at >= ? AND created_at < ?'
    );
    $summary_statement->execute([$range_start, $range_end]);
    $summary = $summary_statement->fetch();

    $daily_statement = $pdo->prepare(
        'SELECT DATE(created_at) AS sales_date,
                COUNT(*) AS order_count,
                COALESCE(SUM(total_amount), 0) AS revenue
         FROM orders
         WHERE created_at >= ? AND created_at < ?
         GROUP BY DATE(created_at)
         ORDER BY sales_date DESC'
    );
    $daily_statement->execute([$range_start, $range_end]);
    $daily_sales = $daily_statement->fetchAll();

    $dishes_statement = $pdo->prepare(
        'SELECT oi.item_name,
                SUM(oi.quantity) AS quantity_sold,
                SUM(oi.line_total) AS revenue
         FROM order_items oi
         JOIN orders o ON o.order_id = oi.order_id
         WHERE o.created_at >= ? AND o.created_at < ?
         GROUP BY oi.item_name
         ORDER BY quantity_sold DESC, revenue DESC
         LIMIT 5'
    );
    $dishes_statement->execute([$range_start, $range_end]);
    $popular_dishes = $dishes_statement->fetchAll();

    $recent_statement = $pdo->prepare(
        'SELECT order_number, status, fulfillment_type, total_amount, created_at
         FROM orders
         WHERE created_at >= ? AND created_at < ?
         ORDER BY created_at DESC
         LIMIT 10'
    );
    $recent_statement->execute([$range_start, $range_end]);
    $recent_orders = $recent_statement->fetchAll();
}

$money = static fn ($amount): string => 'LKR ' . number_format((float)$amount, 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Reports - UrbanEats</title>
    <link rel="icon" type="image/png" href="../public/images/urbaneats-logo.png">
    <link rel="stylesheet" href="../public/style.css">
</head>
<body class="admin-dashboard admin-records-page admin-sales-page">
    <header class="admin-navbar">
        <a class="admin-brand" href="../index.php">
            <img class="admin-brand-icon" src="../public/images/urbaneats-logo.png" alt="">
            <span>Urban<span>Eats</span><small>CLOUD KITCHEN</small></span>
        </a>
        <nav class="admin-nav-links" aria-label="Admin navigation">
            <a href="dashboard.php">Dashboard</a>
            <a href="categories.php">Categories</a>
            <a href="users.php">Users &amp; Roles</a>
            <a href="audit-log.php">Audit Logs</a>
            <a class="active" href="reports.php">Sales Reports</a>
        </nav>
        <div class="admin-account">
            <span class="admin-account-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8') ?></span>
            <a class="admin-logout" href="../auth/logout.php" aria-label="Sign out"><span aria-hidden="true">↗</span> Logout</a>
        </div>
    </header>

    <main class="admin-sales-main">
        <section class="admin-sales-heading">
            <div>
                <span class="admin-sales-eyebrow">PERFORMANCE OVERVIEW</span>
                <h1>Sales Reports</h1>
                <p>Track order revenue, daily performance, and your most popular dishes.</p>
            </div>
            <form class="admin-sales-filter" method="get" action="reports.php">
                <label>
                    <span>From</span>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <label>
                    <span>To</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date, ENT_QUOTES, 'UTF-8') ?>" required>
                </label>
                <button type="submit">Apply dates</button>
            </form>
        </section>

        <?php if ($filter_error !== ''): ?>
            <p class="admin-sales-error" role="alert"><?= htmlspecialchars($filter_error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <section class="admin-sales-stats" aria-label="Sales summary">
                <article class="admin-sales-stat">
                    <span class="admin-sales-stat-icon" aria-hidden="true">↗</span>
                    <span class="admin-sales-stat-label">Order revenue</span>
                    <strong><?= $money($summary['revenue']) ?></strong>
                    <small><?= htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($end_date, ENT_QUOTES, 'UTF-8') ?></small>
                </article>
                <article class="admin-sales-stat">
                    <span class="admin-sales-stat-icon" aria-hidden="true">▤</span>
                    <span class="admin-sales-stat-label">Orders</span>
                    <strong><?= number_format((int)$summary['order_count']) ?></strong>
                    <small>Orders in selected period</small>
                </article>
                <article class="admin-sales-stat">
                    <span class="admin-sales-stat-icon" aria-hidden="true">◉</span>
                    <span class="admin-sales-stat-label">Average order</span>
                    <strong><?= $money($summary['average_order']) ?></strong>
                    <small>Average order value</small>
                </article>
            </section>

            <div class="admin-sales-grid">
                <section class="admin-sales-panel">
                    <div class="admin-sales-panel-heading">
                        <div><h2>Daily sales</h2><p>Revenue and order totals by day</p></div>
                    </div>
                    <div class="admin-sales-table-wrap">
                        <table class="admin-sales-table">
                            <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead>
                            <tbody>
                                <?php if (!$daily_sales): ?>
                                    <tr><td colspan="3" class="admin-sales-empty">No orders found for these dates.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($daily_sales as $day): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('M j, Y', strtotime($day['sales_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= number_format((int)$day['order_count']) ?></td>
                                            <td><?= $money($day['revenue']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-sales-panel">
                    <div class="admin-sales-panel-heading">
                        <div><h2>Popular dishes</h2><p>Top 5 by quantity sold</p></div>
                    </div>
                    <div class="admin-sales-table-wrap">
                        <table class="admin-sales-table">
                            <thead><tr><th>Dish</th><th>Qty</th><th>Revenue</th></tr></thead>
                            <tbody>
                                <?php if (!$popular_dishes): ?>
                                    <tr><td colspan="3" class="admin-sales-empty">No dish sales found for these dates.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($popular_dishes as $dish): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($dish['item_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= number_format((int)$dish['quantity_sold']) ?></td>
                                            <td><?= $money($dish['revenue']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <section class="admin-sales-panel admin-sales-recent">
                <div class="admin-sales-panel-heading">
                    <div><h2>Recent orders</h2><p>Latest orders in the selected period</p></div>
                </div>
                <div class="admin-sales-table-wrap">
                    <table class="admin-sales-table">
                        <thead><tr><th>Order</th><th>Date</th><th>Fulfilment</th><th>Status</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php if (!$recent_orders): ?>
                                <tr><td colspan="5" class="admin-sales-empty">No orders found for these dates.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(ucfirst($order['fulfillment_type']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="admin-sales-status"><?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td><?= $money($order['total_amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
