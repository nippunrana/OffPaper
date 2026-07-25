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

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

// Handle GET or mode=get_history requests to fetch stored chat history
if ($_SERVER['REQUEST_METHOD'] === 'GET' || ($input['mode'] ?? '') === 'get_history') {
    $docId = (int)($_GET['document_id'] ?? $_GET['id'] ?? $input['document_id'] ?? $input['id'] ?? 0);
    $uuid = trim((string)($_GET['uuid'] ?? $input['uuid'] ?? ''));
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
            $stmt = $db->prepare('SELECT id FROM user_uploads WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $docId, 'user_id' => $userId]);
        } else {
            $stmt = $db->prepare('SELECT id FROM user_uploads WHERE uuid = :uuid AND user_id = :user_id');
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

        $kbStmt = $db->prepare('SELECT chat_history, summary FROM user_uploads_knowledgebase WHERE user_upload_id = :upload_id');
        $kbStmt->execute(['upload_id' => $doc['id']]);
        $kbRow = $kbStmt->fetch();

        $chatHistory = [];
        if (!empty($kbRow['chat_history'])) {
            $chatHistory = is_array($kbRow['chat_history']) ? $kbRow['chat_history'] : (json_decode($kbRow['chat_history'], true) ?: []);
        }

        // Pull plan_snapshots from user_uploads extracted_json
        $planSnapshots = [];
        $extractedStmt = $db->prepare('SELECT extracted_json FROM user_uploads WHERE id = :id');
        $extractedStmt->execute(['id' => $doc['id']]);
        $extractedRow = $extractedStmt->fetch();
        if (!empty($extractedRow['extracted_json'])) {
            $ext = is_array($extractedRow['extracted_json'])
                ? $extractedRow['extracted_json']
                : (json_decode($extractedRow['extracted_json'], true) ?: []);
            $planSnapshots = $ext['plan_snapshots'] ?? [];
        }

        echo json_encode([
            'ok'             => true,
            'chat_history'   => $chatHistory,
            'summary'        => $kbRow['summary'] ?? null,
            'plan_snapshots' => $planSnapshots,
        ]);
        exit;
    } catch (Throwable $e) {
        echo json_encode([
            'ok' => false,
            'error' => 'Error fetching chat history: ' . $e->getMessage(),
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'ok' => false,
        'error' => 'Method not allowed.',
    ]);
    exit;
}

$message = trim((string)($input['message'] ?? ''));
$docId = (int)($input['document_id'] ?? $input['id'] ?? 0);
$uuid = trim((string)($input['uuid'] ?? ''));
$mode = trim((string)($input['mode'] ?? ''));
$userId = (int)$_SESSION['user_id'];

// Handle clear_history mode — wipe chat history for this document
if ($mode === 'clear_history') {
    if ($docId <= 0 && empty($uuid)) {
        echo json_encode(['ok' => false, 'error' => 'Valid document identifier is required.']);
        exit;
    }
    try {
        $db = db();
        if ($docId > 0) {
            $stmt = $db->prepare('SELECT id FROM user_uploads WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $docId, 'user_id' => $userId]);
        } else {
            $stmt = $db->prepare('SELECT id FROM user_uploads WHERE uuid = :uuid AND user_id = :user_id');
            $stmt->execute(['uuid' => $uuid, 'user_id' => $userId]);
        }
        $doc = $stmt->fetch();
        if (!$doc) {
            echo json_encode(['ok' => false, 'error' => 'Document not found or access denied.']);
            exit;
        }
        $db->prepare('DELETE FROM user_uploads_knowledgebase WHERE user_upload_id = :upload_id AND user_id = :user_id')
           ->execute(['upload_id' => (int)$doc['id'], 'user_id' => $userId]);
        echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => 'Error clearing chat history: ' . $e->getMessage()]);
    }
    exit;
}

