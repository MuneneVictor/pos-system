<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

require_guest();

if (registration_is_open()) {
    redirect('auth/register');
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $username = mb_strtolower(trim((string) ($_POST['username'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors['general'] = 'Enter your username and password.';
    } else {
        $stmt = db()->prepare(
            'SELECT
                id, role_id, branch_id, full_name, username, email,
                password_hash, status, failed_login_attempts, locked_until
             FROM users
             WHERE username = :username
             LIMIT 1'
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        $now = new DateTimeImmutable();

        if ($user && $user['status'] === 'locked' && !empty($user['locked_until'])) {
            $lockedUntil = new DateTimeImmutable((string) $user['locked_until']);

            if ($lockedUntil <= $now) {
                $unlockStmt = db()->prepare(
                    'UPDATE users
                     SET status = "active",
                         failed_login_attempts = 0,
                         locked_until = NULL
                     WHERE id = :id'
                );
                $unlockStmt->execute([':id' => (int) $user['id']]);

                $user['status'] = 'active';
                $user['failed_login_attempts'] = 0;
                $user['locked_until'] = null;
            }
        }

        $validPassword = $user && password_verify($password, (string) $user['password_hash']);
        $accountAvailable = $user && $user['status'] === 'active';

        if ($accountAvailable && $validPassword) {
            if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
                $rehash = db()->prepare(
                    'UPDATE users SET password_hash = :hash, password_changed_at = NOW() WHERE id = :id'
                );
                $rehash->execute([
                    ':hash' => password_hash($password, PASSWORD_DEFAULT),
                    ':id' => (int) $user['id'],
                ]);
            }

            $successStmt = db()->prepare(
                'UPDATE users
                 SET failed_login_attempts = 0,
                     locked_until = NULL,
                     status = "active",
                     last_login_at = NOW(),
                     last_login_ip = :ip
                 WHERE id = :id'
            );
            $successStmt->execute([
                ':ip' => request_ip(),
                ':id' => (int) $user['id'],
            ]);

            login_user($user);

            audit_log(
                'login',
                'auth',
                (int) $user['id'],
                'User signed in successfully.',
                null,
                ['username' => $user['username']],
                (int) $user['id']
            );

            redirect('dashboard');
        }

        if ($user && $user['status'] !== 'inactive') {
            $attempts = (int) $user['failed_login_attempts'] + 1;

            if ($attempts >= 5) {
                $lockStmt = db()->prepare(
                    'UPDATE users
                     SET failed_login_attempts = :attempts,
                         status = "locked",
                         locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                     WHERE id = :id'
                );
                $lockStmt->execute([
                    ':attempts' => $attempts,
                    ':id' => (int) $user['id'],
                ]);

                audit_log(
                    'account_locked',
                    'auth',
                    (int) $user['id'],
                    'Account temporarily locked after repeated failed sign-in attempts.',
                    null,
                    ['failed_attempts' => $attempts],
                    (int) $user['id']
                );
            } else {
                $attemptStmt = db()->prepare(
                    'UPDATE users
                     SET failed_login_attempts = :attempts
                     WHERE id = :id'
                );
                $attemptStmt->execute([
                    ':attempts' => $attempts,
                    ':id' => (int) $user['id'],
                ]);
            }
        }

        // Keep the same response for unknown usernames and wrong passwords.
        $errors['general'] = 'The username or password is incorrect.';
        usleep(250000);
    }
}

$successMessage = flash('success');
$errorMessage = flash('error');
$infoMessage = flash('info');

$pageTitle = 'Sign In';
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
<div class="auth-shell">
    <section class="auth-story">
        <div class="auth-story__brand">
            <div class="brand-mark brand-mark--large">WC</div>
            <div>
                <strong>Wambo wa Carpets</strong>
                <span>Business Management System</span>
            </div>
        </div>

        <div class="auth-story__content">
            <span class="auth-kicker">Good to see you again</span>
            <h1>Your shop, beautifully organized.</h1>
            <p>
                Sign in to manage Wambo wa Carpets from one secure workspace designed
                to stay simple even as the business grows.
            </p>

            <div class="auth-highlight">
                <span class="auth-highlight__icon">✦</span>
                <div>
                    <strong>Built to stay clear</strong>
                    <p>No cluttered menus or confusing screens—just the information needed to run the shop.</p>
                </div>
            </div>
        </div>

        <p class="auth-story__foot">Secure access · Wambo wa Carpets</p>
    </section>

    <section class="auth-panel">
        <div class="auth-panel__top">
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="Toggle theme">
                <span data-theme-icon>☾</span>
            </button>
        </div>

        <div class="auth-form-wrap auth-form-wrap--login">
            <span class="eyebrow">Secure access</span>
            <h2>Sign in to continue</h2>
            <p class="auth-subtitle">Use your username and password.</p>

            <?php if ($successMessage): ?>
                <div class="alert alert--success"><?= e($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($infoMessage): ?>
                <div class="alert alert--info"><?= e($infoMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert--danger"><?= e($errorMessage) ?></div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert--danger"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form method="post" action="" class="auth-form" novalidate>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="username">Username</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        value="<?= e($username) ?>"
                        autocomplete="username"
                        maxlength="40"
                        spellcheck="false"
                        required
                        autofocus
                    >
                </div>

                <div class="field">
                    <div class="field-label-row">
                        <label for="password">Password</label>
                    </div>
                    <div class="password-field">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="password-toggle" data-password-toggle="password">
                            Show
                        </button>
                    </div>
                </div>

                <button class="auth-submit" type="submit">
                    Sign in
                    <span>→</span>
                </button>
            </form>

            <div class="auth-login-help">
                <span>Account registration uses email.</span>
                <span>Sign-in uses username + password.</span>
            </div>
        </div>
    </section>
</div>

<script src="<?= e(asset_url('js/theme.js')) ?>"></script>
<script src="<?= e(asset_url('js/auth.js')) ?>"></script>
</body>
</html>
