<?php
declare(strict_types=1);

/**
 * Record an important system event.
 *
 * Audit logging must never interrupt the main action if logging itself fails.
 */
function audit_log(
    string $action,
    string $module,
    ?int $recordId,
    string $description,
    ?array $oldValues = null,
    ?array $newValues = null,
    ?int $userId = null
): void {
    try {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
        }

        $stmt = db()->prepare(
            'INSERT INTO audit_logs
                (user_id, action, module, record_id, description, old_values, new_values, ip_address, user_agent)
             VALUES
                (:user_id, :action, :module, :record_id, :description, :old_values, :new_values, :ip_address, :user_agent)'
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':module' => $module,
            ':record_id' => $recordId,
            ':description' => $description,
            ':old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_THROW_ON_ERROR),
            ':new_values' => $newValues === null ? null : json_encode($newValues, JSON_THROW_ON_ERROR),
            ':ip_address' => request_ip(),
            ':user_agent' => user_agent(),
        ]);
    } catch (Throwable $exception) {
        error_log('Audit log failed: ' . $exception->getMessage());
    }
}
