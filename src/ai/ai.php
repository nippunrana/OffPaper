<?php
/**
 * OffPaper — AI Helper Entrypoint
 * 
 * Includes Gemini client, schemas, and pipeline, providing procedural helper functions.
 */

require_once __DIR__ . '/GeminiClient.php';
require_once __DIR__ . '/document_upload/DocumentSchemas.php';
require_once __DIR__ . '/document_upload/DocumentPipeline.php';

/**
 * High-level helper to process an upload record by ID or UUID through the Gemini AI pipeline.
 *
 * @param int|string $uploadIdOrUuid Database ID or UUID of the upload record.
 * @param array $options Pipeline options (e.g. 'classifierModel', 'extractorModel', 'forcedDocType').
 * @return array Processed upload details including extracted JSON and doc_type.
 */
function ai_process_upload($uploadIdOrUuid, array $options = []): array
{
    $pipeline = new DocumentPipeline();
    return $pipeline->processUploadRecord($uploadIdOrUuid, $options);
}

/**
 * Direct helper to run AI extraction on any document file path.
 *
 * @param string $filePath Absolute filesystem path.
 * @param string $mimeType MIME type.
 * @param array $options Pipeline options.
 * @return array Extraction result.
 */
function ai_extract_document(string $filePath, string $mimeType, array $options = []): array
{
    $pipeline = new DocumentPipeline();
    return $pipeline->processDocument($filePath, $mimeType, $options);
}
