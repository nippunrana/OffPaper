<?php
/**
 * EarlySnap — Multi-Stage & Multi-Category AI Document Processing Pipeline
 * 
 * Manages classification, multi-category specialized prompt execution, and DB persistence for uploaded documents.
 */

class DocumentPipeline
{
    private GeminiClient $client;

    public function __construct(?GeminiClient $client = null)
    {
        $this->client = $client ?? new GeminiClient();
    }

    /**
     * Run multi-stage AI classification and extraction on an uploaded document file.
     * 
     * @param string $absoluteFilePath Absolute filesystem path to the file.
     * @param string $mimeType MIME type of the file.
     * @param array $pipelineOptions Custom configuration:
     *   - 'classifierModel' => string (default: 'gemini-3.5-flash-lite')
     *   - 'extractorModel' => string (default: 'gemini-3.5-flash-lite')
     *   - 'forcedCategory' => string|array|null (skip classification if provided)
     * @return array Pipeline result array:
     *   - 'success' => bool
     *   - 'summary' => string (10-20 words)
     *   - 'categories' => array
     *   - 'extracted_data' => array (keyed by category name)
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
        $forcedCategory = $pipelineOptions['forcedCategory'] ?? null;
        $referenceDate = $pipelineOptions['referenceDate'] ?? date('Y-m-d (l, F j, Y)');

        $categories = [];
        $summary = '';
        $classificationResult = null;

        // Stage 1: Document Multi-Category Classification & Summarization (if not forced)
        if (!empty($forcedCategory)) {
            $categories = is_array($forcedCategory) ? $forcedCategory : [$forcedCategory];
            $summary = 'User forced category extraction: ' . implode(', ', $categories);
        } else {
            $classifierConfig = DocumentSchemas::getClassifierConfig($referenceDate);
            
            $classifierResponse = $this->client->generateContent($classifierConfig['prompt'], [
                'model' => $classifierModel,
                'filePath' => $absoluteFilePath,
                'mimeType' => $mimeType,
                'systemInstruction' => $classifierConfig['systemInstruction'],
                'responseSchema' => $classifierConfig['responseSchema'],
                'temperature' => 0.1,
            ]);

            $classificationResult = $classifierResponse['data'] ?? [];
            $summary = $classificationResult['summary'] ?? '';
            $rawCategories = $classificationResult['categories'] ?? [];

            if (is_array($rawCategories) && !empty($rawCategories)) {
                $categories = array_values(array_unique($rawCategories));
            } else if (is_string($rawCategories) && !empty($rawCategories)) {
                $categories = [$rawCategories];
            } else {
                $categories = ['plan'];
            }
        }

        // Stage 2: Specialized Data Extraction for each detected category
        $extractedData = [];

        foreach ($categories as $category) {
            $extractionConfig = DocumentSchemas::getConfigForCategory($category, $referenceDate);

            $extractionResponse = $this->client->generateContent($extractionConfig['prompt'], [
                'model' => $extractorModel,
                'filePath' => $absoluteFilePath,
                'mimeType' => $mimeType,
                'systemInstruction' => $extractionConfig['systemInstruction'],
                'responseSchema' => $extractionConfig['responseSchema'],
                'temperature' => 0.2,
            ]);

            $extractedData[$category] = $extractionResponse['data'] ?? [];
        }

        // Stage 3: Category-Aware Suggested Questions Generation
        $suggestedQuestions = [];
        try {
            $questionsConfig = DocumentSchemas::getSuggestedQuestionsConfig($categories, $extractedData, $referenceDate);
            $questionsResponse = $this->client->generateContent($questionsConfig['prompt'], [
                'model' => $extractorModel,
                'filePath' => $absoluteFilePath,
                'mimeType' => $mimeType,
                'systemInstruction' => $questionsConfig['systemInstruction'],
                'responseSchema' => $questionsConfig['responseSchema'],
                'temperature' => 0.3,
            ]);

            $rawQuestions = $questionsResponse['data']['questions'] ?? [];
            if (is_array($rawQuestions)) {
                foreach ($rawQuestions as $q) {
                    if (is_string($q) && trim($q) !== '') {
                        $suggestedQuestions[] = trim($q);
                    }
                }
                $suggestedQuestions = array_values(array_unique($suggestedQuestions));
                $suggestedQuestions = array_slice($suggestedQuestions, 0, 3);
            }
        } catch (Throwable $qErr) {
            error_log('Error generating suggested questions in DocumentPipeline: ' . $qErr->getMessage());
        }

        if (empty($suggestedQuestions)) {
            $suggestedQuestions = self::getDefaultQuestionsForCategories($categories, $extractedData);
        }

        return [
            'success' => true,
            'summary' => $summary,
            'categories' => $categories,
            'extracted_data' => $extractedData,
            'suggested_questions' => $suggestedQuestions,
            'classification' => $classificationResult,
            'models' => [
                'classifier' => $classifierModel,
                'extractor' => $extractorModel,
            ]
        ];
    }

    /**
     * Fallback category-aware questions generator, incorporating extracted document entities.
     */
    public static function getDefaultQuestionsForCategories(array $categories, array $extractedData = []): array
    {
        $questions = [];

        // Entity extractions
        $bills = $extractedData['bills'] ?? [];
        $deadline = $extractedData['deadline'] ?? [];
        $prescription = $extractedData['prescription'] ?? [];
        $lab = $extractedData['labreport'] ?? [];
        $plan = $extractedData['plan'] ?? [];

        $vendor = $bills['vendor_name'] ?? null;
        $curr = !empty($bills['currency']) ? trim($bills['currency']) . ' ' : '';
        $total = isset($bills['grand_total']) && is_numeric($bills['grand_total']) ? $curr . number_format((float)$bills['grand_total'], 2) : null;
        $dueDate = $bills['payment_due_date'] ?? $deadline['due_date'] ?? null;

        $dlTitle = $deadline['title'] ?? null;

        $medName = $prescription['medications'][0]['name'] ?? null;
        $doctor = $prescription['doctor_name'] ?? $prescription['clinic_hospital'] ?? null;

        $labName = $lab['lab_name'] ?? null;
        $testName = $lab['test_results'][0]['test_name'] ?? null;

        $planTitle = $plan['plan_title'] ?? null;
        $firstTask = $plan['action_items'][0]['task'] ?? null;

        foreach ($categories as $cat) {
            switch ($cat) {
                case 'bills':
                    if ($vendor && $total) {
                        $questions[] = "What is the tax amount and due date for the {$total} bill from {$vendor}?";
                    } elseif ($vendor) {
                        $questions[] = "Can you breakdown the itemized charges and total for {$vendor}?";
                    } else {
                        $questions[] = 'What is the grand total, tax, and payment due date for this bill?';
                    }
                    break;

                case 'deadline':
                    if ($dlTitle && $dueDate) {
                        $questions[] = "What action is required before the {$dueDate} deadline for {$dlTitle}?";
                    } elseif ($dlTitle) {
                        $questions[] = "What is the exact due date and priority for {$dlTitle}?";
                    } else {
                        $questions[] = 'What is the exact deadline date and required action?';
                    }
                    break;

                case 'prescription':
                    if ($medName && $doctor) {
                        $questions[] = "What dosage instructions did {$doctor} prescribe for {$medName}?";
                    } elseif ($medName) {
                        $questions[] = "How often should I take {$medName} and for how long?";
                    } else {
                        $questions[] = 'What are the prescribed medications, dosages, and special instructions?';
                    }
                    break;

                case 'labreport':
                    if ($testName && $labName) {
                        $questions[] = "What is the result and reference range for {$testName} from {$labName}?";
                    } elseif ($testName) {
                        $questions[] = "Are any test results like {$testName} flagged as high or low?";
                    } else {
                        $questions[] = 'Are any diagnostic test results on this report flagged as abnormal?';
                    }
                    break;

                case 'plan':
                default:
                    if ($planTitle && $firstTask) {
                        $questions[] = "What is step 1 ({$firstTask}) and the rest of {$planTitle}?";
                    } elseif ($planTitle) {
                        $questions[] = "What are the action steps and assignees listed in {$planTitle}?";
                    } else {
                        $questions[] = 'What are the key action steps, assignees, and target dates in this plan?';
                    }
                    break;
            }
        }

        // Fill remaining questions up to 3 for single-category documents
        if (count($questions) < 3 && count($categories) === 1) {
            $cat = $categories[0] ?? 'plan';
            if ($cat === 'bills') {
                if ($vendor) {
                    $questions[] = "Can you list all itemized line items and prices from {$vendor}?";
                    $questions[] = "What payment methods or due dates are specified for {$vendor}?";
                } else {
                    $questions[] = 'Can you list all itemized line items and unit prices?';
                    $questions[] = 'What payment due date or tax details are shown?';
                }
            } elseif ($cat === 'deadline') {
                if ($dlTitle) {
                    $questions[] = "Who is the issuing organization for {$dlTitle}?";
                    $questions[] = "What priority and action steps apply to {$dlTitle}?";
                } else {
                    $questions[] = 'Who is the issuing organization for this deadline?';
                    $questions[] = 'What priority level is assigned to this deadline?';
                }
            } elseif ($cat === 'prescription') {
                if ($medName) {
                    $questions[] = "Are there any special warnings or intake instructions for {$medName}?";
                    $questions[] = "Who prescribed {$medName} and when is follow-up needed?";
                } else {
                    $questions[] = 'How often should each medicine be taken?';
                    $questions[] = 'Who prescribed this and are there special instructions?';
                }
            } elseif ($cat === 'labreport') {
                if ($labName) {
                    $questions[] = "Which test panel items from {$labName} were strictly within normal limits?";
                    $questions[] = "What overall diagnostic summary is provided by {$labName}?";
                } else {
                    $questions[] = 'What are the specific numerical test values and reference ranges?';
                    $questions[] = 'Which test results were normal vs out-of-range?';
                }
            } else {
                if ($planTitle) {
                    $questions[] = "Who is assigned to complete tasks in {$planTitle}?";
                    $questions[] = "What notes or target dates are attached to {$planTitle}?";
                } else {
                    $questions[] = 'Who is assigned to each task in the plan?';
                    $questions[] = 'What is the target completion date or note?';
                }
            }
        }

        return array_slice(array_values(array_unique($questions)), 0, 3);
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
            $db->prepare("UPDATE user_uploads SET status = 'error' WHERE id = :id")->execute([':id' => $uploadRecord['id']]);
            throw new RuntimeException('File does not exist on disk: ' . $absolutePath);
        }