if (empty($message)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Message prompt cannot be empty.',
    ]);
    exit;
}

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

    // Load user_uploads_knowledgebase row if exists
    $kbStmt = $db->prepare('SELECT id, chat_history, summary FROM user_uploads_knowledgebase WHERE user_upload_id = :upload_id');
    $kbStmt->execute(['upload_id' => $doc['id']]);
    $kbRow = $kbStmt->fetch();

    $chatHistory = [];
    if (!empty($kbRow['chat_history'])) {
        $chatHistory = is_array($kbRow['chat_history']) ? $kbRow['chat_history'] : (json_decode($kbRow['chat_history'], true) ?: []);
    }

    // Parse extracted JSON data
    $extracted = [];
    if (!empty($doc['extracted_json'])) {
        if (is_array($doc['extracted_json'])) {
            $extracted = $doc['extracted_json'];
        } else {
            $extracted = json_decode($doc['extracted_json'], true) ?: [];
        }
    }

    // Decode categories
    $categories = [];
    if (!empty($doc['categories'])) {
        if (is_array($doc['categories'])) {
            $categories = $doc['categories'];
        } else {
            $categories = json_decode($doc['categories'], true) ?: [$doc['categories']];
        }
    } elseif (!empty($doc['doc_type'])) {
        $categories = [$doc['doc_type']];
    }

    $isPlanAssist = ($mode === 'plan_assist');

    if ($isPlanAssist) {
        $systemPrompt = <<<EOT
You are OffPaper Plan Assistant, an expert AI advisor for strategic plans, action checklists, and handwritten goals.
Your task is to analyze the document and help the user strengthen, prioritize, and execute their plan.

STRICT INSTRUCTIONS:
1. SUGGEST & QUESTION: Give concrete suggestions to strengthen the plan, and ask clarifying questions to learn more about it (e.g. missing steps, potential risks, or timeline details).
2. CONCISE & THOUGHTFUL: Deliver direct, high-value answers without fluff or wordy introductory filler.
3. BULLET POINTS OVER PARAGRAPHS: Do not write long paragraphs. Break information down into bullet points (*) and short, scannable lines.
4. BOLD HIGHLIGHTS: Highlight important details, risks, deadlines, and key terms in **bold**.
5. GROUNDED ANSWERS: Base your response strictly on the document's provided data and image content. If details are missing, state so clearly in a brief bullet.
EOT;
    } else {
        $systemPrompt = <<<EOT
You are OffPaper AI Assistant, an expert document intelligence assistant.
Your task is to answer user questions regarding the current paper document using its summary, extracted JSON details, category context, and document details.

STRICT FORMATTING & TONE INSTRUCTIONS:
1. CONCISE & THOUGHTFUL: Deliver direct, high-value answers without fluff or wordy introductory filler.
2. BULLET POINTS OVER PARAGRAPHS: Do not write long paragraphs. Break information down into bullet points (*) and short, scannable lines.
3. BOLD HIGHLIGHTS: Highlight important details, dates, numbers, vendor names, and action items in **bold**.
4. GROUNDED ANSWERS: Base your response strictly on the document's provided data and image content. If details are missing, state so clearly in a brief bullet.
EOT;
    }

    $docContextPrompt = "DOCUMENT METADATA:\n";
    $docContextPrompt .= "- Title/Filename: " . ($doc['original_filename'] ?? $doc['filename'] ?? 'Document') . "\n";
    $docContextPrompt .= "- Categories: " . implode(', ', $categories) . "\n";
    $docContextPrompt .= "- AI Summary: " . ($doc['summary'] ?? 'N/A') . "\n";
    $docContextPrompt .= "- Upload Date: " . ($doc['created_at'] ?? 'N/A') . "\n\n";

    if (!empty($extracted)) {
        $docContextPrompt .= "EXTRACTED STRUCTURED DATA:\n";
        $docContextPrompt .= json_encode($extracted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";
    }

    if (!empty($chatHistory)) {
        $contextLabel = $isPlanAssist ? 'PRIOR PLAN CONVERSATION HISTORY' : 'PRIOR CONVERSATION HISTORY';
        $docContextPrompt .= $contextLabel . ":\n";
        foreach ($chatHistory as $turn) {
            $role = ($turn['role'] ?? 'user') === 'user' ? 'User' : 'AI Assistant';
            $docContextPrompt .= $role . ": " . ($turn['text'] ?? '') . "\n";
        }
        $docContextPrompt .= "\n";
    }

    $docContextPrompt .= "USER QUESTION: " . $message;

    // Call Gemini Client with gemini-3.5-flash-lite
    $gemini = new GeminiClient(null, 'gemini-3.5-flash-lite');

    $options = [
        'model' => 'gemini-3.5-flash-lite',
        'systemInstruction' => $systemPrompt,
        'temperature' => 0.2,
    ];

    // Attach document file if path exists
    $filePath = $doc['file_path'] ?? '';
    if (!empty($filePath) && file_exists($filePath)) {
        $options['filePath'] = $filePath;
        $options['mimeType'] = $doc['mime_type'] ?? null;
    }

    $result = $gemini->generateContent($docContextPrompt, $options);
    $replyText = $result['raw_text'] ?? 'No response generated.';

    $chatHistory[] = [
        'role' => 'user',
        'text' => $message,
        'ts' => date('c'),
    ];
    $chatHistory[] = [
        'role' => 'ai',
        'text' => $replyText,
        'ts' => date('c'),
    ];

    $upsertStmt = $db->prepare('
        INSERT INTO user_uploads_knowledgebase (user_upload_id, user_id, chat_history, created_at, updated_at)
        VALUES (:user_upload_id, :user_id, :chat_history::jsonb, NOW(), NOW())
        ON CONFLICT (user_upload_id)
        DO UPDATE SET chat_history = EXCLUDED.chat_history, updated_at = NOW()
    ');
    $upsertStmt->execute([
        'user_upload_id' => (int)$doc['id'],
        'user_id' => $userId,
        'chat_history' => json_encode($chatHistory, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);

    echo json_encode([
        'ok' => true,
        'reply' => $replyText,
        'model_used' => $result['model_used'] ?? 'gemini-3.5-flash-lite',
        'chat_history' => $chatHistory,
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'AI Chat Service error: ' . $e->getMessage(),
    ]);
}

