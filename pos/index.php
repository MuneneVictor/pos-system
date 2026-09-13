<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_permission('pos.use');

$paymentMethods = db()->query(
    'SELECT id,name,code,method_type,requires_reference
     FROM payment_methods
     WHERE is_active=1
     ORDER BY sort_order,name'
)->fetchAll();

if (!user_can('credit.sell')) {
    $paymentMethods = array_values(array_filter(
        $paymentMethods,
        static fn(array $m): bool => $m['method_type'] !== 'credit'
    ));
}

$heldSale = null;
$heldId = max(0, (int) ($_GET['held'] ?? 0));
if ($heldId > 0 && user_can('pos.hold')) {
    $stmt = db()->prepare(
        'SELECT s.id,s.sale_no,s.customer_id,s.notes,c.name customer_name,c.phone customer_phone
         FROM sales s
         LEFT JOIN customers c ON c.id=s.customer_id
         WHERE s.id=:id AND s.status="held"
         LIMIT 1'
    );
    $stmt->execute([':id' => $heldId]);
    $header = $stmt->fetch();

    if ($header) {
        $itemsStmt = db()->prepare(
            'SELECT si.product_id id,si.product_name_snapshot name,si.sku_snapshot sku,
                    si.unit_snapshot short_name,si.quantity,si.unit_price,si.discount_amount,
                    p.current_stock,p.track_stock,u.allows_decimal,p.image_path
             FROM sale_items si
             INNER JOIN products p ON p.id=si.product_id
             INNER JOIN units u ON u.id=p.unit_id
             WHERE si.sale_id=:sale_id
             ORDER BY si.id'
        );
        $itemsStmt->execute([':sale_id' => $heldId]);
        $header['items'] = $itemsStmt->fetchAll();
        $heldSale = $header;
    }
}

$recent = db()->query(
    'SELECT p.id,p.name,p.sku,p.selling_price,p.current_stock,p.image_path,p.track_stock,
            u.short_name,u.allows_decimal
     FROM products p
     INNER JOIN units u ON u.id=p.unit_id
     WHERE p.status="active"
     ORDER BY p.updated_at DESC
     LIMIT 12'
)->fetchAll();

$pageTitle = 'Point of Sale';
$pageStyles = ['admin.css', 'commerce.css', 'pos.css'];
$pageScripts = ['pos.js'];
require BASE_PATH . '/includes/header.php';
require BASE_PATH . '/includes/sidebar.php';
?>
<div class="app-main pos-app-main">
    <?php require BASE_PATH . '/includes/navbar.php'; ?>
    <main class="pos-workspace" data-pos-root>
        <section class="pos-catalogue">
            <div class="pos-search-bar">
                <div class="pos-search-input-wrap">
                    <span class="pos-search-icon">⌕</span>
                    <input id="pos-search" type="search" autocomplete="off"
                           placeholder="Scan barcode or search product / SKU" autofocus>
                    <kbd>F2</kbd>
                </div>
                <a class="button button--secondary" href="<?= e(app_url('pos/held_sales.php')) ?>">Held sales</a>
            </div>

            <div class="pos-search-status" data-search-status>
                <?= $heldSale ? 'Resuming ' . e($heldSale['sale_no']) : 'Start typing or scan a barcode' ?>
            </div>

            <div class="pos-product-grid" data-product-results>
                <?php foreach ($recent as $product): ?>
                    <button type="button" class="pos-product-card"
                            data-product='<?= e(json_encode($product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>'>
                        <div class="pos-product-image">
                            <?php if ($product['image_path']): ?>
                                <img src="<?= e(app_url($product['image_path'])) ?>" alt="">
                            <?php else: ?><span><?= e(initials($product['name'])) ?></span><?php endif; ?>
                        </div>
                        <div class="pos-product-copy">
                            <strong><?= e($product['name']) ?></strong>
                            <span><?= e($product['sku']) ?></span>
                            <b><?= e(money($product['selling_price'])) ?>/<?= e($product['short_name']) ?></b>
                            <?php if ((int) $product['track_stock'] === 1): ?>
                                <small>Stock <?= e(format_quantity($product['current_stock'])) ?> <?= e($product['short_name']) ?></small>
                            <?php else: ?><small>Stock not tracked</small><?php endif; ?>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="pos-cart-panel">
            <div class="pos-cart-head">
                <div><span class="eyebrow">Current sale</span><h2><?= $heldSale ? e($heldSale['sale_no']) : 'New sale' ?></h2></div>
                <button class="pos-clear-button" type="button" data-clear-cart>Clear</button>
            </div>

            <div class="pos-customer-box">
                <label for="customer-search">Customer <span>optional unless using credit</span></label>
                <div class="pos-customer-search">
                    <input id="customer-search" type="search" autocomplete="off" placeholder="Search customer name / phone">
                    <?php if (user_can('customers.create')): ?>
                        <a href="<?= e(app_url('customers/add.php')) ?>" target="_self" title="Add customer">+</a>
                    <?php endif; ?>
                </div>
                <div class="customer-search-results" data-customer-results></div>
                <div class="selected-customer" data-selected-customer hidden></div>
            </div>

            <div class="pos-cart-lines" data-cart-lines>
                <div class="pos-empty-cart" data-empty-cart>
                    <span>▤</span><strong>Cart is empty</strong><small>Add a product to begin.</small>
                </div>
            </div>

            <div class="pos-summary">
                <div><span>Subtotal</span><strong data-subtotal><?= e(APP_CURRENCY_SYMBOL) ?> 0.00</strong></div>
                <div><span>Discount</span><strong data-discount><?= e(APP_CURRENCY_SYMBOL) ?> 0.00</strong></div>
                <div class="pos-total-row"><span>Total</span><strong data-total><?= e(APP_CURRENCY_SYMBOL) ?> 0.00</strong></div>
            </div>

            <div class="pos-actions">
                <?php if (user_can('pos.hold')): ?>
                    <button type="button" class="button button--secondary" data-hold-sale>Hold sale</button>
                <?php endif; ?>
                <button type="button" class="button button--primary pos-pay-button" data-open-payment disabled>Pay & complete</button>
            </div>
        </aside>
    </main>
