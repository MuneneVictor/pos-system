<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

if (is_logged_in() && current_user() !== null) {
    redirect('dashboard.php');
}

if (registration_is_open()) {
    redirect('auth/register.php');
}

redirect('login.php');
