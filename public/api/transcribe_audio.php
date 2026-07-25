<?php
/**
 * OffPaper — Speech-To-Text API Endpoint
 *
 * Transcribes recorded voice audio clips using Gemini 3.5 Flash Lite multimodal audio processing.
 */

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/ai/GeminiClient.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'ok'    => false,
        'error' => 'Authentication required.',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok'    => false,
        'error' => 'Method not allowed. Only POST is accepted.',
    ]);
    exit;
}

$tempFilePath = null;
$mimeType = 'audio/webm';

try {
    if (!empty($_FILES['audio']['tmp_name']) && is_uploaded_file($_FILES['audio']['tmp_name'])) {
        $tempFilePath = $_FILES['audio']['tmp_name'];
        $uploadedType = $_FILES['audio']['type'] ?? '';
        if (!empty($uploadedType)) {
            $mimeType = strtolower(explode(';', $uploadedType)[0]);
        }
    } else {
        // Fallback: Check raw base64 JSON payload
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);

        if (is_array($input) && !empty($input['audio'])) {
            $data = $input['audio'];
            if (preg_match('/^data:(audio\/[a-z0-9\-\+\.]+);base64,/i', $data, $matches)) {
                $mimeType = strtolower($matches[1]);
                $data = substr($data, strpos($data, ',') + 1);
            }
            $binaryData = base64_decode($data);
            if ($binaryData === false || strlen($binaryData) === 0) {
                throw new RuntimeException('Invalid base64 audio data provided.');
            }

            $tempFilePath = tempnam(sys_get_temp_dir(), 'stt_') . '.webm';
            file_put_contents($tempFilePath, $binaryData);
        }
    }

    if (empty($tempFilePath) || !file_exists($tempFilePath)) {
        throw new RuntimeException('No audio file was received or saved.');
    }

    // Normalize generic mime types if browser sent generic video/webm or octet-stream
    if ($mimeType === 'application/octet-stream' || str_starts_with($mimeType, 'video/')) {
        $mimeType = 'audio/webm';
    }

    $gemini = new GeminiClient();
    $result = $gemini->generateContent(
        'Transcribe the spoken audio verbatim into plain text. Output ONLY the transcribed speech text. Do not include any quotes, preamble, markdown formatting, or explanations.',
        [
            'model'             => 'gemini-3.5-flash-lite',
            'filePath'          => $tempFilePath,
            'mimeType'          => $mimeType,
            'systemInstruction' => 'You are an expert Speech-to-Text transcription engine. Transcribe human speech from audio files accurately, preserving the original language and wording exactly as spoken without commentary.',
            'temperature'       => 0.1,
        ]
    );

    $transcript = trim($result['raw_text'] ?? '');

    echo json_encode([
        'ok'         => true,
        'transcript' => $transcript,
        'model'      => $result['model_used'] ?? 'gemini-3.5-flash-lite',
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage(),
    ]);
} finally {
    // Clean up temporary file if created via base64 fallback
    if (!empty($tempFilePath) && str_contains($tempFilePath, 'stt_') && file_exists($tempFilePath)) {
        @unlink($tempFilePath);
    }
}
