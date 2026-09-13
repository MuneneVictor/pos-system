<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Discover the application's public base path automatically.
 *
 * Examples:
 * /pos-system
 * /systems/Wambo wa
 * ''
 *
 * No hostname, protocol or hard-coded installation folder is required.
 */
function app_base_path(): string
{
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $projectPath = str_replace(
        '\\',
        '/',
        (string) (realpath(BASE_PATH) ?: BASE_PATH)
    );

    $scriptFilename = str_replace(
        '\\',
        '/',
        (string) ($_SERVER['SCRIPT_FILENAME'] ?? '')
    );

    if ($scriptFilename !== '') {
        $scriptFilename = str_replace(
            '\\',
            '/',
            (string) (realpath($scriptFilename) ?: $scriptFilename)
        );
    }

    $scriptName = str_replace(
        '\\',
        '/',
        (string) ($_SERVER['SCRIPT_NAME'] ?? '')
    );

    // Most reliable method: compare the physical entry script with project root.
    if (
        $scriptFilename !== '' &&
        $scriptName !== '' &&
        str_starts_with($scriptFilename, $projectPath)
    ) {
        $relativeScript = ltrim(
            substr($scriptFilename, strlen($projectPath)),
            '/'
        );

        if ($relativeScript !== '') {
            $suffix = '/' . $relativeScript;

            if (str_ends_with($scriptName, $suffix)) {
                $detected = substr($scriptName, 0, -strlen($suffix));
                $basePath = rtrim($detected, '/');
                return $basePath;
            }
        }
    }

    // Fallback: derive it from DOCUMENT_ROOT when available.
    $documentRoot = str_replace(
        '\\',
        '/',
        (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')
    );

    if ($documentRoot !== '') {
        $documentRoot = rtrim(
            str_replace(
                '\\',
                '/',
                (string) (realpath($documentRoot) ?: $documentRoot)
            ),
            '/'
        );

        if ($documentRoot !== '' && str_starts_with($projectPath, $documentRoot)) {
            $relativeProject = trim(
                substr($projectPath, strlen($documentRoot)),
                '/'
            );

            $basePath = $relativeProject === '' ? '' : '/' . $relativeProject;
            return $basePath;
        }
    }

    // Safe final fallback for an application installed at web root.
    $basePath = '';
    return $basePath;
}

/**
 * Build an application-relative URL.
 *
 * Returns only a path, never a fixed domain or protocol.
 */
function app_url(string $path = ''): string
{
    $base = app_base_path();

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    return app_url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function set_old_input(array $input): void
{
    $_SESSION['_old'] = $input;
}

function clear_old_input(): void
{
    unset($_SESSION['_old']);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function money(int|float|string $amount): string
{
    return APP_CURRENCY_SYMBOL . ' ' . number_format((float) $amount, 2);
}

function request_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function user_agent(): string
{
    return mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        if ($part !== '') {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }
    }

    return $letters !== '' ? $letters : 'WC';
}


function slugify(string $value): string
{
    $value = mb_strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
    return trim($value, '-');
}

function format_datetime(?string $value, string $format = 'd M Y, H:i'): string
{
    if (!$value) {
        return '—';
    }

    try {
        return (new DateTimeImmutable($value))->format($format);
    } catch (Throwable) {
        return '—';
    }
}

function current_page_contains(string $needle): bool
{
    $script = str_replace('\\', '/', (string) ($_SERVER['PHP_SELF'] ?? ''));
    return str_contains($script, $needle);
}
