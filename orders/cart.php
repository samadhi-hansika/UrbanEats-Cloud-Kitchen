<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/orders.php';

require_role('Customer');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = (string)($_POST['action'] ?? '');
    $item_id = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
    if ($item_id) {
        if ($action === 'add') {
            cart_add($item_id, max(1, (int)($_POST['quantity'] ?? 1)));
            set_flash('success', 'Dish added to your cart.');
        } elseif ($action === 'update') {
            cart_set($item_id, (int)($_POST['quantity'] ?? 0));
        } elseif ($action === 'remove') {
            cart_set($item_id, 0);
        }
    }
    redirect('orders/cart.php');
}

$items = load_cart_from_database($pdo);
$subtotal = array_sum(array_column($items, 'line_total'));
$page_title = 'Your Cart';
include '../includes/header.php';
?>
<section class="order-page">
    <div class="order-page-heading"><span class="menu-eyebrow">YOUR ORDER</span><h1>Shopping <span>cart</span></h1><p>Review your dishes before checkout.</p></div>
    <?php if (!$items): ?>
        <div class="card order-empty"><h2>Your cart is empty</h2><p>Choose something fresh from our menu to get started.</p><a class="btn-primary" href="<?= e(url('menu.php')) ?>">Browse menu</a></div>
    <?php else: ?>
        <div class="order-layout">
            <div class="card">
                <?php foreach ($items as $item): ?>
                    <article class="cart-row">
                        <img src="<?= e(url($item['image_path'])) ?>" alt="<?= e($item['name']) ?>">
                        <div><h2><?= e($item['name']) ?></h2><p><?= money((float)$item['price']) ?> each · <?= (int)$item['stock_quantity'] ?> in stock</p></div>
                        <form method="post" class="cart-quantity"><?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="item_id" value="<?= (int)$item['item_id'] ?>"><label><span class="sr-only">Quantity for <?= e($item['name']) ?></span><input type="number" name="quantity" min="0" max="99" value="<?= (int)$item['quantity'] ?>"></label><button type="submit">Update</button></form>
                        <strong><?= money((float)$item['line_total']) ?></strong>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="item_id" value="<?= (int)$item['item_id'] ?>"><button type="submit" class="link-button">Remove</button></form>
                    </article>
                <?php endforeach; ?>
            </div>
            <aside class="card order-summary"><h2>Order summary</h2><p><span>Subtotal</span><strong><?= money($subtotal) ?></strong></p><a class="btn-primary" href="<?= e(url('orders/checkout.php')) ?>">Continue to checkout</a></aside>
        </div>
    <?php endif; ?>
</section>
<?php include '../includes/footer.php'; ?>
