<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

require_auth();

if (!user_can('customers.view') && !user_can('pos.use')) {
    json_response([
        'ok' => false,
        'message' => 'You do not have permission to search customers.',
    ], 403);
}

$q = trim((string) ($_GET['q'] ?? ''));

if ($q === '') {
    json_response([
        'ok' => true,
        'customers' => [],
    ]);
}

$like = '%' . $q . '%';

$stmt = db()->prepare(
    'SELECT
        id,
        name,
        phone,
        email,
        credit_limit,
        account_balance
     FROM customers
     WHERE status = "active"
       AND (
            name LIKE :name_search
            OR phone LIKE :phone_search
            OR email LIKE :email_search
       )
     ORDER BY
        CASE
            WHEN name LIKE :name_starts THEN 0
            WHEN phone LIKE :phone_starts THEN 1
            ELSE 2
        END,
        name ASC
     LIMIT 15'
);

$stmt->execute([
    ':name_search' => $like,
    ':phone_search' => $like,
    ':email_search' => $like,
    ':name_starts' => $q . '%',
    ':phone_starts' => $q . '%',
]);

json_response([
    'ok' => true,
    'customers' => $stmt->fetchAll(),
]);
