<?php
declare(strict_types=1);

$flashTypes = [
    'success' => 'success',
    'error' => 'danger',
    'warning' => 'warning',
    'info' => 'info',
];

foreach ($flashTypes as $key => $class):
    $message = flash($key);
    if (!$message) {
        continue;
    }
?>
    <div class="app-alert app-alert--<?= e($class) ?>" role="alert">
        <span><?= e($message) ?></span>
        <button type="button" class="app-alert__close" data-alert-close aria-label="Dismiss">×</button>
    </div>
<?php endforeach; ?>
