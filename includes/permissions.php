<?php
declare(strict_types=1);

function permission_slugs_for_user(int $userId): array
{
    static $cache = [];

    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    $stmt = db()->prepare(
        'SELECT DISTINCT p.slug
         FROM users u
         INNER JOIN role_permissions rp ON rp.role_id = u.role_id
         INNER JOIN permissions p ON p.id = rp.permission_id
         WHERE u.id = :user_id
           AND u.status = "active"'
    );
    $stmt->execute([':user_id' => $userId]);

    $cache[$userId] = array_column($stmt->fetchAll(), 'slug');
    return $cache[$userId];
}

function user_can(string $permission): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $user = current_user();

    if (!$user) {
        return false;
    }

    // Owner is always treated as full access, even if a seed row is accidentally missing.
    if (($user['role_slug'] ?? '') === 'owner') {
        return true;
    }

    return in_array(
        $permission,
        permission_slugs_for_user((int) $user['id']),
        true
    );
}

function require_permission(string $permission): void
{
    require_auth();

    if (user_can($permission)) {
        return;
    }

    audit_log(
        'access_denied',
        'security',
        null,
        'Access denied for permission: ' . $permission,
        null,
        ['permission' => $permission]
    );

    http_response_code(403);
    require BASE_PATH . '/403.php';
    exit;
}

function current_user_is_owner(): bool
{
    $user = current_user();
    return $user !== null && ($user['role_slug'] ?? '') === 'owner';
}

function role_permissions(int $roleId): array
{
    $stmt = db()->prepare(
        'SELECT p.slug
         FROM role_permissions rp
         INNER JOIN permissions p ON p.id = rp.permission_id
         WHERE rp.role_id = :role_id
         ORDER BY p.module, p.name'
    );
    $stmt->execute([':role_id' => $roleId]);

    return array_column($stmt->fetchAll(), 'slug');
}
