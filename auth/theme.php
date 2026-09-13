<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$token = is_array($payload) ? ($payload['_csrf'] ?? null) : null;
$theme = is_array($payload) ? ($payload['theme'] ?? null) : null;

if (!is_string($token) || !verify_csrf_token($token)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

if (!is_string($theme) || !in_array($theme, ['light', 'dark'], true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid theme']);
    exit;
}

$stmt = db()->prepare(
    'INSERT INTO user_preferences (user_id, theme)
     VALUES (:user_id, :theme)
     ON DUPLICATE KEY UPDATE theme = VALUES(theme), updated_at = CURRENT_TIMESTAMP'
);
$stmt->execute([
    ':user_id' => (int) $_SESSION['user_id'],
    ':theme' => $theme,
]);

set_session_theme($theme);

echo json_encode(['ok' => true, 'theme' => $theme]);
