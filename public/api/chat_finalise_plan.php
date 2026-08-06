<?php
/**
 * Finalise Plan API
 *
 * Reads the full chat history for a document, asks Gemini to synthesise
 * a clean finalised plan from it, stores the result as a versioned snapshot
 * inside extracted_json->plan_snapshots on user_uploads (max 3 kept),
 * then clears the chat history so the user starts fresh with the plan.
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/ai/GeminiClient.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input    = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$docId  = (int)($input['document_id'] ?? $input['id'] ?? 0);
$uuid   = trim((string)($input['uuid'] ?? ''));
$userId = (int)$_SESSION['user_id'];

if ($docId <= 0 && empty($uuid)) {
    echo json_encode(['ok' => false, 'error' => 'Valid document identifier is required.']);
    exit;
}

try {
    $db = db();

    // Load document (ownership check)
    if ($docId > 0) {
        $stmt = $db->prepare('SELECT * FROM user_uploads WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $docId, 'user_id' => $userId]);
    } else {
        $stmt = $db->prepare('SELECT * FROM user_uploads WHERE uuid = :uuid AND user_id = :user_id');
        $stmt->execute(['uuid' => $uuid, 'user_id' => $userId]);
    }
    $doc = $stmt->fetch();

    if (!$doc) {
        echo json_encode(['ok' => false, 'error' => 'Document not found or access denied.']);
        exit;
    }

    // Load chat history
    $kbStmt = $db->prepare('SELECT id, chat_history FROM user_uploads_knowledgebase WHERE user_upload_id = :upload_id');
    $kbStmt->execute(['upload_id' => $doc['id']]);
    $kbRow = $kbStmt->fetch();

    $chatHistory = [];
    if (!empty($kbRow['chat_history'])) {
        $chatHistory = is_array($kbRow['chat_history'])
            ? $kbRow['chat_history']
            : (json_decode($kbRow['chat_history'], true) ?: []);
    }

    if (empty($chatHistory)) {
        echo json_encode(['ok' => false, 'error' => 'No chat history to finalise. Have a conversation first!']);
        exit;
    }

    // Build the full conversation transcript for Gemini
    $transcript = '';
    foreach ($chatHistory as $turn) {
        $role        = ($turn['role'] ?? 'user') === 'user' ? 'User' : 'AI Assistant';
        $transcript .= $role . ': ' . ($turn['text'] ?? '') . "\n";
    }

    // Pull original extracted JSON for context
    $extracted = [];
    if (!empty($doc['extracted_json'])) {
        $extracted = is_array($doc['extracted_json'])
            ? $doc['extracted_json']
            : (json_decode($doc['extracted_json'], true) ?: []);
    }

    $originalPlanData = '';
    if (!empty($extracted['data']['plan'])) {
        $originalPlanData = json_encode($extracted['data']['plan'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // Synthesis prompt
    $systemPrompt = <<<EOT
You are EarlySnap Plan Synthesiser, an expert AI that turns planning conversations into polished, actionable plans.
Your sole job is to produce the FINALISED PLAN output — nothing else.

RULES:
1. Read the entire conversation below and extract every decision, action item, idea, note, priority, risk, and timeline discussed.
2. Merge them with the original document plan data (if provided) to form one comprehensive, up-to-date plan.
3. Structure the output clearly using these sections (omit any section that has no content):
   - ## 🎯 Goal / Vision  (one clear sentence)
   - ## ✅ Action Items  (numbered, ordered by priority or sequence)
   - ## 📅 Timeline / Deadlines  (key dates and milestones, if discussed)
   - ## ⚠️ Risks & Notes  (identified risks, open questions, important caveats)
   - ## 💡 Next Steps  (immediate next 1-3 actions the user should take)
4. Use **bold** for key terms, dates, and priorities.
5. Be concise but complete. Every point should be directly traceable to the conversation.
6. Do NOT add fluff, disclaimers, or preamble. Output only the structured plan.
7. After the full plan, output this marker on its own line:
   ---GREETING---
   Then write ONE warm sentence (max 25 words) welcoming the user back to continue refining this specific plan. Reference the plan's central goal directly. No opener like "Welcome back" or "Here is".
EOT;

    $userPrompt  = "TODAY'S DATE: " . date('Y-m-d') . "\n\n";
    if (!empty($originalPlanData)) {
        $userPrompt .= "ORIGINAL DOCUMENT PLAN DATA:\n" . $originalPlanData . "\n\n";
    }
    $userPrompt .= "FULL CHAT TRANSCRIPT:\n" . $transcript . "\n\nProduce the finalised plan now.";

    // Call Gemini
    $gemini = new GeminiClient(null, 'gemini-3.5-flash-lite');
    $options = [
        'model'            => 'gemini-3.5-flash-lite',
        'systemInstruction' => $systemPrompt,
        'temperature'      => 0.3,
    ];

    // Optionally re-attach document image for visual context
    $filePath = $doc['file_path'] ?? '';
    if (!empty($filePath) && file_exists($filePath)) {
        $options['filePath'] = $filePath;
        $options['mimeType'] = $doc['mime_type'] ?? null;
    }

    $result    = $gemini->generateContent($userPrompt, $options);
    $planText  = trim($result['raw_text'] ?? '');

    if (empty($planText)) {
        echo json_encode(['ok' => false, 'error' => 'AI failed to generate a plan. Please try again.']);
        exit;
    }

    // Split plan text and greeting on the ---GREETING--- delimiter
    $greeting = '';
    $delimiter = '---GREETING---';
    if (str_contains($planText, $delimiter)) {
        [$planText, $greetingRaw] = explode($delimiter, $planText, 2);
        $planText = trim($planText);
        $greeting = trim($greetingRaw);
    }

    $finalisedAt = date('c'); // ISO 8601

    // Build new snapshot entry (includes greeting for contextual re-entry message)
    $newSnapshot = [
        'finalised_at' => $finalisedAt,
        'plan_text'    => $planText,
        'greeting'     => $greeting,
    ];

    // Load existing snapshots from extracted_json, prepend new one, keep max 3
    $snapshots = $extracted['plan_snapshots'] ?? [];
    if (!is_array($snapshots)) {
        $snapshots = [];
    }
    array_unshift($snapshots, $newSnapshot);          // newest first
    $snapshots = array_slice($snapshots, 0, 3);       // keep only 3

    $extracted['plan_snapshots'] = $snapshots;

    // Persist updated extracted_json
    $updateStmt = $db->prepare(
        'UPDATE user_uploads SET extracted_json = :extracted_json WHERE id = :id AND user_id = :user_id'
    );
    $updateStmt->execute([
        'extracted_json' => json_encode($extracted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'id'             => (int)$doc['id'],
        'user_id'        => $userId,
    ]);

    // Clear chat history
    $db->prepare(
        'UPDATE user_uploads_knowledgebase SET chat_history = \'[]\'::jsonb, updated_at = NOW() WHERE user_upload_id = :upload_id AND user_id = :user_id'
    )->execute(['upload_id' => (int)$doc['id'], 'user_id' => $userId]);

    echo json_encode([
        'ok'             => true,
        'plan_text'      => $planText,
        'finalised_at'   => $finalisedAt,
        'greeting'       => $greeting,
        'snapshot_count' => count($snapshots),
    ]);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Finalise Plan error: ' . $e->getMessage()]);
}
