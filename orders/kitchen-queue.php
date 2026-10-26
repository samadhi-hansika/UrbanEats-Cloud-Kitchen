<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/orders.php';
require_role(['Kitchen Staff', 'Administrator']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $order_id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
    $next_status = (string)($_POST['status'] ?? '');
    if ($order_id && isset(order_status_transitions()[(string)($_POST['current_status'] ?? '')])
        && in_array($next_status, order_status_transitions()[(string)$_POST['current_status']], true)) {
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare('UPDATE orders SET status = ? WHERE order_id = ? AND status = ?');
            $statement->execute([$next_status, $order_id, $_POST['current_status']]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('The order status changed. Refresh and try again.');
            }
            $pdo->prepare('INSERT INTO order_status_history (order_id, status, changed_by, note) VALUES (?, ?, ?, ?)')
                ->execute([$order_id, $next_status, $_SESSION['user_id'], 'Updated by kitchen staff.']);
            $pdo->commit();
            log_action($pdo, $_SESSION['user_id'], 'ORDER_STATUS_UPDATED', "Order {$order_id} moved to {$next_status}.");
            set_flash('success', 'Order status updated.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            set_flash('error', $exception->getMessage());
        }
    } else {
        set_flash('error', 'That order status transition is not allowed.');
    }
    redirect('orders/kitchen-queue.php');
}
$orders = $pdo->query("SELECT o.order_id, o.order_number, o.fulfillment_type, o.total_amount, o.status, o.created_at, u.full_name FROM orders o JOIN users u ON u.user_id = o.user_id WHERE o.status NOT IN ('Completed', 'Cancelled') ORDER BY o.created_at ASC")->fetchAll();
$page_title = 'Kitchen Order Queue';
include '../includes/header.php';
?>
<section class="order-page"><div class="order-page-heading"><span class="menu-eyebrow">KITCHEN OPERATIONS</span><h1>Order <span>queue</span></h1><p>Move orders through the permitted workflow stages.</p></div><div class="card"><div class="order-table-wrap"><table class="data-table"><thead><tr><th>Order</th><th>Customer</th><th>Type</th><th>Placed</th><th>Status</th><th>Next action</th></tr></thead><tbody><?php foreach ($orders as $order): $next = order_status_transitions()[$order['status']] ?? []; ?><tr><td><?= e($order['order_number']) ?></td><td><?= e($order['full_name']) ?></td><td><?= e(ucfirst($order['fulfillment_type'])) ?></td><td><?= e(date('d M H:i', strtotime($order['created_at']))) ?></td><td><span class="order-status"><?= e($order['status']) ?></span></td><td><?php foreach ($next as $status): ?><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$order['order_id'] ?>"><input type="hidden" name="current_status" value="<?= e($order['status']) ?>"><input type="hidden" name="status" value="<?= e($status) ?>"><button type="submit"><?= e($status) ?></button></form><?php endforeach; ?></td></tr><?php endforeach; ?><?php if (!$orders): ?><tr><td colspan="6">No active orders in the queue.</td></tr><?php endif; ?></tbody></table></div></div></section>
<?php include '../includes/footer.php'; ?>
