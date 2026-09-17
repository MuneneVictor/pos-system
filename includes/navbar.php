<?php
declare(strict_types=1);

$user = current_user();
?>
<header class="topbar">
    <div class="topbar__left">
        <button class="icon-button mobile-menu-button"
                type="button"
                data-sidebar-toggle
                aria-label="Open navigation">
            ☰
        </button>

        <div class="page-heading">
            <span class="page-heading__eyebrow">Wambo wa Carpets</span>
            <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
        </div>
    </div>

    <div class="topbar__actions">
        <button class="icon-button"
                type="button"
                data-theme-toggle
                aria-label="Switch theme"
                title="Switch theme">
            <span data-theme-icon>☾</span>
        </button>

        <div class="user-menu">
            <div class="avatar"><?= e(initials($user['full_name'] ?? 'User')) ?></div>
            <div class="user-menu__copy">
                <strong><?= e($user['full_name'] ?? 'User') ?></strong>
                <span>@<?= e($user['username'] ?? '') ?></span>
            </div>
        </div>

        <a class="logout-button" href="<?= e(app_url('logout')) ?>">
            Sign out
        </a>
    </div>
</header>
