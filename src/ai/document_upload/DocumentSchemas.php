<?php
/**
 * OffPaper — Multi-Category Document Schemas & System Prompts
 * 
 * Provides declarative prompts and strict JSON schemas for Gemini model responses
 * supporting multi-category document processing (prescription, labreport, plan, bills, deadline).
 */

class DocumentSchemas
{
    /**
     * Stage 1: Multi-Category Classifier Prompt & Schema
     */
    public static function getClassifierConfig(): array
    {
        return [
            'systemInstruction' => 'You are an expert document classification engine for OffPaper. Analyze the provided image of a paper document, receipt, or handwritten note, identify all applicable categories it belongs to, and generate a concise summary. Read ONLY what is visible in the image.',
            'prompt' => 'Analyze this document image. Determine which of the target categories apply: prescription, labreport, plan, bills, deadline. A single document MAY belong to multiple categories simultaneously. Write a summary of the document in EXACTLY 10 to 20 words.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'summary' => [
                        'type' => 'string',
                        'description' => 'Concise summary of the document strictly between 10 and 20 words long.'
                    ],
                    'categories' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'enum' => ['prescription', 'labreport', 'plan', 'bills', 'deadline']
                        ],
                        'description' => 'List of detected document categories.'
                    ],
                    'confidence' => [
                        'type' => 'number',
                        'description' => 'Classification confidence score from 0.0 to 1.0'
                    ]
                ],
                'required' => ['summary', 'categories', 'confidence']
            ]
        ];
    }

    /**
     * Stage 2: Bills Extractor Schema (`bills`)
     */
    public static function getBillsConfig(): array
    {
        return [
            'systemInstruction' => 'You are a high-precision invoice and receipt parser for OffPaper. Extract vendor details, line items, prices, tax, total amounts, and payment due dates from the document image. Read ONLY visible text. Do NOT estimate or compute missing prices.',
            'prompt' => 'Extract all bill/invoice details: vendor name, bill date, invoice number, currency, itemized line items (description, quantity, unit price, total price), subtotal, tax, grand total, and payment due date.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'vendor_name' => ['type' => 'string', 'nullable' => true],
                    'bill_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD format if available', 'nullable' => true],
                    'invoice_number' => ['type' => 'string', 'nullable' => true],
                    'currency' => ['type' => 'string', 'description' => 'Currency code or symbol e.g. USD, EUR, INR, $', 'nullable' => true],
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'description' => ['type' => 'string'],
                                'quantity' => ['type' => 'number', 'nullable' => true],
                                'unit_price' => ['type' => 'number', 'nullable' => true],
                                'total_price' => ['type' => 'number', 'nullable' => true]
                            ],
                            'required' => ['description']
                        ]
                    ],
                    'subtotal' => ['type' => 'number', 'nullable' => true],
                    'tax' => ['type' => 'number', 'nullable' => true],
                    'grand_total' => ['type' => 'number', 'nullable' => true],
                    'payment_due_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD format if available', 'nullable' => true],
                    'extraction_confidence' => ['type' => 'number']
                ],
                'required' => ['items']
            ]
        ];
    }

    /**
     * Stage 2: Deadline Extractor Schema (`deadline`)
     */
    public static function getDeadlineConfig(): array
    {
        return [
            'systemInstruction' => 'You are a time-sensitive task and deadline extractor for OffPaper. Extract key date commitments, due dates, submission deadlines, and appointment schedules from the document image.',
            'prompt' => 'Extract all deadline details from this document: title/event, due date, due time, priority, issuer or organization, and action required.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string', 'nullable' => true],
                    'due_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD format', 'nullable' => true],
                    'due_time' => ['type' => 'string', 'description' => 'HH:MM 24-hr or 12-hr format', 'nullable' => true],
                    'priority' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                    'issuer_or_organization' => ['type' => 'string', 'nullable' => true],
                    'action_required' => ['type' => 'string', 'nullable' => true],
                    'extraction_confidence' => ['type' => 'number']
                ],
                'required' => ['priority']
            ]
        ];
    }

    /**
     * Stage 2: Prescription Extractor Schema (`prescription`)
     */
    public static function getPrescriptionConfig(): array
    {
        return [
            'systemInstruction' => 'You are a specialized medical prescription digitizer for OffPaper. Extract doctor details, patient info, and prescribed medications accurately from the document image. Ground all extractions strictly in the source text.',
            'prompt' => 'Extract patient details, prescribing doctor name, clinic/hospital name, prescription date, and all prescribed medications (name, dosage, frequency, duration, special instructions).',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'doctor_name' => ['type' => 'string', 'nullable' => true],
                    'clinic_hospital' => ['type' => 'string', 'nullable' => true],
                    'prescription_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD format', 'nullable' => true],
                    'patient_name' => ['type' => 'string', 'nullable' => true],
                    'medications' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'dosage' => ['type' => 'string', 'nullable' => true],
                                'frequency' => ['type' => 'string', 'nullable' => true],
                                'duration' => ['type' => 'string', 'nullable' => true],
                                'special_instructions' => ['type' => 'string', 'nullable' => true]
                            ],
                            'required' => ['name']
                        ]
                    ],
                    'extraction_confidence' => ['type' => 'number']
                ],
                'required' => ['medications']
            ]
        ];
    }

    /**
     * Stage 2: Lab Report Extractor Schema (`labreport`)
     */
    public static function getLabReportConfig(): array
    {
        return [
            'systemInstruction' => 'You are a diagnostic lab report extraction engine for OffPaper. Extract test panel results, numerical values, units, reference ranges, and status flags from the document image. Do NOT calculate or modify values — record only what is visible.',
            'prompt' => 'Extract diagnostic lab report details: lab name, report date, patient name, and all test results (test name, observed value, unit, reference range, status flag).',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'lab_name' => ['type' => 'string', 'nullable' => true],
                    'report_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD format', 'nullable' => true],
                    'patient_name' => ['type' => 'string', 'nullable' => true],
                    'test_results' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'test_name' => ['type' => 'string'],
                                'observed_value' => ['type' => 'string', 'nullable' => true],
                                'unit' => ['type' => 'string', 'nullable' => true],
                                'reference_range' => ['type' => 'string', 'nullable' => true],
                                'status_flag' => ['type' => 'string', 'nullable' => true, 'description' => 'normal, high, low, critical, or null']
                            ],
                            'required' => ['test_name']
                        ]
                    ],
                    'extraction_confidence' => ['type' => 'number']
                ],
                'required' => ['test_results']
            ]
        ];
    }

    /**
     * Stage 2: Action Plan Extractor Schema (`plan`)
     */
    public static function getPlanConfig(): array
    {
        return [
            'systemInstruction' => 'You are an actionable plan and task list parser for OffPaper. Extract goals, step-by-step action items, assignees, and notes from handwritten or typed document notes.',
            'prompt' => 'Extract action plan details: plan title, note date, sequential action items (step number, task description, assigned to, status), and extra notes.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'plan_title' => ['type' => 'string', 'nullable' => true],
                    'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD format', 'nullable' => true],
                    'action_items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'step_number' => ['type' => 'number'],
                                'task' => ['type' => 'string'],
                                'assigned_to' => ['type' => 'string', 'nullable' => true],
                                'status' => ['type' => 'string', 'nullable' => true, 'description' => 'pending or completed']
                            ],
                            'required' => ['step_number', 'task']
                        ]
                    ],
                    'notes' => ['type' => 'string', 'nullable' => true],
                    'extraction_confidence' => ['type' => 'number']
                ],
                'required' => ['action_items']
            ]
        ];
    }

    /**
     * Get extraction configuration for a specified category string
     */
    public static function getConfigForCategory(string $category): array
    {
        return match ($category) {
            'bills', 'bill', 'receipt' => self::getBillsConfig(),
            'deadline' => self::getDeadlineConfig(),
            'prescription' => self::getPrescriptionConfig(),
            'labreport', 'lab_report' => self::getLabReportConfig(),
            'plan', 'handwritten_note' => self::getPlanConfig(),
            default => self::getPlanConfig(),
        };
    }

    /**
     * Backward compatibility alias for getConfigForCategory
     */
    public static function getConfigForDocType(string $docType): array
    {
        return self::getConfigForCategory($docType);
    }
}
