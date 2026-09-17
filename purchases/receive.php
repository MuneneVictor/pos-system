<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_permission('purchases.receive');

$purchaseId = max(0, (int) ($_GET['id'] ?? 0));

$purchaseStmt = db()->prepare(
    'SELECT p.*, s.name AS supplier_name
     FROM purchases p
     INNER JOIN suppliers s ON s.id = p.supplier_id
     WHERE p.id = :id
     LIMIT 1'
);
$purchaseStmt->execute([':id' => $purchaseId]);
$purchase = $purchaseStmt->fetch();

if (!$purchase || !in_array($purchase['status'], ['ordered', 'partially_received'], true)) {
    flash('error', 'This purchase cannot receive stock.');
    redirect('purchases/view?id=' . $purchaseId);
}

$itemsStmt = db()->prepare(
    'SELECT pi.*, pr.name AS product_name, pr.sku, pr.current_stock, pr.average_cost, pr.track_stock,
            u.short_name, u.allows_decimal
     FROM purchase_items pi
     INNER JOIN products pr ON pr.id = pi.product_id
     INNER JOIN units u ON u.id = pr.unit_id
     WHERE pi.purchase_id = :id
     ORDER BY pi.id'
);
$itemsStmt->execute([':id' => $purchaseId]);
$items = $itemsStmt->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $receiveInput = $_POST['receive_qty'] ?? [];
    if (!is_array($receiveInput)) {
        $receiveInput = [];
    }

    $requested = [];
    $any = false;

    foreach ($items as $item) {
        $raw = (string) ($receiveInput[$item['id']] ?? '0');
        try {
            $quantityMilli = quantity_to_milli($raw);
            $orderedMilli = quantity_to_milli($item['quantity']);
            $receivedMilli = quantity_to_milli($item['quantity_received']);
            $remainingMilli = $orderedMilli - $receivedMilli;

            if ($quantityMilli < 0 || $quantityMilli > $remainingMilli) {
                throw new RuntimeException(
                    'Receive quantity cannot exceed the remaining quantity for ' . $item['product_name'] . '.'
                );
            }
            if ((int) $item['allows_decimal'] === 0 && $quantityMilli % 1000 !== 0) {
                throw new RuntimeException($item['product_name'] . ' accepts whole quantities only.');
            }

            if ($quantityMilli > 0) {
                $any = true;
            }
            $requested[(int) $item['id']] = $quantityMilli;
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage() ?: 'Invalid receive quantity.';
        }
    }

    if (!$any) {
        $errors[] = 'Enter at least one quantity to receive.';
    }

    if (!$errors) {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            $purchaseLock = $pdo->prepare(
                'SELECT id, status, branch_id, purchase_no
                 FROM purchases
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE'
            );
            $purchaseLock->execute([':id' => $purchaseId]);
            $lockedPurchase = $purchaseLock->fetch();

            if (!$lockedPurchase || !in_array($lockedPurchase['status'], ['ordered', 'partially_received'], true)) {
                throw new RuntimeException('This purchase was already fully received or changed by another user.');
            }

            foreach ($items as $displayItem) {
                $quantityMilli = $requested[(int) $displayItem['id']] ?? 0;
                if ($quantityMilli <= 0) {
                    continue;
                }

                // Re-read and lock each purchase item inside the transaction so two users
                // cannot receive the same remaining quantity at the same time.
                $itemLock = $pdo->prepare(
                    'SELECT pi.id, pi.product_id, pi.quantity, pi.quantity_received, pi.unit_cost,
                            pr.name AS product_name
                     FROM purchase_items pi
                     INNER JOIN products pr ON pr.id = pi.product_id
                     WHERE pi.id = :item_id AND pi.purchase_id = :purchase_id
                     LIMIT 1
                     FOR UPDATE'
                );
                $itemLock->execute([
                    ':item_id' => (int) $displayItem['id'],
                    ':purchase_id' => $purchaseId,
                ]);
                $item = $itemLock->fetch();

                if (!$item) {
                    throw new RuntimeException('A purchase item is no longer available.');
                }

                $orderedMilli = quantity_to_milli($item['quantity']);
                $alreadyReceivedMilli = quantity_to_milli($item['quantity_received']);
                $remainingMilli = $orderedMilli - $alreadyReceivedMilli;

                if ($quantityMilli > $remainingMilli) {
                    throw new RuntimeException(
                        $item['product_name'] . ' now has only ' . format_quantity(milli_to_decimal($remainingMilli)) . ' remaining to receive.'
                    );
                }

                $productLock = $pdo->prepare(
                    'SELECT current_stock, average_cost, track_stock
                     FROM products
                     WHERE id = :id
                     LIMIT 1
                     FOR UPDATE'
                );
                $productLock->execute([':id' => (int) $item['product_id']]);
                $product = $productLock->fetch();

                if (!$product) {
                    throw new RuntimeException('Product not found while receiving stock.');
                }

                $beforeMilli = quantity_to_milli($product['current_stock']);
                $afterMilli = $beforeMilli;
                $oldCost4 = decimal4_to_units($product['average_cost']);
                $newCost4 = decimal4_to_units($item['unit_cost']);

                if ((int) $product['track_stock'] === 1) {
                    $afterMilli = $beforeMilli + $quantityMilli;
                    $weightedCost4 = $afterMilli > 0
                        ? rounded_divide(
                            ($beforeMilli * $oldCost4) + ($quantityMilli * $newCost4),
                            $afterMilli
                        )
                        : $newCost4;

                    $productUpdate = $pdo->prepare(
                        'UPDATE products
                         SET current_stock = :stock,
                             average_cost = :average_cost,
                             buying_price = :buying_price
                         WHERE id = :id'
                    );
                    $productUpdate->execute([
                        ':stock' => milli_to_decimal($afterMilli),
                        ':average_cost' => units4_to_decimal($weightedCost4),
                        ':buying_price' => cents_to_decimal(rounded_divide($newCost4, 100)),
                        ':id' => (int) $item['product_id'],
                    ]);

                    $movement = $pdo->prepare(
                        'INSERT INTO stock_movements
                         (branch_id, product_id, movement_type, reference_type, reference_id, reference_no,
                          quantity_change, unit_cost, stock_before, stock_after, reason, created_by)
                         VALUES
                         (:branch, :product, "purchase", "purchase", :reference_id, :reference_no,
                          :quantity, :unit_cost, :stock_before, :stock_after, :reason, :user)'
                    );
                    $movement->execute([
                        ':branch' => (int) $lockedPurchase['branch_id'],
                        ':product' => (int) $item['product_id'],
                        ':reference_id' => $purchaseId,
                        ':reference_no' => $lockedPurchase['purchase_no'],
                        ':quantity' => milli_to_decimal($quantityMilli),
                        ':unit_cost' => $item['unit_cost'],
                        ':stock_before' => milli_to_decimal($beforeMilli),
                        ':stock_after' => milli_to_decimal($afterMilli),
                        ':reason' => 'Purchase receipt from ' . $purchase['supplier_name'],
                        ':user' => (int) current_user()['id'],
                    ]);
                } else {
                    // Non-stock-tracked items still need their latest/historical cost basis updated.
                    $productUpdate = $pdo->prepare(
                        'UPDATE products
                         SET average_cost = :average_cost,
                             buying_price = :buying_price
                         WHERE id = :id'
                    );
                    $productUpdate->execute([
                        ':average_cost' => units4_to_decimal($newCost4),
                        ':buying_price' => cents_to_decimal(rounded_divide($newCost4, 100)),
                        ':id' => (int) $item['product_id'],
                    ]);
                }

                $newReceivedMilli = $alreadyReceivedMilli + $quantityMilli;
                $itemUpdate = $pdo->prepare(
                    'UPDATE purchase_items SET quantity_received = :received WHERE id = :id'
                );
                $itemUpdate->execute([
                    ':received' => milli_to_decimal($newReceivedMilli),
                    ':id' => (int) $item['id'],
                ]);
            }

            $remainingStmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM purchase_items
                 WHERE purchase_id = :id
                   AND quantity_received < quantity'
            );
            $remainingStmt->execute([':id' => $purchaseId]);
            $remainingCount = (int) $remainingStmt->fetchColumn();
            $newStatus = $remainingCount === 0 ? 'received' : 'partially_received';

            $purchaseUpdate = $pdo->prepare(
                'UPDATE purchases
                 SET status = :status,
                     received_by = :user,
                     received_at = IF(:received_flag = 1, NOW(), received_at)
                 WHERE id = :id'
            );
            $purchaseUpdate->execute([
                ':status' => $newStatus,
                ':received_flag' => $newStatus === 'received' ? 1 : 0,
                ':user' => (int) current_user()['id'],
                ':id' => $purchaseId,
            ]);

            transactional_audit(
                $pdo,
                'purchase_received',
                'purchases',
                $purchaseId,
                'Stock received for purchase ' . $purchase['purchase_no'] . '.',
                null,
                ['status' => $newStatus]
            );

            $pdo->commit();
            flash('success', 'Stock received and inventory updated.');
            redirect('purchases/view?id=' . $purchaseId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Purchase receive failed: ' . $exception->getMessage());
            $errors[] = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Stock could not be received. No inventory changes were saved.';
        }
    }
}

