<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/orders.php';

require_role('Customer');
$items = load_cart_from_database($pdo);
if (!$items) {
    set_flash('error', 'Add at least one dish before checking out.');
    redirect('orders/cart.php');
}

$customer = $pdo->prepare('SELECT phone FROM users WHERE user_id = ?');
$customer->execute([$_SESSION['user_id']]);
$customer_phone = (string)$customer->fetchColumn();
$subtotal = array_sum(array_column($items, 'line_total'));
$error = '';
$old = ['fulfillment_type' => 'delivery', 'address' => '', 'phone' => $customer_phone, 'payment_method' => 'cash_on_delivery', 'coupon_code' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    foreach ($old as $key => $value) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            $old[$key] = trim($_POST[$key]);
        }
    }
    if (!in_array($old['payment_method'], ['cash_on_delivery', 'card', 'bank_transfer'], true)) {
        $error = 'Select a valid payment method.';
    } elseif (!is_valid_phone($old['phone'])) {
        $error = 'Enter a valid phone number.';
    } elseif ($old['fulfillment_type'] === 'delivery' && $old['address'] === '') {
        $error = 'Enter a delivery address.';
    } else {
        try {
            $order_id = create_order($pdo, (int)$_SESSION['user_id'], $old);
            redirect('orders/tracking.php?id=' . $order_id . '&placed=1');
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}
$page_title = 'Checkout';
include '../includes/header.php';
?>
<section class="order-page">
    <div class="order-page-heading"><span class="menu-eyebrow">SECURE CHECKOUT</span><h1>Complete your <span>order</span></h1><p>Prices and stock are checked again when you place the order.</p></div>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="order-layout">
        <form method="post" class="card order-form"><?= csrf_field() ?><h2>Delivery details</h2>
            <div class="checkout-choice"><label><input type="radio" name="fulfillment_type" value="delivery" <?= $old['fulfillment_type'] === 'delivery' ? 'checked' : '' ?>> Delivery</label><label><input type="radio" name="fulfillment_type" value="pickup" <?= $old['fulfillment_type'] === 'pickup' ? 'checked' : '' ?>> Pickup</label></div>
            <div class="form-group"><label for="address">Address (required for delivery)</label><textarea id="address" name="address" rows="3"><?= e($old['address']) ?></textarea></div>
            <div class="form-group"><label for="phone">Contact phone</label><input id="phone" name="phone" value="<?= e($old['phone']) ?>" required></div>
            <div class="form-group"><label for="payment_method">Payment method</label><select id="payment_method" name="payment_method"><option value="cash_on_delivery">Cash on delivery</option><option value="card">Card (simulated)</option><option value="bank_transfer">Bank transfer (simulated)</option></select></div>
            <div class="form-group"><label for="coupon_code">Coupon code (optional)</label><input id="coupon_code" name="coupon_code" value="<?= e($old['coupon_code']) ?>" maxlength="40"></div>
            <button class="btn-primary" type="submit">Place order · <?= money($subtotal) ?> before discount</button>
        </form>
        <aside class="card order-summary"><h2>Items</h2><?php foreach ($items as $item): ?><p><span><?= (int)$item['quantity'] ?> × <?= e($item['name']) ?></span><strong><?= money((float)$item['line_total']) ?></strong></p><?php endforeach; ?><hr><p><span>Subtotal</span><strong><?= money($subtotal) ?></strong></p></aside>
    </div>
</section>
<?php include '../includes/footer.php'; ?>
