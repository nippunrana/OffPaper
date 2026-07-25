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

$message = trim((string)($input['message'] ?? ''));
$docId = (int)($input['document_id'] ?? $input['id'] ?? 0);
$uuid = trim((string)($input['uuid'] ?? ''));
$userId = (int)$_SESSION['user_id'];

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

    $systemPrompt = <<<EOT
You are OffPaper AI Assistant, an expert document intelligence assistant.
Your task is to answer user questions regarding the current paper document using its summary, extracted JSON details, category context, and document details.

STRICT FORMATTING & TONE INSTRUCTIONS:
1. CONCISE & THOUGHTFUL: Deliver direct, high-value answers without fluff or wordy introductory filler.
2. BULLET POINTS OVER PARAGRAPHS: Do not write long paragraphs. Break information down into bullet points (*) and short, scannable lines.
3. BOLD HIGHLIGHTS: Highlight important details, dates, numbers, vendor names, and action items in **bold**.
4. GROUNDED ANSWERS: Base your response strictly on the document's provided data and image content. If details are missing, state so clearly in a brief bullet.
EOT;

    $docContextPrompt = "DOCUMENT METADATA:\n";
    $docContextPrompt .= "- Title/Filename: " . ($doc['original_name'] ?? $doc['filename'] ?? 'Document') . "\n";
    $docContextPrompt .= "- Categories: " . implode(', ', $categories) . "\n";
    $docContextPrompt .= "- AI Summary: " . ($doc['summary'] ?? 'N/A') . "\n";
    $docContextPrompt .= "- Upload Date: " . ($doc['created_at'] ?? 'N/A') . "\n\n";

    if (!empty($extracted)) {
        $docContextPrompt .= "EXTRACTED STRUCTURED DATA:\n";
        $docContextPrompt .= json_encode($extracted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";
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

    echo json_encode([
        'ok' => true,
        'reply' => $replyText,
        'model_used' => $result['model_used'] ?? 'gemini-3.5-flash-lite',
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'AI Chat Service error: ' . $e->getMessage(),
    ]);
}