        try {
            // Process document through 2-pass AI pipeline
            $result = $this->processDocument($absolutePath, $uploadRecord['mime_type'], $pipelineOptions);

            $primaryDocType = $result['categories'][0] ?? 'plan';
            $suggestedQuestions = $result['suggested_questions'] ?? [];
            $extractedJson = json_encode([
                'summary' => $result['summary'],
                'categories' => $result['categories'],
                'data' => $result['extracted_data'],
                'suggested_questions' => $suggestedQuestions,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $suggestedQuestionsJson = json_encode($suggestedQuestions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // Update user_uploads table in PostgreSQL
            $updateStmt = $db->prepare('
                UPDATE user_uploads
                SET status = :status,
                    doc_type = :doc_type,
                    extracted_json = :extracted_json,
                    suggested_questions = :suggested_questions
                WHERE id = :id
            ');

            $updateStmt->execute([
                ':status' => 'processed',
                ':doc_type' => $primaryDocType,
                ':extracted_json' => $extractedJson,
                ':suggested_questions' => $suggestedQuestionsJson,
                ':id' => $uploadRecord['id'],
            ]);

            return [
                'upload_id' => $uploadRecord['id'],
                'uuid' => $uploadRecord['uuid'],
                'status' => 'processed',
                'summary' => $result['summary'],
                'categories' => $result['categories'],
                'suggested_questions' => $suggestedQuestions,
                'extracted_json' => [
                    'summary' => $result['summary'],
                    'categories' => $result['categories'],
                    'data' => $result['extracted_data'],
                    'suggested_questions' => $suggestedQuestions,
                ],
                'pipeline_info' => $result,
            ];
        } catch (Throwable $e) {
            error_log('Gemini AI Pipeline failure for upload ID ' . $uploadRecord['id'] . ': ' . $e->getMessage());

            $db->prepare("UPDATE user_uploads SET status = 'error' WHERE id = :id")->execute([':id' => $uploadRecord['id']]);

            throw $e;
        }
    }
}
