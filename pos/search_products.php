<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

require_auth();

if (!user_can('pos.use')) {
    json_response([
        'ok' => false,
        'message' => 'You do not have permission to use the Point of Sale.',
    ], 403);
}

$q = trim((string) ($_GET['q'] ?? ''));

if ($q === '') {
    json_response([
        'ok' => true,
        'products' => [],
        'exact' => false,
    ]);
}

/*
|--------------------------------------------------------------------------
| Exact SKU / Barcode Match
|--------------------------------------------------------------------------
| Use separate PDO parameter names. Native PDO prepared statements do not
| reliably allow one named placeholder to be reused several times.
*/
$exactStmt = db()->prepare(
    'SELECT
        p.id,
        p.name,
        p.sku,
        p.barcode,
        p.selling_price,
        p.current_stock,
        p.image_path,
        p.track_stock,
        u.short_name,
        u.allows_decimal
     FROM products p
     INNER JOIN units u ON u.id = p.unit_id
     WHERE p.status = "active"
       AND (
            p.barcode = :barcode_exact
            OR p.sku = :sku_exact
       )
     LIMIT 1'
);

$exactStmt->execute([
    ':barcode_exact' => $q,
    ':sku_exact' => $q,
]);

$exactProduct = $exactStmt->fetch();

if ($exactProduct) {
    json_response([
        'ok' => true,
        'products' => [$exactProduct],
        'exact' => true,
    ]);
}

/*
|--------------------------------------------------------------------------
| Live Product Search
|--------------------------------------------------------------------------
| Search name, SKU and barcode as the user types.
*/
$like = '%' . $q . '%';

$searchStmt = db()->prepare(
    'SELECT
        p.id,
        p.name,
        p.sku,
        p.barcode,
        p.selling_price,
        p.current_stock,
        p.image_path,
        p.track_stock,
        u.short_name,
        u.allows_decimal
     FROM products p
     INNER JOIN units u ON u.id = p.unit_id
     WHERE p.status = "active"
       AND (
            p.name LIKE :name_search
            OR p.sku LIKE :sku_search
            OR p.barcode LIKE :barcode_search
       )
     ORDER BY
        CASE
            WHEN p.name LIKE :name_starts THEN 0
            WHEN p.sku LIKE :sku_starts THEN 1
            ELSE 2
        END,
        p.name ASC
     LIMIT 24'
);

$searchStmt->execute([
    ':name_search' => $like,
    ':sku_search' => $like,
    ':barcode_search' => $like,
    ':name_starts' => $q . '%',
    ':sku_starts' => $q . '%',
]);

json_response([
    'ok' => true,
    'products' => $searchStmt->fetchAll(),
    'exact' => false,
]);
