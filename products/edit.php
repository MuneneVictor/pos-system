<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_permission('products.update');

$productId = max(0, (int) ($_GET['id'] ?? 0));
$stmt = db()->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();
if (!$product) { flash('error', 'Product not found.'); redirect('products/index.php'); }

$categories = db()->query('SELECT id, name FROM categories WHERE status = "active" ORDER BY name')->fetchAll();
$units = db()->query('SELECT id, name, short_name, allows_decimal FROM units WHERE is_active = 1 ORDER BY name')->fetchAll();
$suppliers = db()->query('SELECT id, name FROM suppliers WHERE status = "active" ORDER BY name')->fetchAll();

$data = [
    'name' => (string) $product['name'], 'sku' => (string) $product['sku'], 'barcode' => (string) ($product['barcode'] ?? ''),
    'category_id' => (string) $product['category_id'], 'unit_id' => (string) $product['unit_id'],
    'description' => (string) ($product['description'] ?? ''), 'buying_price' => (string) $product['buying_price'],
    'selling_price' => (string) $product['selling_price'], 'minimum_stock' => (string) $product['minimum_stock'],
    'current_stock' => (string) $product['current_stock'], 'default_supplier_id' => (string) ($product['default_supplier_id'] ?? ''),
    'track_stock' => (int) $product['track_stock'], 'status' => (string) $product['status'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    foreach (['name','sku','barcode','category_id','unit_id','description','buying_price','selling_price','minimum_stock','default_supplier_id','status'] as $key) {
        $data[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $data['track_stock'] = isset($_POST['track_stock']) ? 1 : 0;
    $data['sku'] = strtoupper($data['sku']);

    if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 180) $errors['name'] = 'Enter a product name.';
    if ($data['sku'] === '' || mb_strlen($data['sku']) > 80) $errors['sku'] = 'Enter a valid SKU.';
    if (!in_array($data['status'], ['active','inactive'], true)) $errors['status'] = 'Invalid product status.';

    try {
        $buyingCents = money_to_cents($data['buying_price']);
        $sellingCents = money_to_cents($data['selling_price']);
        $minimumMilli = quantity_to_milli($data['minimum_stock']);
        if ($buyingCents < 0 || $sellingCents < 0 || $minimumMilli < 0) throw new InvalidArgumentException();
    } catch (Throwable) {
        $errors['buying_price'] = 'Prices and quantities must be valid non-negative numbers.';
        $buyingCents = $sellingCents = $minimumMilli = 0;
    }

    $valid = db()->prepare('SELECT id FROM categories WHERE id=:id AND status="active"'); $valid->execute([':id'=>(int)$data['category_id']]); if (!$valid->fetchColumn()) $errors['category_id']='Choose a valid category.';
    $valid = db()->prepare('SELECT id FROM units WHERE id=:id AND is_active=1'); $valid->execute([':id'=>(int)$data['unit_id']]); if (!$valid->fetchColumn()) $errors['unit_id']='Choose a valid unit.';
    if ((float) $product['current_stock'] != 0.0 && (int) $data['unit_id'] !== (int) $product['unit_id']) $errors['unit_id'] = 'You cannot change the selling unit while this product has stock on hand.';
    if ((float) $product['current_stock'] != 0.0 && $data['track_stock'] === 0) $errors['track_stock'] = 'Clear the stock balance before disabling stock tracking.';
    if ($data['default_supplier_id'] !== '') { $valid=db()->prepare('SELECT id FROM suppliers WHERE id=:id AND status="active"'); $valid->execute([':id'=>(int)$data['default_supplier_id']]); if(!$valid->fetchColumn())$errors['default_supplier_id']='Choose a valid supplier.'; }

    $dup = db()->prepare('SELECT id, sku, barcode FROM products WHERE id <> :id AND (sku = :sku OR (barcode IS NOT NULL AND barcode = :barcode)) LIMIT 1');
    $dup->execute([':id'=>$productId, ':sku'=>$data['sku'], ':barcode'=>$data['barcode'] !== '' ? $data['barcode'] : '__NO_BARCODE__']);
    if ($existing=$dup->fetch()) { if($existing['sku']===$data['sku'])$errors['sku']='That SKU is already in use.'; if($data['barcode']!==''&&$existing['barcode']===$data['barcode'])$errors['barcode']='That barcode is already in use.'; }

    $originalImagePath = (string) ($product['image_path'] ?? '');
    $imagePath = $originalImagePath;
    $newImageUploaded = false;
    if (!$errors && isset($_FILES['image']) && (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
        try { $imagePath = (string) save_product_image($_FILES['image']); $newImageUploaded = true; } catch(Throwable $e){ $errors['image']=$e->getMessage(); }
    }

    if (!$errors) {
        try {
            $update = db()->prepare(
                'UPDATE products SET category_id=:category_id, unit_id=:unit_id, default_supplier_id=:supplier_id,
                 name=:name, sku=:sku, barcode=:barcode, description=:description, buying_price=:buying_price,
                 selling_price=:selling_price, minimum_stock=:minimum_stock, image_path=:image_path,
                 track_stock=:track_stock, status=:status WHERE id=:id'
            );
            $update->execute([
                ':category_id'=>(int)$data['category_id'], ':unit_id'=>(int)$data['unit_id'], ':supplier_id'=>$data['default_supplier_id']!==''?(int)$data['default_supplier_id']:null,
                ':name'=>$data['name'], ':sku'=>$data['sku'], ':barcode'=>$data['barcode']!==''?$data['barcode']:null, ':description'=>$data['description']!==''?$data['description']:null,
                ':buying_price'=>cents_to_decimal($buyingCents), ':selling_price'=>cents_to_decimal($sellingCents), ':minimum_stock'=>milli_to_decimal($minimumMilli),
                ':image_path'=>$imagePath?:null, ':track_stock'=>$data['track_stock'], ':status'=>$data['status'], ':id'=>$productId,
            ]);

            if ($newImageUploaded && $originalImagePath && str_starts_with($originalImagePath, 'uploads/products/')) {
                $oldFile = BASE_PATH . '/' . ltrim($originalImagePath, '/');
                if (is_file($oldFile)) @unlink($oldFile);
            }

            audit_log(
                'product_updated',
                'products',
                $productId,
                'Product updated: ' . $data['name'] . '.',
                ['name'=>$product['name'],'sku'=>$product['sku'],'selling_price'=>$product['selling_price'],'status'=>$product['status']],
                ['name'=>$data['name'],'sku'=>$data['sku'],'selling_price'=>cents_to_decimal($sellingCents),'status'=>$data['status']]
            );
            flash('success','Product updated successfully.');
            redirect('products/view.php?id='.$productId);
        } catch (Throwable $e) {
            if ($newImageUploaded && $imagePath) {
                $newFile = BASE_PATH . '/' . ltrim($imagePath, '/');
                if (is_file($newFile)) @unlink($newFile);
            }
            error_log('Product update failed: ' . $e->getMessage());
            $errors['general'] = 'The product could not be updated. Please try again.';
        }
    }
}

$pageTitle='Edit Product'; $pageStyles=['admin.css','commerce.css'];
require BASE_PATH.'/includes/header.php'; require BASE_PATH.'/includes/sidebar.php'; $isEdit=true;
?>
<div class="app-main"><?php require BASE_PATH.'/includes/navbar.php'; ?><main class="content">
<div class="page-toolbar"><div><a class="back-link" href="<?= e(app_url('products/view.php?id='.$productId)) ?>">← Back to product</a><h2>Edit product</h2><p>Stock quantities are changed only through controlled inventory movements.</p></div></div>
<?php if(isset($errors['general'])):?><div class="app-alert app-alert--danger"><?=e($errors['general'])?></div><?php endif;?>
<form method="post" enctype="multipart/form-data" class="form-card"><?=csrf_field()?><?php require __DIR__.'/_form.php';?><div class="form-actions"><a class="button button--ghost" href="<?=e(app_url('products/view.php?id='.$productId))?>">Cancel</a><button class="button button--primary" type="submit">Save changes</button></div></form>
</main></div><?php require BASE_PATH.'/includes/footer.php';?>
