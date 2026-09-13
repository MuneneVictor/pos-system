<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_permission('dashboard.view');

$user = current_user();
$pdo = db();

/*
|--------------------------------------------------------------------------
| RBAC capability map
|--------------------------------------------------------------------------
| Owner/Admin automatically passes user_can() for every permission.
| Other roles only receive the dashboard sections their current permissions
| allow. The queries themselves are also conditional, not only the HTML.
*/
$canSales = user_can('sales.view') || user_can('reports.sales');
$canProfit = user_can('reports.profit');
$canPayments = user_can('reports.payments');
$canExpenses = user_can('expenses.view') || user_can('reports.expenses');
$canInventory = user_can('inventory.view') || user_can('reports.inventory');
$canProducts = user_can('products.view');
$canPurchases = user_can('purchases.view') || user_can('reports.purchases');
$canCustomers = user_can('customers.view') || user_can('reports.customers');
$canCredit = user_can('credit.view') || user_can('reports.customers');
$canUsers = user_can('users.view');
$canAudit = user_can('audit.view');
$canSuppliers = user_can('suppliers.view');

/*
|--------------------------------------------------------------------------
| Date range filter
|--------------------------------------------------------------------------
*/
$period = trim((string) ($_GET['period'] ?? 'today'));
$allowedPeriods = ['today', 'yesterday', 'week', 'month', 'custom'];

if (!in_array($period, $allowedPeriods, true)) {
    $period = 'today';
}

$today = new DateTimeImmutable('today');
$startDate = $today;
$endDateExclusive = $today->modify('+1 day');
$periodLabel = 'Today';

switch ($period) {
    case 'yesterday':
        $startDate = $today->modify('-1 day');
        $endDateExclusive = $today;
        $periodLabel = 'Yesterday';
        break;

    case 'week':
        $startDate = $today->modify('monday this week');
        $endDateExclusive = $today->modify('+1 day');
        $periodLabel = 'This Week';
        break;

    case 'month':
        $startDate = $today->modify('first day of this month');
        $endDateExclusive = $today->modify('+1 day');
        $periodLabel = 'This Month';
        break;

    case 'custom':
        $fromRaw = trim((string) ($_GET['from'] ?? ''));
        $toRaw = trim((string) ($_GET['to'] ?? ''));

        $from = DateTimeImmutable::createFromFormat('!Y-m-d', $fromRaw);
        $to = DateTimeImmutable::createFromFormat('!Y-m-d', $toRaw);

        if (
            $from instanceof DateTimeImmutable &&
            $to instanceof DateTimeImmutable &&
            $from <= $to
        ) {
            $startDate = $from;
            $endDateExclusive = $to->modify('+1 day');
            $periodLabel = $from->format('d M Y') . ' – ' . $to->format('d M Y');
        } else {
            $period = 'today';
            $periodLabel = 'Today';
        }
        break;
}

$start = $startDate->format('Y-m-d 00:00:00');
$end = $endDateExclusive->format('Y-m-d 00:00:00');

function dashboard_query_one(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: [];
}