$pageTitle = 'Receive ' . $purchase['purchase_no'];
$pageStyles = ['admin.css', 'commerce.css'];
require BASE_PATH . '/includes/header.php';
require BASE_PATH . '/includes/sidebar.php';
?>
<div class="app-main">
    <?php require BASE_PATH . '/includes/navbar.php'; ?>
    <main class="content">
        <div class="page-toolbar">
            <div>
                <a class="back-link" href="<?= e(app_url('purchases/view?id=' . $purchaseId)) ?>">← Purchase</a>
                <h2>Receive stock</h2>
                <p><?= e($purchase['purchase_no']) ?> · <?= e($purchase['supplier_name']) ?></p>
            </div>
        </div>

        <?php foreach ($errors as $error): ?>
            <div class="app-alert app-alert--danger"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" class="form-card">
            <?= csrf_field() ?>
            <div class="table-wrap">
                <table class="data-table receive-table">
                    <thead><tr><th>Product</th><th>Ordered</th><th>Already received</th><th>Remaining</th><th>Receive now</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item):
                        $remaining = quantity_to_milli($item['quantity']) - quantity_to_milli($item['quantity_received']);
                    ?>
                        <tr>
                            <td><strong><?= e($item['product_name']) ?></strong><small class="table-note"><?= e($item['sku']) ?></small></td>
                            <td><?= e(format_quantity($item['quantity'])) ?> <?= e($item['short_name']) ?></td>
                            <td><?= e(format_quantity($item['quantity_received'])) ?> <?= e($item['short_name']) ?></td>
                            <td><?= e(format_quantity(milli_to_decimal($remaining))) ?> <?= e($item['short_name']) ?></td>
                            <td>
                                <div class="qty-with-unit">
                                    <input type="number"
                                           name="receive_qty[<?= e((string) $item['id']) ?>]"
                                           min="0"
                                           max="<?= e(milli_to_decimal($remaining)) ?>"
                                           step="<?= (int) $item['allows_decimal'] === 1 ? '0.001' : '1' ?>"
                                           value="<?= e(milli_to_decimal($remaining)) ?>">
                                    <span><?= e($item['short_name']) ?></span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <a class="button button--ghost" href="<?= e(app_url('purchases/view?id=' . $purchaseId)) ?>">Cancel</a>
                <button class="button button--primary" type="submit">Receive stock</button>
            </div>
        </form>
    </main>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
