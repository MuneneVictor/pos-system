<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_permission('products.view');

$q = trim((string) ($_GET['q'] ?? ''));
$categoryId = max(0, (int) ($_GET['category'] ?? 0));
$status = (string) ($_GET['status'] ?? 'active');
$stock = (string) ($_GET['stock'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 24;

if (!in_array($status, ['active', 'inactive', 'all'], true)) {
    $status = 'active';
}
if (!in_array($stock, ['', 'low', 'out'], true)) {
    $stock = '';
}

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(p.name LIKE :q OR p.sku LIKE :q OR p.barcode LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = :category_id';
    $params[':category_id'] = $categoryId;
}
if ($status !== 'all') {
    $where[] = 'p.status = :status';
    $params[':status'] = $status;
}
if ($stock === 'low') {
    $where[] = 'p.track_stock = 1 AND p.current_stock <= p.minimum_stock AND p.current_stock > 0';
} elseif ($stock === 'out') {
    $where[] = 'p.track_stock = 1 AND p.current_stock <= 0';
}

$whereSql = implode(' AND ', $where);
$count = db()->prepare("SELECT COUNT(*) FROM products p WHERE {$whereSql}");
$count->execute($params);
$total = (int) $count->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    "SELECT p.*, c.name AS category_name, u.name AS unit_name, u.short_name,
            u.allows_decimal, s.name AS supplier_name
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     INNER JOIN units u ON u.id = p.unit_id
     LEFT JOIN suppliers s ON s.id = p.default_supplier_id
     WHERE {$whereSql}
     ORDER BY p.name
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = db()->query(
    'SELECT id, name FROM categories WHERE status = "active" ORDER BY name'
)->fetchAll();

$summary = db()->query(
    'SELECT COUNT(*) AS total,
            SUM(status = "active") AS active_count,
            SUM(track_stock = 1 AND current_stock <= minimum_stock) AS low_count,
            COALESCE(SUM(current_stock * average_cost), 0) AS stock_value
     FROM products'
)->fetch() ?: [];

$pageTitle = 'Products';
$pageStyles = ['admin.css', 'commerce.css'];
require BASE_PATH . '/includes/header.php';
require BASE_PATH . '/includes/sidebar.php';
?>
<div class="app-main">
    <?php require BASE_PATH . '/includes/navbar.php'; ?>
    <main class="content">
        <?php require BASE_PATH . '/includes/alerts.php'; ?>

        <div class="page-toolbar">
            <div>
                <span class="eyebrow">Catalogue</span>
                <h2>Products</h2>
                <p>Carpets, PVC, wallpapers, mats and every sellable shop item.</p>
            </div>
            <div class="toolbar-actions">
                <?php if (user_can('products.update')): ?>
                    <a class="button button--secondary" href="<?= e(app_url('products/categories.php')) ?>">Categories & units</a>
                <?php endif; ?>
                <?php if (user_can('products.create')): ?>
                    <a class="button button--primary" href="<?= e(app_url('products/add.php')) ?>">+ Add product</a>
                <?php endif; ?>
            </div>
        </div>

        <section class="mini-metric-grid">
            <article class="mini-metric"><span>Total products</span><strong><?= e((string) ($summary['total'] ?? 0)) ?></strong></article>
            <article class="mini-metric"><span>Active products</span><strong><?= e((string) ($summary['active_count'] ?? 0)) ?></strong></article>
            <article class="mini-metric"><span>Low / out of stock</span><strong><?= e((string) ($summary['low_count'] ?? 0)) ?></strong></article>
            <article class="mini-metric"><span>Stock value</span><strong><?= e(money((string) ($summary['stock_value'] ?? 0))) ?></strong></article>
        </section>

        <section class="panel-card table-panel">
            <form class="filter-bar" method="get">
                <div class="filter-search">
                    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search product, SKU or barcode">
                </div>
                <select name="category">
                    <option value="0">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="stock">
                    <option value="">All stock</option>
                    <option value="low" <?= $stock === 'low' ? 'selected' : '' ?>>Low stock</option>
                    <option value="out" <?= $stock === 'out' ? 'selected' : '' ?>>Out of stock</option>
                </select>
                <select name="status">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All statuses</option>
                </select>
                <button class="button button--secondary" type="submit">Filter</button>
                <?php if ($q !== '' || $categoryId || $stock !== '' || $status !== 'active'): ?>
                    <a class="button button--ghost" href="<?= e(app_url('products/index.php')) ?>">Clear</a>
                <?php endif; ?>
            </form>

            <div class="table-wrap">
                <table class="data-table product-table">
                    <thead><tr><th>Product</th><th>Category</th><th>Stock</th><th>Buying</th><th>Selling</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$products): ?>
                        <tr><td colspan="7"><div class="empty-state"><strong>No products found</strong><span>Add a product or change the filters.</span></div></td></tr>
                    <?php else: foreach ($products as $product):
                        $isLow = (int) $product['track_stock'] === 1 && (float) $product['current_stock'] <= (float) $product['minimum_stock'];
                    ?>
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <div class="product-thumb">
                                        <?php if ($product['image_path']): ?>
                                            <img src="<?= e(app_url($product['image_path'])) ?>" alt="">
                                        <?php else: ?><span><?= e(initials($product['name'])) ?></span><?php endif; ?>
                                    </div>
                                    <div><strong><?= e($product['name']) ?></strong><span><?= e($product['sku']) ?><?= $product['barcode'] ? ' · ' . e($product['barcode']) : '' ?></span></div>
                                </div>
                            </td>
                            <td><?= e($product['category_name']) ?></td>
                            <td>
                                <strong class="<?= $isLow ? 'text-warning' : '' ?>"><?= e(format_quantity($product['current_stock'])) ?> <?= e($product['short_name']) ?></strong>
                                <?php if ($isLow): ?><small class="table-note">Low stock</small><?php endif; ?>
                            </td>
                            <td><?= e(money($product['average_cost'])) ?></td>
                            <td><strong><?= e(money($product['selling_price'])) ?></strong><small class="table-note">/<?= e($product['short_name']) ?></small></td>
                            <td><span class="status-badge status-badge--<?= e($product['status']) ?>"><?= e(ucfirst($product['status'])) ?></span></td>
                            <td class="table-action-cell"><a class="table-action" href="<?= e(app_url('products/view.php?id=' . (int) $product['id'])) ?>">View</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span>Showing <?= $total === 0 ? 0 : $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= $total ?></span>
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination">
                        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++):
                            $query = $_GET; $query['page'] = $p; ?>
                            <a class="<?= $p === $page ? 'is-current' : '' ?>" href="<?= e(app_url('products/index.php?' . http_build_query($query))) ?>"><?= $p ?></a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
