<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';

require_role(['Kitchen Staff', 'Administrator']);

// Handle Availability Toggle Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_availability') {
    verify_csrf_token();
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);

    if ($item_id) {
        $find_stmt = $pdo->prepare("SELECT item_id, name, is_available FROM food_items WHERE item_id = :id");
        $find_stmt->execute(['id' => $item_id]);
        $item = $find_stmt->fetch();

        if ($item) {
            $new_status = $item['is_available'] ? 0 : 1;
            $update_stmt = $pdo->prepare("UPDATE food_items SET is_available = :status WHERE item_id = :id");
            $update_stmt->execute(['status' => $new_status, 'id' => $item_id]);

            $status_label = $new_status ? 'Available' : 'Out of Stock';
            log_action($pdo, $_SESSION['user_id'] ?? null, 'FOOD_ITEM_STATUS_TOGGLE', "Toggled availability for '{$item['name']}' (ID: $item_id) to $status_label");
            set_flash('success', "'{$item['name']}' is now marked as {$status_label}.");
        } else {
            set_flash('error', "Food item not found.");
        }
    }

    // Preserve filter parameters on redirect
    $redirect_params = [];
    foreach (['q', 'category', 'status', 'sort', 'page'] as $param) {
        if (!empty($_GET[$param])) {
            $redirect_params[$param] = $_GET[$param];
        }
    }
    $query_str = !empty($redirect_params) ? '?' . http_build_query($redirect_params) : '';
    redirect('kitchen/dashboard.php' . $query_str);
}

// 1. Fetch Kitchen Overview Statistics
$total_items_count = (int)$pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
$available_items_count = (int)$pdo->query("SELECT COUNT(*) FROM food_items WHERE is_available = 1")->fetchColumn();
$unavailable_items_count = (int)$pdo->query("SELECT COUNT(*) FROM food_items WHERE is_available = 0")->fetchColumn();
$total_categories_count = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
if ($total_categories_count === 0) {
    $total_categories_count = (int)$pdo->query("SELECT COUNT(DISTINCT category) FROM food_items")->fetchColumn();
}

// 2. Fetch All Food Categories for Filter Dropdown
$all_categories = $pdo->query("SELECT category_name FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($all_categories)) {
    $all_categories = $pdo->query("SELECT DISTINCT category FROM food_items ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
}

// 3. Process Search, Filter, Sort & Pagination Parameters
$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) && is_string($_GET['category']) ? trim($_GET['category']) : '';
$status = isset($_GET['status']) && in_array($_GET['status'], ['available', 'unavailable'], true) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) && is_string($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page !== false && $page !== null) ? max(1, $page) : 1;
$per_page = 8;

$sort_options = [
    'name_asc' => 'fi.name ASC',
    'name_desc' => 'fi.name DESC',
    'price_asc' => 'fi.price ASC, fi.name ASC',
    'price_desc' => 'fi.price DESC, fi.name ASC',
    'status_desc' => 'fi.is_available DESC, fi.name ASC',
];
$sort_sql = $sort_options[$sort] ?? 'fi.name ASC';

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(fi.name LIKE :s_name OR fi.description LIKE :s_desc OR fi.category LIKE :s_cat)';
    $term = '%' . $search . '%';
    $params['s_name'] = $term;
    $params['s_desc'] = $term;
    $params['s_cat'] = $term;
}

if ($category !== '' && in_array($category, $all_categories, true)) {
    $conditions[] = 'fi.category = :category';
    $params['category'] = $category;
} elseif ($category !== '') {
    $category = '';
}

if ($status === 'available') {
    $conditions[] = 'fi.is_available = 1';
} elseif ($status === 'unavailable') {
    $conditions[] = 'fi.is_available = 0';
}

$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Count Total Filtered Dishes
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM food_items fi $where_clause");
$count_stmt->execute($params);
$filtered_total = (int)$count_stmt->fetchColumn();

