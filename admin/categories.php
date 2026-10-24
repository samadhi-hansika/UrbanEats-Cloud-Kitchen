<?php
/**
 * Food Category Management Console
 * UrbanEats Cloud Kitchen Platform
 * 
 * Features:
 * - View Categories with real-time statistics and dish counts
 * - Add New Categories with uniqueness validation
 * - Edit Existing Categories with automated cascade to food items
 * - Safe Delete Categories with dish-link dependency guard
 * - Quick Toggle Active/Inactive status
 * - Audit logging and CSRF protection
 */

require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';

// Accessible to Administrator and Kitchen Staff
require_role(['Administrator', 'Kitchen Staff']);

$current_user_id = $_SESSION['user_id'] ?? null;
$current_role = $_SESSION['role'] ?? 'Administrator';

// -----------------------------------------------------------------------------
// POST Request Handlers
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';

    // 1. ADD CATEGORY
    if ($action === 'add') {
        $category_name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($category_name === '') {
            set_flash('error', 'Category name is required.');
        } elseif (mb_strlen($category_name) > 80) {
            set_flash('error', 'Category name cannot exceed 80 characters.');
        } else {
            // Check for duplicate category name (case-insensitive)
            $check_stmt = $pdo->prepare("SELECT category_id FROM categories WHERE LOWER(TRIM(category_name)) = LOWER(?)");
            $check_stmt->execute([$category_name]);

            if ($check_stmt->fetch()) {
                set_flash('error', "A category named '{$category_name}' already exists. Please choose a different name.");
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO categories (category_name, description, is_active) VALUES (?, ?, ?)");
                $insert_stmt->execute([
                    $category_name,
                    $description !== '' ? $description : null,
                    $is_active
                ]);
                $new_id = (int)$pdo->lastInsertId();

                log_action($pdo, $current_user_id, 'CATEGORY_CREATED', "Created new category '{$category_name}' (ID: {$new_id})");
                set_flash('success', "Food category '{$category_name}' was created successfully.");
                redirect('admin/categories.php');
            }
        }
    }

    // 2. EDIT CATEGORY
    elseif ($action === 'edit') {
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $category_name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!$category_id) {
            set_flash('error', 'Invalid category ID specified.');
        } elseif ($category_name === '') {
            set_flash('error', 'Category name cannot be empty.');
        } elseif (mb_strlen($category_name) > 80) {
            set_flash('error', 'Category name cannot exceed 80 characters.');
        } else {
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $existing = $stmt->fetch();

            if (!$existing) {
                set_flash('error', 'Category not found.');
            } else {
                // Check name duplicate on other categories
                $dup_stmt = $pdo->prepare("SELECT category_id FROM categories WHERE LOWER(TRIM(category_name)) = LOWER(?) AND category_id != ?");
                $dup_stmt->execute([$category_name, $category_id]);

                if ($dup_stmt->fetch()) {
                    set_flash('error', "Another category with the name '{$category_name}' already exists.");
                } else {
                    $old_name = $existing['category_name'];

                    $pdo->beginTransaction();
                    try {
                        $update_stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ?, is_active = ? WHERE category_id = ?");
                        $update_stmt->execute([
                            $category_name,
                            $description !== '' ? $description : null,
                            $is_active,
                            $category_id
                        ]);

                        // If the category name was changed, synchronize existing items in food_items
                        $items_updated = 0;
                        if ($old_name !== $category_name) {
                            $sync_stmt = $pdo->prepare("UPDATE food_items SET category = ? WHERE category = ?");
                            $sync_stmt->execute([$category_name, $old_name]);
                            $items_updated = $sync_stmt->rowCount();
                        }

                        $pdo->commit();

                        $log_msg = "Updated category ID: {$category_id} ('{$old_name}' → '{$category_name}')";
                        if ($items_updated > 0) {
                            $log_msg .= " and updated {$items_updated} linked dish(es)";
                        }
                        log_action($pdo, $current_user_id, 'CATEGORY_UPDATED', $log_msg);

                        set_flash('success', "Category '{$category_name}' was successfully updated.");
                        redirect('admin/categories.php');
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        set_flash('error', 'Failed to update category: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    // 3. TOGGLE STATUS
    elseif ($action === 'toggle_status') {
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        if ($category_id) {
            $stmt = $pdo->prepare("SELECT category_id, category_name, is_active FROM categories WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch();

            if ($category) {
                $new_status = $category['is_active'] ? 0 : 1;
                $update_stmt = $pdo->prepare("UPDATE categories SET is_active = ? WHERE category_id = ?");
                $update_stmt->execute([$new_status, $category_id]);

                $status_text = $new_status ? 'Active' : 'Inactive';
                log_action($pdo, $current_user_id, 'CATEGORY_STATUS_TOGGLED', "Toggled status of '{$category['category_name']}' (ID: {$category_id}) to {$status_text}");
                set_flash('success', "Category '{$category['category_name']}' is now marked as {$status_text}.");
            }
        }
        redirect('admin/categories.php');
    }

    // 4. DELETE CATEGORY
    elseif ($action === 'delete') {
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        if (!$category_id) {
            set_flash('error', 'Invalid category specified for deletion.');
        } else {
            $stmt = $pdo->prepare("SELECT category_id, category_name FROM categories WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch();

            if (!$category) {
                set_flash('error', 'Category not found.');
            } else {
                // Dependency check: Ensure no dishes are currently assigned
                $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM food_items WHERE category = ?");
                $count_stmt->execute([$category['category_name']]);
                $linked_dish_count = (int)$count_stmt->fetchColumn();

                if ($linked_dish_count > 0) {
                    set_flash('error', "Cannot delete category '{$category['category_name']}' because {$linked_dish_count} active food item(s) are currently assigned to it. Please reassign or delete those dishes first, or set this category to Inactive.");
                } else {
                    $del_stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
                    $del_stmt->execute([$category_id]);

                    log_action($pdo, $current_user_id, 'CATEGORY_DELETED', "Deleted category '{$category['category_name']}' (ID: {$category_id})");
                    set_flash('success', "Category '{$category['category_name']}' was permanently removed.");
                }
            }
        }
        redirect('admin/categories.php');
    }
}

// -----------------------------------------------------------------------------
// Data Fetching: Statistics & List with Filters
// -----------------------------------------------------------------------------
$total_categories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$active_categories = (int)$pdo->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
$inactive_categories = (int)$pdo->query("SELECT COUNT(*) FROM categories WHERE is_active = 0")->fetchColumn();
$total_food_items = (int)$pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();

// Search, status filter, and sort parameters
$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$status_filter = isset($_GET['status']) && in_array($_GET['status'], ['active', 'inactive'], true) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) && is_string($_GET['sort']) ? $_GET['sort'] : 'name_asc';

$sort_options = [
    'name_asc'   => 'c.category_name ASC',
    'name_desc'  => 'c.category_name DESC',
    'items_desc' => 'item_count DESC, c.category_name ASC',
    'items_asc'  => 'item_count ASC, c.category_name ASC',
    'newest'     => 'c.category_id DESC',
    'oldest'     => 'c.category_id ASC',
];
$order_by = $sort_options[$sort] ?? 'c.category_name ASC';

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(c.category_name LIKE :s_name OR c.description LIKE :s_desc)';
    $params['s_name'] = '%' . $search . '%';
    $params['s_desc'] = '%' . $search . '%';
}

if ($status_filter === 'active') {
    $conditions[] = 'c.is_active = 1';
} elseif ($status_filter === 'inactive') {
    $conditions[] = 'c.is_active = 0';
}

$where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$list_query = "
    SELECT c.category_id, c.category_name, c.description, c.is_active, c.created_at, c.updated_at,
           COUNT(fi.item_id) AS item_count
    FROM categories c
    LEFT JOIN food_items fi ON fi.category = c.category_name
    {$where_clause}
    GROUP BY c.category_id, c.category_name, c.description, c.is_active, c.created_at, c.updated_at
    ORDER BY {$order_by}
";

$list_stmt = $pdo->prepare($list_query);
$list_stmt->execute($params);
$categories = $list_stmt->fetchAll();

// Helper to determine category icon based on name
function get_category_emoji(string $name): string {
    $lower = mb_strtolower($name);
    if (str_contains($lower, 'burger')) return '🍔';
    if (str_contains($lower, 'bowl')) return '🥗';
    if (str_contains($lower, 'dessert') || str_contains($lower, 'sweet') || str_contains($lower, 'cake')) return '🍰';
    if (str_contains($lower, 'drink') || str_contains($lower, 'beverage') || str_contains($lower, 'juice')) return '🍹';
    if (str_contains($lower, 'pasta') || str_contains($lower, 'spaghetti') || str_contains($lower, 'noodle')) return '🍝';
    if (str_contains($lower, 'rice') || str_contains($lower, 'meal') || str_contains($lower, 'curry') || str_contains($lower, 'biryani')) return '🍚';
    if (str_contains($lower, 'side') || str_contains($lower, 'fries') || str_contains($lower, 'snack')) return '🍟';
    if (str_contains($lower, 'wrap') || str_contains($lower, 'roll') || str_contains($lower, 'shawarma') || str_contains($lower, 'burrito')) return '🌯';
    if (str_contains($lower, 'pizza')) return '🍕';
    if (str_contains($lower, 'seafood') || str_contains($lower, 'fish') || str_contains($lower, 'prawn')) return '🦐';
    if (str_contains($lower, 'soup')) return '🍲';
    if (str_contains($lower, 'coffee') || str_contains($lower, 'tea')) return '☕';
    if (str_contains($lower, 'sandwich')) return '🥪';
    return '🍽️';
}

$has_active_filters = ($search !== '' || $status_filter !== 'all' || $sort !== 'name_asc');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Food Category Management - UrbanEats Cloud Kitchen</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body class="admin-dashboard">

    <!-- Global Admin Top Navigation Bar -->
    <header class="admin-navbar">
        <a class="admin-brand" href="../index.php">
            <span class="admin-brand-icon" aria-hidden="true">🍴</span>
            <span>Urban<span>Eats</span><small>CLOUD KITCHEN</small></span>
        </a>
        <nav class="admin-nav-links" aria-label="Admin navigation">
            <a href="dashboard.php">Dashboard</a>
            <a class="active" href="categories.php">Categories</a>
            <a href="users.php">Users &amp; Roles</a>
            <a href="audit-log.php">Audit Logs</a>
            <?php if ($current_role === 'Kitchen Staff'): ?>
                <a href="../kitchen/dashboard.php">Kitchen Menu</a>
            <?php endif; ?>
        </nav>
        <div class="admin-account">
            <span class="admin-account-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Staff') ?></span>
            <a class="admin-logout" href="../auth/logout.php" aria-label="Sign out">↗</a>
        </div>
    </header>

    <main class="cat-management-wrap">

        <!-- Flash Messages Display -->
        <?php $flashes = get_flashes(); ?>
        <?php if (!empty($flashes)): ?>
            <div style="margin-bottom: 1.5rem;">
                <?php foreach ($flashes as $type => $messages): ?>
                    <?php
                    $is_error = ($type === 'error' || $type === 'danger');
                    $bg_color = $is_error ? '#fef2f2' : '#ecfdf5';
                    $text_color = $is_error ? '#991b1b' : '#065f46';
                    $border_color = $is_error ? '#fecaca' : '#a7f3d0';
                    $icon = $is_error ? '⚠️' : '✅';
                    ?>
                    <?php foreach ($messages as $msg): ?>
                        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.95rem 1.25rem; border-radius: 12px; margin-bottom: 0.6rem; background: <?= $bg_color ?>; color: <?= $text_color ?>; border: 1px solid <?= $border_color ?>; font-size: 0.9rem; font-weight: 500;">
                            <span><?= $icon ?></span>
                            <span style="flex: 1;"><?= htmlspecialchars($msg) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Hero Section -->
        <section class="cat-hero" aria-labelledby="cat-hero-heading">
            <div class="cat-hero-copy">
                <span class="cat-hero-eyebrow"><span aria-hidden="true">✦</span> Menu Architecture</span>
                <h1 id="cat-hero-heading">Food Category <span>Management</span></h1>
                <p>Create, update, and organize your kitchen's menu categories. Keep customer browsing seamless and items neatly classified.</p>
                <button type="button" class="cat-hero-cta" onclick="openAddModal()">
                    <span aria-hidden="true">＋</span> Add New Category
                </button>
            </div>
            <div class="cat-hero-visual" aria-hidden="true">
                <div class="cat-hero-card">
                    <div class="cat-hero-icon-stack">🥗🍔🍰</div>
                    <div class="cat-hero-stats-mini">
                        <strong><?= $total_categories ?> Categories</strong>
                        <span><?= $total_food_items ?> total dishes categorized</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Overview Cards -->
        <section class="cat-stats-grid" aria-label="Category statistics">
            <div class="cat-stat-card">
                <div class="cat-stat-icon total" aria-hidden="true">📑</div>
                <div class="cat-stat-info">
                    <span class="cat-stat-val"><?= $total_categories ?></span>
                    <span class="cat-stat-lbl">Total Categories</span>
                </div>
            </div>
            <div class="cat-stat-card">
                <div class="cat-stat-icon active" aria-hidden="true">🟢</div>
                <div class="cat-stat-info">
                    <span class="cat-stat-val"><?= $active_categories ?></span>
                    <span class="cat-stat-lbl">Active on Menu</span>
                </div>
            </div>
            <div class="cat-stat-card">
                <div class="cat-stat-icon inactive" aria-hidden="true">⏸️</div>
                <div class="cat-stat-info">
                    <span class="cat-stat-val"><?= $inactive_categories ?></span>
                    <span class="cat-stat-lbl">Hidden / Inactive</span>
                </div>
            </div>
            <div class="cat-stat-card">
                <div class="cat-stat-icon items" aria-hidden="true">🍽️</div>
                <div class="cat-stat-info">
                    <span class="cat-stat-val"><?= $total_food_items ?></span>
                    <span class="cat-stat-lbl">Linked Food Items</span>
                </div>
            </div>
        </section>

        <!-- Main Categories Panel -->
        <section class="cat-panel" aria-label="Food Categories Table">
            <div class="cat-panel-header">
                <div>
                    <h2>Category Directory</h2>
                    <p>Review all menu categories, linked dish counts, and real-time display availability.</p>
                </div>
                <button type="button" class="btn-cat-action btn-cat-edit" style="padding: 0.6rem 1.1rem; font-size: 0.85rem;" onclick="openAddModal()">
                    <span aria-hidden="true">＋</span> Add Category
                </button>
            </div>

            <!-- Search, Filter & Sort Toolbar -->
            <form class="cat-toolbar" method="GET" action="categories.php">
                <div class="cat-toolbar-left">
                    <div class="cat-search-box">
                        <span class="cat-search-icon" aria-hidden="true">🔍</span>
                        <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search category name or description...">
                    </div>
                    <select name="status" class="cat-select-filter" aria-label="Filter by status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active Only</option>
                        <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                    </select>
                    <select name="sort" class="cat-select-filter" aria-label="Sort categories">
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A → Z</option>
                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name: Z → A</option>
                        <option value="items_desc" <?= $sort === 'items_desc' ? 'selected' : '' ?>>Most Dishes</option>
                        <option value="items_asc" <?= $sort === 'items_asc' ? 'selected' : '' ?>>Fewest Dishes</option>
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Added</option>
                    </select>
                    <button type="submit" class="cat-toolbar-btn">Filter</button>
                    <?php if ($has_active_filters): ?>
                        <a href="categories.php" class="cat-toolbar-reset">Reset filters</a>
                    <?php endif; ?>
                </div>
                <div style="font-size: 0.84rem; color: #64748b; font-weight: 600;">
                    Showing <?= count($categories) ?> of <?= $total_categories ?>
                </div>
            </form>

            <!-- Categories Data Table -->
            <?php if (empty($categories)): ?>
                <div class="cat-empty-state">
                    <div class="cat-empty-icon" aria-hidden="true">📂</div>
                    <h3>No categories found</h3>
                    <p>
                        <?= $has_active_filters ? 'No categories matched your search criteria. Try modifying your filter or query.' : 'There are no food categories created yet.' ?>
                    </p>
                    <?php if ($has_active_filters): ?>
                        <a href="categories.php" class="cat-toolbar-btn">Clear All Filters</a>
                    <?php else: ?>
                        <button type="button" class="cat-hero-cta" onclick="openAddModal()">Create Your First Category</button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">ID</th>
                                <th>Category Details</th>
                                <th>Description</th>
                                <th style="text-align: center;">Dishes</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <?php
                                $cat_id = (int)$cat['category_id'];
                                $cat_name = $cat['category_name'];
                                $cat_desc = $cat['description'] ?? '';
                                $cat_is_active = (bool)$cat['is_active'];
                                $dish_count = (int)$cat['item_count'];
                                $emoji = get_category_emoji($cat_name);
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-id">#<?= $cat_id ?></span>
                                    </td>
                                    <td>
                                        <div class="cat-cell-title">
                                            <span class="cat-cell-avatar" aria-hidden="true"><?= $emoji ?></span>
                                            <div>
                                                <div class="cat-cell-name"><?= htmlspecialchars($cat_name) ?></div>
                                                <small style="color: #94a3b8; font-size: 0.75rem;">Created <?= date('M j, Y', strtotime($cat['created_at'])) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="cat-cell-desc">
                                            <?= !empty($cat_desc) ? htmlspecialchars($cat_desc) : '<span style="color: #94a3b8; font-style: italic;">No description provided</span>' ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-count">
                                            <?= $dish_count ?> <?= $dish_count === 1 ? 'dish' : 'dishes' ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <form method="POST" action="categories.php" style="display: inline-block;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="category_id" value="<?= $cat_id ?>">
                                            <button type="submit" 
                                                    class="badge <?= $cat_is_active ? 'badge-active' : 'badge-inactive' ?>"
                                                    style="border: none; cursor: pointer;"
                                                    title="Click to toggle status (currently <?= $cat_is_active ? 'Active' : 'Inactive' ?>)">
                                                <?= $cat_is_active ? 'Active' : 'Inactive' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="cat-cell-actions" style="justify-content: flex-end;">
                                            <!-- View Dishes Link -->
                                            <a href="../menu.php?category=<?= urlencode($cat_name) ?>" 
                                               target="_blank" 
                                               rel="noopener"
                                               class="btn-cat-action btn-cat-view" 
                                               title="View in Customer Menu">
                                                <span>Preview</span> ↗
                                            </a>

                                            <!-- Edit Category Button -->
                                            <button type="button" 
                                                    class="btn-cat-action btn-cat-edit"
                                                    onclick="openEditModal(<?= $cat_id ?>, '<?= htmlspecialchars(addslashes($cat_name), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($cat_desc), ENT_QUOTES) ?>', <?= $cat_is_active ? 'true' : 'false' ?>)"
                                                    title="Edit category details">
                                                <span>✏️ Edit</span>
                                            </button>

                                            <!-- Delete Category Button with Safe Guard -->
                                            <?php if ($dish_count > 0): ?>
                                                <button type="button" 
                                                        class="btn-cat-action btn-cat-delete disabled"
                                                        onclick="alert('Cannot delete category \'<?= htmlspecialchars(addslashes($cat_name), ENT_QUOTES) ?>\' because <?= $dish_count ?> dish(es) are currently assigned to it.\n\nPlease reassign or delete these dishes in the kitchen catalogue first, or set this category to Inactive.');"
                                                        title="Protected: Cannot delete while <?= $dish_count ?> dish(es) are assigned">
                                                    <span>🗑️</span>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" 
                                                        class="btn-cat-action btn-cat-delete"
                                                        onclick="openDeleteModal(<?= $cat_id ?>, '<?= htmlspecialchars(addslashes($cat_name), ENT_QUOTES) ?>')"
                                                        title="Delete category">
                                                    <span>🗑️ Delete</span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- =======================================================================
         ADD CATEGORY MODAL
         ======================================================================= -->
    <div id="addCategoryModal" class="cat-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="addCatTitle">
        <div class="cat-modal-dialog">
            <div class="cat-modal-header">
                <h3 id="addCatTitle"><span>✨</span> Add New Food Category</h3>
                <button type="button" class="cat-modal-close" onclick="closeModal('addCategoryModal')" aria-label="Close modal">×</button>
            </div>
            <form method="POST" action="categories.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <div class="cat-modal-body">
                    <div class="cat-form-group">
                        <label for="add_category_name">Category Name <small>(required, max 80 characters)</small></label>
                        <input type="text" 
                               id="add_category_name" 
                               name="category_name" 
                               class="cat-form-input" 
                               maxlength="80" 
                               placeholder="e.g. Gourmet Pizzas, Healthy Bowls..." 
                               required 
                               autocomplete="off">
                        <div class="cat-form-hint">Choose a descriptive, unique title for grouping your dishes.</div>
                    </div>
                    <div class="cat-form-group">
                        <label for="add_description">Description <small>(optional)</small></label>
                        <textarea id="add_description" 
                                  name="description" 
                                  class="cat-form-textarea" 
                                  maxlength="500" 
                                  placeholder="Describe the type of dishes in this category..."></textarea>
                    </div>
                    <div class="cat-form-group">
                        <label class="cat-form-checkbox-label">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>Mark as Active immediately (visible on public menu)</span>
                        </label>
                    </div>
                </div>
                <div class="cat-modal-footer">
                    <button type="button" class="cat-toolbar-btn" onclick="closeModal('addCategoryModal')">Cancel</button>
                    <button type="submit" class="cat-hero-cta">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =======================================================================
         EDIT CATEGORY MODAL
         ======================================================================= -->
    <div id="editCategoryModal" class="cat-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="editCatTitle">
        <div class="cat-modal-dialog">
            <div class="cat-modal-header">
                <h3 id="editCatTitle"><span>✏️</span> Edit Food Category</h3>
                <button type="button" class="cat-modal-close" onclick="closeModal('editCategoryModal')" aria-label="Close modal">×</button>
            </div>
            <form method="POST" action="categories.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_category_id" name="category_id" value="">
                <div class="cat-modal-body">
                    <div class="cat-form-group">
                        <label for="edit_category_name">Category Name <small>(required, max 80 characters)</small></label>
                        <input type="text" 
                               id="edit_category_name" 
                               name="category_name" 
                               class="cat-form-input" 
                               maxlength="80" 
                               required 
                               autocomplete="off">
                        <div class="cat-form-hint" style="color: #ea580c;">
                            ℹ️ Note: Changing the category name will automatically update all existing food items assigned to this category.
                        </div>
                    </div>
                    <div class="cat-form-group">
                        <label for="edit_description">Description <small>(optional)</small></label>
                        <textarea id="edit_description" 
                                  name="description" 
                                  class="cat-form-textarea" 
                                  maxlength="500"></textarea>
                    </div>
                    <div class="cat-form-group">
                        <label class="cat-form-checkbox-label">
                            <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                            <span>Category is Active (visible to customers)</span>
                        </label>
                    </div>
                </div>
                <div class="cat-modal-footer">
                    <button type="button" class="cat-toolbar-btn" onclick="closeModal('editCategoryModal')">Cancel</button>
                    <button type="submit" class="cat-hero-cta">Update Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =======================================================================
         DELETE CATEGORY CONFIRMATION MODAL
         ======================================================================= -->
    <div id="deleteCategoryModal" class="cat-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="delCatTitle">
        <div class="cat-modal-dialog" style="max-width: 440px;">
            <div class="cat-modal-header">
                <h3 id="delCatTitle" style="color: #dc2626;"><span>🗑️</span> Confirm Category Deletion</h3>
                <button type="button" class="cat-modal-close" onclick="closeModal('deleteCategoryModal')" aria-label="Close modal">×</button>
            </div>
            <form method="POST" action="categories.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_category_id" name="category_id" value="">
                <div class="cat-modal-body">
                    <p style="margin: 0 0 1rem; color: #374151; font-size: 0.95rem; line-height: 1.5;">
                        Are you sure you want to permanently delete category <strong id="delete_category_name" style="color: #111827;"></strong>?
                    </p>
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 0.85rem; color: #991b1b; font-size: 0.82rem; line-height: 1.4;">
                        ⚠️ This category currently has 0 assigned dishes and can safely be deleted. This operation cannot be reversed.
                    </div>
                </div>
                <div class="cat-modal-footer">
                    <button type="button" class="cat-toolbar-btn" onclick="closeModal('deleteCategoryModal')">Cancel</button>
                    <button type="submit" class="btn-cat-action btn-cat-delete" style="padding: 0.65rem 1.25rem; font-size: 0.88rem;">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Interactive Modal JavaScript -->
    <script>
        function openAddModal() {
            document.getElementById('add_category_name').value = '';
            document.getElementById('add_description').value = '';
            openModal('addCategoryModal');
            setTimeout(() => document.getElementById('add_category_name').focus(), 100);
        }

        function openEditModal(id, name, description, isActive) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_is_active').checked = !!isActive;
            openModal('editCategoryModal');
            setTimeout(() => document.getElementById('edit_category_name').focus(), 100);
        }

        function openDeleteModal(id, name) {
            document.getElementById('delete_category_id').value = id;
            document.getElementById('delete_category_name').textContent = '"' + name + '"';
            openModal('deleteCategoryModal');
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('is-open');
                document.body.style.overflow = '';
            }
        }

        // Close modal on backdrop click
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('cat-modal-backdrop')) {
                closeModal(e.target.id);
            }
        });

        // Close modal on ESC key
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.cat-modal-backdrop.is-open').forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });
    </script>
</body>
</html>
