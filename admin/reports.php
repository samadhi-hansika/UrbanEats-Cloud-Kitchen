<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
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

if (isset($_GET['start_date']) || isset($_GET['from']) || isset($_GET['end_date']) || isset($_GET['to'])) {
    $raw_start = (string)($_GET['start_date'] ?? ($_GET['from'] ?? ''));
    $raw_end = (string)($_GET['end_date'] ?? ($_GET['to'] ?? ''));

    $requested_start = $parse_date($raw_start);
    $requested_end = $parse_date($raw_end);

    if (($raw_start !== '' && $requested_start === null) || ($raw_end !== '' && $requested_end === null)) {
        $filter_error = 'Please choose valid start and end dates.';
    } elseif ($requested_start !== null && $requested_end !== null && $requested_start > $requested_end) {
        $filter_error = 'The start date must be on or before the end date.';
    } else {
        if ($requested_start !== null) {
            $start_date = $requested_start->format('Y-m-d');
        }
        if ($requested_end !== null) {
            $end_date = $requested_end->format('Y-m-d');
        }
    }
}

$summary = ['order_count' => 0, 'revenue' => 0, 'average_order' => 0, 'completed' => 0, 'cancelled' => 0];
$daily_sales = [];
$by_status = [];
$top_dishes = [];
$recent_orders = [];

if ($filter_error === '') {
    $range_start = $start_date . ' 00:00:00';
    $range_end = (new DateTimeImmutable($end_date))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
    $params = [$range_start, $range_end];

    $summary_statement = $pdo->prepare(
        'SELECT COUNT(*) AS order_count,
                COALESCE(SUM(total_amount), 0) AS revenue,
                COALESCE(AVG(total_amount), 0) AS average_order,
                SUM(status = "Completed") AS completed,
                SUM(status = "Cancelled") AS cancelled
         FROM orders
         WHERE created_at >= ? AND created_at < ?'
    );
    $summary_statement->execute($params);
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
    $daily_statement->execute($params);
    $daily_sales = $daily_statement->fetchAll();

    $status_statement = $pdo->prepare(
        'SELECT status, COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS amount
         FROM orders
         WHERE created_at >= ? AND created_at < ?
         GROUP BY status
         ORDER BY order_count DESC'
    );
    $status_statement->execute($params);
    $by_status = $status_statement->fetchAll();

    $dishes_statement = $pdo->prepare(
        'SELECT oi.item_name,
                SUM(oi.quantity) AS quantity_sold,
                SUM(oi.line_total) AS revenue
         FROM order_items oi
         JOIN orders o ON o.order_id = oi.order_id
         WHERE o.created_at >= ? AND o.created_at < ? AND o.status <> "Cancelled"
         GROUP BY oi.item_id, oi.item_name
         ORDER BY quantity_sold DESC, revenue DESC
         LIMIT 10'
    );
    $dishes_statement->execute($params);
    $top_dishes = $dishes_statement->fetchAll();

    $recent_statement = $pdo->prepare(
        'SELECT order_number, status, fulfillment_type, total_amount, created_at
         FROM orders
         WHERE created_at >= ? AND created_at < ?
         ORDER BY created_at DESC
         LIMIT 10'
    );
    $recent_statement->execute($params);
    $recent_orders = $recent_statement->fetchAll();
}

