<?php
declare(strict_types=1);

/**
 * Shared business-domain helpers for stock, money, document numbers and uploads.
 * Financial database values remain decimal strings; authoritative calculations use integers.
 */

function current_branch_id(): int
{
    $user = current_user();
    $branchId = (int) ($user['branch_id'] ?? 0);

    if ($branchId > 0) {
        return $branchId;
    }

    $stmt = db()->query('SELECT id FROM branches WHERE is_active = 1 ORDER BY id LIMIT 1');
    $fallback = (int) $stmt->fetchColumn();

    if ($fallback <= 0) {
        throw new RuntimeException('No active branch is configured.');
    }

    return $fallback;
}

function setting(string $key, mixed $default = null): mixed
{
    static $cache = [];

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = db()->prepare(
        'SELECT setting_value, setting_type FROM settings WHERE setting_key = :setting_key LIMIT 1'
    );
    $stmt->execute([':setting_key' => $key]);
    $row = $stmt->fetch();

    if (!$row) {
        return $cache[$key] = $default;
    }

    $value = $row['setting_value'];

    $cache[$key] = match ($row['setting_type']) {
        'integer' => (int) $value,
        'decimal' => (string) $value,
        'boolean' => in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true),
        'json' => json_decode((string) $value, true),
        default => (string) $value,
    };

    return $cache[$key];
}

function normalize_decimal_input(string|int|float|null $value): string
{
    $value = trim((string) $value);
    $value = str_replace([',', ' '], '', $value);

    return $value === '' ? '0' : $value;
}

