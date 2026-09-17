<?php
declare(strict_types=1);

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

function registration_is_open(): bool
{
    $stmt = db()->query('SELECT COUNT(*) FROM users');
    return (int) $stmt->fetchColumn() === 0;
}

function current_user(bool $refresh = false): ?array
{
    static $cachedUser = null;

    if (!is_logged_in()) {
        return null;
    }

    if ($cachedUser !== null && !$refresh) {
        return $cachedUser;
    }

    $stmt = db()->prepare(
        'SELECT
            u.id,
            u.full_name,
            u.username,
            u.email,
            u.phone,
            u.status,
            u.last_login_at,
            u.branch_id,
            u.role_id,
            r.name AS role_name,
            r.slug AS role_slug,
            b.name AS branch_name,
            COALESCE(up.theme, "light") AS theme
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         LEFT JOIN branches b ON b.id = u.branch_id
         LEFT JOIN user_preferences up ON up.user_id = u.id
         WHERE u.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => (int) $_SESSION['user_id']]);

    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        return null;
    }

    $cachedUser = $user;
    return $cachedUser;
}

function require_auth(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please sign in to continue.');
        redirect('login');
    }

    $lastActivity = (int) ($_SESSION['last_activity'] ?? time());

    if ((time() - $lastActivity) > SESSION_LIFETIME) {
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        audit_log(
            'session_expired',
            'auth',
            $userId > 0 ? $userId : null,
            'User session expired due to inactivity.',
            null,
            null,
            $userId > 0 ? $userId : null
        );

        logout_session();
        flash('error', 'Your session expired. Please sign in again.');
        redirect('login');
    }

    $_SESSION['last_activity'] = time();

    if (
        empty($_SESSION['last_regenerated_at']) ||
        (time() - (int) $_SESSION['last_regenerated_at']) > 1800
    ) {
        session_regenerate_id(true);
        $_SESSION['last_regenerated_at'] = time();
    }

    $user = current_user(true);

    if ($user === null) {
        logout_session();
        flash('error', 'Your account is unavailable. Please contact the administrator.');
        redirect('login');
    }

    set_session_theme($user['theme'] ?? 'light');
}

function require_guest(): void
{
    if (is_logged_in() && current_user() !== null) {
        redirect('dashboard');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regenerated_at'] = time();

    $themeStmt = db()->prepare(
        'SELECT theme FROM user_preferences WHERE user_id = :user_id LIMIT 1'
    );
    $themeStmt->execute([':user_id' => (int) $user['id']]);
    $theme = $themeStmt->fetchColumn();

    set_session_theme(is_string($theme) ? $theme : 'light');
}

function logout_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