$page_title = 'Sales Reports';
include '../includes/header.php';
?>

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
            <span class="admin-account-name"><?= e($_SESSION['full_name'] ?? 'Administrator') ?></span>
            <a class="admin-logout" href="../auth/logout.php" aria-label="Sign out"><span aria-hidden="true">↗</span> Logout</a>
        </div>
    </header>

    <main class="admin-sales-main">
        <section class="admin-sales-heading">
            <div>
                <span class="admin-sales-eyebrow">PERFORMANCE OVERVIEW</span>
                <h1>Sales Reports</h1>
                <p>Track order revenue, daily performance, status distribution, and top dishes.</p>
            </div>
            <form class="admin-sales-filter" method="get" action="reports.php">
                <label>
                    <span>From</span>
                    <input type="date" name="start_date" value="<?= e($start_date) ?>" required>
                </label>
                <label>
                    <span>To</span>
                    <input type="date" name="end_date" value="<?= e($end_date) ?>" required>
                </label>
                <button type="submit" class="btn-primary">Apply dates</button>
            </form>
        </section>

        <?php if ($filter_error !== ''): ?>
            <p class="admin-sales-error" role="alert"><?= e($filter_error) ?></p>
        <?php else: ?>
            <section class="admin-sales-stats" aria-label="Sales summary">
                <article class="admin-sales-stat">
                    <span class="admin-sales-stat-icon" aria-hidden="true">↗</span>
                    <span class="admin-sales-stat-label">Order revenue</span>
                    <strong><?= money((float)$summary['revenue']) ?></strong>
                    <small><%= e($start_date) ?> – <?= e($end_date) ?></small>
                </article>
                <article class="admin-sales-stat">
                    <span class="admin-sales-stat-icon" aria-hidden="true">▤</span>
                    <span class="admin-sales-stat-label">Orders</span>
                    <strong><?= number_format((int)$summary['order_count']) ?></strong>
                    <small>Total orders in period</small>
                </article>
                <article class="admin-sales-stat">
                    <span class="admin-sales-stat-icon" aria-hidden="true">◉</span>
                    <span class="admin-sales-stat-label">Average order</span>
                    <strong><?= money((float)$summary['average_order']) ?></strong>
                    <small>Average order value</small>
                </article>
                <article class="admin-sales-stat">
                    <span class="admin-sales-stat-icon" aria-hidden="true">✓</span>
                    <span class="admin-sales-stat-label">Completed / Cancelled</span>
                    <strong><?= (int)$summary['completed'] ?> / <?= (int)$summary['cancelled'] ?></strong>
                    <small>Order outcomes</small>
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
                                            <td><?= e(date('M j, Y', strtotime($day['sales_date']))) ?></td>
                                            <td><?= number_format((int)$day['order_count']) ?></td>
                                            <td><?= money((float)$day['revenue']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-sales-panel">
                    <div class="admin-sales-panel-heading">
                        <div><h2>Orders by status</h2><p>Breakdown of orders by fulfillment/status</p></div>
                    </div>
                    <div class="admin-sales-table-wrap">
                        <table class="admin-sales-table">
                            <thead><tr><th>Status</th><th>Orders</th><th>Amount</th></tr></thead>
                            <tbody>
                                <?php if (!$by_status): ?>
                                    <tr><td colspan="3" class="admin-sales-empty">No data found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($by_status as $row): ?>
                                        <tr>
                                            <td><?= e($row['status']) ?></td>
                                            <td><?= (int)$row['order_count'] ?></td>
                                            <td><?= money((float)$row['amount']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="admin-sales-grid">
                <section class="admin-sales-panel">
                    <div class="admin-sales-panel-heading">
                        <div><h2>Top-selling dishes</h2><p>Top dishes by quantity sold</p></div>
                    </div>
                    <div class="admin-sales-table-wrap">
                        <table class="admin-sales-table">
                            <thead><tr><th>Dish</th><th>Qty</th><th>Sales</th></tr></thead>
                            <tbody>
                                <?php if (!$top_dishes): ?>
                                    <tr><td colspan="3" class="admin-sales-empty">No dish sales found for these dates.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($top_dishes as $dish): ?>
                                        <tr>
                                            <td><?= e($dish['item_name']) ?></td>
                                            <td><?= number_format((int)$dish['quantity_sold']) ?></td>
                                            <td><?= money((float)$dish['revenue']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-sales-panel">
                    <div class="admin-sales-panel-heading">
                        <div><h2>Recent orders</h2><p>Latest orders in the selected period</p></div>
                    </div>
                    <div class="admin-sales-table-wrap">
                        <table class="admin-sales-table">
                            <thead><tr><th>Order</th><th>Date</th><th>Type</th><th>Status</th><th>Total</th></tr></thead>
                            <tbody>
                                <?php if (!$recent_orders): ?>
                                    <tr><td colspan="5" class="admin-sales-empty">No orders found for these dates.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td><?= e($order['order_number']) ?></td>
                                            <td><?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?></td>
                                            <td><?= e(ucfirst($order['fulfillment_type'])) ?></td>
                                            <td><span class="admin-sales-status"><?= e($order['status']) ?></span></td>
                                            <td><?= money((float)$order['total_amount']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
<?php include '../includes/footer.php'; ?>