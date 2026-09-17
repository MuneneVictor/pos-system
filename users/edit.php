<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_permission('users.update');

$userId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT u.*, r.slug AS role_slug, r.name AS role_name
     FROM users u
     INNER JOIN roles r ON r.id = u.role_id
     WHERE u.id = :id
     LIMIT 1'
);
$stmt->execute([':id' => $userId]);
$editUser = $stmt->fetch();

if (!$editUser) {
    http_response_code(404);
    flash('error', 'User account was not found.');
    redirect('users/index');
}

$roles = db()->query(
    'SELECT id, name, slug
     FROM roles
     WHERE is_active = 1
     ORDER BY is_system DESC, name'
)->fetchAll();

$branches = db()->query(
    'SELECT id, name
     FROM branches
     WHERE is_active = 1
     ORDER BY name'
)->fetchAll();

$errors = [];

$data = [
    'full_name' => (string) $editUser['full_name'],
    'email' => (string) $editUser['email'],
    'username' => (string) $editUser['username'],
    'phone' => (string) ($editUser['phone'] ?? ''),
    'role_id' => (string) $editUser['role_id'],
    'branch_id' => (string) ($editUser['branch_id'] ?? ''),
    'status' => (string) $editUser['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    foreach (array_keys($data) as $key) {
        $data[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    $data['email'] = mb_strtolower($data['email']);
    $data['username'] = mb_strtolower($data['username']);

    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['password_confirmation'] ?? '');

    if (mb_strlen($data['full_name']) < 2 || mb_strlen($data['full_name']) > 150) {
        $errors['full_name'] = 'Enter the staff member’s full name.';
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['email']) > 150) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (!preg_match('/^[a-z0-9._-]{3,40}$/', $data['username'])) {
        $errors['username'] = 'Use 3–40 letters, numbers, dots, underscores or hyphens.';
    }

    if (!in_array($data['status'], ['active', 'inactive', 'locked'], true)) {
        $errors['status'] = 'Choose a valid account status.';
    }

    if ($newPassword !== '') {
        if (
            strlen($newPassword) < 8 ||
            !preg_match('/[A-Z]/', $newPassword) ||
            !preg_match('/[a-z]/', $newPassword) ||
            !preg_match('/[0-9]/', $newPassword)
        ) {
            $errors['new_password'] = 'Use at least 8 characters with uppercase, lowercase and a number.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors['password_confirmation'] = 'The passwords do not match.';
        }
    }

    $roleStmt = db()->prepare(
        'SELECT id, name, slug
         FROM roles
         WHERE id = :id AND is_active = 1
         LIMIT 1'
    );
    $roleStmt->execute([':id' => (int) $data['role_id']]);
    $selectedRole = $roleStmt->fetch();

    if (!$selectedRole) {
        $errors['role_id'] = 'Choose a valid role.';
    } elseif ($selectedRole['slug'] === 'owner' && !current_user_is_owner()) {
        $errors['role_id'] = 'Only an Owner can assign the Owner role.';
    }

    $branchStmt = db()->prepare(
        'SELECT id FROM branches WHERE id = :id AND is_active = 1 LIMIT 1'
    );
    $branchStmt->execute([':id' => (int) $data['branch_id']]);

    if (!$branchStmt->fetchColumn()) {
        $errors['branch_id'] = 'Choose a valid branch.';
    }

    $duplicateStmt = db()->prepare(
        'SELECT id, username, email
         FROM users
         WHERE id <> :id
           AND (username = :username OR email = :email)
         LIMIT 1'
    );
    $duplicateStmt->execute([
        ':id' => $userId,
        ':username' => $data['username'],
        ':email' => $data['email'],
    ]);
    $duplicate = $duplicateStmt->fetch();

    if ($duplicate) {
        if (mb_strtolower((string) $duplicate['username']) === $data['username']) {
            $errors['username'] = 'That username is already in use.';
        }
        if (mb_strtolower((string) $duplicate['email']) === $data['email']) {
            $errors['email'] = 'That email address is already in use.';
        }
    }

    $editingSelf = $userId === (int) current_user()['id'];

    if ($editingSelf) {
        if ((int) $data['role_id'] !== (int) $editUser['role_id']) {
            $errors['role_id'] = 'You cannot change your own role.';
        }

        if ($data['status'] !== 'active') {
            $errors['status'] = 'You cannot deactivate or lock your own account.';
        }
    }

    $removingActiveOwner =
        $editUser['role_slug'] === 'owner' &&
        (
            ($selectedRole && $selectedRole['slug'] !== 'owner') ||
            $data['status'] !== 'active'
        );

    if ($removingActiveOwner) {
        $ownerCountStmt = db()->query(
            'SELECT COUNT(*)
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE r.slug = "owner" AND u.status = "active"'
        );

        if ((int) $ownerCountStmt->fetchColumn() <= 1) {
            $errors['general'] = 'The system must keep at least one active Owner account.';
        }
    }

    if (!$errors) {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $sql =
                'UPDATE users
                 SET role_id = :role_id,
                     branch_id = :branch_id,
                     full_name = :full_name,
                     username = :username,
                     email = :email,
                     phone = :phone,
                     status = :status';

            $params = [
                ':role_id' => (int) $data['role_id'],
                ':branch_id' => (int) $data['branch_id'],
                ':full_name' => $data['full_name'],
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] !== '' ? $data['phone'] : null,
                ':status' => $data['status'],
                ':id' => $userId,
            ];

            if ($newPassword !== '') {
                $sql .= ', password_hash = :password_hash, password_changed_at = NOW(), failed_login_attempts = 0, locked_until = NULL';
                $params[':password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);

                if ($data['status'] === 'locked') {
                    $data['status'] = 'active';
                    $params[':status'] = 'active';
                }
            }

            if ($data['status'] !== 'locked') {
                $sql .= ', failed_login_attempts = 0, locked_until = NULL';
            }

            $sql .= ' WHERE id = :id';

            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute($params);

            $pdo->commit();

            audit_log(
                'user_updated',
                'users',
                $userId,
                'Staff account updated for ' . $data['full_name'] . '.',
                [
                    'full_name' => $editUser['full_name'],
                    'username' => $editUser['username'],
                    'email' => $editUser['email'],
                    'role_id' => (int) $editUser['role_id'],
                    'branch_id' => $editUser['branch_id'] ? (int) $editUser['branch_id'] : null,
                    'status' => $editUser['status'],
                ],
                [
                    'full_name' => $data['full_name'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'role_id' => (int) $data['role_id'],
                    'branch_id' => (int) $data['branch_id'],
                    'status' => $data['status'],
                    'password_changed' => $newPassword !== '',
                ]
            );

            flash('success', 'User account updated successfully.');
            redirect('users/index');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Update user failed: ' . $exception->getMessage());
            $errors['general'] = 'The account could not be updated. Please try again.';
        }
    }
}

