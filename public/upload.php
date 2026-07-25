<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Endpoint accepts authenticated users
require_login();
$user = current_user();

// Determine response type (JSON vs standard HTML redirect)
$isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

function send_response(bool $success, string $message, array $extra = [], int $statusCode = 200, bool $isJson = true): void
{
    if ($isJson) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_SLASHES);
        exit;
    } else {
        flash_set($success ? 'success' : 'error', $message);
        redirect($success ? '/dashboard.php' : '/dashboard.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(false, 'Method not allowed', [], 405, $isJson);
}

// Handle request data (JSON or Multipart Form)
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?? [];

$csrfToken = $_POST['csrf'] ?? $jsonInput['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrfToken)) {
    send_response(false, 'Invalid security token. Please refresh and try again.', [], 403, $isJson);
}

$uploadDir = APP_ROOT . '/user-uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$binaryData = null;
$originalFilename = 'camera_capture.jpg';
$source = $_POST['source'] ?? $jsonInput['source'] ?? 'camera';

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/heic' => 'heic',
    'image/heif' => 'heif',
];

if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $fileInfo = $_FILES['photo'];
    $originalFilename = !empty($fileInfo['name']) ? basename($fileInfo['name']) : 'camera_capture.jpg';
    $binaryData = file_get_contents($fileInfo['tmp_name']);
} elseif (!empty($jsonInput['image_base64'])) {
    $base64Str = $jsonInput['image_base64'];
    if (str_contains($base64Str, ',')) {
        $parts = explode(',', $base64Str, 2);
        $base64Str = $parts[1];
    }
    // Remove any whitespace or line breaks
    $base64Str = preg_replace('/\s+/', '', $base64Str);
    $binaryData = base64_decode($base64Str, false);
    if ($binaryData === false) {
        send_response(false, 'Invalid base64 image data provided.', [], 400, $isJson);
    }
    $originalFilename = !empty($jsonInput['filename']) ? basename($jsonInput['filename']) : 'camera_capture.jpg';
} else {
    $uploadError = $_FILES['photo']['error'] ?? null;
    $msg = 'No image file received.';
    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
        $msg = 'Uploaded file exceeds maximum allowed size.';
    }
    send_response(false, $msg, [], 400, $isJson);
}

// Verify size limit (max 15MB)
$fileSize = strlen($binaryData);
if ($fileSize > 15 * 1024 * 1024) {
    send_response(false, 'File is too large (maximum 15MB allowed).', [], 400, $isJson);
}

// Detect MIME type accurately using finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$detectedMime = $finfo->buffer($binaryData);

if ($detectedMime && isset($allowedMimes[$detectedMime])) {
    $mimeType = $detectedMime;
    $ext = $allowedMimes[$detectedMime];
} else {
    // Default fallback to jpg if buffer detection yields application/octet-stream for camera frames
    $mimeType = 'image/jpeg';
    $ext = 'jpg';
}

// Generate unique UUID for the filename
$fileUuid = generate_uuid();
$storedFilename = $fileUuid . '.' . $ext;
$relativePath = 'user-uploads/' . $storedFilename;
$targetAbsolutePath = $uploadDir . '/' . $storedFilename;

if (file_put_contents($targetAbsolutePath, $binaryData) === false) {
    send_response(false, 'Failed to save file to server.', [], 500, $isJson);
}

// Record in database if DB table user_uploads exists
$dbId = null;
try {
    $db = db();
    $stmt = $db->prepare('
        INSERT INTO user_uploads (uuid, user_id, filename, original_filename, file_path, file_size, mime_type, source, status)
        VALUES (:uuid, :user_id, :filename, :original_filename, :file_path, :file_size, :mime_type, :source, :status)
        RETURNING id
    ');
    $stmt->execute([
        ':uuid' => $fileUuid,
        ':user_id' => $user['id'],
        ':filename' => $storedFilename,
        ':original_filename' => $originalFilename,
        ':file_path' => $relativePath,
        ':file_size' => $fileSize,
        ':mime_type' => $mimeType,
        ':source' => $source,
        ':status' => 'pending',
    ]);
    $dbId = $stmt->fetchColumn();
} catch (Throwable $e) {
    error_log('Database record insertion for upload failed: ' . $e->getMessage());
}

send_response(true, 'Document captured and saved successfully!', [
    'upload' => [
        'id' => $dbId,
        'uuid' => $fileUuid,
        'filename' => $storedFilename,
        'original_name' => $originalFilename,
        'file_path' => $relativePath,
        'file_size' => $fileSize,
        'mime_type' => $mimeType,
        'source' => $source,
    ]
], 200, $isJson);
