<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Secure document file server endpoint
require_login();
$user = current_user();

$uuid = $_GET['uuid'] ?? $_GET['id'] ?? '';
if (empty($uuid)) {
    http_response_code(400);
    exit('Missing document identifier');
}

$db = db();
if (is_numeric($uuid)) {
    $stmt = $db->prepare('SELECT * FROM user_uploads WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => (int)$uuid, ':user_id' => $user['id']]);
} else {
    $stmt = $db->prepare('SELECT * FROM user_uploads WHERE uuid = :uuid AND user_id = :user_id');
    $stmt->execute([':uuid' => $uuid, ':user_id' => $user['id']]);
}

$doc = $stmt->fetch();
if (!$doc) {
    http_response_code(404);
    exit('Document not found');
}

$filePath = APP_ROOT . '/' . ltrim($doc['file_path'], '/');
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found on server');
}

$mimeType = !empty($doc['mime_type']) ? $doc['mime_type'] : 'image/jpeg';

// Set headers for safe browser display
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;
