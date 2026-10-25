<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/orders.php';
require_role('Customer');
$statement = $pdo->prepare('SELECT order_id, order_number, fulfillment_type, total_amount, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$statement->execute([$_SESSION['user_id']]);
$orders = $statement->fetchAll();
$page_title = 'Order History';
include '../includes/header.php';
?>
<section class="order-page"><div class="order-page-heading"><span class="menu-eyebrow">YOUR ORDERS</span><h1>Order <span>history</span></h1><p>Track every meal from UrbanEats.</p></div><div class="card"><div class="order-table-wrap"><table class="data-table"><thead><tr><th>Order</th><th>Date</th><th>Type</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($orders as $order): ?><tr><td><?= e($order['order_number']) ?></td><td><?= e(date('d M Y, H:i', strtotime($order['created_at']))) ?></td><td><?= e(ucfirst($order['fulfillment_type'])) ?></td><td><?= money((float)$order['total_amount']) ?></td><td><span class="order-status"><?= e($order['status']) ?></span></td><td><a href="<?= e(url('orders/tracking.php?id=' . $order['order_id'])) ?>">View tracking</a></td></tr><?php endforeach; ?><?php if (!$orders): ?><tr><td colspan="6">You have not placed an order yet.</td></tr><?php endif; ?></tbody></table></div></div></section>
<?php include '../includes/footer.php'; ?>
