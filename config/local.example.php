<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| LOCAL DATABASE CONFIGURATION
|--------------------------------------------------------------------------
| Copy this file to:
|     config/local.php
|
| Then update the values for your MySQL installation.
| Never commit config/local.php to source control.
*/

return [
    'database' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'shop_pos',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
