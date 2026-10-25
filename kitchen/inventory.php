<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';

require_role(['Kitchen Staff', 'Administrator']);

try {
    $pdo->query('SELECT stock_quantity, low_stock_threshold FROM food_items LIMIT 0');
    $pdo->query('SELECT movement_id FROM inventory_movements LIMIT 0');
} catch (PDOException $exception) {
    error_log('[Inventory Schema Not Ready] ' . $exception->getMessage());
    http_response_code(503);
    $page_title = 'Inventory Setup Required';
    include '../includes/header.php';
    ?>
    <section class="inventory-page">
        <div class="inventory-panel" role="alert">
            <h1>Inventory setup required</h1>
            <p>The database is missing the stock tracking fields or movement history table.</p>
            <p>In phpMyAdmin, select the <strong>urbaneats_db</strong> database and run <code>database/inventory_migration.sql</code> once. Then reload this page.</p>
            <p>If you already ran the migration, confirm it was applied to the same database configured in <code>config/db.php</code>.</p>
        </div>
    </section>
    <?php
    include '../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $item_id = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
    $stock_value = is_string($_POST['stock_quantity'] ?? null) ? $_POST['stock_quantity'] : '';
    $threshold_value = is_string($_POST['low_stock_threshold'] ?? null) ? $_POST['low_stock_threshold'] : '';
    $note = is_string($_POST['note'] ?? null) ? trim($_POST['note']) : '';
    $stock_quantity = filter_var($stock_value, FILTER_VALIDATE_INT);
    $low_stock_threshold = filter_var($threshold_value, FILTER_VALIDATE_INT);

    if (!$item_id || $stock_quantity === false || $stock_quantity < 0 || $stock_quantity > 2147483647) {
        set_flash('error', 'Enter a valid non-negative whole number for current stock.');
    } elseif ($low_stock_threshold === false || $low_stock_threshold < 0 || $low_stock_threshold > 2147483647) {
        set_flash('error', 'Enter a valid non-negative whole number for the low-stock threshold.');
    } elseif (mb_strlen($note) > 255) {
        set_flash('error', 'The movement note cannot exceed 255 characters.');
    } else {
        try {
            $pdo->beginTransaction();
            $find_stmt = $pdo->prepare('SELECT name, stock_quantity, low_stock_threshold FROM food_items WHERE item_id = ? FOR UPDATE');
            $find_stmt->execute([$item_id]);
            $item = $find_stmt->fetch();

            if (!$item) {
                $pdo->rollBack();
                set_flash('error', 'Food item not found.');
            } else {
                $previous_quantity = (int)$item['stock_quantity'];
                $quantity_change = $stock_quantity - $previous_quantity;
                $update_stmt = $pdo->prepare('UPDATE food_items SET stock_quantity = ?, low_stock_threshold = ? WHERE item_id = ?');
                $update_stmt->execute([$stock_quantity, $low_stock_threshold, $item_id]);

                if ($quantity_change !== 0) {
                    $movement_stmt = $pdo->prepare(
                        'INSERT INTO inventory_movements (item_id, item_name, changed_by, quantity_change, quantity_after, note)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $movement_stmt->execute([
                        $item_id,
                        $item['name'],
                        $_SESSION['user_id'] ?? null,
                        $quantity_change,
                        $stock_quantity,
                        $note !== '' ? $note : null,
                    ]);
                }

                $pdo->commit();
                log_action(
                    $pdo,
                    $_SESSION['user_id'] ?? null,
                    'INVENTORY_UPDATED',
                    "Updated stock for '{$item['name']}' (ID: {$item_id}) from {$previous_quantity} to {$stock_quantity}"
                );
                set_flash('success', "Inventory for '{$item['name']}' was updated.");
            }
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[Inventory Update Failure] ' . $exception->getMessage());
            set_flash('error', 'Inventory could not be updated. Please try again.');
        }
    }

    $redirect_filter = is_string($_GET['status'] ?? null) ? $_GET['status'] : 'all';
    $redirect_query = in_array($redirect_filter, ['all', 'low', 'out'], true) && $redirect_filter !== 'all'
        ? '?' . http_build_query(['status' => $redirect_filter])
        : '';
    redirect('kitchen/inventory.php' . $redirect_query);
}

