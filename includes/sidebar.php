<?php
declare(strict_types=1);

$user = current_user();
$script = str_replace('\\', '/', (string) ($_SERVER['PHP_SELF'] ?? ''));

function sidebar_active(string $segment): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['PHP_SELF'] ?? ''));
    return str_contains($script, '/' . trim($segment, '/') . '/') ? 'is-active' : '';
}
?>
<aside class="sidebar" data-sidebar>
    <div class="sidebar__brand">
        <div class="brand-mark">WC</div>
        <div class="brand-copy">
            <strong>Wambowa Carpets</strong>
            <span>Shop Management</span>
        </div>
    </div>

    <nav class="sidebar__nav" aria-label="Main navigation">
        <span class="nav-label">Workspace</span>

        <?php if (user_can('dashboard.view')): ?>
            <a class="nav-link <?= basename($script) === 'dashboard.php' ? 'is-active' : '' ?>"
               href="<?= e(app_url('dashboard.php')) ?>">
                <span class="nav-icon">⌂</span><span>Dashboard</span>
            </a>
        <?php endif; ?>

        <?php if (user_can('pos.use')): ?>
            <a class="nav-link <?= sidebar_active('pos') ?>" href="<?= e(app_url('pos/index.php')) ?>">
                <span class="nav-icon">▣</span><span>Point of Sale</span>
            </a>
        <?php endif; ?>

        <?php if (user_can('sales.view')): ?>
            <a class="nav-link <?= sidebar_active('sales') ?>" href="<?= e(app_url('sales/index.php')) ?>">
                <span class="nav-icon">▤</span><span>Sales</span>
            </a>
        <?php endif; ?>

        <?php if (user_can('products.view') || user_can('inventory.view') || user_can('purchases.view') || user_can('suppliers.view')): ?>
            <span class="nav-label nav-label--spaced">Stock & purchasing</span>
        <?php endif; ?>

        <?php if (user_can('products.view')): ?>
            <a class="nav-link <?= sidebar_active('products') ?>" href="<?= e(app_url('products/index.php')) ?>">
                <span class="nav-icon">◇</span><span>Products</span>
            </a>
        <?php endif; ?>

        <?php if (user_can('inventory.view')): ?>
            <a class="nav-link <?= sidebar_active('inventory') ?>" href="<?= e(app_url('inventory/index.php')) ?>">
                <span class="nav-icon">▦</span><span>Inventory</span>
            </a>
        <?php endif; ?>

        <?php if (user_can('purchases.view')): ?>
            <a class="nav-link <?= sidebar_active('purchases') ?>" href="<?= e(app_url('purchases/index.php')) ?>">
                <span class="nav-icon">↓</span><span>Purchases</span>
            </a>
        <?php endif; ?>

        <?php if (user_can('suppliers.view')): ?>
            <a class="nav-link <?= sidebar_active('suppliers') ?>" href="<?= e(app_url('suppliers/index.php')) ?>">
                <span class="nav-icon">⌂</span><span>Suppliers</span>
            </a>
        <?php endif; ?>

        <?php if (user_can('customers.view') || user_can('credit.view')): ?>
            <span class="nav-label nav-label--spaced">Customers</span>
        <?php endif; ?>

        <?php if (user_can('customers.view')): ?>
            <a class="nav-link <?= sidebar_active('customers') ?>" href="<?= e(app_url('customers/index.php')) ?>">
                <span class="nav-icon">◎</span><span>Customers</span>
            </a>
        <?php endif; ?>

        <?php if (user_can('credit.view')): ?>
            <a class="nav-link <?= sidebar_active('credit') ?>" href="<?= e(app_url('credit/debts.php')) ?>">
                <span class="nav-icon">◫</span><span>Debtors</span>
            </a>
        <?php endif; ?>

        <?php if (user_can('users.view') || user_can('roles.manage') || user_can('permissions.manage')): ?>
            <span class="nav-label nav-label--spaced">Administration</span>
        <?php endif; ?>

        <?php if (user_can('users.view')): ?>
            <a class="nav-link <?= sidebar_active('users') && !in_array(basename($script), ['roles.php', 'permissions.php'], true) ? 'is-active' : '' ?>"
               href="<?= e(app_url('users/index.php')) ?>">
                <span class="nav-icon">♙</span><span>Users</span>
            </a>
        <?php endif; ?>

        <?php if (user_can('roles.manage')): ?>
            <a class="nav-link <?= basename($script) === 'roles.php' ? 'is-active' : '' ?>"
               href="<?= e(app_url('users/roles.php')) ?>">
                <span class="nav-icon">◆</span><span>Roles</span>
            </a>
        <?php endif; ?>

        <?php if (user_can('permissions.manage')): ?>
            <a class="nav-link <?= basename($script) === 'permissions.php' ? 'is-active' : '' ?>"
               href="<?= e(app_url('users/permissions.php')) ?>">
                <span class="nav-icon">⚿</span><span>Permissions</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar__footer">
        <div class="sidebar-user">
            <div class="avatar avatar--small"><?= e(initials($user['full_name'] ?? 'User')) ?></div>
            <div>
                <strong><?= e($user['full_name'] ?? 'User') ?></strong>
                <span><?= e($user['role_name'] ?? '') ?></span>
            </div>
        </div>

        <a class="sidebar-mobile-logout" href="<?= e(app_url('logout.php')) ?>">
            <span class="sidebar-mobile-logout__icon">↪</span>
            <span>Sign out</span>
        </a>
    </div>
</aside>
