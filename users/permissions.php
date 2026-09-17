<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_permission('permissions.manage');

$roles = db()->query(
    'SELECT id, name, slug, description, is_active
     FROM roles
     WHERE is_active = 1
     ORDER BY is_system DESC, name'
)->fetchAll();

$roleId = (int) ($_GET['role'] ?? $_POST['role_id'] ?? 0);

if ($roleId <= 0 && $roles) {
    foreach ($roles as $candidate) {
        if ($candidate['slug'] !== 'owner') {
            $roleId = (int) $candidate['id'];
            break;
        }
    }
}

$roleStmt = db()->prepare(
    'SELECT id, name, slug, description, is_system, is_active
     FROM roles
     WHERE id = :id
     LIMIT 1'
);
$roleStmt->execute([':id' => $roleId]);
$role = $roleStmt->fetch();

if (!$role) {
    flash('error', 'Role not found.');
    redirect('users/roles');
}

if ($role['slug'] === 'owner') {
    $allPermissions = db()->query(
        'SELECT id, module, name, slug, description
         FROM permissions
         ORDER BY module, name'
    )->fetchAll();

    $groupedPermissions = [];
    foreach ($allPermissions as $permission) {
        $groupedPermissions[$permission['module']][] = $permission;
    }

    $assigned = array_column($allPermissions, 'slug');
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_valid_csrf();

        $selectedIds = $_POST['permissions'] ?? [];
        if (!is_array($selectedIds)) {
            $selectedIds = [];
        }

        $selectedIds = array_values(array_unique(array_filter(
            array_map('intval', $selectedIds),
            static fn (int $id): bool => $id > 0
        )));

        $oldSlugs = role_permissions($roleId);

        $pdo = db();

        try {
            $pdo->beginTransaction();

            $delete = $pdo->prepare(
                'DELETE FROM role_permissions WHERE role_id = :role_id'
            );
            $delete->execute([':role_id' => $roleId]);

            if ($selectedIds) {
                $validPlaceholders = implode(',', array_fill(0, count($selectedIds), '?'));
                $validStmt = $pdo->prepare(
                    "SELECT id FROM permissions WHERE id IN ({$validPlaceholders})"
                );
                $validStmt->execute($selectedIds);
                $validIds = array_map('intval', array_column($validStmt->fetchAll(), 'id'));

                $insert = $pdo->prepare(
                    'INSERT INTO role_permissions (role_id, permission_id)
                     VALUES (:role_id, :permission_id)'
                );

                foreach ($validIds as $permissionId) {
                    $insert->execute([
                        ':role_id' => $roleId,
                        ':permission_id' => $permissionId,
                    ]);
                }
            }

            $pdo->commit();

            $newSlugs = role_permissions($roleId);

            audit_log(
                'permissions_changed',
                'users',
                $roleId,
                'Permissions updated for role ' . $role['name'] . '.',
                ['permissions' => $oldSlugs],
                ['permissions' => $newSlugs]
            );

            flash('success', 'Permissions saved for ' . $role['name'] . '.');
            redirect('users/permissions?role=' . $roleId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Permission update failed: ' . $exception->getMessage());
            flash('error', 'Permissions could not be saved. Please try again.');
            redirect('users/permissions?role=' . $roleId);
        }
    }

    $allPermissions = db()->query(
        'SELECT id, module, name, slug, description
         FROM permissions
         ORDER BY module, name'
    )->fetchAll();

    $groupedPermissions = [];
    foreach ($allPermissions as $permission) {
        $groupedPermissions[$permission['module']][] = $permission;
    }

    $assigned = role_permissions($roleId);
}

$pageTitle = 'Permissions';
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
                <h2>Permissions</h2>
                <p>Choose exactly what each role can see and do.</p>
            </div>
        </div>

        <section class="permission-layout">
            <aside class="role-selector">
                <span class="role-selector__label">Select role</span>
                <?php foreach ($roles as $roleOption): ?>
                    <a class="role-selector__item <?= (int) $roleOption['id'] === $roleId ? 'is-active' : '' ?>"
                       href="<?= e(app_url('users/permissions?role=' . (int) $roleOption['id'])) ?>">
                        <span><?= e($roleOption['name']) ?></span>
                        <?php if ($roleOption['slug'] === 'owner'): ?><small>Full access</small><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </aside>

            <section class="panel-card permission-panel">
                <div class="permission-panel__head">
                    <div>
                        <span class="eyebrow"><?= e($role['slug'] === 'owner' ? 'Protected system role' : 'Role access') ?></span>
                        <h3><?= e($role['name']) ?></h3>
                        <p><?= e($role['description'] ?: 'No description provided.') ?></p>
                    </div>

                    <?php if ($role['slug'] === 'owner'): ?>
                        <span class="subtle-badge">All permissions</span>
                    <?php endif; ?>
                </div>

                <?php if ($role['slug'] === 'owner'): ?>
                    <div class="app-alert app-alert--info">
                        The Owner role always has full system access and cannot be restricted.
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="role_id" value="<?= e((string) $roleId) ?>">

                    <div class="permission-groups">
                        <?php foreach ($groupedPermissions as $module => $modulePermissions): ?>
                            <fieldset class="permission-group" <?= $role['slug'] === 'owner' ? 'disabled' : '' ?>>
                                <legend>
                                    <span><?= e(ucwords(str_replace('_', ' ', $module))) ?></span>
                                    <small><?= e((string) count($modulePermissions)) ?> permissions</small>
                                </legend>

                                <div class="permission-options">
                                    <?php foreach ($modulePermissions as $permission): ?>
                                        <label class="permission-option">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="<?= e((string) $permission['id']) ?>"
                                                <?= in_array($permission['slug'], $assigned, true) ? 'checked' : '' ?>
                                            >
                                            <span class="permission-option__check">✓</span>
                                            <span>
                                                <strong><?= e($permission['name']) ?></strong>
                                                <small><?= e($permission['description'] ?: $permission['slug']) ?></small>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($role['slug'] !== 'owner'): ?>
                        <div class="form-actions sticky-actions">
                            <a class="button button--ghost" href="<?= e(app_url('users/roles')) ?>">Back to roles</a>
                            <button class="button button--primary" type="submit">Save permissions</button>
                        </div>
                    <?php endif; ?>
                </form>
            </section>
        </section>
    </main>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
