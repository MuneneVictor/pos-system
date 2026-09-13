<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (
        empty($_SESSION['_csrf_token']) ||
        empty($_SESSION['_csrf_created_at']) ||
        (time() - (int) $_SESSION['_csrf_created_at']) > 7200
    ) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['_csrf_created_at'] = time();
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    return hash_equals(csrf_token(), $token);
}

function require_valid_csrf(): void
{
    $token = $_POST['_csrf'] ?? null;

    if (!verify_csrf_token(is_string($token) ? $token : null)) {
        http_response_code(419);
        exit('Your session token is invalid or expired. Please refresh the page and try again.');
    }
}
