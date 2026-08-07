<?php
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'Authentication required.',
    ]);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$docId = (int) ($input['document_id'] ?? $input['id'] ?? 0);
if ($docId <= 0) {
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid document ID.',
    ]);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = db()->prepare(
    'SELECT u.id, u.user_id, u.filename, u.extracted_json, kb.summary 
     FROM user_uploads u 
     LEFT JOIN user_uploads_knowledgebase kb ON kb.user_upload_id = u.id 
     WHERE u.id = :id AND u.user_id = :user_id'
);
$stmt->execute(['id' => $docId, 'user_id' => $userId]);
$doc = $stmt->fetch();

if (!$doc) {
    echo json_encode([
        'ok' => false,
        'error' => 'Document not found or access denied.',
    ]);
    exit;
}

$extracted = [];
if (!empty($doc['extracted_json'])) {
    $extracted = is_string($doc['extracted_json']) ? json_decode($doc['extracted_json'], true) : $doc['extracted_json'];
}

// Flexible resolution of deadline data structure
$d = null;
if (is_array($extracted)) {
    if (!empty($extracted['deadline']) && is_array($extracted['deadline'])) {
        $d = $extracted['deadline'];
    } elseif (!empty($extracted['data']['deadline']) && is_array($extracted['data']['deadline'])) {
        $d = $extracted['data']['deadline'];
    } elseif (!empty($extracted['bills']) && is_array($extracted['bills']) && !empty($extracted['bills']['payment_due_date'])) {
        $d = $extracted['bills'];
        $d['due_date'] = $extracted['bills']['payment_due_date'];
        $d['title'] = ($extracted['bills']['vendor_name'] ?? 'Bill') . ' Payment Due';
    } elseif (!empty($extracted['due_date']) || !empty($extracted['payment_due_date'])) {
        $d = $extracted['data'] ?? $extracted;
    }
}

if (!$d || !is_array($d)) {
    echo json_encode([
        'ok' => false,
        'error' => 'No deadline category data found for this document.',
    ]);
    exit;
}

$title = !empty($d['title']) ? $d['title'] : (!empty($d['vendor_name']) ? $d['vendor_name'] . ' Payment Due' : 'EarlySnap Deadline Notice');
$dueDate = !empty($d['due_date']) ? trim($d['due_date']) : (!empty($d['payment_due_date']) ? trim($d['payment_due_date']) : '');
$dueTime = !empty($d['due_time']) ? trim($d['due_time']) : '';
$priority = !empty($d['priority']) ? strtoupper($d['priority']) : '';
$issuer = !empty($d['issuer_or_organization']) ? $d['issuer_or_organization'] : (!empty($d['vendor_name']) ? $d['vendor_name'] : '');
$action = !empty($d['action_required']) ? $d['action_required'] : '';
$docSummary = !empty($doc['summary']) ? $doc['summary'] : '';

if (empty($dueDate)) {
    echo json_encode([
        'ok' => false,
        'error' => 'No valid due date present in this document.',
    ]);
    exit;
}

// Check if due date has already passed
$dueCheckTs = false;
if (!empty($dueTime)) {
    $dueCheckTs = strtotime($dueDate . ' ' . $dueTime);
} else {
    $dueCheckTs = strtotime($dueDate . ' 23:59:59');
}

if ($dueCheckTs !== false && $dueCheckTs < time()) {
    echo json_encode([
        'ok' => false,
        'error' => 'The due date for this deadline has already passed. Cannot add to calendar.',
    ]);
    exit;
}

// Build event description
$descParts = ["EarlySnap Deadline Record"];
if (!empty($issuer)) {
    $descParts[] = "Issuer: " . $issuer;
}
if (!empty($priority)) {
    $descParts[] = "Priority: " . $priority;
}
if (!empty($action)) {
    $descParts[] = "Action Required: " . $action;
}
if (!empty($docSummary)) {
    $descParts[] = "\nAI Summary: " . $docSummary;
}

$description = implode("\n", $descParts);

// Calculate start and end date/time
$parsedTimestamp = false;
if (!empty($dueTime)) {
    $parsedTimestamp = strtotime($dueDate . ' ' . $dueTime);
}

if ($parsedTimestamp !== false && $parsedTimestamp > 0) {
    $start = ['dateTime' => date('c', $parsedTimestamp)];
    $end   = ['dateTime' => date('c', $parsedTimestamp + 3600)];
} else {
    $formattedDate = date('Y-m-d', strtotime($dueDate));
    $start = ['date' => $formattedDate];
    $end   = ['date' => $formattedDate];
}

$eventData = [
    'summary'     => $title,
    'description' => $description,
    'start'       => $start,
    'end'         => $end,
    // The app-created EarlySnap calendar has no default reminders, so set them explicitly.
    'reminders'   => [
        'useDefault' => false,
        'overrides'  => [
            ['method' => 'popup', 'minutes' => 1440], // 1 day before
            ['method' => 'popup', 'minutes' => 60],   // 1 hour before
        ],
    ],
];

$res = google_calendar_add_event($userId, $eventData);

if (!$res['ok']) {
    $response = [
        'ok'    => false,
        'error' => $res['error'] ?? 'Failed to create event in Google Calendar.',
    ];
    if (!empty($res['auth_needed'])) {
        $response['auth_url'] = url('/auth/google/start.php');
    }
    echo json_encode($response);
    exit;
}

// Update extracted_json with calendar details across structure formats
if (isset($extracted['deadline']) && is_array($extracted['deadline'])) {
    $extracted['deadline']['calendar_event_id'] = $res['event_id'] ?? '';
    $extracted['deadline']['calendar_html_link'] = $res['html_link'] ?? '';
    $extracted['deadline']['calendar_id'] = $res['calendar_id'] ?? '';
}
if (isset($extracted['data']['deadline']) && is_array($extracted['data']['deadline'])) {
    $extracted['data']['deadline']['calendar_event_id'] = $res['event_id'] ?? '';
    $extracted['data']['deadline']['calendar_html_link'] = $res['html_link'] ?? '';
    $extracted['data']['deadline']['calendar_id'] = $res['calendar_id'] ?? '';
}
if (!isset($extracted['deadline']) && !isset($extracted['data']['deadline'])) {
    $extracted['calendar_event_id'] = $res['event_id'] ?? '';
    $extracted['calendar_html_link'] = $res['html_link'] ?? '';
    $extracted['calendar_id'] = $res['calendar_id'] ?? '';
}

$updateStmt = db()->prepare('UPDATE user_uploads SET extracted_json = :extracted WHERE id = :id AND user_id = :user_id');
$updateStmt->execute([
    'extracted' => json_encode($extracted),
    'id'        => $docId,
    'user_id'   => $userId,
]);

echo json_encode([
    'ok'         => true,
    'event_id'   => $res['event_id'] ?? '',
    'html_link'  => $res['html_link'] ?? '',
    'message'    => 'Deadline successfully added to Google Calendar!',
]);
