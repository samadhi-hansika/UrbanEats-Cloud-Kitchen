<?php
/**
 * Shared Component C order and cart helpers.
 */

require_once __DIR__ . '/functions.php';

function cart_items(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $cart = $_SESSION['cart'] ?? [];
    return is_array($cart) ? $cart : [];
}

function cart_count(): int
{
    return array_sum(array_map('intval', cart_items()));
}

function cart_add(int $item_id, int $quantity = 1): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $cart = cart_items();
    $cart[$item_id] = max(1, (int)($cart[$item_id] ?? 0) + $quantity);
    $_SESSION['cart'] = $cart;
}

function cart_set(int $item_id, int $quantity): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $cart = cart_items();
    if ($quantity > 0) {
        $cart[$item_id] = min($quantity, 99);
    } else {
        unset($cart[$item_id]);
    }
    $_SESSION['cart'] = $cart;
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

function order_status_transitions(): array
{
    return [
        'Placed' => ['Preparing', 'Cancelled'],
        'Preparing' => ['Ready', 'Cancelled'],
        'Ready' => ['Out for Delivery', 'Collected', 'Cancelled'],
        'Out for Delivery' => ['Completed'],
        'Collected' => ['Completed'],
        'Completed' => [],
        'Cancelled' => [],
    ];
}

function money(float $amount): string
{
    return 'LKR ' . number_format($amount, 2);
}

function load_cart_from_database(PDO $pdo): array
{
    $cart = cart_items();
    if (!$cart) {
        return [];
    }

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare(
        "SELECT item_id, name, price, image_path, is_available, stock_quantity
         FROM food_items WHERE item_id IN ($placeholders)"
    );
    $statement->execute($ids);
    $items = [];
    foreach ($statement->fetchAll() as $item) {
        $item['quantity'] = min((int)$cart[(int)$item['item_id']], 99);
        $item['line_total'] = (float)$item['price'] * $item['quantity'];
        $items[(int)$item['item_id']] = $item;
    }
    return $items;
}

function validate_coupon(PDO $pdo, string $code, float $subtotal): array
{
    if ($code === '') {
        return ['coupon' => null, 'discount' => 0.0, 'error' => null];
    }
    $statement = $pdo->prepare(
        'SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND expires_at >= NOW() FOR UPDATE'
    );
    $statement->execute([strtoupper($code)]);
    $coupon = $statement->fetch();
    if (!$coupon) {
        return ['coupon' => null, 'discount' => 0.0, 'error' => 'The coupon code is invalid or has expired.'];
    }
    if ($coupon['usage_limit'] !== null && (int)$coupon['times_used'] >= (int)$coupon['usage_limit']) {
        return ['coupon' => null, 'discount' => 0.0, 'error' => 'This coupon has reached its usage limit.'];
    }
    if ($subtotal < (float)$coupon['minimum_order_value']) {
        return ['coupon' => null, 'discount' => 0.0, 'error' => 'Your order does not meet this coupon minimum.'];
    }
    $discount = $coupon['discount_type'] === 'percentage'
        ? $subtotal * ((float)$coupon['discount_value'] / 100)
        : (float)$coupon['discount_value'];
    return ['coupon' => $coupon, 'discount' => min($subtotal, $discount), 'error' => null];
}

function create_order(PDO $pdo, int $user_id, array $details): int
{
    $pdo->beginTransaction();
    try {
        $cart = cart_items();
        if (!$cart) {
            throw new RuntimeException('Your cart is empty.');
        }

        $items = load_cart_from_database($pdo);
        if (count($items) !== count($cart)) {
            throw new RuntimeException('One or more selected dishes are no longer available.');
        }

        $subtotal = 0.0;
        foreach ($items as $item) {
            $quantity = (int)$item['quantity'];
            if (!(int)$item['is_available'] || (int)$item['stock_quantity'] < $quantity) {
                throw new RuntimeException("Insufficient stock for {$item['name']}.");
            }
            $subtotal += (float)$item['price'] * $quantity;
        }

        $coupon_result = validate_coupon($pdo, strtoupper(trim((string)($details['coupon_code'] ?? ''))), $subtotal);
        if ($coupon_result['error']) {
            throw new RuntimeException($coupon_result['error']);
        }
        $discount = $coupon_result['discount'];
        $total = max(0, $subtotal - $discount);
        $type = $details['fulfillment_type'] === 'pickup' ? 'pickup' : 'delivery';
        $address = $type === 'delivery' ? trim((string)$details['address']) : null;
        if ($type === 'delivery' && $address === '') {
            throw new RuntimeException('A delivery address is required.');
        }

        $order_number = 'UE' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
        $statement = $pdo->prepare(
            'INSERT INTO orders (user_id, order_number, fulfillment_type, address, phone, subtotal, discount_amount, total_amount, coupon_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $user_id, $order_number, $type, $address, trim((string)$details['phone']),
            $subtotal, $discount, $total, $coupon_result['coupon']['coupon_id'] ?? null
        ]);
        $order_id = (int)$pdo->lastInsertId();

        $item_statement = $pdo->prepare(
            'INSERT INTO order_items (order_id, item_id, item_name, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stock_statement = $pdo->prepare(
            'UPDATE food_items SET stock_quantity = stock_quantity - ? WHERE item_id = ? AND is_available = 1 AND stock_quantity >= ?'
        );
        foreach ($items as $item) {
            $quantity = (int)$item['quantity'];
            $stock_statement->execute([$quantity, $item['item_id'], $quantity]);
            if ($stock_statement->rowCount() !== 1) {
                throw new RuntimeException("Stock changed while ordering {$item['name']}. Please try again.");
            }
            $item_statement->execute([
                $order_id, $item['item_id'], $item['name'], $item['price'], $quantity, $item['line_total']
            ]);
        }

        $payment_statement = $pdo->prepare(
            "INSERT INTO payments (order_id, payment_method, amount, payment_status, paid_at) VALUES (?, ?, ?, 'Recorded', NOW())"
        );
        $payment_statement->execute([$order_id, $details['payment_method'], $total]);
        $history_statement = $pdo->prepare(
            'INSERT INTO order_status_history (order_id, status, changed_by, note) VALUES (?, ?, ?, ?)'
        );
        $history_statement->execute([$order_id, 'Placed', $user_id, 'Order placed by customer.']);
        if ($coupon_result['coupon']) {
            $pdo->prepare('UPDATE coupons SET times_used = times_used + 1 WHERE coupon_id = ?')
                ->execute([$coupon_result['coupon']['coupon_id']]);
        }
        $pdo->commit();
        cart_clear();
        log_action($pdo, $user_id, 'ORDER_CREATED', "Created order {$order_number}.");
        return $order_id;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