$status = is_string($_GET['status'] ?? null) && in_array($_GET['status'], ['all', 'low', 'out'], true)
    ? $_GET['status']
    : 'all';
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$conditions = [];
$params = [];

if ($status === 'low') {
    $conditions[] = 'stock_quantity > 0 AND stock_quantity <= low_stock_threshold';
} elseif ($status === 'out') {
    $conditions[] = 'stock_quantity = 0';
}
if ($search !== '') {
    $conditions[] = 'name LIKE ?';
    $params[] = '%' . $search . '%';
}

$where_clause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$items_stmt = $pdo->prepare(
    "SELECT item_id, name, category, stock_quantity, low_stock_threshold
     FROM food_items $where_clause
     ORDER BY stock_quantity ASC, name ASC"
);
$items_stmt->execute($params);
$items = $items_stmt->fetchAll();

$summary = $pdo->query(
    'SELECT COUNT(*) AS item_count,
            COALESCE(SUM(stock_quantity), 0) AS unit_count,
            SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) AS low_count,
            SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) AS out_count
     FROM food_items'
)->fetch();

$movements_stmt = $pdo->query(
    "SELECT im.item_name, im.quantity_change, im.quantity_after, im.note, im.created_at,
            COALESCE(u.full_name, 'Unknown user') AS changed_by_name
     FROM inventory_movements im
     LEFT JOIN users u ON u.user_id = im.changed_by
     ORDER BY im.movement_id DESC
     LIMIT 50"
);
$movements = $movements_stmt->fetchAll();

$page_title = 'Kitchen Inventory Management';
include '../includes/header.php';
?>

