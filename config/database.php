<?php
declare(strict_types=1);

/**
 * Return one shared PDO database connection.
 *
 * Database credentials are loaded exclusively from the root .env file.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = trim((string) env('DB_HOST', '127.0.0.1'));
    $port = trim((string) env('DB_PORT', '3306'));
    $name = trim((string) env('DB_NAME'));
    $user = trim((string) env('DB_USER'));
    $password = (string) env('DB_PASSWORD', '');

    if ($name === '') {
        throw new RuntimeException('DB_NAME is missing from the .env file.');
    }

    if ($user === '') {
        throw new RuntimeException('DB_USER is missing from the .env file.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $name
    );

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);

        // Keep application/database dates consistent.
        $pdo->exec("SET time_zone = '+03:00'");

        return $pdo;
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());

        if (defined('APP_DEBUG') && APP_DEBUG) {
            throw $exception;
        }

        throw new RuntimeException(
            'Unable to connect to the database. Please check the .env database settings.'
        );
    }
}