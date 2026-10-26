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

    if ($item_id && $item_id > 0) {
        if ($action === 'add') {
            $quantity = max(1, (int)($_POST['quantity'] ?? 1));
            $item_statement = $pdo->prepare(
                'SELECT item_id FROM food_items WHERE item_id = ? AND is_available = 1'
            );
            $item_statement->execute([$item_id]);
            if ($item_statement->fetchColumn()) {
                cart_add($item_id, $quantity);
                set_flash('success', 'Dish added to your cart.');
            } else {
                set_flash('error', 'This dish is no longer available.');
            }
        } elseif ($action === 'update') {
            $quantity = filter_var($_POST['quantity'] ?? 0, FILTER_VALIDATE_INT);
            cart_set($item_id, $quantity === false ? 0 : $quantity);
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
<section class="order-history order-cart-page">
    <div class="order-history-heading">
        <span class="order-history-eyebrow">YOUR ORDER, YOUR WAY</span>
        <h1>Your <span>cart</span></h1>
        <p>Review your dishes before you continue shopping.</p>
    </div>

    <?php if (!$items): ?>
        <div class="order-history-empty cart-empty-state">
            <span class="cart-empty-icon" aria-hidden="true">🛒</span>
            <h2>Your cart is empty</h2>
            <p>Choose something fresh from our menu to get started.</p>
            <a class="order-history-browse" href="<?= e(url('menu.php')) ?>">Browse menu</a>
        </div>
    <?php else: ?>
        <?php $cart_item_count = function_exists('cart_count') ? cart_count() : count($items); ?>
        <div class="cart-layout">
            <div class="cart-items-panel">
                <div class="cart-panel-heading">
                    <div>
                        <span class="cart-panel-eyebrow">READY WHEN YOU ARE</span>
                        <h2>Your selected dishes</h2>
                    </div>
                    <span class="cart-item-count"><?= $cart_item_count ?> <?= $cart_item_count === 1 ? 'item' : 'items' ?></span>
                </div>
                <?php foreach ($items as $item): ?>
                    <article class="cart-row">
                        <img src="<?= e(url($item['image_path'])) ?>" alt="<?= e($item['name']) ?>">
                        <div class="cart-item-info">
                            <h2><?= e($item['name']) ?></h2>
<p class="cart-unit-price"><?= money((float)$item['price']) ?> <span>each</span></p>
                            <?php if ((int)$item['stock_quantity'] < (int)$item['quantity']): ?>
                                <p class="cart-stock-warning">
                                    Only <?= (int)$item['stock_quantity'] ?> available. Update the quantity or remove this dish.
                                </p>
                            <?php endif; ?>
                            <form method="post" class="cart-remove-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="item_id" value="<?= (int)$item['item_id'] ?>">
                                <button type="submit" class="link-button">
                                    <span aria-hidden="true">×</span> Remove
                                </button>
                            </form>
                        </div>
                        <form method="post" class="cart-quantity">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="item_id" value="<?= (int)$item['item_id'] ?>">
<label class="cart-quantity-control">
                                <span class="sr-only">Quantity for <?= e($item['name']) ?></span>
                                <input type="number" name="quantity" min="0" max="99" value="<?= (int)$item['quantity'] ?>" aria-label="Quantity for <?= e($item['name']) ?>">
                            </label>
                            <button class="cart-update-button" type="submit">Update</button>
                        </form>
                        <strong class="cart-line-total"><?= money((float)$item['line_total']) ?></strong>
                    </article>
                <?php endforeach; ?>
                <a class="cart-continue-link" href="<?= e(url('menu.php')) ?>">
                    <span aria-hidden="true">←</span> Continue shopping
                </a>
            </aside>
        </div>
    <?php endif; ?>
</section>
git add .
git commit -m "Resolve empty merge conflict"
git push
<?php include '../includes/footer.php'; ?>
