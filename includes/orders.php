<?php
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
    $cart[$item_id] = min(99, max(1, (int)($cart[$item_id] ?? 0) + $quantity));
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

function load_cart_from_database(PDO $pdo): array
{
    $cart = cart_items();
    if (!$cart) {
        return [];
    }

    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare(
        "SELECT item_id, name, price, image_path, is_available, stock_quantity
         FROM food_items
         WHERE item_id IN ($placeholders)"
    );
    $statement->execute($ids);

    $items = [];
    foreach ($statement->fetchAll() as $item) {
        $item_id = (int)$item['item_id'];
        if (!(int)$item['is_available']) {
            continue;
        }
        $item['quantity'] = min((int)$cart[$item_id], 99);
        $item['line_total'] = (float)$item['price'] * $item['quantity'];
        $items[$item_id] = $item;
    }

    return $items;
}

function money(float $amount): string
{
    return 'LKR ' . number_format($amount, 2);
}

function create_order(PDO $pdo, int $user_id, array $details): int
{
    $pdo->beginTransaction();

    try {
        $cart = cart_items();
        if (!$cart) {
            throw new RuntimeException('Your cart is empty.');
        }

        $item_ids = array_map('intval', array_keys($cart));
        $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
        $items_statement = $pdo->prepare(
            "SELECT item_id, name, price, is_available, stock_quantity
             FROM food_items
             WHERE item_id IN ($placeholders)
             FOR UPDATE"
        );
        $items_statement->execute($item_ids);
        $items = $items_statement->fetchAll();

        if (count($items) !== count($cart)) {
            throw new RuntimeException('One or more dishes in your cart are no longer available.');
        }

        $subtotal = 0.0;
        foreach ($items as &$item) {
            $item_id = (int)$item['item_id'];
            $quantity = filter_var($cart[$item_id] ?? null, FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity < 1 || $quantity > 99) {
                throw new RuntimeException('A cart quantity is invalid. Please update your cart and try again.');
            }
            if (!(int)$item['is_available']) {
                throw new RuntimeException($item['name'] . ' is no longer available.');
            }
            if ((int)$item['stock_quantity'] < $quantity) {
                throw new RuntimeException(
                    $item['name'] . ' has only ' . (int)$item['stock_quantity'] . ' available. Please update your cart.'
                );
            }

            $item['quantity'] = $quantity;
            $item['line_total'] = (float)$item['price'] * $quantity;
            $subtotal += $item['line_total'];
        }
        unset($item);

        $fulfillment_type = (string)($details['fulfillment_type'] ?? '');
        if (!in_array($fulfillment_type, ['delivery', 'pickup'], true)) {
            throw new RuntimeException('Choose delivery or pickup.');
        }

        $address = trim((string)($details['address'] ?? ''));
        if ($fulfillment_type === 'delivery' && $address === '') {
            throw new RuntimeException('Enter a delivery address.');
        }
        if (strlen($address) > 500) {
            throw new RuntimeException('The delivery address must be 500 characters or fewer.');
        }

        $phone = trim((string)($details['phone'] ?? ''));
        if (!is_valid_phone($phone)) {
            throw new RuntimeException('Enter a valid contact number, such as 0771234567 or +94771234567.');
        }

        $payment_method = (string)($details['payment_method'] ?? '');
        if (!in_array($payment_method, ['cash_on_delivery', 'card', 'bank_transfer'], true)) {
            throw new RuntimeException('Choose a valid payment method.');
        }

        $order_number = 'UE' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
        $order_statement = $pdo->prepare(
            'INSERT INTO orders
                (user_id, order_number, fulfillment_type, address, phone, subtotal, discount_amount, total_amount)
             VALUES (?, ?, ?, ?, ?, ?, 0, ?)'
        );
        $order_statement->execute([
            $user_id,
            $order_number,
            $fulfillment_type,
            $fulfillment_type === 'delivery' ? $address : null,
            $phone,
            $subtotal,
            $subtotal,
        ]);
        $order_id = (int)$pdo->lastInsertId();

        $order_item_statement = $pdo->prepare(
            'INSERT INTO order_items (order_id, item_id, item_name, unit_price, quantity, line_total)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stock_statement = $pdo->prepare(
            'UPDATE food_items
             SET stock_quantity = stock_quantity - ?
             WHERE item_id = ? AND is_available = 1 AND stock_quantity >= ?'
        );
        $movement_statement = $pdo->prepare(
            'INSERT INTO inventory_movements
                (item_id, item_name, changed_by, quantity_change, quantity_after, note)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        foreach ($items as $item) {
            $item_id = (int)$item['item_id'];
            $quantity = (int)$item['quantity'];
            $stock_statement->execute([$quantity, $item_id, $quantity]);
            if ($stock_statement->rowCount() !== 1) {
                throw new RuntimeException(
                    'Stock changed while placing your order for ' . $item['name'] . '. Please try again.'
                );
            }

            $order_item_statement->execute([
                $order_id,
                $item_id,
                $item['name'],
                $item['price'],
                $quantity,
                $item['line_total'],
            ]);
            $movement_statement->execute([
                $item_id,
                $item['name'],
                $user_id,
                -$quantity,
                (int)$item['stock_quantity'] - $quantity,
                'Stock reserved for order ' . $order_number,
            ]);
        }

        $payment_statement = $pdo->prepare(
            "INSERT INTO payments (order_id, payment_method, amount, payment_status, paid_at)
             VALUES (?, ?, ?, 'Pending', NULL)"
        );
        $payment_statement->execute([$order_id, $payment_method, $subtotal]);

        $history_statement = $pdo->prepare(
            'INSERT INTO order_status_history (order_id, status, changed_by, note)
             VALUES (?, ?, ?, ?)'
        );
        $history_statement->execute([$order_id, 'Placed', $user_id, 'Order placed by customer.']);

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
