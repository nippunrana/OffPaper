<?php
/**
 * OffPaper — Gemini API REST Client
 * 
 * Direct cURL integration with Google Gemini REST API.
 * Supports multimodal inputs (images/PDFs), customizable models (gemini-3.5-flash-lite, gemini-3.6-flash, etc.),
 * and structured output enforcement via responseSchema.
 */

class GeminiClient
{
    private string $apiKey;
    private string $baseUrl;
    private string $defaultModel;

    public function __construct(?string $apiKey = null, string $defaultModel = 'gemini-3.5-flash-lite')
    {
        $this->apiKey = $apiKey ?? env('GEMINI_API_KEY', '');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
        $this->defaultModel = $defaultModel;

        if (empty($this->apiKey)) {
            throw new RuntimeException('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
        }
    }

    /**
     * Generate content with optional multimodal inline media and JSON schema enforcement.
     *
     * @param string $prompt User prompt text.
     * @param array $options Options array:
     *   - 'model' => string (model ID e.g. gemini-3.5-flash-lite, gemini-3.6-flash)
     *   - 'filePath' => string (absolute file path to image/pdf)
     *   - 'mimeType' => string (e.g. image/jpeg, application/pdf)
     *   - 'systemInstruction' => string (system instruction text)
     *   - 'responseSchema' => array (JSON Schema array structure)
     *   - 'temperature' => float
     * @return array Decoded response array or parsed JSON structured object.
     */
    public function generateContent(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $url = $this->baseUrl . rawurlencode($model) . ':generateContent?key=' . urlencode($this->apiKey);

        if (!empty($options['contents']) && is_array($options['contents'])) {
            $requestBody = [
                'contents' => $options['contents']
            ];
        } else {
            $parts = [];

            // Attach file (image/PDF) as base64 inlineData if provided
            if (!empty($options['filePath']) && file_exists($options['filePath'])) {
                $fileData = file_get_contents($options['filePath']);
                if ($fileData === false) {
                    throw new RuntimeException('Failed to read file for Gemini request: ' . $options['filePath']);
                }

                $mimeType = $options['mimeType'] ?? $this->detectMimeType($options['filePath'], $fileData);
                
                $parts[] = [
                    'inlineData' => [
                        'mimeType' => $mimeType,
                        'data' => base64_encode($fileData)
                    ]
                ];
            }

            // Add prompt text
            if ($prompt !== '') {
                $parts[] = [
                    'text' => $prompt
                ];
            }

            $requestBody = [
                'contents' => [
                    [
                        'parts' => $parts
                    ]
                ]
            ];
        }

        // System Instruction
        if (!empty($options['systemInstruction'])) {
            $requestBody['systemInstruction'] = [
                'parts' => [
                    ['text' => $options['systemInstruction']]
                ]
            ];
        }

        // Generation Config
        $generationConfig = [];
        if (isset($options['temperature'])) {
            $generationConfig['temperature'] = (float)$options['temperature'];
        }

        if (!empty($options['responseSchema'])) {
            $generationConfig['responseMimeType'] = 'application/json';
            $generationConfig['responseSchema'] = $options['responseSchema'];
        } elseif (!empty($options['jsonOutput'])) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        if (!empty($generationConfig)) {
            $requestBody['generationConfig'] = $generationConfig;
        }

        // Execute cURL request
        $ch = curl_init($url);
        $jsonPayload = json_encode($requestBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Gemini API cURL error: ' . $curlError);
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to parse Gemini API JSON response: ' . json_last_error_msg() . ' | Raw: ' . substr($response, 0, 500));
        }

        if ($httpCode !== 200) {
            $errorMessage = $decoded['error']['message'] ?? 'Unknown Gemini API HTTP ' . $httpCode . ' error';
            throw new RuntimeException('Gemini API Error (HTTP ' . $httpCode . '): ' . $errorMessage);
        }

        // Extract generated text from candidates[0].content.parts[0].text
        $candidateText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($candidateText === null) {
            $finishReason = $decoded['candidates'][0]['finishReason'] ?? 'UNKNOWN';
            throw new RuntimeException('Gemini API returned empty output (Finish reason: ' . $finishReason . ')');
        }

        // If JSON schema was requested, parse the output as JSON array/object
        if (!empty($options['responseSchema']) || !empty($options['jsonOutput'])) {
            $parsedJson = json_decode($candidateText, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsedJson)) {
                return [
                    'raw_text'    => $candidateText,
                    'data'        => $parsedJson,
                    'model_used'  => $model,
                    'raw_response'=> $decoded,
                ];
            }
        }

        return [
            'raw_text'    => $candidateText,
            'data'        => null,
            'model_used'  => $model,
            'raw_response'=> $decoded,
        ];
    }

    private function detectMimeType(string $filePath, string $binaryData): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binaryData);

        if ($mime && $mime !== 'application/octet-stream') {
            return $mime;
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'        => 'image/png',
            'webp'       => 'image/webp',
            'pdf'        => 'application/pdf',
            default      => 'image/jpeg',
        };
    }
}
