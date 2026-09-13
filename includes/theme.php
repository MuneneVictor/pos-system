<?php
declare(strict_types=1);

function current_theme(): string
{
    $theme = $_SESSION['theme'] ?? 'light';

    return in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
}

function set_session_theme(string $theme): void
{
    if (!in_array($theme, ['light', 'dark'], true)) {
        $theme = 'light';
    }

    $_SESSION['theme'] = $theme;
}
