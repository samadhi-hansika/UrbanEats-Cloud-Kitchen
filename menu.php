<?php
require_once 'config/db.php';
require_once 'includes/csrf.php';
require_once 'includes/functions.php';
require_once 'includes/orders.php';

$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) && is_string($_GET['category']) ? trim($_GET['category']) : '';
$sort = isset($_GET['sort']) && is_string($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $page !== false && $page !== null ? max(1, $page) : 1;
$per_page = 9;

$sort_options = [
    'name_asc' => 'fi.name ASC',
    'price_asc' => 'fi.price ASC, fi.name ASC',
    'price_desc' => 'fi.price DESC, fi.name ASC',
];
$sort = isset($sort_options[$sort]) ? $sort : 'name_asc';

$categories = $pdo->query(
    'SELECT c.category_name FROM categories c WHERE c.is_active = 1 ORDER BY c.category_name ASC'
)->fetchAll(PDO::FETCH_COLUMN);

if (empty($categories)) {
    $categories = $pdo->query(
        'SELECT DISTINCT category FROM food_items WHERE is_available = 1 ORDER BY category ASC'
    )->fetchAll(PDO::FETCH_COLUMN);
}

$conditions = ['fi.is_available = 1'];
$parameters = [];
if ($search !== '') {
    $conditions[] = '(fi.name LIKE :search_name OR fi.description LIKE :search_description OR fi.category LIKE :search_category)';
    $search_term = '%' . $search . '%';
    $parameters['search_name'] = $search_term;
    $parameters['search_description'] = $search_term;
    $parameters['search_category'] = $search_term;
}
if ($category !== '' && in_array($category, $categories, true)) {
    $conditions[] = 'fi.category = :category';
    $parameters['category'] = $category;
} elseif ($category !== '') {
    $category = '';
}

$where = implode(' AND ', $conditions);
$count_statement = $pdo->prepare("SELECT COUNT(*) FROM food_items fi WHERE $where");
$count_statement->execute($parameters);
$total_items = (int) $count_statement->fetchColumn();
$total_pages = max(1, (int) ceil($total_items / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$item_statement = $pdo->prepare(
    "SELECT fi.item_id, fi.name, fi.description, fi.category, fi.price, fi.image_path
     FROM food_items fi
     WHERE $where
     ORDER BY {$sort_options[$sort]}
     LIMIT :limit OFFSET :offset"
);
foreach ($parameters as $key => $value) {
    $item_statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$item_statement->bindValue(':limit', $per_page, PDO::PARAM_INT);
$item_statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$item_statement->execute();
$items = $item_statement->fetchAll();

$page_title = 'Public Menu';
include 'includes/header.php';
?>

<section class="menu-page">
    <div class="menu-heading">
        <span class="menu-eyebrow">FRESH FROM OUR KITCHEN</span>
        <h1>Explore the <span>menu</span></h1>
        <p>Find a new favorite from our freshly prepared selection.</p>
    </div>

    <form class="menu-controls" method="get" action="<?= e(url('menu.php')) ?>">
        <label class="menu-search">
            <span>Search menu</span>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search dishes or categories">
        </label>
        <label>
            <span>Category</span>
            <select name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $menu_category): ?>
                    <option value="<?= e($menu_category) ?>" <?= $category === $menu_category ? 'selected' : '' ?>>
                        <?= e($menu_category) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Sort by</span>
            <select name="sort">
                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
            </select>
        </label>
        <button class="menu-submit" type="submit">Update menu</button>
    </form>

    <?php if (!empty($categories)): ?>
        <nav class="menu-category-chips" aria-label="Filter dishes by category">
            <a href="<?= e(url('menu.php') . ($search !== '' ? '?q=' . urlencode($search) . ($sort !== 'name_asc' ? '&sort=' . urlencode($sort) : '') : ($sort !== 'name_asc' ? '?sort=' . urlencode($sort) : ''))) ?>" 
               class="menu-chip <?= $category === '' ? 'active' : '' ?>">
                All Categories
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="<?= e(url('menu.php') . '?' . http_build_query(array_merge(['category' => $cat], $search !== '' ? ['q' => $search] : [], $sort !== 'name_asc' ? ['sort' => $sort] : []))) ?>" 
                   class="menu-chip <?= $category === $cat ? 'active' : '' ?>">
                    <?= e($cat) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <div class="menu-results" aria-live="polite">
        <p><?= $total_items ?> <?= $total_items === 1 ? 'dish' : 'dishes' ?> available</p>
        <?php if ($search !== '' || $category !== ''): ?>
            <a href="<?= e(url('menu.php')) ?>">Clear filters</a>
        <?php endif; ?>
    </div>

    <?php if ($items): ?>
        <div class="menu-grid">
            <?php foreach ($items as $item): ?>
                <article class="menu-card">
                    <img src="<?= e(url($item['image_path'])) ?>" alt="<?= e($item['name']) ?>" loading="lazy" onerror="this.onerror=null; this.src='<?= e(url('public/images/home-food.png')) ?>';">
                    <div class="menu-card-content">
                        <div class="menu-card-meta">
                            <span class="menu-category"><?= e($item['category']) ?></span>
                            <strong class="menu-price">LKR <?= number_format((float) $item['price'], 2) ?></strong>
                        </div>
                        <h2><?= e($item['name']) ?></h2>
                        <p><?= e($item['description']) ?></p>
                        <?php if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'Customer'): ?>
                            <form method="post" action="<?= e(url('orders/cart.php')) ?>" class="menu-add-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="item_id" value="<?= (int)$item['item_id'] ?>">
                                <button class="btn-primary" type="submit">Add to cart</button>
                            </form>
                        <?php elseif (empty($_SESSION['user_id'])): ?>
                            <a class="btn-primary menu-add-form menu-sign-in" href="<?= e(url('auth/login.php?reason=auth_required')) ?>">Sign in to add to cart</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="menu-pagination" aria-label="Menu pages">
                <?php
                $page_url = static function (int $target_page) use ($search, $category, $sort): string {
                    $query = ['page' => $target_page, 'sort' => $sort];
                    if ($search !== '') {
                        $query['q'] = $search;
                    }
                    if ($category !== '') {
                        $query['category'] = $category;
                    }
                    return url('menu.php') . '?' . http_build_query($query);
                };
                ?>
                <?php if ($page > 1): ?>
                    <a href="<?= e($page_url($page - 1)) ?>" aria-label="Previous page">← Previous</a>
                <?php endif; ?>
                <span>Page <?= $page ?> of <?= $total_pages ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="<?= e($page_url($page + 1)) ?>" aria-label="Next page">Next →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="menu-empty">
            <span aria-hidden="true">🍽</span>
            <h2>No dishes found</h2>
            <p>Try a different search or category to see what is available.</p>
            <?php if ($search !== '' || $category !== ''): ?>
                <a href="<?= e(url('menu.php')) ?>">View the full menu</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
