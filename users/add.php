<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_permission('users.create');

$errors = [];

$data = [
    'full_name' => '',
    'email' => '',
    'username' => '',
    'phone' => '',
    'role_id' => '',
    'branch_id' => '',
    'status' => 'active',
];

$rolesStmt = db()->query(
    'SELECT id, name, slug
     FROM roles
     WHERE is_active = 1
     ORDER BY is_system DESC, name'
);
$roles = $rolesStmt->fetchAll();

$branches = db()->query(
    'SELECT id, name
     FROM branches
     WHERE is_active = 1
     ORDER BY name'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    foreach (array_keys($data) as $key) {
        if ($key === 'status') {
            $data[$key] = trim((string) ($_POST[$key] ?? 'active'));
        } else {
            $data[$key] = trim((string) ($_POST[$key] ?? ''));
        }
    }

    $data['email'] = mb_strtolower($data['email']);
    $data['username'] = mb_strtolower($data['username']);

    $password = (string) ($_POST['password'] ?? '');
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

    if ($data['phone'] !== '' && mb_strlen($data['phone']) > 50) {
        $errors['phone'] = 'Phone number is too long.';
    }

    if (strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ) {
        $errors['password'] = 'Use at least 8 characters with uppercase, lowercase and a number.';
    }

    if ($password !== $confirmPassword) {
        $errors['password_confirmation'] = 'The passwords do not match.';
    }

    if (!in_array($data['status'], ['active', 'inactive'], true)) {
        $errors['status'] = 'Choose a valid account status.';
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
        $errors['role_id'] = 'Only an Owner can create another Owner account.';
    }

    $branchStmt = db()->prepare(
        'SELECT id FROM branches WHERE id = :id AND is_active = 1 LIMIT 1'
    );
    $branchStmt->execute([':id' => (int) $data['branch_id']]);

    if (!$branchStmt->fetchColumn()) {
        $errors['branch_id'] = 'Choose a valid branch.';
    }

    $duplicateStmt = db()->prepare(
        'SELECT username, email
         FROM users
         WHERE username = :username OR email = :email
         LIMIT 1'
    );
    $duplicateStmt->execute([
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

    if (!$errors) {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO users
                    (role_id, branch_id, full_name, username, email, phone, password_hash, status, password_changed_at, created_by)
                 VALUES
                    (:role_id, :branch_id, :full_name, :username, :email, :phone, :password_hash, :status, NOW(), :created_by)'
            );

            $stmt->execute([
                ':role_id' => (int) $data['role_id'],
                ':branch_id' => (int) $data['branch_id'],
                ':full_name' => $data['full_name'],
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] !== '' ? $data['phone'] : null,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':status' => $data['status'],
                ':created_by' => (int) current_user()['id'],
            ]);

            $userId = (int) $pdo->lastInsertId();

            $prefStmt = $pdo->prepare(
                'INSERT INTO user_preferences (user_id, theme)
                 VALUES (:user_id, "light")'
            );
            $prefStmt->execute([':user_id' => $userId]);

            $pdo->commit();

            audit_log(
                'user_created',
                'users',
                $userId,
                'Staff account created for ' . $data['full_name'] . '.',
                null,
                [
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'role_id' => (int) $data['role_id'],
                    'branch_id' => (int) $data['branch_id'],
                    'status' => $data['status'],
                ]
            );

            flash('success', 'Staff account created successfully.');
            redirect('users/index');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Create user failed: ' . $exception->getMessage());
            $errors['general'] = 'The account could not be created. Please try again.';
        }
    }
}

$pageTitle = 'Add User';
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
                <h2>Add staff member</h2>
                <p>Create a secure login account and assign the correct role.</p>
            </div>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="app-alert app-alert--danger"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form method="post" class="form-card">
            <?= csrf_field() ?>

            <div class="form-section">
                <div class="form-section__heading">
                    <span class="eyebrow">Identity</span>
                    <h3>Staff details</h3>
                </div>

                <div class="form-grid">
                    <div class="field field--span-2">
                        <label for="full_name">Full name</label>
                        <input id="full_name" name="full_name" type="text"
                               value="<?= e($data['full_name']) ?>" maxlength="150" required autofocus>
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
                        <?php if (isset($errors['phone'])): ?><small class="field-error"><?= e($errors['phone']) ?></small><?php endif; ?>
                    </div>

                    <div class="field field--span-2">
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text"
                               value="<?= e($data['username']) ?>" maxlength="40" required>
                        <small class="field-help">This is used together with the password at login.</small>
                        <?php if (isset($errors['username'])): ?><small class="field-error"><?= e($errors['username']) ?></small><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section__heading">
                    <span class="eyebrow">Access</span>
                    <h3>Role and branch</h3>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="role_id">Role</label>
                        <select id="role_id" name="role_id" required>
                            <option value="">Choose role</option>
                            <?php foreach ($roles as $role): ?>
                                <?php if ($role['slug'] === 'owner' && !current_user_is_owner()) continue; ?>
                                <option value="<?= e((string) $role['id']) ?>"
                                    <?= (string) $data['role_id'] === (string) $role['id'] ? 'selected' : '' ?>>
                                    <?= e($role['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['role_id'])): ?><small class="field-error"><?= e($errors['role_id']) ?></small><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="branch_id">Branch</label>
                        <select id="branch_id" name="branch_id" required>
                            <option value="">Choose branch</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?= e((string) $branch['id']) ?>"
                                    <?= (string) $data['branch_id'] === (string) $branch['id'] ? 'selected' : '' ?>>
                                    <?= e($branch['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['branch_id'])): ?><small class="field-error"><?= e($errors['branch_id']) ?></small><?php endif; ?>
                    </div>

                    <div class="field field--span-2">
                        <label for="status">Account status</label>
                        <select id="status" name="status">
                            <option value="active" <?= $data['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $data['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <?php if (isset($errors['status'])): ?><small class="field-error"><?= e($errors['status']) ?></small><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section__heading">
                    <span class="eyebrow">Security</span>
                    <h3>Temporary password</h3>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input id="password" name="password" type="password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" data-password-toggle="password">Show</button>
                        </div>
                        <?php if (isset($errors['password'])): ?><small class="field-error"><?= e($errors['password']) ?></small><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <div class="password-field">
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" data-password-toggle="password_confirmation">Show</button>
                        </div>
                        <?php if (isset($errors['password_confirmation'])): ?><small class="field-error"><?= e($errors['password_confirmation']) ?></small><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a class="button button--ghost" href="<?= e(app_url('users/index')) ?>">Cancel</a>
                <button class="button button--primary" type="submit">Create account</button>
            </div>
        </form>
    </main>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
