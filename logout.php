<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

if (is_logged_in()) {
    $userId = (int) $_SESSION['user_id'];

    audit_log(
        'logout',
        'auth',
        $userId,
        'User signed out.',
        null,
        null,
        $userId
    );
}

logout_session();

// Start a fresh session so flash messaging works after logout.
session_start();
flash('success', 'You have been signed out safely.');

redirect('login.php');
