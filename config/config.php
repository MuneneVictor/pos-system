<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Wambo Wa Carpets POS',
        'environment' => 'development',
        'debug' => false,
        'timezone' => 'Africa/Nairobi',
        'currency' => 'KES',
        'currency_symbol' => 'KSh',
    ],

    'session' => [
        'name' => 'Wambo Wa_carpets_session',
        'lifetime' => 3600,
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    'security' => [
        'csrf_token_name' => '_csrf',
        'csrf_lifetime' => 7200,
    ],
];
