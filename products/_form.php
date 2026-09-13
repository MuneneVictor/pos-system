<?php
/** @var array $data */
/** @var array $errors */
/** @var array $categories */
/** @var array $units */
/** @var array $suppliers */
/** @var bool $isEdit */
?>
<div class="form-section">
    <div class="form-section__heading"><span class="eyebrow">Product</span><h3>Basic details</h3></div>
    <div class="form-grid">
        <div class="field field--span-2"><label for="name">Product name</label><input id="name" name="name" value="<?= e($data['name']) ?>" maxlength="180" required autofocus><?php if (isset($errors['name'])): ?><small class="field-error"><?= e($errors['name']) ?></small><?php endif; ?></div>
        <div class="field"><label for="sku">SKU</label><input id="sku" name="sku" value="<?= e($data['sku']) ?>" maxlength="80" required><?php if (isset($errors['sku'])): ?><small class="field-error"><?= e($errors['sku']) ?></small><?php endif; ?></div>
        <div class="field"><label for="barcode">Barcode</label><input id="barcode" name="barcode" value="<?= e($data['barcode']) ?>" maxlength="120" autocomplete="off"><?php if (isset($errors['barcode'])): ?><small class="field-error"><?= e($errors['barcode']) ?></small><?php endif; ?></div>
        <div class="field"><label for="category_id">Category</label><select id="category_id" name="category_id" required><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?= e((string) $category['id']) ?>" <?= (string) $data['category_id'] === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select><?php if (isset($errors['category_id'])): ?><small class="field-error"><?= e($errors['category_id']) ?></small><?php endif; ?></div>
        <div class="field"><label for="unit_id">Selling unit</label><select id="unit_id" name="unit_id" required><option value="">Select unit</option><?php foreach ($units as $unit): ?><option value="<?= e((string) $unit['id']) ?>" data-decimal="<?= (int) $unit['allows_decimal'] ?>" <?= (string) $data['unit_id'] === (string) $unit['id'] ? 'selected' : '' ?>><?= e($unit['name']) ?> (<?= e($unit['short_name']) ?>)</option><?php endforeach; ?></select><?php if (isset($errors['unit_id'])): ?><small class="field-error"><?= e($errors['unit_id']) ?></small><?php endif; ?></div>
        <div class="field field--span-2"><label for="description">Description</label><textarea id="description" name="description" rows="4" maxlength="5000"><?= e($data['description']) ?></textarea></div>
    </div>
</div>

<div class="form-section">
    <div class="form-section__heading"><span class="eyebrow">Pricing & stock</span><h3>Cost, selling price and stock controls</h3></div>
    <div class="form-grid">
        <div class="field"><label for="buying_price">Buying price</label><input id="buying_price" name="buying_price" type="number" min="0" step="0.01" value="<?= e($data['buying_price']) ?>" required><?php if (isset($errors['buying_price'])): ?><small class="field-error"><?= e($errors['buying_price']) ?></small><?php endif; ?></div>
        <div class="field"><label for="selling_price">Selling price</label><input id="selling_price" name="selling_price" type="number" min="0" step="0.01" value="<?= e($data['selling_price']) ?>" required><?php if (isset($errors['selling_price'])): ?><small class="field-error"><?= e($errors['selling_price']) ?></small><?php endif; ?></div>
        <div class="field"><label for="minimum_stock">Minimum stock level</label><input id="minimum_stock" name="minimum_stock" type="number" min="0" step="0.001" value="<?= e($data['minimum_stock']) ?>" required><?php if (isset($errors['minimum_stock'])): ?><small class="field-error"><?= e($errors['minimum_stock']) ?></small><?php endif; ?></div>
        <?php if (!$isEdit): ?>
            <div class="field"><label for="opening_stock">Opening stock</label><input id="opening_stock" name="opening_stock" type="number" min="0" step="0.001" value="<?= e($data['opening_stock']) ?>"><small class="field-help">Creates an opening-stock movement.</small><?php if (isset($errors['opening_stock'])): ?><small class="field-error"><?= e($errors['opening_stock']) ?></small><?php endif; ?></div>
        <?php else: ?>
            <div class="field"><label>Current stock</label><input value="<?= e(format_quantity($data['current_stock'])) ?>" disabled><small class="field-help">Use Inventory → Stock Adjustment to change stock.</small></div>
        <?php endif; ?>
        <div class="field field--span-2"><label for="default_supplier_id">Default supplier</label><select id="default_supplier_id" name="default_supplier_id"><option value="">No default supplier</option><?php foreach ($suppliers as $supplier): ?><option value="<?= e((string) $supplier['id']) ?>" <?= (string) $data['default_supplier_id'] === (string) $supplier['id'] ? 'selected' : '' ?>><?= e($supplier['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field field--span-2"><label class="checkbox-row"><input type="checkbox" name="track_stock" value="1" <?= (int) $data['track_stock'] === 1 ? 'checked' : '' ?>> Track stock for this product</label><?php if (isset($errors['track_stock'])): ?><small class="field-error"><?= e($errors['track_stock']) ?></small><?php endif; ?></div>
    </div>
</div>

<div class="form-section">
    <div class="form-section__heading"><span class="eyebrow">Presentation</span><h3>Image and availability</h3></div>
    <div class="form-grid">
        <div class="field field--span-2"><label for="image">Product image</label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><small class="field-help">JPG, PNG or WEBP, maximum 5 MB.</small><?php if (isset($errors['image'])): ?><small class="field-error"><?= e($errors['image']) ?></small><?php endif; ?></div>
        <div class="field field--span-2"><label for="status">Status</label><select id="status" name="status"><option value="active" <?= $data['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $data['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
    </div>
</div>
