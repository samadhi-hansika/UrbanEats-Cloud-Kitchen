<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/orders.php';
require_role('Customer');
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$statement = $pdo->prepare('SELECT * FROM orders WHERE order_id = ? AND user_id = ?');
$statement->execute([$order_id, $_SESSION['user_id']]);
$order = $statement->fetch();
if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}
$items = $pdo->prepare('SELECT item_name, unit_price, quantity, line_total FROM order_items WHERE order_id = ? ORDER BY order_item_id');
$items->execute([$order_id]);
$history = $pdo->prepare('SELECT h.*, u.full_name FROM order_status_history h LEFT JOIN users u ON u.user_id = h.changed_by WHERE h.order_id = ? ORDER BY h.created_at ASC');
$history->execute([$order_id]);
$page_title = 'Track ' . $order['order_number'];
include '../includes/header.php';
?>
<section class="order-page"><div class="order-page-heading"><span class="menu-eyebrow">ORDER TRACKING</span><h1><?= e($order['order_number']) ?></h1><p>Current status: <strong class="order-status"><?= e($order['status']) ?></strong></p></div><div class="order-layout"><div class="card"><h2>Status timeline</h2><ol class="status-timeline"><?php foreach ($history as $event): ?><li><strong><?= e($event['status']) ?></strong><span><?= e(date('d M Y, H:i', strtotime($event['created_at']))) ?></span><p><?= e($event['note'] ?? '') ?></p></li><?php endforeach; ?></ol></div><aside class="card order-summary"><h2>Invoice</h2><?php foreach ($items as $item): ?><p><span><?= (int)$item['quantity'] ?> × <?= e($item['item_name']) ?></span><strong><?= money((float)$item['line_total']) ?></strong></p><?php endforeach; ?><hr><p><span>Subtotal</span><strong><?= money((float)$order['subtotal']) ?></strong></p><p><span>Discount</span><strong>- <?= money((float)$order['discount_amount']) ?></strong></p><p><span>Total</span><strong><?= money((float)$order['total_amount']) ?></strong></p><button class="btn-primary" type="button" onclick="window.print()">Print invoice</button></aside></div></section>
<?php include '../includes/footer.php'; ?>
