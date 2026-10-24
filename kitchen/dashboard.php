<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';

require_role(['Kitchen Staff', 'Administrator']);

function upload_food_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed. Please select the image again.');
    }

    if (!isset($file['tmp_name'], $file['size']) || (int)$file['size'] < 1 || (int)$file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Food images must be no larger than 5 MB.');
    }

    $file_info = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $file_info->file($file['tmp_name']);
    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed_types[$mime_type]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Upload a valid JPG, PNG, or WebP image.');
    }

    $upload_directory = __DIR__ . '/../public/images/menu/uploads';
    if (!is_dir($upload_directory) && !@mkdir($upload_directory, 0755, true) && !is_dir($upload_directory)) {
        throw new RuntimeException('The image upload directory is unavailable. Please contact an administrator.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed_types[$mime_type];
    if (!move_uploaded_file($file['tmp_name'], $upload_directory . '/' . $filename)) {
        throw new RuntimeException('The image could not be saved. Please try again.');
    }

    return 'public/images/menu/uploads/' . $filename;
}

function remove_uploaded_food_image(string $image_path): bool
{
    if (!preg_match('#^public/images/menu/uploads/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $image_path)) {
        return true;
    }

    $file_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $image_path);
    return !is_file($file_path) || unlink($file_path);
}

function remove_food_image_if_unused(PDO $pdo, string $image_path, ?int $except_item_id = null): bool
{
    if (!preg_match('#^public/images/menu/uploads/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $image_path)) {
        return true;
    }

    $statement = $pdo->prepare('SELECT COUNT(*) FROM food_items WHERE image_path = ? AND item_id != ?');
    $statement->execute([$image_path, $except_item_id ?? 0]);
    if ((int)$statement->fetchColumn() > 0) {
        return true;
    }

    return remove_uploaded_food_image($image_path);
}

$all_categories = $pdo->query("SELECT category_name FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$existing_item_categories = $pdo->query("SELECT DISTINCT category FROM food_items ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
$all_categories = array_values(array_unique(array_merge($all_categories, $existing_item_categories)));
sort($all_categories, SORT_NATURAL | SORT_FLAG_CASE);

$redirect_to_dashboard = static function (): void {
    $redirect_params = [];
    foreach (['q', 'category', 'status', 'sort', 'page'] as $param) {
        if (isset($_GET[$param]) && is_string($_GET[$param]) && $_GET[$param] !== '') {
            $redirect_params[$param] = $_GET[$param];
        }
    }
    $query_string = $redirect_params ? '?' . http_build_query($redirect_params) : '';
    redirect('kitchen/dashboard.php' . $query_string);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

    if ($action === 'toggle_availability') {
        $item_id = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
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
                set_flash('error', 'Food item not found.');
            }
        } else {
            set_flash('error', 'Invalid food item.');
        }
        $redirect_to_dashboard();
    }

    if ($action === 'add' || $action === 'edit') {
        $item_id = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
        $existing_item = null;
        if ($action === 'edit' && $item_id) {
            $find_stmt = $pdo->prepare('SELECT item_id, name, description, category, price, image_path, is_available FROM food_items WHERE item_id = ?');
            $find_stmt->execute([$item_id]);
            $existing_item = $find_stmt->fetch();
        }

        $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
        $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';
        $category = is_string($_POST['category'] ?? null) ? trim($_POST['category']) : '';
        $price = is_string($_POST['price'] ?? null) ? trim($_POST['price']) : '';
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        if ($action === 'edit' && (!$item_id || !$existing_item)) {
            set_flash('error', 'The food item could not be found.');
            $redirect_to_dashboard();
        }

        if ($name === '') {
            set_flash('error', 'Food item name is required.');
        } elseif (mb_strlen($name) > 120) {
            set_flash('error', 'Food item name cannot exceed 120 characters.');
        } elseif (mb_strlen($description) > 2000) {
            set_flash('error', 'Description cannot exceed 2,000 characters.');
        } elseif ($category === '' || mb_strlen($category) > 80 || !in_array($category, $all_categories, true)) {
            set_flash('error', 'Select a valid food category.');
        } elseif (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $price) || (float)$price <= 0) {
            set_flash('error', 'Enter a valid price greater than zero with up to two decimal places.');
        } else {
            $new_image_path = null;
            $save_succeeded = false;
            try {
                $new_image_path = upload_food_image($_FILES['image'] ?? []);
                $image_path = $new_image_path ?? ($existing_item['image_path'] ?? 'public/images/home-food.png');

                if ($action === 'add') {
                    $save_stmt = $pdo->prepare('INSERT INTO food_items (name, description, category, price, image_path, is_available) VALUES (?, ?, ?, ?, ?, ?)');
                    $save_stmt->execute([$name, $description, $category, $price, $image_path, $is_available]);
                    $saved_item_id = (int)$pdo->lastInsertId();
                    log_action($pdo, $_SESSION['user_id'] ?? null, 'FOOD_ITEM_CREATED', "Created food item '{$name}' (ID: {$saved_item_id})");
                    set_flash('success', "'{$name}' was added to the menu.");
                } else {
                    $save_stmt = $pdo->prepare('UPDATE food_items SET name = ?, description = ?, category = ?, price = ?, image_path = ?, is_available = ? WHERE item_id = ?');
                    $save_stmt->execute([$name, $description, $category, $price, $image_path, $is_available, $item_id]);
                    log_action($pdo, $_SESSION['user_id'] ?? null, 'FOOD_ITEM_UPDATED', "Updated food item '{$name}' (ID: {$item_id})");
                    set_flash('success', "'{$name}' was updated.");
                }

                $save_succeeded = true;
            } catch (RuntimeException $exception) {
                if ($new_image_path !== null && !remove_uploaded_food_image($new_image_path)) {
                    error_log('[Food Item Image Cleanup Failure] Could not remove ' . $new_image_path);
                }
                set_flash('error', $exception->getMessage());
            } catch (PDOException $exception) {
                if ($new_image_path !== null && !remove_uploaded_food_image($new_image_path)) {
                    error_log('[Food Item Image Cleanup Failure] Could not remove ' . $new_image_path);
                }
                error_log('[Food Item Save Failure] ' . $exception->getMessage());
                set_flash('error', 'The food item could not be saved. Please try again.');
            }

            if ($save_succeeded) {
                if ($new_image_path !== null && $existing_item && $existing_item['image_path'] !== $new_image_path) {
                    try {
                        if (!remove_food_image_if_unused($pdo, $existing_item['image_path'], (int)$item_id)) {
                            error_log('[Food Item Image Cleanup Failure] Could not remove ' . $existing_item['image_path']);
                            set_flash('info', 'The food item was saved, but its previous uploaded image could not be removed.');
                        }
                    } catch (PDOException $exception) {
                        error_log('[Food Item Image Cleanup Failure] ' . $exception->getMessage());
                        set_flash('info', 'The food item was saved, but its previous uploaded image could not be removed.');
                    }
                }
                $redirect_to_dashboard();
            }
        }
    } elseif ($action === 'delete') {
        $item_id = filter_var($_POST['item_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$item_id) {
            set_flash('error', 'Invalid food item specified for deletion.');
        } else {
            $find_stmt = $pdo->prepare('SELECT name, image_path FROM food_items WHERE item_id = ?');
            $find_stmt->execute([$item_id]);
            $item = $find_stmt->fetch();

            if (!$item) {
                set_flash('error', 'Food item not found.');
            } else {
                $delete_stmt = $pdo->prepare('DELETE FROM food_items WHERE item_id = ?');
                $delete_stmt->execute([$item_id]);
                log_action($pdo, $_SESSION['user_id'] ?? null, 'FOOD_ITEM_DELETED', "Deleted food item '{$item['name']}' (ID: {$item_id})");
                set_flash('success', "'{$item['name']}' was deleted.");
                try {
                    if (!remove_food_image_if_unused($pdo, $item['image_path'])) {
                        error_log('[Food Item Image Cleanup Failure] Could not remove ' . $item['image_path']);
                        set_flash('info', 'The food item was deleted, but its uploaded image could not be removed.');
                    }
                } catch (PDOException $exception) {
                    error_log('[Food Item Image Cleanup Failure] ' . $exception->getMessage());
                    set_flash('info', 'The food item was deleted, but its uploaded image could not be removed.');
                }
            }
        }
        $redirect_to_dashboard();
    }
}

// 1. Fetch Kitchen Overview Statistics
$total_items_count = (int)$pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
$available_items_count = (int)$pdo->query("SELECT COUNT(*) FROM food_items WHERE is_available = 1")->fetchColumn();
$unavailable_items_count = (int)$pdo->query("SELECT COUNT(*) FROM food_items WHERE is_available = 0")->fetchColumn();
$total_categories_count = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
if ($total_categories_count === 0) {
    $total_categories_count = (int)$pdo->query("SELECT COUNT(DISTINCT category) FROM food_items")->fetchColumn();
}

// 2. Load the selected item for the add/edit form
$edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$editing_item = null;
if ($edit_id) {
    $edit_stmt = $pdo->prepare('SELECT item_id, name, description, category, price, image_path, is_available FROM food_items WHERE item_id = ?');
    $edit_stmt->execute([$edit_id]);
    $editing_item = $edit_stmt->fetch();
    if (!$editing_item) {
        set_flash('error', 'Food item not found.');
        $redirect_to_dashboard();
    }
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

$form_values = $editing_item ?? [
    'name' => '',
    'description' => '',
    'category' => '',
    'price' => '',
    'is_available' => 1,
];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add', 'edit'], true)) {
    foreach (['name', 'description', 'category', 'price'] as $field) {
        $form_values[$field] = is_string($_POST[$field] ?? null) ? $_POST[$field] : '';
    }
    $form_values['is_available'] = isset($_POST['is_available']) ? 1 : 0;
}
$form_action = $editing_item ? 'edit' : 'add';
$form_query = $_GET;
if ($editing_item) {
    $form_query['edit'] = (int)$editing_item['item_id'];
} else {
    unset($form_query['edit']);
}
$form_action_url = url('kitchen/dashboard.php') . ($form_query ? '?' . http_build_query($form_query) : '');

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
            <p>Add and update dishes, prices, images, and availability for customer ordering.</p>
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

    <form class="food-item-form" method="post" action="<?= e($form_action_url) ?>" enctype="multipart/form-data">
        <div class="food-item-form-heading">
            <div>
                <h3><?= $form_action === 'edit' ? 'Edit Food Item' : 'Add New Food Item' ?></h3>
                <p><?= $form_action === 'edit' ? 'Update the selected item details.' : 'Create a dish for the customer menu.' ?></p>
            </div>
            <?php if ($form_action === 'edit'): ?>
                <a class="btn-kitchen-reset" href="<?= e(url('kitchen/dashboard.php')) ?>">Cancel edit</a>
            <?php endif; ?>
        </div>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= e($form_action) ?>">
        <?php if ($form_action === 'edit'): ?>
            <input type="hidden" name="item_id" value="<?= (int)$editing_item['item_id'] ?>">
        <?php endif; ?>

        <div class="food-item-fields">
            <div class="food-item-field">
                <label for="food-name">Food name</label>
                <input id="food-name" name="name" type="text" maxlength="120" required value="<?= e((string)$form_values['name']) ?>">
            </div>
            <div class="food-item-field">
                <label for="food-category">Category</label>
                <select id="food-category" name="category" required>
                    <option value="">Select a category</option>
                    <?php foreach ($all_categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $form_values['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="food-item-field">
                <label for="food-price">Price (LKR)</label>
                <input id="food-price" name="price" type="number" min="0.01" max="99999999.99" step="0.01" required value="<?= e((string)$form_values['price']) ?>">
            </div>
            <div class="food-item-field">
                <label for="food-image">Food image</label>
                <input id="food-image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <small>JPG, PNG, or WebP; maximum 5 MB. Leave empty to <?= $form_action === 'edit' ? 'keep the current image' : 'use the default image' ?>.</small>
                <?php if ($form_action === 'edit'): ?>
                    <img class="food-item-current-image" src="<?= e(url($editing_item['image_path'])) ?>" alt="Current image for <?= e($editing_item['name']) ?>">
                <?php endif; ?>
            </div>
            <div class="food-item-field food-item-description">
                <label for="food-description">Description</label>
                <textarea id="food-description" name="description" rows="3" maxlength="2000"><?= e((string)$form_values['description']) ?></textarea>
            </div>
            <label class="food-item-availability">
                <input type="checkbox" name="is_available" value="1" <?= $form_values['is_available'] ? 'checked' : '' ?>>
                Available for customer orders
            </label>
        </div>
        <div class="food-item-form-actions">
            <button type="submit" class="btn-kitchen-filter"><?= $form_action === 'edit' ? 'Save Food Item' : 'Add Food Item' ?></button>
        </div>
    </form>

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
                        <th style="text-align: right;">Actions</th>
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
                                <div class="kitchen-item-actions">
                                    <?php $edit_query = $_GET; $edit_query['edit'] = (int)$item['item_id']; ?>
                                    <a class="btn-item-edit" href="<?= e(url('kitchen/dashboard.php') . '?' . http_build_query($edit_query)) ?>">Edit</a>
                                    <form method="post" action="<?= e(url('kitchen/dashboard.php?' . http_build_query($_GET))) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_id" value="<?= (int)$item['item_id'] ?>">
                                        <button type="submit" class="btn-item-delete" onclick="return confirm('Delete this food item from the menu?')">Delete</button>
                                    </form>
                                    <form method="post" action="<?= e(url('kitchen/dashboard.php?' . http_build_query($_GET))) ?>">
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
                                </div>
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
