<?php
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'Authentication required.',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'ok' => false,
        'error' => 'Method not allowed.',
    ]);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

// CSRF token validation
$csrfToken = $input['csrf'] ?? $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrfToken)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid security token. Please refresh and try again.',
    ]);
    exit;
}

$docId = (int) ($input['document_id'] ?? $input['id'] ?? 0);
$uuid = trim((string) ($input['uuid'] ?? ''));
$userId = (int) $_SESSION['user_id'];

if ($docId <= 0 && empty($uuid)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid document identifier provided.',
    ]);
    exit;
}

try {
    $db = db();
    if ($docId > 0) {
        $stmt = $db->prepare('SELECT * FROM user_uploads WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $docId, 'user_id' => $userId]);
    } else {
        $stmt = $db->prepare('SELECT * FROM user_uploads WHERE uuid = :uuid AND user_id = :user_id');
        $stmt->execute(['uuid' => $uuid, 'user_id' => $userId]);
    }
    $doc = $stmt->fetch();

    if (!$doc) {
        echo json_encode([
            'ok' => false,
            'error' => 'Document not found or access denied.',
        ]);
        exit;
    }

    $targetDocId = (int) $doc['id'];

    // 1. Delete associated Google Calendar event if present
    if (!empty($doc['extracted_json'])) {
        $extracted = is_string($doc['extracted_json']) ? json_decode($doc['extracted_json'], true) : $doc['extracted_json'];
        $calendarEventId = '';
        $calendarId = '';

        if (is_array($extracted)) {
            if (!empty($extracted['deadline']['calendar_event_id'])) {
                $calendarEventId = $extracted['deadline']['calendar_event_id'];
                $calendarId = $extracted['deadline']['calendar_id'] ?? '';
            } elseif (!empty($extracted['data']['deadline']['calendar_event_id'])) {
                $calendarEventId = $extracted['data']['deadline']['calendar_event_id'];
                $calendarId = $extracted['data']['deadline']['calendar_id'] ?? '';
            } elseif (!empty($extracted['calendar_event_id'])) {
                $calendarEventId = $extracted['calendar_event_id'];
                $calendarId = $extracted['calendar_id'] ?? '';
            }
        }

        if (!empty($calendarEventId)) {
            try {
                google_calendar_delete_event($userId, $calendarEventId, $calendarId);
            } catch (Throwable $calEx) {
                error_log('Failed to delete Google Calendar event ' . $calendarEventId . ': ' . $calEx->getMessage());
            }
        }
    }

    // 2. Remove physical document file from disk
    if (!empty($doc['file_path'])) {
        $filePath = APP_ROOT . '/' . ltrim($doc['file_path'], '/');
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
    if (!empty($doc['filename'])) {
        $altPath = APP_ROOT . '/user-uploads/' . basename($doc['filename']);
        if (file_exists($altPath)) {
            @unlink($altPath);
        }
    }

    // 3. Delete database record (ON DELETE CASCADE auto-deletes user_uploads_knowledgebase)
    $deleteStmt = $db->prepare('DELETE FROM user_uploads WHERE id = :id AND user_id = :user_id');
    $deleteStmt->execute([
        'id' => $targetDocId,
        'user_id' => $userId,
    ]);

    echo json_encode([
        'ok' => true,
        'message' => 'Document and all associated data deleted successfully.',
        'deleted_id' => $targetDocId,
    ]);
} catch (Throwable $e) {
    error_log('Document deletion error: ' . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'error' => 'An error occurred while deleting the document.',
    ]);
}
