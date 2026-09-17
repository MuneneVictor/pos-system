<?php
declare(strict_types=1);

require_auth();

$user = current_user();
$pageTitle = $pageTitle ?? 'Dashboard';
$bodyClass = $bodyClass ?? '';
$pageStyles = $pageStyles ?? [];
?>
<!doctype html>
<html lang="en"
      data-theme="<?= e(current_theme()) ?>"
      data-user-theme="<?= e(current_theme()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="app-authenticated" content="1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>

    <link rel="stylesheet" href="<?= e(asset_url('css/variables.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
    <?php foreach ($pageStyles as $stylesheet): ?>
        <link rel="stylesheet" href="<?= e(asset_url('css/' . ltrim((string) $stylesheet, '/'))) ?>">
    <?php endforeach; ?>

    <script>
        window.APP_THEME_ENDPOINT = <?= json_encode(app_url('auth/theme'), JSON_UNESCAPED_SLASHES) ?>;
        (function () {
            const serverTheme = document.documentElement.dataset.userTheme || 'light';
            document.documentElement.setAttribute('data-theme', serverTheme);
            try { localStorage.setItem('pos-theme', serverTheme); } catch (e) {}
        })();
    </script>
</head>
<body class="app-body <?= e($bodyClass) ?>">
<div class="app-shell">
