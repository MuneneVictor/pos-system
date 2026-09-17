<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/config/bootstrap.php';
    require_auth();
}

$pageTitle = 'Access Denied';
$pageStyles = ['admin.css'];

require BASE_PATH . '/includes/header.php';
require BASE_PATH . '/includes/sidebar.php';
?>
<div class="app-main">
    <?php require BASE_PATH . '/includes/navbar.php'; ?>

    <main class="content">
        <section class="empty-page-card">
            <div class="empty-page-card__icon">!</div>
            <h2>You do not have access to this page</h2>
            <p>Your account does not have the required permission for this action.</p>
            <a class="button button--primary" href="<?= e(app_url('dashboard')) ?>">Back to dashboard</a>
        </section>
    </main>
</div>
<?php require BASE_PATH . '/includes/footer.php'; ?>
