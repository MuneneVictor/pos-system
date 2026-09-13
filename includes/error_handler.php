<?php
declare(strict_types=1);

function configure_error_handling(bool $debug): void
{
    ini_set('display_errors', $debug ? '1' : '0');
    ini_set('display_startup_errors', $debug ? '1' : '0');
    error_reporting(E_ALL);

    ini_set('log_errors', '1');
    ini_set('error_log', LOG_PATH . '/php-error.log');

    set_exception_handler(function (Throwable $exception) use ($debug): void {
        error_log(sprintf(
            "[%s] %s in %s:%d\n%s",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ));

        if ($debug) {
            http_response_code(500);
            echo '<pre>' . htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8') . '</pre>';
            return;
        }

        http_response_code(500);
        echo 'Something went wrong while processing the request. Please try again.';
    });
}
