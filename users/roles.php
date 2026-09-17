<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_permission('roles.manage');

$errors = [];
$mode = trim((string) ($_POST['mode'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    if ($mode === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $slug = slugify($name);

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Enter a role name between 2 and 100 characters.';
        }

        if ($slug === '') {
            $errors['name'] = 'Enter a valid role name.';
        }

        if (!$errors) {
            $check = db()->prepare('SELECT COUNT(*) FROM roles WHERE slug = :slug OR name = :name');
            $check->execute([':slug' => $slug, ':name' => $name]);

            if ((int) $check->fetchColumn() > 0) {
                $errors['name'] = 'A role with that name already exists.';
            }
        }

        if (!$errors) {
            $stmt = db()->prepare(
                'INSERT INTO roles (name, slug, description, is_system, is_active)
                 VALUES (:name, :slug, :description, 0, 1)'
            );
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description !== '' ? $description : null,
            ]);

            $roleId = (int) db()->lastInsertId();

            audit_log(
                'role_created',
                'users',
                $roleId,
                'Role created: ' . $name . '.',
                null,
                ['name' => $name, 'slug' => $slug]
            );

            flash('success', 'Role created. You can now assign its permissions.');
            redirect('users/permissions?role=' . $roleId);
        }
    }

    if ($mode === 'toggle') {
        $roleId = (int) ($_POST['role_id'] ?? 0);

        $stmt = db()->prepare(
            'SELECT id, name, slug, is_system, is_active
             FROM roles
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $roleId]);
        $role = $stmt->fetch();

        if (!$role) {
            $errors['general'] = 'Role not found.';
        } elseif ((int) $role['is_system'] === 1) {
            $errors['general'] = 'Built-in system roles cannot be disabled.';
        } else {
            $newState = (int) $role['is_active'] === 1 ? 0 : 1;

            if ($newState === 0) {
                $usedStmt = db()->prepare(
                    'SELECT COUNT(*) FROM users WHERE role_id = :role_id AND status = "active"'
                );
                $usedStmt->execute([':role_id' => $roleId]);

                if ((int) $usedStmt->fetchColumn() > 0) {
                    $errors['general'] = 'This role has active users and cannot be disabled.';
                }
            }

            if (!$errors) {
                $update = db()->prepare(
                    'UPDATE roles SET is_active = :is_active WHERE id = :id'
                );
                $update->execute([
                    ':is_active' => $newState,
                    ':id' => $roleId,
                ]);

                audit_log(
                    'role_status_changed',
                    'users',
                    $roleId,
                    'Role status changed for ' . $role['name'] . '.',
                    ['is_active' => (int) $role['is_active']],
                    ['is_active' => $newState]
                );

                flash('success', 'Role status updated.');
                redirect('users/roles');
            }
        }
    }
}

$roles = db()->query(
    'SELECT
        r.id,
        r.name,
        r.slug,
        r.description,
        r.is_system,
        r.is_active,
        COUNT(DISTINCT u.id) AS user_count,
        COUNT(DISTINCT rp.permission_id) AS permission_count
     FROM roles r
     LEFT JOIN users u ON u.role_id = r.id
     LEFT JOIN role_permissions rp ON rp.role_id = r.id
     GROUP BY r.id
     ORDER BY r.is_system DESC, r.name'
)->fetchAll();

$pageTitle = 'Roles';
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
                <h2>Roles</h2>
                <p>Roles group permissions into practical access levels for staff.</p>
            </div>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="app-alert app-alert--danger"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <section class="role-layout">
            <div class="role-list">
                <?php foreach ($roles as $role): ?>
                    <article class="role-card <?= (int) $role['is_active'] === 0 ? 'is-inactive' : '' ?>">
                        <div class="role-card__head">
                            <div class="role-card__icon"><?= e(initials($role['name'])) ?></div>
                            <div>
                                <h3><?= e($role['name']) ?></h3>
                                <span><?= e((int) $role['is_system'] === 1 ? 'Built-in role' : 'Custom role') ?></span>
                            </div>
                            <span class="status-badge status-badge--<?= (int) $role['is_active'] === 1 ? 'active' : 'inactive' ?>">
                                <?= (int) $role['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>

                        <p><?= e($role['description'] ?: 'No description provided.') ?></p>

                        <div class="role-card__stats">
                            <span><strong><?= e((string) $role['user_count']) ?></strong> users</span>
                            <span><strong><?= e((string) $role['permission_count']) ?></strong> permissions</span>
                        </div>

                        <div class="role-card__actions">
                            <?php if ($role['slug'] === 'owner'): ?>
                                <span class="subtle-badge">Full access</span>
                            <?php else: ?>
                                <a class="button button--secondary button--small"
                                   href="<?= e(app_url('users/permissions?role=' . (int) $role['id'])) ?>">
                                    Manage permissions
                                </a>
                            <?php endif; ?>

                            <?php if ((int) $role['is_system'] === 0): ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="mode" value="toggle">
                                    <input type="hidden" name="role_id" value="<?= e((string) $role['id']) ?>">
                                    <button class="button button--ghost button--small" type="submit">
                                        <?= (int) $role['is_active'] === 1 ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="form-card role-create-card">
                <div class="form-section__heading">
                    <span class="eyebrow">New role</span>
                    <h3>Create a custom role</h3>
                    <p>Create the role first, then choose exactly what it can access.</p>
                </div>

                <form method="post" class="stack-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="mode" value="create">

                    <div class="field">
                        <label for="name">Role name</label>
                        <input id="name" name="name" type="text" maxlength="100"
                               value="<?= e((string) ($_POST['name'] ?? '')) ?>"
                               placeholder="e.g. Sales Supervisor" required>
                        <?php if (isset($errors['name'])): ?><small class="field-error"><?= e($errors['name']) ?></small><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"
                                  placeholder="What is this role responsible for?"><?= e((string) ($_POST['description'] ?? '')) ?></textarea>
                    </div>

                    <button class="button button--primary" type="submit">Create role</button>
                </form>
            </aside>
        </section>
    </main>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