$pageTitle = 'Edit User';
$pageStyles = ['admin.css'];
$pageScripts = ['auth.js'];

require BASE_PATH . '/includes/header.php';
require BASE_PATH . '/includes/sidebar.php';
?>
<div class="app-main">
    <?php require BASE_PATH . '/includes/navbar.php'; ?>

    <main class="content">
        <div class="page-toolbar">
            <div>
                <a class="back-link" href="<?= e(app_url('users/index')) ?>">← Back to users</a>
                <h2>Edit staff account</h2>
                <p>Update identity, access level, account status or password.</p>
            </div>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="app-alert app-alert--danger"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form method="post" class="form-card">
            <?= csrf_field() ?>

            <div class="profile-strip">
                <div class="avatar profile-strip__avatar"><?= e(initials($editUser['full_name'])) ?></div>
                <div>
                    <strong><?= e($editUser['full_name']) ?></strong>
                    <span>@<?= e($editUser['username']) ?> · Created <?= e(format_datetime($editUser['created_at'], 'd M Y')) ?></span>
                </div>
                <span class="status-badge status-badge--<?= e($editUser['status']) ?>">
                    <?= e(ucfirst($editUser['status'])) ?>
                </span>
            </div>

            <div class="form-section">
                <div class="form-section__heading">
                    <span class="eyebrow">Identity</span>
                    <h3>Staff details</h3>
                </div>

                <div class="form-grid">
                    <div class="field field--span-2">
                        <label for="full_name">Full name</label>
                        <input id="full_name" name="full_name" type="text"
                               value="<?= e($data['full_name']) ?>" maxlength="150" required>
                        <?php if (isset($errors['full_name'])): ?><small class="field-error"><?= e($errors['full_name']) ?></small><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email"
                               value="<?= e($data['email']) ?>" maxlength="150" required>
                        <?php if (isset($errors['email'])): ?><small class="field-error"><?= e($errors['email']) ?></small><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="phone">Phone number</label>
                        <input id="phone" name="phone" type="text"
                               value="<?= e($data['phone']) ?>" maxlength="50">
                    </div>

                    <div class="field field--span-2">
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text"
                               value="<?= e($data['username']) ?>" maxlength="40" required>
                        <?php if (isset($errors['username'])): ?><small class="field-error"><?= e($errors['username']) ?></small><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section__heading">
                    <span class="eyebrow">Access</span>
                    <h3>Role, branch and account status</h3>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="role_id">Role</label>
                        <select id="role_id" name="role_id" required <?= $userId === (int) current_user()['id'] ? 'disabled' : '' ?>>
                            <?php foreach ($roles as $role): ?>
                                <?php if ($role['slug'] === 'owner' && !current_user_is_owner()) continue; ?>
                                <option value="<?= e((string) $role['id']) ?>"
                                    <?= (string) $data['role_id'] === (string) $role['id'] ? 'selected' : '' ?>>
                                    <?= e($role['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($userId === (int) current_user()['id']): ?>
                            <input type="hidden" name="role_id" value="<?= e($data['role_id']) ?>">
                            <small class="field-help">Your own role cannot be changed here.</small>
                        <?php endif; ?>
                        <?php if (isset($errors['role_id'])): ?><small class="field-error"><?= e($errors['role_id']) ?></small><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="branch_id">Branch</label>
                        <select id="branch_id" name="branch_id" required>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= e((string) $branch['id']) ?>"
                                    <?= (string) $data['branch_id'] === (string) $branch['id'] ? 'selected' : '' ?>>
                                    <?= e($branch['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field field--span-2">
                        <label for="status">Account status</label>
                        <select id="status" name="status" <?= $userId === (int) current_user()['id'] ? 'disabled' : '' ?>>
                            <option value="active" <?= $data['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $data['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="locked" <?= $data['status'] === 'locked' ? 'selected' : '' ?>>Locked</option>
                        </select>
                        <?php if ($userId === (int) current_user()['id']): ?>
                            <input type="hidden" name="status" value="active">
                            <small class="field-help">You cannot disable your own account.</small>
                        <?php endif; ?>
                        <?php if (isset($errors['status'])): ?><small class="field-error"><?= e($errors['status']) ?></small><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section__heading">
                    <span class="eyebrow">Password</span>
                    <h3>Change password</h3>
                    <p>Leave these fields empty to keep the current password.</p>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="new_password">New password</label>
                        <div class="password-field">
                            <input id="new_password" name="new_password" type="password" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-password-toggle="new_password">Show</button>
                        </div>
                        <?php if (isset($errors['new_password'])): ?><small class="field-error"><?= e($errors['new_password']) ?></small><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm new password</label>
                        <div class="password-field">
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                            <button type="button" class="password-toggle" data-password-toggle="password_confirmation">Show</button>
                        </div>
                        <?php if (isset($errors['password_confirmation'])): ?><small class="field-error"><?= e($errors['password_confirmation']) ?></small><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a class="button button--ghost" href="<?= e(app_url('users/index')) ?>">Cancel</a>
                <button class="button button--primary" type="submit">Save changes</button>
            </div>
        </form>
    </main>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
