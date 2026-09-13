<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . '/config');
}

if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', BASE_PATH . '/storage');
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', BASE_PATH . '/uploads');
}

if (!defined('LOG_PATH')) {
    define('LOG_PATH', STORAGE_PATH . '/logs');
}
