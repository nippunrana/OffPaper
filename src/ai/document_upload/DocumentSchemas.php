<?php
/**
 * OffPaper — Document Schemas & Prompts Repository
 * 
 * Provides declarative prompts and strict JSON schemas for Gemini model responses
 * specific to document image/PDF upload processing.
 */

class DocumentSchemas
{
    /**
     * Stage 1: Document Classifier Prompt & Schema
     */
    public static function getClassifierConfig(): array
    {
        return [
            'systemInstruction' => 'You are an expert document classification AI. Analyze the image or document and determine its exact type.',
            'prompt' => 'Analyze this document. Classify its primary document type into one of the following categories: bill, prescription, handwritten_note, receipt, or general. Provide a high-confidence classification and a concise 1-sentence summary.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'doc_type' => [
                        'type' => 'string',
                        'enum' => ['bill', 'prescription', 'handwritten_note', 'receipt', 'general']
                    ],
                    'confidence' => [
                        'type' => 'number',
                        'description' => 'Confidence score from 0.0 to 1.0'
                    ],
                    'summary' => [
                        'type' => 'string',
                        'description' => '1-sentence summary of the document'
                    ]
                ],
                'required' => ['doc_type', 'confidence', 'summary']
            ]
        ];
    }

    /**
     * Stage 2: Specialized Bill & Invoice Extraction Schema
     */
    public static function getBillConfig(): array
    {
        return [
            'systemInstruction' => 'You are a financial document processing AI. Extract structured bill and invoice data with precision.',
            'prompt' => 'Extract all bill details from this document: vendor name, invoice/account number, dates, total due amount, line items, and payment instructions.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'vendor_name' => ['type' => 'string'],
                    'invoice_number' => ['type' => 'string'],
                    'account_number' => ['type' => 'string'],
                    'issue_date' => ['type' => 'string', 'description' => 'ISO date format YYYY-MM-DD if available'],
                    'due_date' => ['type' => 'string', 'description' => 'ISO date format YYYY-MM-DD if available'],
                    'total_amount' => ['type' => 'number'],
                    'currency' => ['type' => 'string'],
                    'line_items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'description' => ['type' => 'string'],
                                'quantity' => ['type' => 'number'],
                                'amount' => ['type' => 'number']
                            ],
                            'required' => ['description', 'amount']
                        ]
                    ],
                    'notes' => ['type' => 'string']
                ],
                'required' => ['vendor_name', 'total_amount', 'due_date']
            ]
        ];
    }

    /**
     * Stage 2: Specialized Prescription & Medical Record Schema
     */
    public static function getPrescriptionConfig(): array
    {
        return [
            'systemInstruction' => 'You are a medical record processing AI. Extract clinical prescription and health record data with extreme accuracy.',
            'prompt' => 'Extract patient details, doctor information, clinic name, date, diagnosis, and prescribed medications from this medical document.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'patient_name' => ['type' => 'string'],
                    'doctor_name' => ['type' => 'string'],
                    'clinic_or_hospital' => ['type' => 'string'],
                    'prescription_date' => ['type' => 'string', 'description' => 'ISO date format YYYY-MM-DD if available'],
                    'diagnosis_notes' => ['type' => 'string'],
                    'medications' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'dosage' => ['type' => 'string'],
                                'frequency' => ['type' => 'string'],
                                'duration' => ['type' => 'string'],
                                'instructions' => ['type' => 'string']
                            ],
                            'required' => ['name', 'dosage']
                        ]
                    ]
                ],
                'required' => ['medications']
            ]
        ];
    }

    /**
     * Stage 2: Specialized Handwritten Note Schema
     */
    public static function getHandwrittenNoteConfig(): array
    {
        return [
            'systemInstruction' => 'You are an advanced handwriting OCR and note parsing AI. Accurately transcribe handwritten paper notes.',
            'prompt' => 'Transcribe the handwritten note. Extract full verbatim text, summary, key deadlines or action items, and mentioned dates.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'transcription' => ['type' => 'string'],
                    'summary' => ['type' => 'string'],
                    'action_items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'task' => ['type' => 'string'],
                                'deadline' => ['type' => 'string'],
                                'assigned_to' => ['type' => 'string']
                            ],
                            'required' => ['task']
                        ]
                    ],
                    'dates_mentioned' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ]
                ],
                'required' => ['transcription', 'summary']
            ]
        ];
    }

    /**
     * Stage 2: Specialized Receipt Schema
     */
    public static function getReceiptConfig(): array
    {
        return [
            'systemInstruction' => 'You are a receipt processing AI. Extract structured transaction details from receipts.',
            'prompt' => 'Extract store/merchant name, transaction date, total paid, tax amount, payment method, and line items from this receipt.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'merchant_name' => ['type' => 'string'],
                    'transaction_date' => ['type' => 'string'],
                    'total_paid' => ['type' => 'number'],
                    'tax_amount' => ['type' => 'number'],
                    'payment_method' => ['type' => 'string'],
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'price' => ['type' => 'number'],
                                'qty' => ['type' => 'number']
                            ],
                            'required' => ['name', 'price']
                        ]
                    ]
                ],
                'required' => ['merchant_name', 'total_paid']
            ]
        ];
    }

    /**
     * Stage 2: General Fallback Document Schema
     */
    public static function getGeneralConfig(): array
    {
        return [
            'systemInstruction' => 'You are a general document extraction AI. Extract structured summary and data points.',
            'prompt' => 'Extract key structured information from this document: title, summary, key points, dates, and amounts.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'summary' => ['type' => 'string'],
                    'key_points' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'key_dates' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'key_amounts' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'label' => ['type' => 'string'],
                                'amount' => ['type' => 'number']
                            ]
                        ]
                    ],
                    'extracted_text' => ['type' => 'string']
                ],
                'required' => ['title', 'summary']
            ]
        ];
    }

    /**
     * Get extraction configuration for a specified document type
     */
    public static function getConfigForDocType(string $docType): array
    {
        return match ($docType) {
            'bill' => self::getBillConfig(),
            'prescription' => self::getPrescriptionConfig(),
            'handwritten_note' => self::getHandwrittenNoteConfig(),
            'receipt' => self::getReceiptConfig(),
            default => self::getGeneralConfig(),
        };
    }
}