function dashboard_query_all(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$dateParams = [
    ':start_date' => $start,
    ':end_date' => $end,
];

/*
|--------------------------------------------------------------------------
| Permission-scoped business data
|--------------------------------------------------------------------------
*/
$salesSummary = [
    'revenue' => 0,
    'transactions' => 0,
    'discounts' => 0,
    'amount_received' => 0,
    'credit_balance' => 0,
];

if ($canSales) {
    $salesSummary = dashboard_query_one(
        $pdo,
        'SELECT
            COALESCE(SUM(total_amount), 0) AS revenue,
            COUNT(*) AS transactions,
            COALESCE(SUM(discount_amount), 0) AS discounts,
            COALESCE(SUM(amount_paid), 0) AS amount_received,
            COALESCE(SUM(balance_due), 0) AS credit_balance
         FROM sales
         WHERE status = "completed"
           AND sale_date >= :start_date
           AND sale_date < :end_date',
        $dateParams
    );
}

$profitSummary = [
    'cogs' => 0,
    'gross_profit' => 0,
];

if ($canProfit) {
    $profitSummary = dashboard_query_one(
        $pdo,
        'SELECT
            COALESCE(SUM(cogs_amount), 0) AS cogs,
            COALESCE(SUM(gross_profit), 0) AS gross_profit
         FROM sales
         WHERE status = "completed"
           AND sale_date >= :start_date
           AND sale_date < :end_date',
        $dateParams
    );
}

$expenseTotal = 0.0;

if ($canExpenses) {
    $expenseStmt = $pdo->prepare(
        'SELECT COALESCE(SUM(amount), 0)
         FROM expenses
         WHERE status = "active"
           AND expense_date >= :start_date
           AND expense_date < :end_date'
    );
    $expenseStmt->execute($dateParams);
    $expenseTotal = (float) $expenseStmt->fetchColumn();
}

$paymentBreakdown = [];

if ($canPayments) {
    $paymentBreakdown = dashboard_query_all(
        $pdo,
        'SELECT
            pm.method_type,
            pm.name,
            COALESCE(SUM(sp.amount), 0) AS total
         FROM sale_payments sp
         INNER JOIN sales s ON s.id = sp.sale_id
         INNER JOIN payment_methods pm ON pm.id = sp.payment_method_id
         WHERE s.status = "completed"
           AND sp.paid_at >= :start_date
           AND sp.paid_at < :end_date
         GROUP BY pm.id, pm.method_type, pm.name
         ORDER BY total DESC',
        $dateParams
    );
}

$itemsSold = 0.0;
$recentSales = [];

if ($canSales) {
    $itemsStmt = $pdo->prepare(
        'SELECT COALESCE(SUM(si.quantity), 0)
         FROM sale_items si
         INNER JOIN sales s ON s.id = si.sale_id
         WHERE s.status = "completed"
           AND s.sale_date >= :start_date
           AND s.sale_date < :end_date'
    );
    $itemsStmt->execute($dateParams);
    $itemsSold = (float) $itemsStmt->fetchColumn();

    $recentSales = dashboard_query_all(
        $pdo,
        'SELECT
            s.id,
            s.sale_no,
            s.total_amount,
            s.payment_status,
            s.sale_date,
            c.name AS customer_name,
            u.full_name AS cashier_name
         FROM sales s
         LEFT JOIN customers c ON c.id = s.customer_id
         INNER JOIN users u ON u.id = s.created_by
         WHERE s.status = "completed"
         ORDER BY s.sale_date DESC, s.id DESC
         LIMIT 6'
    );
}

$topProducts = [];

if (user_can('reports.sales')) {
    $topProducts = dashboard_query_all(
        $pdo,
        'SELECT
            si.product_name_snapshot AS product_name,
            si.unit_snapshot AS unit_name,
            COALESCE(SUM(si.quantity), 0) AS quantity_sold,
            COALESCE(SUM(si.line_total), 0) AS revenue
         FROM sale_items si
         INNER JOIN sales s ON s.id = si.sale_id
         WHERE s.status = "completed"
           AND s.sale_date >= :start_date
           AND s.sale_date < :end_date
         GROUP BY si.product_id, si.product_name_snapshot, si.unit_snapshot
         ORDER BY revenue DESC
         LIMIT 6',
        $dateParams
    );
}

$inventorySummary = [
    'stock_value' => 0,
    'low_stock' => 0,
    'out_of_stock' => 0,
];
$lowStockProducts = [];

if ($canInventory) {
    $inventorySummary = dashboard_query_one(
        $pdo,
        'SELECT
            COALESCE(SUM(current_stock * average_cost), 0) AS stock_value,
            COALESCE(SUM(CASE WHEN track_stock = 1 AND current_stock <= minimum_stock THEN 1 ELSE 0 END), 0) AS low_stock,
            COALESCE(SUM(CASE WHEN track_stock = 1 AND current_stock <= 0 THEN 1 ELSE 0 END), 0) AS out_of_stock
         FROM products
         WHERE status = "active"'
    );

    $lowStockProducts = dashboard_query_all(
        $pdo,
        'SELECT
            p.id,
            p.name,
            p.current_stock,
            p.minimum_stock,
            u.short_name
         FROM products p
         INNER JOIN units u ON u.id = p.unit_id
         WHERE p.status = "active"
           AND p.track_stock = 1
           AND p.current_stock <= p.minimum_stock
         ORDER BY p.current_stock ASC, p.name ASC
         LIMIT 6'
    );
}

$productCount = 0;
if ($canProducts) {
    $productCount = (int) $pdo->query(
        'SELECT COUNT(*) FROM products WHERE status = "active"'
    )->fetchColumn();
}

$purchaseSummary = [
    'total' => 0,
    'count' => 0,
    'balance' => 0,
];
$recentPurchases = [];

if ($canPurchases) {
    $purchaseSummary = dashboard_query_one(
        $pdo,
        'SELECT
            COALESCE(SUM(total_amount), 0) AS total,
            COUNT(*) AS count,
            COALESCE(SUM(balance_due), 0) AS balance
         FROM purchases
         WHERE status <> "cancelled"
           AND purchase_date >= :start_date
           AND purchase_date < :end_date',
        $dateParams
    );

    $recentPurchases = dashboard_query_all(
        $pdo,
        'SELECT
            p.id,
            p.purchase_no,
            p.total_amount,
            p.payment_status,
            p.status,
            p.purchase_date,
            s.name AS supplier_name
         FROM purchases p
         INNER JOIN suppliers s ON s.id = p.supplier_id
         WHERE p.status <> "cancelled"
         ORDER BY p.purchase_date DESC, p.id DESC
         LIMIT 5'
    );
}

$customerSummary = [
    'customers' => 0,
    'outstanding_debt' => 0,
    'debtors' => 0,
];
$topDebtors = [];

if ($canCustomers || $canCredit) {
    $customerSummary = dashboard_query_one(
        $pdo,
        'SELECT
            COUNT(*) AS customers,
            COALESCE(SUM(CASE WHEN account_balance > 0 THEN account_balance ELSE 0 END), 0) AS outstanding_debt,
            COALESCE(SUM(CASE WHEN account_balance > 0 THEN 1 ELSE 0 END), 0) AS debtors
         FROM customers
         WHERE status = "active"'
    );
}

if ($canCredit) {
    $topDebtors = dashboard_query_all(
        $pdo,
        'SELECT id, name, phone, account_balance
         FROM customers
         WHERE status = "active"
           AND account_balance > 0
         ORDER BY account_balance DESC
         LIMIT 6'
    );
}

$activeUsers = 0;
if ($canUsers) {
    $activeUsers = (int) $pdo->query(
        'SELECT COUNT(*) FROM users WHERE status = "active"'
    )->fetchColumn();
}

$activeSuppliers = 0;
if ($canSuppliers) {
    $activeSuppliers = (int) $pdo->query(
        'SELECT COUNT(*) FROM suppliers WHERE status = "active"'
    )->fetchColumn();
}

$recentActivity = [];
if ($canAudit) {
    $recentActivity = dashboard_query_all(
        $pdo,
        'SELECT
            a.action,
            a.module,
            a.description,
            a.created_at,
            u.full_name
         FROM audit_logs a
         LEFT JOIN users u ON u.id = a.user_id
         ORDER BY a.id DESC
         LIMIT 8'
    );
}

$salesTrend = [];
if ($canSales) {
    $trendStart = $today->modify('-6 days')->format('Y-m-d 00:00:00');
    $trendEnd = $today->modify('+1 day')->format('Y-m-d 00:00:00');

    $trendRows = dashboard_query_all(
        $pdo,
        'SELECT
            DATE(sale_date) AS sale_day,
            COALESCE(SUM(total_amount), 0) AS revenue
         FROM sales
         WHERE status = "completed"
           AND sale_date >= :trend_start
           AND sale_date < :trend_end
         GROUP BY DATE(sale_date)
         ORDER BY sale_day ASC',
        [
            ':trend_start' => $trendStart,
            ':trend_end' => $trendEnd,
        ]
    );

    $trendMap = [];
    foreach ($trendRows as $row) {
        $trendMap[(string) $row['sale_day']] = (float) $row['revenue'];
    }

    for ($i = 6; $i >= 0; $i--) {
        $day = $today->modify('-' . $i . ' days');
        $key = $day->format('Y-m-d');
        $salesTrend[] = [
            'label' => $day->format('D'),
            'date' => $day->format('d M'),
            'value' => $trendMap[$key] ?? 0.0,
        ];
    }
}

$maxTrend = 0.0;
foreach ($salesTrend as $point) {
    $maxTrend = max($maxTrend, (float) $point['value']);
}

$grossProfit = (float) ($profitSummary['gross_profit'] ?? 0);
$cogs = (float) ($profitSummary['cogs'] ?? 0);
$netProfit = $grossProfit - $expenseTotal;

$hour = (int) date('G');
$greeting = $hour < 12
    ? 'Good morning'
    : ($hour < 18 ? 'Good afternoon' : 'Good evening');

$firstName = explode(' ', trim((string) ($user['full_name'] ?? 'User')))[0] ?? 'User';

$pageTitle = 'Dashboard';
$pageStyles = ['admin.css', 'dashboard.css'];

require BASE_PATH . '/includes/header.php';
require BASE_PATH . '/includes/sidebar.php';
?>
<div class="app-main">
    <?php require BASE_PATH . '/includes/navbar.php'; ?>

    <main class="content dashboard-content">
        <?php require BASE_PATH . '/includes/alerts.php'; ?>

        <section class="dashboard-hero">
            <div>
                <span class="dashboard-hero__eyebrow">Wambowa Carpets</span>
                <h2><?= e($greeting) ?>, <?= e($firstName) ?>.</h2>
                <p>
                    <?= current_user_is_owner()
                        ? 'Your complete business overview is below.'
                        : 'Your dashboard shows the business information your role is allowed to access.' ?>
                </p>
            </div>

            <div class="dashboard-hero__meta">
                <span><?= e(date('l')) ?></span>
                <strong><?= e(date('d M Y')) ?></strong>
                <small><?= e($user['role_name'] ?? '') ?></small>
            </div>
        </section>

        <section class="dashboard-filter-card">
            <div>
                <span class="dashboard-section-kicker">Overview period</span>
                <strong><?= e($periodLabel) ?></strong>
            </div>

            <form method="get" class="dashboard-period-form">
                <select name="period" id="dashboard-period" aria-label="Dashboard period">
                    <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="yesterday" <?= $period === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>This Week</option>
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>This Month</option>
                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                </select>

                <div class="dashboard-custom-dates <?= $period === 'custom' ? 'is-visible' : '' ?>" data-custom-range>
                    <input type="date" name="from" value="<?= e((string) ($_GET['from'] ?? $startDate->format('Y-m-d'))) ?>" aria-label="From date">
                    <span>to</span>
                    <input type="date" name="to" value="<?= e((string) ($_GET['to'] ?? $endDateExclusive->modify('-1 day')->format('Y-m-d'))) ?>" aria-label="To date">
                </div>

                <button class="button button--secondary" type="submit">Apply</button>
            </form>
        </section>

        <?php if ($canSales || $canProfit || $canExpenses || $canInventory || $canPurchases || $canCredit || $canUsers): ?>
            <section class="dashboard-metrics">
                <?php if ($canSales): ?>
                    <article class="overview-card overview-card--primary">
                        <div class="overview-card__label"><span>Sales</span><i>↗</i></div>
                        <strong><?= e(money((float) ($salesSummary['revenue'] ?? 0))) ?></strong>
                        <small><?= e((string) ($salesSummary['transactions'] ?? 0)) ?> completed transaction(s)</small>
                    </article>

                    <article class="overview-card">
                        <div class="overview-card__label"><span>Items sold</span><i>▦</i></div>
                        <strong><?= e(number_format($itemsSold, 3)) ?></strong>
                        <small>Quantity sold in selected period</small>
                    </article>
                <?php endif; ?>

                <?php if ($canProfit): ?>
                    <article class="overview-card">
                        <div class="overview-card__label"><span>COGS</span><i>↓</i></div>
                        <strong><?= e(money($cogs)) ?></strong>
                        <small>Cost of goods sold</small>
                    </article>

                    <article class="overview-card overview-card--success">
                        <div class="overview-card__label"><span>Gross profit</span><i>✦</i></div>
                        <strong><?= e(money($grossProfit)) ?></strong>
                        <small>Revenue less cost of goods sold</small>
                    </article>
                <?php endif; ?>

                <?php if ($canExpenses): ?>
                    <article class="overview-card">
                        <div class="overview-card__label"><span>Expenses</span><i>−</i></div>
                        <strong><?= e(money($expenseTotal)) ?></strong>
                        <small>Operating expenses</small>
                    </article>
                <?php endif; ?>

                <?php if ($canProfit && $canExpenses): ?>
                    <article class="overview-card overview-card--success">
                        <div class="overview-card__label"><span>Net profit</span><i>≈</i></div>
                        <strong><?= e(money($netProfit)) ?></strong>
                        <small>Gross profit less operating expenses</small>
                    </article>
                <?php endif; ?>

                <?php if ($canInventory): ?>
                    <article class="overview-card">
                        <div class="overview-card__label"><span>Stock value</span><i>◇</i></div>
                        <strong><?= e(money((float) ($inventorySummary['stock_value'] ?? 0))) ?></strong>
                        <small><?= e((string) ($inventorySummary['low_stock'] ?? 0)) ?> low-stock item(s)</small>
                    </article>
                <?php elseif ($canProducts): ?>
                    <article class="overview-card">
                        <div class="overview-card__label"><span>Products</span><i>◇</i></div>
                        <strong><?= e((string) $productCount) ?></strong>
                        <small>Active products</small>
                    </article>
                <?php endif; ?>

                <?php if ($canCredit): ?>
                    <article class="overview-card overview-card--warning">
                        <div class="overview-card__label"><span>Outstanding debt</span><i>◫</i></div>
                        <strong><?= e(money((float) ($customerSummary['outstanding_debt'] ?? 0))) ?></strong>
                        <small><?= e((string) ($customerSummary['debtors'] ?? 0)) ?> debtor(s)</small>
                    </article>
                <?php endif; ?>

                <?php if ($canPurchases): ?>
                    <article class="overview-card">
                        <div class="overview-card__label"><span>Purchases</span><i>↓</i></div>
                        <strong><?= e(money((float) ($purchaseSummary['total'] ?? 0))) ?></strong>
                        <small><?= e((string) ($purchaseSummary['count'] ?? 0)) ?> purchase(s)</small>
                    </article>
                <?php endif; ?>

                <?php if ($canUsers): ?>
                    <article class="overview-card">
                        <div class="overview-card__label"><span>Active staff</span><i>◎</i></div>
                        <strong><?= e((string) $activeUsers) ?></strong>
                        <small>Accounts able to sign in</small>
                    </article>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="dashboard-main-grid">
            <?php if ($canSales): ?>
                <article class="dashboard-panel dashboard-panel--wide">
                    <div class="dashboard-panel__head">
                        <div>
                            <span class="dashboard-section-kicker">Performance</span>
                            <h3>7-day sales trend</h3>
                        </div>
                        <?php if (user_can('sales.view')): ?>
                            <a href="<?= e(app_url('sales/index.php')) ?>">View sales →</a>
                        <?php endif; ?>
                    </div>

                    <div class="sales-bars" aria-label="Seven day sales trend">
                        <?php foreach ($salesTrend as $point): ?>
                            <?php
                            $height = $maxTrend > 0
                                ? max(4, ((float) $point['value'] / $maxTrend) * 100)
                                : 4;
                            ?>
                            <div class="sales-bar-item" title="<?= e($point['date'] . ': ' . money($point['value'])) ?>">
                                <div class="sales-bar-track">
                                    <div class="sales-bar-fill" style="height: <?= e(number_format($height, 2, '.', '')) ?>%"></div>
                                </div>
                                <strong><?= e($point['label']) ?></strong>
                                <small><?= e(money($point['value'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endif; ?>

            <?php if ($canPayments): ?>
                <article class="dashboard-panel">
                    <div class="dashboard-panel__head">
                        <div>
                            <span class="dashboard-section-kicker">Money received</span>
                            <h3>Payment methods</h3>
                        </div>
                    </div>

                    <?php if (!$paymentBreakdown): ?>
                        <div class="dashboard-empty">No payments in this period.</div>
                    <?php else: ?>
                        <div class="summary-list">
                            <?php foreach ($paymentBreakdown as $payment): ?>
                                <div class="summary-list__row">
                                    <div>
                                        <span class="summary-dot"></span>
                                        <strong><?= e($payment['name']) ?></strong>
                                    </div>
                                    <b><?= e(money((float) $payment['total'])) ?></b>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endif; ?>

            <?php if (user_can('reports.sales')): ?>
                <article class="dashboard-panel">
                    <div class="dashboard-panel__head">
                        <div>
                            <span class="dashboard-section-kicker">Sales mix</span>
                            <h3>Top products</h3>
                        </div>
                    </div>

                    <?php if (!$topProducts): ?>
                        <div class="dashboard-empty">No product sales in this period.</div>
                    <?php else: ?>
                        <div class="rank-list">
                            <?php foreach ($topProducts as $index => $product): ?>
                                <div class="rank-row">
                                    <span class="rank-row__number"><?= e((string) ($index + 1)) ?></span>
                                    <div>
                                        <strong><?= e($product['product_name']) ?></strong>
                                        <small><?= e(number_format((float) $product['quantity_sold'], 3)) ?> <?= e($product['unit_name']) ?> sold</small>
                                    </div>
                                    <b><?= e(money((float) $product['revenue'])) ?></b>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endif; ?>

            <?php if ($canInventory): ?>
                <article class="dashboard-panel">
                    <div class="dashboard-panel__head">
                        <div>
                            <span class="dashboard-section-kicker">Inventory attention</span>
                            <h3>Low stock</h3>
                        </div>
                        <?php if (user_can('inventory.view')): ?>
                            <a href="<?= e(app_url('inventory/low_stock.php')) ?>">View inventory →</a>
                        <?php endif; ?>
                    </div>

                    <?php if (!$lowStockProducts): ?>
                        <div class="dashboard-good-state">✓ Stock levels currently look healthy.</div>
                    <?php else: ?>
                        <div class="summary-list">
                            <?php foreach ($lowStockProducts as $product): ?>
                                <div class="summary-list__row">
                                    <div>
                                        <strong><?= e($product['name']) ?></strong>
                                        <small>
                                            Minimum <?= e(number_format((float) $product['minimum_stock'], 3)) ?> <?= e($product['short_name']) ?>
                                        </small>
                                    </div>
                                    <b class="text-warning">
                                        <?= e(number_format((float) $product['current_stock'], 3)) ?> <?= e($product['short_name']) ?>
                                    </b>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endif; ?>

            <?php if ($canCredit): ?>
                <article class="dashboard-panel">
                    <div class="dashboard-panel__head">
                        <div>
                            <span class="dashboard-section-kicker">Receivables</span>
                            <h3>Top debtors</h3>
                        </div>
                        <a href="<?= e(app_url('credit/debts.php')) ?>">View debtors →</a>
                    </div>

                    <?php if (!$topDebtors): ?>
                        <div class="dashboard-good-state">✓ No outstanding customer debt.</div>
                    <?php else: ?>
                        <div class="summary-list">
                            <?php foreach ($topDebtors as $debtor): ?>
                                <div class="summary-list__row">
                                    <div>
                                        <strong><?= e($debtor['name']) ?></strong>
                                        <small><?= e($debtor['phone'] ?: 'No phone') ?></small>
                                    </div>
                                    <b class="text-warning"><?= e(money((float) $debtor['account_balance'])) ?></b>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endif; ?>

            <?php if ($canSales): ?>
                <article class="dashboard-panel dashboard-panel--wide">
                    <div class="dashboard-panel__head">
                        <div>
                            <span class="dashboard-section-kicker">Latest transactions</span>
                            <h3>Recent sales</h3>
                        </div>
                        <?php if (user_can('sales.view')): ?>
                            <a href="<?= e(app_url('sales/index.php')) ?>">All sales →</a>
                        <?php endif; ?>
                    </div>

                    <?php if (!$recentSales): ?>
                        <div class="dashboard-empty">No completed sales yet.</div>
                    <?php else: ?>
                        <div class="dashboard-table-wrap">
                            <table class="dashboard-table">
                                <thead>
                                <tr>
                                    <th>Sale</th>
                                    <th>Customer</th>
                                    <th>Cashier</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recentSales as $sale): ?>
                                    <tr>
                                        <td>
                                            <?php if (user_can('sales.view_details')): ?>
                                                <a href="<?= e(app_url('sales/view.php?id=' . (int) $sale['id'])) ?>">
                                                    <?= e($sale['sale_no']) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= e($sale['sale_no']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($sale['customer_name'] ?: 'Walk-in') ?></td>
                                        <td><?= e($sale['cashier_name']) ?></td>
                                        <td><span class="dashboard-status"><?= e(ucfirst($sale['payment_status'])) ?></span></td>
                                        <td><strong><?= e(money((float) $sale['total_amount'])) ?></strong></td>
                                        <td><?= e(format_datetime($sale['sale_date'], 'd M, H:i')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endif; ?>

            <?php if ($canPurchases): ?>
                <article class="dashboard-panel">
                    <div class="dashboard-panel__head">
                        <div>
                            <span class="dashboard-section-kicker">Supply</span>
                            <h3>Recent purchases</h3>
                        </div>
                        <?php if (user_can('purchases.view')): ?>
                            <a href="<?= e(app_url('purchases/index.php')) ?>">View purchases →</a>
                        <?php endif; ?>
                    </div>

                    <?php if (!$recentPurchases): ?>
                        <div class="dashboard-empty">No purchase records yet.</div>
                    <?php else: ?>
                        <div class="summary-list">
                            <?php foreach ($recentPurchases as $purchase): ?>
                                <div class="summary-list__row">
                                    <div>
                                        <strong><?= e($purchase['purchase_no']) ?> · <?= e($purchase['supplier_name']) ?></strong>
                                        <small><?= e(format_datetime($purchase['purchase_date'], 'd M Y')) ?> · <?= e(ucfirst(str_replace('_', ' ', $purchase['status']))) ?></small>
                                    </div>
                                    <b><?= e(money((float) $purchase['total_amount'])) ?></b>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endif; ?>

            <?php if ($canAudit): ?>
                <article class="dashboard-panel">
                    <div class="dashboard-panel__head">
                        <div>
                            <span class="dashboard-section-kicker">System activity</span>
                            <h3>Recent activity</h3>
                        </div>
                    </div>

                    <?php if (!$recentActivity): ?>
                        <div class="dashboard-empty">No recent activity.</div>
                    <?php else: ?>
                        <div class="activity-feed">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="activity-feed__item">
                                    <span class="activity-feed__dot"></span>
                                    <div>
                                        <strong><?= e($activity['description']) ?></strong>
                                        <small>
                                            <?= e($activity['full_name'] ?: 'System') ?>
                                            · <?= e(format_datetime($activity['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endif; ?>
        </section>

        <section class="dashboard-panel dashboard-quick-panel">
            <div class="dashboard-panel__head">
                <div>
                    <span class="dashboard-section-kicker">Shortcuts</span>
                    <h3>Quick actions</h3>
                </div>
            </div>

            <div class="quick-actions-grid">
                <?php if (user_can('pos.use')): ?>
                    <a class="quick-action" href="<?= e(app_url('pos/index.php')) ?>">
                        <span>▣</span><div><strong>New sale</strong><small>Open Point of Sale</small></div>
                    </a>
                <?php endif; ?>

                <?php if (user_can('products.create')): ?>
                    <a class="quick-action" href="<?= e(app_url('products/add.php')) ?>">
                        <span>◇</span><div><strong>Add product</strong><small>Create inventory item</small></div>
                    </a>
                <?php endif; ?>

                <?php if (user_can('inventory.adjust')): ?>
                    <a class="quick-action" href="<?= e(app_url('inventory/stock_adjustment.php')) ?>">
                        <span>▦</span><div><strong>Adjust stock</strong><small>Record stock movement</small></div>
                    </a>
                <?php endif; ?>

                <?php if (user_can('purchases.create')): ?>
                    <a class="quick-action" href="<?= e(app_url('purchases/add.php')) ?>">
                        <span>↓</span><div><strong>New purchase</strong><small>Record supplier purchase</small></div>
                    </a>
                <?php endif; ?>

                <?php if (user_can('customers.create')): ?>
                    <a class="quick-action" href="<?= e(app_url('customers/add.php')) ?>">
                        <span>◎</span><div><strong>Add customer</strong><small>Create customer profile</small></div>
                    </a>
                <?php endif; ?>

                <?php if (user_can('credit.record_payment')): ?>
                    <a class="quick-action" href="<?= e(app_url('credit/debts.php')) ?>">
                        <span>◫</span><div><strong>Record debt payment</strong><small>Manage customer balances</small></div>
                    </a>
                <?php endif; ?>

                <?php if (user_can('users.create')): ?>
                    <a class="quick-action" href="<?= e(app_url('users/add.php')) ?>">
                        <span>♙</span><div><strong>Add staff</strong><small>Create staff account</small></div>
                    </a>
                <?php endif; ?>

                <?php if ($canSuppliers): ?>
                    <a class="quick-action" href="<?= e(app_url('suppliers/index.php')) ?>">
                        <span>⌂</span><div><strong>Suppliers</strong><small><?= e((string) $activeSuppliers) ?> active supplier(s)</small></div>
                    </a>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const periodSelect = document.getElementById('dashboard-period');
    const customRange = document.querySelector('[data-custom-range]');

    if (!periodSelect || !customRange) {
        return;
    }

    periodSelect.addEventListener('change', function () {
        customRange.classList.toggle('is-visible', periodSelect.value === 'custom');
    });
});
</script>

<?php require BASE_PATH . '/includes/footer.php'; ?>
