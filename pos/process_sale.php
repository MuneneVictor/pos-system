<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_permission('pos.use');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

$payload = json_input();
require_json_csrf($payload);

$rawItems = $payload['items'] ?? [];
$rawPayments = $payload['payments'] ?? [];
$customerId = max(0, (int) ($payload['customer_id'] ?? 0));
$heldSaleId = max(0, (int) ($payload['held_sale_id'] ?? 0));
$notes = trim((string) ($payload['notes'] ?? ''));

if (!is_array($rawItems) || !$rawItems) {
    json_response(['ok' => false, 'message' => 'The cart is empty.'], 422);
}
if (!is_array($rawPayments) || !$rawPayments) {
    json_response(['ok' => false, 'message' => 'Add at least one payment.'], 422);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $branchId = current_branch_id();
    $userId = (int) current_user()['id'];

    $saleNo = '';
    if ($heldSaleId > 0) {
        if (!user_can('pos.hold')) {
            throw new RuntimeException('You do not have permission to complete a held sale.');
        }
        $heldStmt = $pdo->prepare(
            'SELECT id,sale_no FROM sales WHERE id=:id AND status="held" LIMIT 1 FOR UPDATE'
        );
        $heldStmt->execute([':id' => $heldSaleId]);
        $held = $heldStmt->fetch();
        if (!$held) {
            throw new RuntimeException('The held sale is no longer available.');
        }
        $saleNo = (string) $held['sale_no'];
    } else {
        $saleNo = next_document_number($pdo, 'sale');
    }

    $items = [];
    $seen = [];
    $subtotalCents = 0;
    $discountCents = 0;
    $totalCents = 0;
    $cogsCents = 0;

    foreach ($rawItems as $raw) {
        if (!is_array($raw)) continue;
        $productId = max(0, (int) ($raw['product_id'] ?? 0));
        if ($productId <= 0) continue;
        if (isset($seen[$productId])) {
            throw new RuntimeException('A product appears more than once in the cart.');
        }
        $seen[$productId] = true;

        $productStmt = $pdo->prepare(
            'SELECT p.id,p.name,p.sku,p.selling_price,p.average_cost,p.current_stock,p.track_stock,p.status,
                    u.short_name,u.allows_decimal
             FROM products p
             INNER JOIN units u ON u.id=p.unit_id
             WHERE p.id=:id
             LIMIT 1
             FOR UPDATE'
        );
        $productStmt->execute([':id' => $productId]);
        $product = $productStmt->fetch();

        if (!$product || $product['status'] !== 'active') {
            throw new RuntimeException('One product in the cart is no longer available.');
        }

        $qtyMilli = quantity_to_milli((string) ($raw['quantity'] ?? '0'));
        if ($qtyMilli <= 0) {
            throw new RuntimeException('Sale quantities must be greater than zero.');
        }
        if ((int) $product['allows_decimal'] === 0 && $qtyMilli % 1000 !== 0) {
            throw new RuntimeException($product['name'] . ' accepts whole quantities only.');
        }

        $standardPriceCents = money_to_cents($product['selling_price']);
        $requestedPriceCents = money_to_cents((string) ($raw['unit_price'] ?? $product['selling_price']));
        $unitPriceCents = user_can('pos.price_override') ? $requestedPriceCents : $standardPriceCents;
        if ($unitPriceCents < 0) {
            throw new RuntimeException('Selling price cannot be negative.');
        }

        $lineSubtotal = line_total_cents($qtyMilli, $unitPriceCents);
        $requestedDiscount = money_to_cents((string) ($raw['discount_amount'] ?? '0'));
        $lineDiscount = user_can('pos.discount') ? $requestedDiscount : 0;
        if ($lineDiscount < 0 || $lineDiscount > $lineSubtotal) {
            throw new RuntimeException('A line discount is invalid.');
        }
        $lineTotal = $lineSubtotal - $lineDiscount;
        $cost4 = decimal4_to_units($product['average_cost']);
        $lineCogs = line_cost_cents($qtyMilli, $cost4);
        $lineProfit = $lineTotal - $lineCogs;

        $beforeMilli = quantity_to_milli($product['current_stock']);
        $afterMilli = $beforeMilli;
        if ((int) $product['track_stock'] === 1) {
            $afterMilli = $beforeMilli - $qtyMilli;
            if ($afterMilli < 0 && !allow_negative_stock()) {
                throw new RuntimeException(
                    $product['name'] . ' has only ' . format_quantity($product['current_stock']) . ' ' . $product['short_name'] . ' available.'
                );
            }
        }

        $subtotalCents += $lineSubtotal;
        $discountCents += $lineDiscount;
        $totalCents += $lineTotal;
        $cogsCents += $lineCogs;

        $items[] = [
            'product' => $product,
            'quantity_milli' => $qtyMilli,
            'unit_price_cents' => $unitPriceCents,
            'cost4' => $cost4,
            'discount_cents' => $lineDiscount,
            'line_total_cents' => $lineTotal,
            'cogs_cents' => $lineCogs,
            'profit_cents' => $lineProfit,
            'stock_before_milli' => $beforeMilli,
            'stock_after_milli' => $afterMilli,
        ];
    }

    if (!$items) {
        throw new RuntimeException('The cart does not contain any valid products.');
    }

    // Validate customer before payment processing.
    $customer = null;
    if ($customerId > 0) {
        $customerStmt = $pdo->prepare(
            'SELECT id,name,credit_limit,account_balance,status
             FROM customers WHERE id=:id LIMIT 1 FOR UPDATE'
        );
        $customerStmt->execute([':id' => $customerId]);
        $customer = $customerStmt->fetch();
        if (!$customer || $customer['status'] !== 'active') {
            throw new RuntimeException('The selected customer is unavailable.');
        }
    }

    $payments = [];
    $allocatedCents = 0;
    $actualReceivedCents = 0;
    $creditCents = 0;

    foreach ($rawPayments as $rawPayment) {
        if (!is_array($rawPayment)) continue;
        $methodId = max(0, (int) ($rawPayment['payment_method_id'] ?? 0));
        $amountCents = money_to_cents((string) ($rawPayment['amount'] ?? '0'));
        if ($amountCents <= 0) continue;

        $method = payment_method_by_id($pdo, $methodId);
        if (!$method) {
            throw new RuntimeException('One payment method is unavailable.');
        }
        $reference = trim((string) ($rawPayment['reference_no'] ?? ''));
        if ((int) $method['requires_reference'] === 1 && $reference === '') {
            throw new RuntimeException($method['name'] . ' requires a payment reference.');
        }
        if ($method['method_type'] === 'credit') {
            if (!user_can('credit.sell')) {
                throw new RuntimeException('You do not have permission to make credit sales.');
            }
            if (!$customer) {
                throw new RuntimeException('Select a customer before using credit.');
            }
            $creditCents += $amountCents;
        } else {
            $actualReceivedCents += $amountCents;
        }

        $allocatedCents += $amountCents;
        $payments[] = [
            'method' => $method,
            'amount_cents' => $amountCents,
            'reference_no' => $reference,
        ];
    }

    if ($allocatedCents !== $totalCents) {
        throw new RuntimeException(
            'Payments must equal the sale total exactly. Amount remaining: ' . money(cents_to_decimal($totalCents - $allocatedCents))
        );
    }

    if ($creditCents > 0 && $customer) {
        $currentBalance = money_to_cents($customer['account_balance']);
        $creditLimit = money_to_cents($customer['credit_limit']);
        $projected = $currentBalance + $creditCents;
        if ($creditLimit > 0 && $projected > $creditLimit) {
            throw new RuntimeException('This credit sale would exceed the customer’s credit limit.');
        }
    }

    $grossProfitCents = $totalCents - $cogsCents;
    $paymentStatus = $creditCents > 0
        ? ($actualReceivedCents > 0 ? 'partial' : 'unpaid')
        : 'paid';

    if ($heldSaleId > 0) {
        $update = $pdo->prepare(
            'UPDATE sales SET branch_id=:branch,customer_id=:customer,sale_date=NOW(),status="completed",
             payment_status=:payment_status,subtotal=:subtotal,discount_amount=:discount,total_amount=:total,
             cogs_amount=:cogs,gross_profit=:profit,amount_paid=:paid,balance_due=:balance,change_due=0.00,
             notes=:notes,completed_at=NOW()
             WHERE id=:id AND status="held"'
        );
        $update->execute([
            ':branch' => $branchId, ':customer' => $customerId > 0 ? $customerId : null,
            ':payment_status' => $paymentStatus, ':subtotal' => cents_to_decimal($subtotalCents),
            ':discount' => cents_to_decimal($discountCents), ':total' => cents_to_decimal($totalCents),
            ':cogs' => cents_to_decimal($cogsCents), ':profit' => cents_to_decimal($grossProfitCents),
            ':paid' => cents_to_decimal($actualReceivedCents), ':balance' => cents_to_decimal($creditCents),
            ':notes' => $notes !== '' ? $notes : null, ':id' => $heldSaleId,
        ]);
        $saleId = $heldSaleId;
        $pdo->prepare('DELETE FROM sale_items WHERE sale_id=:id')->execute([':id' => $saleId]);
        $pdo->prepare('DELETE FROM sale_payments WHERE sale_id=:id')->execute([':id' => $saleId]);
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO sales
             (branch_id,sale_no,customer_id,sale_date,status,payment_status,subtotal,discount_amount,total_amount,
              cogs_amount,gross_profit,amount_paid,balance_due,change_due,notes,created_by,completed_at)
             VALUES (:branch,:sale_no,:customer,NOW(),"completed",:payment_status,:subtotal,:discount,:total,
                     :cogs,:profit,:paid,:balance,0.00,:notes,:user,NOW())'
        );
        $insert->execute([
            ':branch' => $branchId, ':sale_no' => $saleNo, ':customer' => $customerId > 0 ? $customerId : null,
            ':payment_status' => $paymentStatus, ':subtotal' => cents_to_decimal($subtotalCents),
            ':discount' => cents_to_decimal($discountCents), ':total' => cents_to_decimal($totalCents),
            ':cogs' => cents_to_decimal($cogsCents), ':profit' => cents_to_decimal($grossProfitCents),
            ':paid' => cents_to_decimal($actualReceivedCents), ':balance' => cents_to_decimal($creditCents),
            ':notes' => $notes !== '' ? $notes : null, ':user' => $userId,
        ]);
        $saleId = (int) $pdo->lastInsertId();
    }

    foreach ($items as $item) {
        $product = $item['product'];
        $saleItem = $pdo->prepare(
            'INSERT INTO sale_items
             (sale_id,product_id,product_name_snapshot,sku_snapshot,unit_snapshot,quantity,unit_price,cost_price,
              discount_amount,line_total,cogs_amount,profit_amount)
             VALUES (:sale,:product,:name,:sku,:unit,:quantity,:price,:cost,:discount,:total,:cogs,:profit)'
        );
        $saleItem->execute([
            ':sale' => $saleId, ':product' => (int) $product['id'], ':name' => $product['name'],
            ':sku' => $product['sku'], ':unit' => $product['short_name'],
            ':quantity' => milli_to_decimal($item['quantity_milli']),
            ':price' => cents_to_decimal($item['unit_price_cents']),
            ':cost' => units4_to_decimal($item['cost4']), ':discount' => cents_to_decimal($item['discount_cents']),
            ':total' => cents_to_decimal($item['line_total_cents']), ':cogs' => cents_to_decimal($item['cogs_cents']),
            ':profit' => cents_to_decimal($item['profit_cents']),
        ]);

        if ((int) $product['track_stock'] === 1) {
            $stockUpdate = $pdo->prepare('UPDATE products SET current_stock=:stock WHERE id=:id');
            $stockUpdate->execute([
                ':stock' => milli_to_decimal($item['stock_after_milli']),
                ':id' => (int) $product['id'],
            ]);

            $movement = $pdo->prepare(
                'INSERT INTO stock_movements
                 (branch_id,product_id,movement_type,reference_type,reference_id,reference_no,quantity_change,
                  unit_cost,stock_before,stock_after,reason,created_by)
                 VALUES (:branch,:product,"sale","sale",:sale,:sale_no,:quantity,:cost,:before,:after,:reason,:user)'
            );
            $movement->execute([
                ':branch' => $branchId, ':product' => (int) $product['id'], ':sale' => $saleId,
                ':sale_no' => $saleNo, ':quantity' => milli_to_decimal(-$item['quantity_milli']),
                ':cost' => units4_to_decimal($item['cost4']), ':before' => milli_to_decimal($item['stock_before_milli']),
                ':after' => milli_to_decimal($item['stock_after_milli']), ':reason' => 'POS sale', ':user' => $userId,
            ]);
        }
    }

    foreach ($payments as $payment) {
        $paymentInsert = $pdo->prepare(
            'INSERT INTO sale_payments(sale_id,payment_method_id,amount,reference_no,paid_at,received_by)
             VALUES (:sale,:method,:amount,:reference,NOW(),:user)'
        );
        $paymentInsert->execute([
            ':sale' => $saleId, ':method' => (int) $payment['method']['id'],
            ':amount' => cents_to_decimal($payment['amount_cents']),
            ':reference' => $payment['reference_no'] !== '' ? $payment['reference_no'] : null,
            ':user' => $userId,
        ]);

        if ($payment['method']['method_type'] === 'cash') {
            $cash = $pdo->prepare(
                'INSERT INTO cash_movements
                 (cash_session_id,branch_id,movement_type,reference_type,reference_id,amount,description,created_by)
                 VALUES (:session,:branch,"sale","sale",:sale,:amount,:description,:user)'
            );
            $cash->execute([
                ':session' => active_cash_session_id($pdo, $userId, $branchId), ':branch' => $branchId,
                ':sale' => $saleId, ':amount' => cents_to_decimal($payment['amount_cents']),
                ':description' => 'Cash sale ' . $saleNo, ':user' => $userId,
            ]);
        }
    }

    if ($creditCents > 0 && $customer) {
        $oldBalance = money_to_cents($customer['account_balance']);
        $newBalance = $oldBalance + $creditCents;
        $customerUpdate = $pdo->prepare('UPDATE customers SET account_balance=:balance WHERE id=:id');
        $customerUpdate->execute([':balance' => cents_to_decimal($newBalance), ':id' => $customerId]);

        $ledger = $pdo->prepare(
            'INSERT INTO customer_ledger
             (customer_id,entry_date,entry_type,sale_id,reference_no,description,debit_amount,credit_amount,balance_after,created_by)
             VALUES (:customer,NOW(),"credit_sale",:sale,:reference,:description,:debit,0.00,:balance,:user)'
        );
        $ledger->execute([
            ':customer' => $customerId, ':sale' => $saleId, ':reference' => $saleNo,
            ':description' => 'Credit sale ' . $saleNo, ':debit' => cents_to_decimal($creditCents),
            ':balance' => cents_to_decimal($newBalance), ':user' => $userId,
        ]);
    }

    transactional_audit(
        $pdo,
        'sale_created',
        'sales',
        $saleId,
        'Sale completed: ' . $saleNo . '.',
        null,
        [
            'total' => cents_to_decimal($totalCents),
            'cogs' => cents_to_decimal($cogsCents),
            'gross_profit' => cents_to_decimal($grossProfitCents),
            'cash_received' => cents_to_decimal($actualReceivedCents),
            'credit' => cents_to_decimal($creditCents),
        ]
    );

    $pdo->commit();
    json_response(['ok' => true, 'sale_id' => $saleId, 'sale_no' => $saleNo]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('POS sale failed: ' . $exception->getMessage());
    $message = $exception instanceof RuntimeException || $exception instanceof InvalidArgumentException
        ? $exception->getMessage()
        : 'The sale could not be completed. No stock or payment changes were saved.';
    json_response(['ok' => false, 'message' => $message], 422);
}