</div>

<div class="pos-modal" data-payment-modal hidden>
    <div class="pos-modal__backdrop" data-close-payment></div>
    <section class="pos-modal__panel">
        <div class="pos-modal__header">
            <div><span class="eyebrow">Payment</span><h2>Complete sale</h2></div>
            <button type="button" class="modal-close" data-close-payment>×</button>
        </div>

        <div class="pos-error" data-pos-error hidden></div>

        <div class="payment-total"><span>Amount due</span><strong data-payment-due><?= e(APP_CURRENCY_SYMBOL) ?> 0.00</strong></div>
        <div class="payment-rows" data-payment-rows></div>
        <button type="button" class="button button--secondary payment-add-row" data-add-payment>+ Split payment</button>
        <div class="field pos-notes-field"><label>Sale notes</label><textarea data-sale-notes rows="2" placeholder="Optional"></textarea></div>
        <div class="payment-balance"><span>Allocated</span><b data-payment-allocated><?= e(APP_CURRENCY_SYMBOL) ?> 0.00</b><span>Remaining</span><b data-payment-remaining><?= e(APP_CURRENCY_SYMBOL) ?> 0.00</b></div>
        <div class="pos-modal__actions"><button type="button" class="button button--ghost" data-close-payment>Cancel</button><button type="button" class="button button--primary" data-complete-sale>Complete sale</button></div>
    </section>
</div>

<script>
window.POS_CONFIG = <?= json_encode([
    'csrf' => csrf_token(),
    'currency' => APP_CURRENCY_SYMBOL,
    'appBase' => app_url(''),
    'searchUrl' => app_url('pos/search_products.php'),
    'customerSearchUrl' => app_url('customers/search.php'),
    'processUrl' => app_url('pos/process_sale.php'),
    'holdUrl' => app_url('pos/hold_sale.php'),
    'receiptBase' => app_url('pos/receipt.php?id='),
    'heldListUrl' => app_url('pos/held_sales.php'),
    'canDiscount' => user_can('pos.discount'),
    'canPriceOverride' => user_can('pos.price_override'),
    'paymentMethods' => $paymentMethods,
    'heldSale' => $heldSale,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php require BASE_PATH . '/includes/footer.php'; ?>
