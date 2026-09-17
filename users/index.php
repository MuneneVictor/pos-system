<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_permission('users.view');

$search = trim((string) ($_GET['q'] ?? ''));
$roleFilter = (int) ($_GET['role'] ?? 0);
$statusFilter = trim((string) ($_GET['status'] ?? ''));

$allowedStatuses = ['active', 'inactive', 'locked'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = '(u.full_name LIKE :search OR u.username LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($roleFilter > 0) {
    $where[] = 'u.role_id = :role_id';
    $params[':role_id'] = $roleFilter;
}

if ($statusFilter !== '') {
    $where[] = 'u.status = :status';
    $params[':status'] = $statusFilter;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare(
    "SELECT COUNT(*)
     FROM users u
     WHERE {$whereSql}"
);
$countStmt->execute($params);
$totalUsers = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalUsers / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listStmt = db()->prepare(
    "SELECT
        u.id,
        u.full_name,
        u.username,
        u.email,
        u.phone,
        u.status,
        u.last_login_at,
        u.created_at,
        r.name AS role_name,
        r.slug AS role_slug,
        b.name AS branch_name
     FROM users u
     INNER JOIN roles r ON r.id = u.role_id
     LEFT JOIN branches b ON b.id = u.branch_id
     WHERE {$whereSql}
     ORDER BY u.created_at DESC, u.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$listStmt->execute($params);
$users = $listStmt->fetchAll();

$roles = db()->query(
    'SELECT id, name FROM roles WHERE is_active = 1 ORDER BY name'
)->fetchAll();

$summary = db()->query(
    'SELECT
        COUNT(*) AS total,
        SUM(status = "active") AS active_count,
        SUM(status = "inactive") AS inactive_count,
        SUM(status = "locked") AS locked_count
     FROM users'
)->fetch() ?: [];

$queryBase = $_GET;
unset($queryBase['page']);

$pageTitle = 'Users';
$pageStyles = ['admin.css'];

require BASE_PATH . '/includes/header.php';
require BASE_PATH . '/includes/sidebar.php';
?>
<div class="app-main">
    <?php require BASE_PATH . '/includes/navbar.php'; ?>

    <main class="content">
        <?php require BASE_PATH . '/includes/alerts.php'; ?>

        <div class="page-toolbar">
            <div>
                <span class="eyebrow">Administration</span>
                <h2>Staff accounts</h2>
                <p>Manage who can access Wambo wa Carpets and what role each person has.</p>
            </div>

            <?php if (user_can('users.create')): ?>
                <a class="button button--primary" href="<?= e(app_url('users/add')) ?>">+ Add user</a>
            <?php endif; ?>
        </div>

        <section class="mini-metric-grid">
            <article class="mini-metric">
                <span>Total users</span>
                <strong><?= e((string) ($summary['total'] ?? 0)) ?></strong>
            </article>
            <article class="mini-metric">
                <span>Active</span>
                <strong><?= e((string) ($summary['active_count'] ?? 0)) ?></strong>
            </article>
            <article class="mini-metric">
                <span>Inactive</span>
                <strong><?= e((string) ($summary['inactive_count'] ?? 0)) ?></strong>
            </article>
            <article class="mini-metric">
                <span>Locked</span>
                <strong><?= e((string) ($summary['locked_count'] ?? 0)) ?></strong>
            </article>
        </section>

        <section class="panel-card table-panel">
            <form method="get" class="filter-bar">
                <div class="filter-search">
                    <label class="sr-only" for="q">Search users</label>
                    <input id="q"
                           type="search"
                           name="q"
                           value="<?= e($search) ?>"
                           placeholder="Search name, username, email or phone">
                </div>

                <select name="role" aria-label="Filter by role">
                    <option value="0">All roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= e((string) $role['id']) ?>" <?= $roleFilter === (int) $role['id'] ? 'selected' : '' ?>>
                            <?= e($role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    <?php foreach ($allowedStatuses as $status): ?>
                        <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                            <?= e(ucfirst($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button class="button button--secondary" type="submit">Filter</button>

                <?php if ($search !== '' || $roleFilter > 0 || $statusFilter !== ''): ?>
                    <a class="button button--ghost" href="<?= e(app_url('users/index')) ?>">Clear</a>
                <?php endif; ?>
            </form>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Last login</th>
                        <th class="table-action-cell"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$users): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <strong>No users found</strong>
                                    <span>Try changing your search or filters.</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $row): ?>
                            <tr>
                                <td>
                                    <div class="table-user">
                                        <div class="avatar avatar--small"><?= e(initials($row['full_name'])) ?></div>
                                        <div>
                                            <strong><?= e($row['full_name']) ?></strong>
                                            <span>@<?= e($row['username']) ?> · <?= e($row['email'] ?? 'No email') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($row['role_name']) ?></td>
                                <td><?= e($row['branch_name'] ?? '—') ?></td>
                                <td>
                                    <span class="status-badge status-badge--<?= e($row['status']) ?>">
                                        <?= e(ucfirst($row['status'])) ?>
                                    </span>
                                </td>
                                <td><?= e(format_datetime($row['last_login_at'])) ?></td>
                                <td class="table-action-cell">
                                    <?php if (user_can('users.update')): ?>
                                        <a class="table-action" href="<?= e(app_url('users/edit?id=' . (int) $row['id'])) ?>">
                                            Edit
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span>
                    Showing <?= $totalUsers === 0 ? 0 : e((string) ($offset + 1)) ?>
                    – <?= e((string) min($offset + $perPage, $totalUsers)) ?>
                    of <?= e((string) $totalUsers) ?>
                </span>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="User pages">
                        <?php if ($page > 1): ?>
                            <?php $prev = http_build_query(array_merge($queryBase, ['page' => $page - 1])); ?>
                            <a href="<?= e(app_url('users/index?' . $prev)) ?>">←</a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($p = $start; $p <= $end; $p++):
                            $pageQuery = http_build_query(array_merge($queryBase, ['page' => $p]));
                        ?>
                            <a class="<?= $p === $page ? 'is-current' : '' ?>"
                               href="<?= e(app_url('users/index?' . $pageQuery)) ?>">
                                <?= e((string) $p) ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <?php $next = http_build_query(array_merge($queryBase, ['page' => $page + 1])); ?>
                            <a href="<?= e(app_url('users/index?' . $next)) ?>">→</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
