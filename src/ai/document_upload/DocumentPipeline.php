<?php
/**
 * OffPaper — Multi-Stage AI Document Processing Pipeline
 * 
 * Manages classification, specialized prompt execution, and DB persistence for uploaded documents.
 */

class DocumentPipeline
{
    private GeminiClient $client;

    public function __construct(?GeminiClient $client = null)
    {
        $this->client = $client ?? new GeminiClient();
    }

    /**
     * Run full multi-stage AI extraction on an uploaded document file.
     * 
     * @param string $absoluteFilePath Absolute filesystem path to the file.
     * @param string $mimeType MIME type of the file.
     * @param array $pipelineOptions Custom configuration for models or explicit doc_type:
     *   - 'classifierModel' => string (default: 'gemini-3.5-flash-lite')
     *   - 'extractorModel' => string (default: 'gemini-3.5-flash-lite')
     *   - 'forcedDocType' => string|null (skip classification if provided)
     * @return array Pipeline result array:
     *   - 'success' => bool
     *   - 'doc_type' => string
     *   - 'extracted_data' => array
     *   - 'classification' => array
     *   - 'models' => array
     */
    public function processDocument(string $absoluteFilePath, string $mimeType, array $pipelineOptions = []): array
    {
        if (!file_exists($absoluteFilePath)) {
            throw new InvalidArgumentException('Document file does not exist at path: ' . $absoluteFilePath);
        }

        $classifierModel = $pipelineOptions['classifierModel'] ?? 'gemini-3.5-flash-lite';
        $extractorModel = $pipelineOptions['extractorModel'] ?? 'gemini-3.5-flash-lite';
        $forcedDocType = $pipelineOptions['forcedDocType'] ?? null;

        $docType = $forcedDocType;
        $classificationResult = null;

        // Stage 1: Document Classification (if docType not forced)
        if (empty($docType)) {
            $classifierConfig = DocumentSchemas::getClassifierConfig();
            
            $classifierResponse = $this->client->generateContent($classifierConfig['prompt'], [
                'model' => $classifierModel,
                'filePath' => $absoluteFilePath,
                'mimeType' => $mimeType,
                'systemInstruction' => $classifierConfig['systemInstruction'],
                'responseSchema' => $classifierConfig['responseSchema'],
                'temperature' => 0.1,
            ]);

            $classificationResult = $classifierResponse['data'] ?? [];
            $docType = $classificationResult['doc_type'] ?? 'general';
        }

        // Stage 2: Specialized Data Extraction based on docType
        $extractionConfig = DocumentSchemas::getConfigForDocType($docType);

        $extractionResponse = $this->client->generateContent($extractionConfig['prompt'], [
            'model' => $extractorModel,
            'filePath' => $absoluteFilePath,
            'mimeType' => $mimeType,
            'systemInstruction' => $extractionConfig['systemInstruction'],
            'responseSchema' => $extractionConfig['responseSchema'],
            'temperature' => 0.2,
        ]);

        $extractedData = $extractionResponse['data'] ?? [];

        return [
            'success' => true,
            'doc_type' => $docType,
            'extracted_data' => $extractedData,
            'classification' => $classificationResult,
            'models' => [
                'classifier' => $classifierModel,
                'extractor' => $extractorModel,
            ]
        ];
    }

    /**
     * Process an upload record by its database ID or UUID and update PostgreSQL.
     * 
     * @param int|string $uploadIdOrUuid ID or UUID of record in user_uploads table.
     * @param array $pipelineOptions Pipeline config options.
     * @return array Processing result.
     */
    public function processUploadRecord($uploadIdOrUuid, array $pipelineOptions = []): array
    {
        $db = db();

        // Fetch upload record
        if (is_numeric($uploadIdOrUuid)) {
            $stmt = $db->prepare('SELECT * FROM user_uploads WHERE id = :id');
            $stmt->execute([':id' => $uploadIdOrUuid]);
        } else {
            $stmt = $db->prepare('SELECT * FROM user_uploads WHERE uuid = :uuid');
            $stmt->execute([':uuid' => $uploadIdOrUuid]);
        }

        $uploadRecord = $stmt->fetch();
        if (!$uploadRecord) {
            throw new RuntimeException('Upload record not found in database: ' . $uploadIdOrUuid);
        }

        $absolutePath = APP_ROOT . '/' . ltrim($uploadRecord['file_path'], '/');
        if (!file_exists($absolutePath)) {
            // Update record status to error
            $db->prepare("UPDATE user_uploads SET status = 'error' WHERE id = :id")->execute([':id' => $uploadRecord['id']]);
            throw new RuntimeException('File does not exist on disk: ' . $absolutePath);
        }

        try {
            // Process document with Gemini API pipeline
            $result = $this->processDocument($absolutePath, $uploadRecord['mime_type'], $pipelineOptions);

            $docType = $result['doc_type'];
            $extractedJson = json_encode($result['extracted_data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // Update user_uploads table in PostgreSQL
            $updateStmt = $db->prepare('
                UPDATE user_uploads
                SET status = :status,
                    doc_type = :doc_type,
                    extracted_json = :extracted_json
                WHERE id = :id
            ');

            $updateStmt->execute([
                ':status' => 'processed',
                ':doc_type' => $docType,
                ':extracted_json' => $extractedJson,
                ':id' => $uploadRecord['id'],
            ]);

            return [
                'upload_id' => $uploadRecord['id'],
                'uuid' => $uploadRecord['uuid'],
                'status' => 'processed',
                'doc_type' => $docType,
                'extracted_json' => $result['extracted_data'],
                'pipeline_info' => $result,
            ];
        } catch (Throwable $e) {
            error_log('Gemini AI Pipeline failure for upload ID ' . $uploadRecord['id'] . ': ' . $e->getMessage());

            // Mark record as error in DB
            $db->prepare("UPDATE user_uploads SET status = 'error' WHERE id = :id")->execute([':id' => $uploadRecord['id']]);

            throw $e;
        }
    }
}