<section class="inventory-page">
    <div class="inventory-heading">
        <div>
            <span class="role-dashboard-eyebrow">KITCHEN WORKSPACE &amp; INVENTORY CONTROL</span>
            <h1>Inventory Management</h1>
            <p>Review current stock, update quantities, and track every recorded stock change.</p>
        </div>
        <a class="kitchen-public-link" href="<?= e(url('kitchen/dashboard.php')) ?>">Food Item Management</a>
    </div>

    <section class="inventory-summary" aria-label="Inventory summary">
        <article class="inventory-summary-card">
            <span>Tracked items</span>
            <strong><?= (int)$summary['item_count'] ?></strong>
        </article>
        <article class="inventory-summary-card">
            <span>Units in stock</span>
            <strong><?= (int)$summary['unit_count'] ?></strong>
        </article>
        <article class="inventory-summary-card inventory-summary-low">
            <span>Low stock</span>
            <strong><?= (int)$summary['low_count'] ?></strong>
        </article>
        <article class="inventory-summary-card inventory-summary-out">
            <span>Out of stock</span>
            <strong><?= (int)$summary['out_count'] ?></strong>
        </article>
    </section>

    <section class="inventory-panel" aria-label="Current inventory">
        <div class="inventory-panel-heading">
            <div>
                <h2>Current Stock</h2>
                <p>Stock at or below its threshold is marked low; zero stock is marked out.</p>
            </div>
            <nav class="inventory-status-filter" aria-label="Filter inventory by status">
                <a href="<?= e(url('kitchen/inventory.php')) ?>" class="<?= $status === 'all' ? 'active' : '' ?>">All</a>
                <a href="<?= e(url('kitchen/inventory.php?status=low')) ?>" class="<?= $status === 'low' ? 'active' : '' ?>">Low stock</a>
                <a href="<?= e(url('kitchen/inventory.php?status=out')) ?>" class="<?= $status === 'out' ? 'active' : '' ?>">Out of stock</a>
            </nav>
        </div>

        <form class="inventory-search" method="get" action="<?= e(url('kitchen/inventory.php')) ?>">
            <?php if ($status !== 'all'): ?>
                <input type="hidden" name="status" value="<?= e($status) ?>">
            <?php endif; ?>
            <label for="inventory-search">Find a food item</label>
            <input id="inventory-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Search by item name">
            <button class="btn-kitchen-filter" type="submit">Search</button>
        </form>

        <?php if ($items): ?>
            <div class="kitchen-table-wrapper">
                <table class="kitchen-table inventory-table">
                    <colgroup>
                        <col class="inventory-column-item">
                        <col class="inventory-column-current">
                        <col class="inventory-column-threshold">
                        <col class="inventory-column-status">
                        <col class="inventory-column-update">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Food item</th>
                            <th>Current<br>stock</th>
                            <th>Low-stock<br>threshold</th>
                            <th>Status</th>
                            <th>Record stock<br>update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php
                            $quantity = (int)$item['stock_quantity'];
                            $threshold = (int)$item['low_stock_threshold'];
                            $stock_status = $quantity === 0 ? 'out' : ($quantity <= $threshold ? 'low' : 'in-stock');
                            $status_label = $stock_status === 'out' ? 'Out of stock' : ($stock_status === 'low' ? 'Low stock' : 'In stock');
                            ?>
                            <tr>
                                <td data-label="Food item">
                                    <strong><?= e($item['name']) ?></strong>
                                    <small><?= e($item['category']) ?></small>
                                </td>
                                <td data-label="Current stock"><strong><?= $quantity ?></strong></td>
                                <td data-label="Low-stock threshold"><?= $threshold ?></td>
                                <td data-label="Status"><span class="inventory-status-badge <?= e($stock_status) ?>"><?= e($status_label) ?></span></td>
                                <td data-label="Record stock update">
                                    <form class="inventory-update-form" method="post" action="<?= e(url('kitchen/inventory.php') . ($status !== 'all' ? '?' . http_build_query(['status' => $status]) : '')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="item_id" value="<?= (int)$item['item_id'] ?>">
                                        <label>
                                            <span>New quantity</span>
                                            <input type="number" name="stock_quantity" min="0" max="2147483647" step="1" value="<?= $quantity ?>" required>
                                        </label>
                                        <label>
                                            <span>Low-stock alert at</span>
                                            <input type="number" name="low_stock_threshold" min="0" max="2147483647" step="1" value="<?= $threshold ?>" required>
                                        </label>
                                        <label class="inventory-note">
                                            <span>Movement note (optional)</span>
                                            <input type="text" name="note" maxlength="255" placeholder="e.g. weekly restock">
                                        </label>
                                        <button type="submit" class="btn-kitchen-filter">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="menu-empty">
                <span aria-hidden="true">📦</span>
                <h2>No inventory items found</h2>
                <p>Try another search or status filter.</p>
            </div>
        <?php endif; ?>
    </section>

    <section class="inventory-panel" aria-label="Recent stock movements">
        <div class="inventory-panel-heading">
            <div>
                <h2>Recent Stock Movements</h2>
                <p>The latest 50 quantity changes, including changes for deleted food items.</p>
            </div>
        </div>
        <?php if ($movements): ?>
            <div class="kitchen-table-wrapper">
                <table class="kitchen-table">
                    <thead>
                        <tr>
                            <th>Food item</th>
                            <th>Change</th>
                            <th>Quantity after</th>
                            <th>Note</th>
                            <th>Recorded by</th>
                            <th>Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movements as $movement): ?>
                            <?php $change = (int)$movement['quantity_change']; ?>
                            <tr>
                                <td><?= e($movement['item_name']) ?></td>
                                <td class="<?= $change > 0 ? 'inventory-increase' : 'inventory-decrease' ?>"><?= $change > 0 ? '+' : '' ?><?= $change ?></td>
                                <td><?= (int)$movement['quantity_after'] ?></td>
                                <td><?= e($movement['note'] ?: '—') ?></td>
                                <td><?= e($movement['changed_by_name']) ?></td>
                                <td><?= e(date('Y-m-d H:i', strtotime($movement['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="inventory-no-movements">No stock movements have been recorded yet.</p>
        <?php endif; ?>
    </section>
</section>

<?php include '../includes/footer.php'; ?>
