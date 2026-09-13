<?php
declare(strict_types=1);

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/env.php';

load_env(BASE_PATH . '/.env');

$config = require __DIR__ . '/config.php';

define('APP_NAME', $config['app']['name']);
define('APP_ENV', $config['app']['environment']);
define('APP_DEBUG', (bool) $config['app']['debug']);
define('APP_TIMEZONE', $config['app']['timezone']);
define('APP_CURRENCY', $config['app']['currency']);
define('APP_CURRENCY_SYMBOL', $config['app']['currency_symbol']);
define('SESSION_LIFETIME', (int) $config['session']['lifetime']);

date_default_timezone_set(APP_TIMEZONE);

if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

require_once BASE_PATH . '/includes/error_handler.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/csrf.php';
require_once BASE_PATH . '/includes/theme.php';
require_once __DIR__ . '/database.php';

configure_error_handling(APP_DEBUG);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['session']['name']);

    // Scope the cookie to the folder where the application is installed.
    // HTTPS is detected automatically, so migration does not require config edits.
    $cookiePath = app_base_path();
    $cookiePath = $cookiePath === '' ? '/' : rtrim($cookiePath, '/') . '/';

    $isHttps =
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    session_set_cookie_params([
        'lifetime' => $config['session']['lifetime'],
        'path' => $cookiePath,
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => (bool) $config['session']['httponly'],
        'samesite' => $config['session']['samesite'],
    ]);

    session_start();
}

require_once BASE_PATH . '/includes/audit.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/permissions.php';
require_once BASE_PATH . '/includes/business.php';
