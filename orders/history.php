<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/orders.php';
require_role('Customer');

$orders_statement = $pdo->prepare(
    'SELECT order_id, order_number, fulfillment_type, address, status, subtotal, discount_amount, total_amount, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC, order_id DESC'
);
$orders_statement->execute([$_SESSION['user_id']]);
$orders = $orders_statement->fetchAll();

$placed_order = null;
$placed_order_id = filter_input(INPUT_GET, 'placed', FILTER_VALIDATE_INT);
if ($placed_order_id !== false && $placed_order_id !== null && $placed_order_id > 0) {
    foreach ($orders as $order) {
        if ((int)$order['order_id'] === $placed_order_id) {
            $placed_order = $order;
            break;
        }
    }
}

$items_by_order = [];
if ($orders) {
    $order_ids = array_column($orders, 'order_id');
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $items_statement = $pdo->prepare(
        "SELECT order_id, item_name, unit_price, quantity, line_total
         FROM order_items
         WHERE order_id IN ($placeholders)
         ORDER BY order_item_id"
    );
    $items_statement->execute($order_ids);
    foreach ($items_statement->fetchAll() as $item) {
        $items_by_order[(int)$item['order_id']][] = $item;
    }
}

$page_title = 'Order History';
include '../includes/header.php';
?>

<section class="order-history">
    <div class="order-history-heading">
        <span class="order-history-eyebrow">YOUR URBANEATS ACCOUNT</span>
        <h1>Order <span>History</span></h1>
        <p>Review your past orders, items, and tracking status.</p>
    </div>

    <?php if ($placed_order): ?>
        <div class="order-confirmation" role="status">
            <span class="order-confirmation-icon" aria-hidden="true">✓</span>
            <div>
                <strong>Your order is confirmed!</strong>
                <p>Order <?= e($placed_order['order_number']) ?> has been successfully placed.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$orders): ?>
        <div class="order-history-empty">
            <span class="order-history-empty-icon" aria-hidden="true">🍽</span>
            <h2>No orders yet</h2>
            <p>Your placed orders will appear here.</p>
            <a class="order-history-browse" href="<?= e(url('menu.php')) ?>">Browse the menu</a>
        </div>
    <?php else: ?>
        <div class="order-history-list">
            <?php foreach ($orders as $order): ?>
                <?php
                $status_class = strtolower(str_replace(' ', '-', (string)$order['status']));
                $order_items = $items_by_order[(int)$order['order_id']] ?? [];
                ?>
                <article class="order-history-card">
                    <div class="order-history-card-heading">
                        <div>
                            <span class="order-history-label">Order</span>
                            <h2><?= e($order['order_number']) ?></h2>
                        </div>
                        <span class="order-status order-status-<?= e($status_class) ?>"><?= e($order['status']) ?></span>
                    </div>
                    <p class="order-history-meta">
                        <?= e(date('M j, Y \a\t g:i A', strtotime($order['created_at']))) ?>
                        <span aria-hidden="true">&middot;</span>
                        <?= e(ucfirst($order['fulfillment_type'])) ?>
                    </p>
                    <?php if ($order['fulfillment_type'] === 'delivery' && $order['address'] !== null && $order['address'] !== ''): ?>
                        <p class="order-history-meta">
                            <strong>Delivery address:</strong> <?= e($order['address']) ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($order_items): ?>
                        <ul class="order-history-items">
                            <?php foreach ($order_items as $item): ?>
                                <li>
                                    <span><?= e($item['item_name']) ?> × <?= (int)$item['quantity'] ?></span>
                                    <span><?= money((float)$item['line_total']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="order-history-total">
                        <span>Total</span>
                        <strong><?= money((float)$order['total_amount']) ?></strong>
                    </div>
                    <div class="order-history-actions" style="margin-top: 1rem;">
                        <a href="<?= e(url('orders/tracking.php?id=' . $order['order_id'])) ?>" class="btn-secondary" style="font-size: 0.9rem; padding: 0.4rem 0.8rem; display: inline-block;">View tracking →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php include '../includes/footer.php'; ?>
