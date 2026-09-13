<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

require_guest();

$registrationOpen = registration_is_open();

if (!$registrationOpen) {
    flash('info', 'Initial registration is already complete. Please sign in.');
    redirect('login.php');
}

$errors = [];
$fullName = '';
$email = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $username = mb_strtolower(trim((string) ($_POST['username'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['password_confirmation'] ?? '');

    set_old_input([
        'full_name' => $fullName,
        'email' => $email,
        'username' => $username,
    ]);

    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 150) {
        $errors['full_name'] = 'Enter the owner’s full name.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (!preg_match('/^[a-z0-9._-]{3,40}$/', $username)) {
        $errors['username'] = 'Use 3–40 letters, numbers, dots, underscores or hyphens.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ) {
        $errors['password'] = 'Use at least one uppercase letter, one lowercase letter and one number.';
    }

    if ($password !== $confirmPassword) {
        $errors['password_confirmation'] = 'The passwords do not match.';
    }

    if (!$errors) {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            // Serialize first-owner registration by locking the owner role row.
            $ownerLockStmt = $pdo->prepare(
                'SELECT id FROM roles WHERE slug = :slug AND is_active = 1 LIMIT 1 FOR UPDATE'
            );
            $ownerLockStmt->execute([':slug' => 'owner']);
            $lockedRoleId = $ownerLockStmt->fetchColumn();

            if (!$lockedRoleId) {
                throw new RuntimeException('Owner role is missing. Please import the foundational database first.');
            }

            $existingUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

            if ($existingUsers > 0) {
                throw new RuntimeException('Initial registration has already been completed.');
            }

            $roleStmt = $pdo->prepare(
                'SELECT id FROM roles WHERE slug = :slug AND is_active = 1 LIMIT 1'
            );
            $roleStmt->execute([':slug' => 'owner']);
            $roleId = $roleStmt->fetchColumn();

            if (!$roleId) {
                throw new RuntimeException('Owner role is missing. Please import the foundational database first.');
            }

            $branchStmt = $pdo->prepare(
                'SELECT id FROM branches WHERE code = :code AND is_active = 1 LIMIT 1'
            );
            $branchStmt->execute([':code' => 'MAIN']);
            $branchId = $branchStmt->fetchColumn();

            if (!$branchId) {
                throw new RuntimeException('Main branch is missing. Please import the foundational database first.');
            }

            $duplicateStmt = $pdo->prepare(
                'SELECT COUNT(*) FROM users WHERE email = :email OR username = :username'
            );
            $duplicateStmt->execute([
                ':email' => $email,
                ':username' => $username,
            ]);

            if ((int) $duplicateStmt->fetchColumn() > 0) {
                throw new RuntimeException('That email address or username is already registered.');
            }

            $insert = $pdo->prepare(
                'INSERT INTO users
                    (role_id, branch_id, full_name, username, email, password_hash, status, password_changed_at)
                 VALUES
                    (:role_id, :branch_id, :full_name, :username, :email, :password_hash, "active", NOW())'
            );

            $insert->execute([
                ':role_id' => (int) $roleId,
                ':branch_id' => (int) $branchId,
                ':full_name' => $fullName,
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $userId = (int) $pdo->lastInsertId();

            $preferenceStmt = $pdo->prepare(
                'INSERT INTO user_preferences (user_id, theme) VALUES (:user_id, "light")'
            );
            $preferenceStmt->execute([':user_id' => $userId]);

            $pdo->commit();

            audit_log(
                'user_registered',
                'auth',
                $userId,
                'Initial Administrator/Owner account registered.',
                null,
                [
                    'full_name' => $fullName,
                    'username' => $username,
                    'email' => $email,
                    'role' => 'owner',
                ],
                $userId
            );

            clear_old_input();
            flash('success', 'Owner account created successfully. Sign in using your username and password.');
            redirect('login.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Registration failed: ' . $exception->getMessage());

            $errors['general'] = APP_DEBUG
                ? $exception->getMessage()
                : 'We could not create the account. Please check your details and try again.';
        }
    }
}

$pageTitle = 'Create Owner Account';
?>
<!doctype html>
<html lang="en" data-theme="<?= e(current_theme()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('css/variables.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/auth.css')) ?>">
</head>
<body class="auth-body">
<div class="auth-shell auth-shell--register">
    <section class="auth-story">
        <div class="auth-story__brand">
            <div class="brand-mark brand-mark--large">WC</div>
            <div>
                <strong>Wambo wa Carpets</strong>
                <span>Business Management System</span>
            </div>
        </div>

        <div class="auth-story__content">
            <span class="auth-kicker">Welcome to your new workspace</span>
            <h1>Run the shop with clarity, not paperwork.</h1>
            <p>
                Sales, stock, purchases, expenses and customer balances will live in one
                clean system built around how Wambo wa Carpets actually works.
            </p>

            <div class="auth-feature-list">
                <div><span>✓</span> Secure owner-controlled access</div>
                <div><span>✓</span> Light and dark themes</div>
                <div><span>✓</span> Responsive on desktop, tablet and phone</div>
            </div>
        </div>

        <p class="auth-story__foot">Wambo wa Carpets · Shop Management System</p>
    </section>

    <section class="auth-panel">
        <div class="auth-panel__top">
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="Toggle theme">
                <span data-theme-icon>☾</span>
            </button>
        </div>

        <div class="auth-form-wrap">
            <span class="eyebrow">First-time setup</span>
            <h2>Create the owner account</h2>
            <p class="auth-subtitle">
                Your email registers the account. After setup, you will sign in with
                your username and password.
            </p>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert--danger"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form method="post" action="" class="auth-form" novalidate>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="full_name">Full name</label>
                    <input
                        id="full_name"
                        name="full_name"
                        type="text"
                        value="<?= e($fullName) ?>"
                        autocomplete="name"
                        maxlength="150"
                        required
                        autofocus
                    >
                    <?php if (isset($errors['full_name'])): ?>
                        <small class="field-error"><?= e($errors['full_name']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="<?= e($email) ?>"
                        autocomplete="email"
                        maxlength="150"
                        required
                    >
                    <small class="field-help">Required for registration and account recovery later.</small>
                    <?php if (isset($errors['email'])): ?>
                        <small class="field-error"><?= e($errors['email']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="username">Choose a username</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        value="<?= e($username) ?>"
                        autocomplete="username"
                        maxlength="40"
                        spellcheck="false"
                        required
                    >
                    <small class="field-help">This is what you will use to sign in.</small>
                    <?php if (isset($errors['username'])): ?>
                        <small class="field-error"><?= e($errors['username']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                        >
                        <button type="button" class="password-toggle" data-password-toggle="password">
                            Show
                        </button>
                    </div>
                    <small class="field-help">At least 8 characters with uppercase, lowercase and a number.</small>
                    <?php if (isset($errors['password'])): ?>
                        <small class="field-error"><?= e($errors['password']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <div class="password-field">
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                        >
                        <button type="button" class="password-toggle" data-password-toggle="password_confirmation">
                            Show
                        </button>
                    </div>
                    <?php if (isset($errors['password_confirmation'])): ?>
                        <small class="field-error"><?= e($errors['password_confirmation']) ?></small>
                    <?php endif; ?>
                </div>

                <button class="auth-submit" type="submit">
                    Create owner account
                    <span>→</span>
                </button>
            </form>

            <p class="auth-security-note">
                After the first owner account is created, public registration automatically closes.
            </p>
        </div>
    </section>
</div>

<script src="<?= e(asset_url('js/theme.js')) ?>"></script>
<script src="<?= e(asset_url('js/auth.js')) ?>"></script>
</body>
</html>
