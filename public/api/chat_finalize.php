<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/ai/GeminiClient.php';

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

$docId = (int)($input['document_id'] ?? $input['id'] ?? 0);
$uuid = trim((string)($input['uuid'] ?? ''));
$userId = (int)$_SESSION['user_id'];

if ($docId <= 0 && empty($uuid)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Valid document identifier is required.',
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

    $kbStmt = $db->prepare('SELECT id, chat_history FROM user_uploads_knowledgebase WHERE user_upload_id = :upload_id');
    $kbStmt->execute(['upload_id' => $doc['id']]);
    $kbRow = $kbStmt->fetch();

    $chatHistory = [];
    if (!empty($kbRow['chat_history'])) {
        $chatHistory = is_array($kbRow['chat_history']) ? $kbRow['chat_history'] : (json_decode($kbRow['chat_history'], true) ?: []);
    }

    if (empty($chatHistory)) {
        echo json_encode([
            'ok' => true,
            'message' => 'No chat history to summarize.',
        ]);
        exit;
    }

    // Format chat history into text prompt
    $historyText = "CONVERSATION HISTORY FOR PLAN RECAP:\n";
    foreach ($chatHistory as $turn) {
        $role = ($turn['role'] ?? 'user') === 'user' ? 'User' : 'AI Assistant';
        $historyText .= $role . ": " . ($turn['text'] ?? '') . "\n";
    }

    $systemPrompt = <<<EOT
You are an expert AI plan editor. Given a conversation history between a user and an AI about a strategic plan, write a concise 2 to 4 sentence recap summarizing the key suggestions, open questions, and next action items discussed.

STRICT FORMATTING RULES:
1. Exactly 2 to 4 clear, high-density sentences.
2. Focus on action items, risks, and suggestions identified during the discussion.
3. Plain text only — no bullet points, headers, or conversational filler.
EOT;

    $gemini = new GeminiClient(null, 'gemini-3.5-flash-lite');
    $options = [
        'model' => 'gemini-3.5-flash-lite',
        'systemInstruction' => $systemPrompt,
        'temperature' => 0.2,
    ];

    $result = $gemini->generateContent($historyText, $options);
    $summaryText = trim($result['raw_text'] ?? '');

    if (!empty($summaryText)) {
        $upsertStmt = $db->prepare('
            INSERT INTO user_uploads_knowledgebase (user_upload_id, user_id, summary, updated_at)
            VALUES (:user_upload_id, :user_id, :summary, NOW())
            ON CONFLICT (user_upload_id)
            DO UPDATE SET summary = EXCLUDED.summary, updated_at = NOW()
        ');
        $upsertStmt->execute([
            'user_upload_id' => (int)$doc['id'],
            'user_id' => $userId,
            'summary' => $summaryText,
        ]);
    }

    echo json_encode([
        'ok' => true,
        'summary' => $summaryText,
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'Chat Finalize error: ' . $e->getMessage(),
    ]);
}
