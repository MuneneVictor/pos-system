<?php
declare(strict_types=1);

/**
 * Load a simple KEY=VALUE .env file.
 *
 * Supported:
 * DB_HOST=localhost
 * DB_NAME=erp_system
 * DB_USER=root
 * DB_PASSWORD=@MUNENE
 *
 * Blank lines and lines beginning with # are ignored.
 * Single-quoted and double-quoted values are also supported.
 */
function load_env(string $file): void
{
    if (!is_file($file) || !is_readable($file)) {
        throw new RuntimeException(
            'The .env file is missing. Copy .env.example to .env and add the database credentials.'
        );
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        throw new RuntimeException('Unable to read the .env file.');
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $separator = strpos($line, '=');

        if ($separator === false) {
            continue;
        }

        $key = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));

        if ($key === '' || !preg_match('/^[A-Z0-9_]+$/i', $key)) {
            continue;
        }

        $length = strlen($value);

        if (
            $length >= 2 &&
            (
                ($value[0] === '"' && $value[$length - 1] === '"') ||
                ($value[0] === "'" && $value[$length - 1] === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        // Do not overwrite variables supplied by the server/hosting environment.
        if (getenv($key) !== false) {
            continue;
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

/**
 * Read an environment value.
 */
function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    return $value;
}