$total_pages = max(1, (int)ceil($filtered_total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Fetch Paginated Dishes
$items_stmt = $pdo->prepare("
    SELECT fi.item_id, fi.name, fi.description, fi.category, fi.price, fi.image_path, fi.is_available
    FROM food_items fi
    $where_clause
    ORDER BY $sort_sql
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) {
    $items_stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
}
$items_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$items_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$items_stmt->execute();
$items = $items_stmt->fetchAll();

// Pagination URL Builder Helper
$pagination_url = static function (int $target_page) use ($search, $category, $status, $sort): string {
    $query = ['page' => $target_page, 'sort' => $sort];
    if ($search !== '') {
        $query['q'] = $search;
    }
    if ($category !== '') {
        $query['category'] = $category;
    }
    if ($status !== 'all') {
        $query['status'] = $status;
    }
    return url('kitchen/dashboard.php') . '?' . http_build_query($query);
};

$page_title = 'Kitchen Staff Dashboard - Public Menu Management';
include '../includes/header.php';
?>

<!-- Kitchen Welcome Hero Section -->
<section class="role-dashboard kitchen-dashboard">
    <div class="role-dashboard-copy">
        <span class="role-dashboard-eyebrow">KITCHEN WORKSPACE &amp; INVENTORY CONTROL</span>
        <h1>Welcome to the<br><span>Kitchen, <?= e($_SESSION['full_name'] ?? 'Staff') ?></span></h1>
        <p>Monitor available dishes, manage live customer menu availability, and track food categories in real time.</p>
        <div class="role-dashboard-actions">
            <a class="role-dashboard-primary" href="<?= e(url('menu.php')) ?>" target="_blank" rel="noopener">
                Preview Customer Menu <span aria-hidden="true">↗</span>
            </a>
            <a class="role-dashboard-secondary" href="<?= e(url('auth/profile.php')) ?>">Manage Profile</a>
        </div>
    </div>
    <div class="role-dashboard-visual">
        <img src="<?= e(url('public/images/kitchen-team.png')) ?>" alt="Kitchen staff team at work">
    </div>
</section>

<!-- Kitchen Live Overview Statistics -->
<section class="kitchen-stats-grid" aria-label="Kitchen statistics summary">
    <div class="kitchen-stat-card">
        <div class="kitchen-stat-icon total" aria-hidden="true">🍽️</div>
        <div class="kitchen-stat-details">
            <span class="kitchen-stat-value"><?= $total_items_count ?></span>
            <span class="kitchen-stat-label">Total Dishes</span>
        </div>
    </div>
    <div class="kitchen-stat-card">
        <div class="kitchen-stat-icon available" aria-hidden="true">✅</div>
        <div class="kitchen-stat-details">
            <span class="kitchen-stat-value"><?= $available_items_count ?></span>
            <span class="kitchen-stat-label">Available on Menu</span>
        </div>
    </div>
    <div class="kitchen-stat-card">
        <div class="kitchen-stat-icon unavailable" aria-hidden="true">⏸️</div>
        <div class="kitchen-stat-details">
            <span class="kitchen-stat-value"><?= $unavailable_items_count ?></span>
            <span class="kitchen-stat-label">Out of Stock</span>
        </div>
    </div>
    <a href="../admin/categories.php" class="kitchen-stat-card" style="text-decoration: none; color: inherit;" title="Manage Food Categories">
        <div class="kitchen-stat-icon categories" aria-hidden="true">📑</div>
        <div class="kitchen-stat-details">
            <span class="kitchen-stat-value"><?= $total_categories_count ?></span>
            <span class="kitchen-stat-label">Food Categories ↗</span>
        </div>
    </a>
</section>

<!-- Kitchen Menu & Food Items Management Panel -->
<section class="kitchen-panel" aria-label="Food Items Management">
    <div class="kitchen-panel-header">
        <div class="kitchen-panel-title">
            <h2>Food Items &amp; Public Menu Catalogue</h2>
            <p>Review dishes, verify pricing and descriptions, and toggle item availability for customer ordering.</p>
        </div>
        <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
            <a class="kitchen-public-link" href="../admin/categories.php" style="background: #ea580c; border-color: #ea580c;">
                <span aria-hidden="true">📑</span>
                <span>Manage Categories</span>
            </a>
            <a class="kitchen-public-link" href="<?= e(url('menu.php')) ?>" target="_blank" rel="noopener">
                <span>Customer View </span>
                <span aria-hidden="true">↗</span>
            </a>
        </div>
    </div>

    <!-- Search, Category, Status, & Sorting Controls -->
    <form class="kitchen-filter-form" method="get" action="<?= e(url('kitchen/dashboard.php')) ?>">
        <div class="kitchen-filter-group">
            <label for="filter-search">Search Dishes</label>
            <input type="search" id="filter-search" name="q" value="<?= e($search) ?>" placeholder="Search name, category, description...">
        </div>

        <div class="kitchen-filter-group">
            <label for="filter-category">Category</label>
            <select id="filter-category" name="category">
                <option value="">All Categories</option>
                <?php foreach ($all_categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                        <?= e($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="kitchen-filter-group">
            <label for="filter-status">Status</label>
            <select id="filter-status" name="status">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Dishes</option>
                <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available Only</option>
                <option value="unavailable" <?= $status === 'unavailable' ? 'selected' : '' ?>>Out of Stock Only</option>
            </select>
        </div>

        <div class="kitchen-filter-group">
            <label for="filter-sort">Sort By</label>
            <select id="filter-sort" name="sort">
                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name: Z to A</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="status_desc" <?= $sort === 'status_desc' ? 'selected' : '' ?>>Available First</option>
            </select>
        </div>

        <button type="submit" class="btn-kitchen-filter">Filter</button>

        <?php if ($search !== '' || $category !== '' || $status !== 'all' || $sort !== 'name_asc'): ?>
            <a href="<?= e(url('kitchen/dashboard.php')) ?>" class="btn-kitchen-reset">Reset</a>
        <?php endif; ?>
    </form>

    <div class="menu-results" aria-live="polite">
        <p>Showing <strong><?= count($items) ?></strong> of <strong><?= $filtered_total ?></strong> dishes (Total in system: <?= $total_items_count ?>)</p>
        <?php if ($search !== '' || $category !== '' || $status !== 'all'): ?>
            <a href="<?= e(url('kitchen/dashboard.php')) ?>">Clear active filters</a>
        <?php endif; ?>
    </div>

    <!-- Food Items Table -->
    <?php if (!empty($items)): ?>
        <div class="kitchen-table-wrapper">
            <table class="kitchen-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Image</th>
                        <th>Dish Details</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Menu Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <img class="kitchen-dish-thumb" src="<?= e(url($item['image_path'])) ?>" alt="<?= e($item['name']) ?>" loading="lazy" onerror="this.onerror=null; this.src='<?= e(url('public/images/home-food.png')) ?>';">
                            </td>
                            <td>
                                <div class="kitchen-dish-info">
                                    <h3><?= e($item['name']) ?></h3>
                                    <p><?= e($item['description']) ?></p>
                                </div>
                            </td>
                            <td>
                                <span class="menu-category"><?= e($item['category']) ?></span>
                            </td>
                            <td>
                                <span class="kitchen-price">LKR <?= number_format((float)$item['price'], 2) ?></span>
                            </td>
                            <td>
                                <?php if ($item['is_available']): ?>
                                    <span class="badge-status available">Available</span>
                                <?php else: ?>
                                    <span class="badge-status unavailable">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <form method="post" action="<?= e(url('kitchen/dashboard.php?' . http_build_query($_GET))) ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_availability">
                                    <input type="hidden" name="item_id" value="<?= (int)$item['item_id'] ?>">
                                    <?php if ($item['is_available']): ?>
                                        <button type="submit" class="btn-toggle-out" title="Mark this dish as Out of Stock (hides from public menu)">
                                            Mark Out of Stock
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn-toggle-in" title="Mark this dish as Available (shows on public menu)">
                                            Mark Available
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
            <nav class="menu-pagination" aria-label="Kitchen food pages">
                <?php if ($page > 1): ?>
                    <a href="<?= e($pagination_url($page - 1)) ?>" aria-label="Previous page">← Previous</a>
                <?php endif; ?>

                <span>Page <?= $page ?> of <?= $total_pages ?></span>

                <?php if ($page < $total_pages): ?>
                    <a href="<?= e($pagination_url($page + 1)) ?>" aria-label="Next page">Next →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="menu-empty">
            <span aria-hidden="true">🍳</span>
            <h2>No food items found</h2>
            <p>No dishes match the specified filter or search query.</p>
            <a href="<?= e(url('kitchen/dashboard.php')) ?>">Reset filters</a>
        </div>
    <?php endif; ?>
</section>

<?php include '../includes/footer.php'; ?>