function decimal_string_to_scaled_int(string|int|float|null $value, int $scale): int
{
    $value = normalize_decimal_input($value);

    if (!preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
        throw new InvalidArgumentException('Invalid numeric value.');
    }

    $negative = str_starts_with($value, '-');
    $unsigned = ltrim($value, '+-');
    [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');

    $fraction = preg_replace('/\D/', '', $fraction) ?? '';
    $roundDigit = null;

    if (strlen($fraction) > $scale) {
        $roundDigit = (int) $fraction[$scale];
    }

    $fraction = substr(str_pad($fraction, $scale, '0'), 0, $scale);
    $multiplier = 10 ** $scale;
    $scaled = ((int) $whole * $multiplier) + (int) ($fraction === '' ? 0 : $fraction);

    if ($roundDigit !== null && $roundDigit >= 5) {
        $scaled++;
    }

    return $negative ? -$scaled : $scaled;
}

function scaled_int_to_decimal(int $value, int $scale): string
{
    $negative = $value < 0;
    $value = abs($value);
    $multiplier = 10 ** $scale;
    $whole = intdiv($value, $multiplier);
    $fraction = $value % $multiplier;

    $result = $scale > 0
        ? $whole . '.' . str_pad((string) $fraction, $scale, '0', STR_PAD_LEFT)
        : (string) $whole;

    return $negative ? '-' . $result : $result;
}

function money_to_cents(string|int|float|null $value): int
{
    return decimal_string_to_scaled_int($value, 2);
}

function cents_to_decimal(int $value): string
{
    return scaled_int_to_decimal($value, 2);
}

function quantity_to_milli(string|int|float|null $value): int
{
    return decimal_string_to_scaled_int($value, 3);
}

function milli_to_decimal(int $value): string
{
    return scaled_int_to_decimal($value, 3);
}

function decimal4_to_units(string|int|float|null $value): int
{
    return decimal_string_to_scaled_int($value, 4);
}

function units4_to_decimal(int $value): string
{
    return scaled_int_to_decimal($value, 4);
}

function rounded_divide(int $numerator, int $denominator): int
{
    if ($denominator === 0) {
        throw new DivisionByZeroError('Cannot divide by zero.');
    }

    $negative = ($numerator < 0) xor ($denominator < 0);
    $numerator = abs($numerator);
    $denominator = abs($denominator);
    $result = intdiv($numerator + intdiv($denominator, 2), $denominator);

    return $negative ? -$result : $result;
}

/** Quantity (3dp) × money (2dp) -> money cents. */
function line_total_cents(int $quantityMilli, int $unitPriceCents): int
{
    return rounded_divide($quantityMilli * $unitPriceCents, 1000);
}

/** Quantity (3dp) × cost (4dp) -> money cents. */
function line_cost_cents(int $quantityMilli, int $costFourDecimals): int
{
    return rounded_divide($quantityMilli * $costFourDecimals, 100000);
}

function format_quantity(string|int|float $quantity, int $decimalPlaces = 3): string
{
    $value = number_format((float) $quantity, $decimalPlaces, '.', '');
    return rtrim(rtrim($value, '0'), '.');
}

function next_document_number(PDO $pdo, string $sequenceKey): string
{
    $stmt = $pdo->prepare(
        'SELECT prefix, current_value, padding
         FROM document_sequences
         WHERE sequence_key = :sequence_key
         LIMIT 1
         FOR UPDATE'
    );
    $stmt->execute([':sequence_key' => $sequenceKey]);
    $sequence = $stmt->fetch();

    if (!$sequence) {
        throw new RuntimeException('Document sequence is not configured: ' . $sequenceKey);
    }

    $nextValue = (int) $sequence['current_value'] + 1;

    $update = $pdo->prepare(
        'UPDATE document_sequences
         SET current_value = :current_value
         WHERE sequence_key = :sequence_key'
    );
    $update->execute([
        ':current_value' => $nextValue,
        ':sequence_key' => $sequenceKey,
    ]);

    return (string) $sequence['prefix'] . str_pad(
        (string) $nextValue,
        (int) $sequence['padding'],
        '0',
        STR_PAD_LEFT
    );
}

function product_upload_directory(): string
{
    return UPLOAD_PATH . '/products';
}

function save_product_image(array $file, ?string $existingPath = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The product image could not be uploaded.');
    }

    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Product image must be 5 MB or smaller.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Invalid product image upload.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Use a JPG, PNG or WEBP product image.');
    }

    $directory = product_upload_directory();
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the product upload directory.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $target = $directory . '/' . $filename;

    if (!move_uploaded_file($tmpName, $target)) {
        throw new RuntimeException('Unable to store the uploaded product image.');
    }

    if ($existingPath && str_starts_with($existingPath, 'uploads/products/')) {
        $oldFile = BASE_PATH . '/' . ltrim($existingPath, '/');
        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    return 'uploads/products/' . $filename;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    $decoded = json_decode((string) $raw, true);
    return is_array($decoded) ? $decoded : [];
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function require_json_csrf(array $payload): void
{
    $token = $payload['_csrf'] ?? null;
    if (!is_string($token) || !verify_csrf_token($token)) {
        json_response(['ok' => false, 'message' => 'Your session token expired. Refresh the page and try again.'], 419);
    }
}

function allow_negative_stock(): bool
{
    return (bool) setting('inventory.allow_negative_stock', false);
}

function payment_method_by_id(PDO $pdo, int $paymentMethodId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, code, method_type, requires_reference
         FROM payment_methods
         WHERE id = :id AND is_active = 1
         LIMIT 1'
    );
    $stmt->execute([':id' => $paymentMethodId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function active_cash_session_id(PDO $pdo, int $userId, int $branchId): ?int
{
    $stmt = $pdo->prepare(
        'SELECT cs.id
         FROM cash_sessions cs
         INNER JOIN cash_registers cr ON cr.id = cs.cash_register_id
         WHERE cs.status = "open"
           AND cs.opened_by = :user_id
           AND cr.branch_id = :branch_id
         ORDER BY cs.opened_at DESC
         LIMIT 1'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':branch_id' => $branchId,
    ]);

    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

function transactional_audit(
    PDO $pdo,
    string $action,
    string $module,
    ?int $recordId,
    string $description,
    ?array $oldValues = null,
    ?array $newValues = null
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs
         (user_id, action, module, record_id, description, old_values, new_values, ip_address, user_agent)
         VALUES (:user_id, :action, :module, :record_id, :description, :old_values, :new_values, :ip_address, :user_agent)'
    );
    $stmt->execute([
        ':user_id' => isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
        ':action' => $action,
        ':module' => $module,
        ':record_id' => $recordId,
        ':description' => $description,
        ':old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_THROW_ON_ERROR),
        ':new_values' => $newValues === null ? null : json_encode($newValues, JSON_THROW_ON_ERROR),
        ':ip_address' => request_ip(),
        ':user_agent' => user_agent(),
    ]);
}
