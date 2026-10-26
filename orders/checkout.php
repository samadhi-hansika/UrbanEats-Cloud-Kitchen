<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/orders.php';

require_role('Customer');

$items = load_cart_from_database($pdo);
if (!$items) {
    set_flash('error', 'Your cart is empty. Add a dish before checking out.');
    redirect('orders/cart.php');
}

$customer_statement = $pdo->prepare('SELECT phone FROM users WHERE user_id = ?');
$customer_statement->execute([$_SESSION['user_id']]);
$customer_phone = (string)$customer_statement->fetchColumn();

$values = [
    'fulfillment_type' => 'delivery',
    'address' => '',
    'phone' => $customer_phone,
    'payment_method' => 'cash_on_delivery',
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    foreach ($values as $key => $default) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            $values[$key] = trim($_POST[$key]);
        }
    }

    try {
        $order_id = create_order($pdo, (int)$_SESSION['user_id'], $values);
        set_flash('success', 'Your order has been placed successfully.');
        redirect('orders/history.php?placed=' . $order_id);
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        error_log(
            '[Order Checkout Error] ' . $exception->getMessage() . PHP_EOL,
            3,
            __DIR__ . '/../logs/error.log'
        );
        $error = 'We could not save your order right now. Please try again.';
    }
}

$subtotal = array_sum(array_column($items, 'line_total'));
$page_title = 'Checkout';
include '../includes/header.php';
?>

<section class="order-history checkout-page">
    <div class="order-history-heading">
        <span class="order-history-eyebrow">ONE LAST STEP</span>
        <h1>Complete your <span>order</span></h1>
        <p>Choose how you would like to receive your freshly prepared meal.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="checkout-layout">
        <form class="checkout-form" method="post" action="<?= e(url('orders/checkout.php')) ?>">
            <?= csrf_field() ?>
            <section class="checkout-section">
                <div class="checkout-section-heading">
                    <span>01</span>
                    <div>
                        <h2>How should we get it to you?</h2>
                        <p>Select delivery or pickup.</p>
                    </div>
                </div>
                <div class="checkout-fulfillment-options">
                    <label class="checkout-option">
                        <input type="radio" name="fulfillment_type" value="delivery" <?= $values['fulfillment_type'] === 'delivery' ? 'checked' : '' ?>>
                        <span><strong>Delivery</strong><small>Bring it to your door</small></span>
                    </label>
                    <label class="checkout-option">
                        <input type="radio" name="fulfillment_type" value="pickup" <?= $values['fulfillment_type'] === 'pickup' ? 'checked' : '' ?>>
                        <span><strong>Pickup</strong><small>Collect from our kitchen</small></span>
                    </label>
                </div>
                <div class="checkout-field">
                    <label for="checkout-address">Delivery address</label>
                    <textarea id="checkout-address" name="address" rows="3" maxlength="500" placeholder="House number, street, city"><?= e($values['address']) ?></textarea>
                    <small>Required for delivery; leave blank for pickup.</small>
                </div>
                <div class="checkout-field">
                    <label for="checkout-phone">Contact phone</label>
                    <input id="checkout-phone" name="phone" type="tel" autocomplete="tel" value="<?= e($values['phone']) ?>" placeholder="0771234567" required>
                </div>
            </section>

            <section class="checkout-section">
                <div class="checkout-section-heading">
                    <span>02</span>
                    <div>
                        <h2>Payment method</h2>
                        <p>Online payments are recorded as pending.</p>
                    </div>
                </div>
                <div class="checkout-field">
                    <label for="checkout-payment">Choose payment</label>
                    <select id="checkout-payment" name="payment_method" required>
                        <option value="cash_on_delivery" <?= $values['payment_method'] === 'cash_on_delivery' ? 'selected' : '' ?>>Cash on delivery</option>
                        <option value="card" <?= $values['payment_method'] === 'card' ? 'selected' : '' ?>>Card (payment handled separately)</option>
                        <option value="bank_transfer" <?= $values['payment_method'] === 'bank_transfer' ? 'selected' : '' ?>>Bank transfer (payment handled separately)</option>
                    </select>
                </div>
            </section>

            <button class="checkout-submit" type="submit">
                Place order <span aria-hidden="true">→</span>
            </button>
        </form>

        <aside class="checkout-summary">
            <span class="checkout-summary-eyebrow">MADE FRESH FOR YOU</span>
            <h2>Your order</h2>
            <ul class="checkout-item-list">
                <?php foreach ($items as $item): ?>
                    <li>
                        <span><strong><?= (int)$item['quantity'] ?> ×</strong> <?= e($item['name']) ?></span>
                        <strong><?= money((float)$item['line_total']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="checkout-total">
                <span>Total</span>
                <strong><?= money((float)$subtotal) ?></strong>
            </div>
            <p class="checkout-summary-note">We’ll check dish availability again when you place your order.</p>
            <a href="<?= e(url('orders/cart.php')) ?>">← Back to your cart</a>
        </aside>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
