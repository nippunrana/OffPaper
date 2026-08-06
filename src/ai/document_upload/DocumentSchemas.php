<?php
/**
 * EarlySnap — Multi-Category Document Schemas & System Prompts
 * 
 * Provides declarative prompts and strict JSON schemas for Gemini model responses
 * supporting multi-category document processing (prescription, labreport, plan, bills, deadline).
 */

class DocumentSchemas
{
    /**
     * Builds standardized date context and relative date calculation rules for system instructions.
     */
    public static function getSystemDateContext(?string $referenceDate = null): string
    {
        $refDate = $referenceDate ?? date('Y-m-d (l, F j, Y)');
        return "CURRENT SYSTEM DATE & TIME CONTEXT:\n"
            . "- Current System Date: {$refDate}\n"
            . "- DATE CALCULATION & RESOLUTION RULES:\n"
            . "  1. Document Written/Visit Date Priority: Check the image carefully for any explicit document date (e.g., Rx date on a prescription, invoice/bill date on a receipt, lab report date, note date).\n"
            . "  2. IF a document writing/issue/visit date is visible on the image, use THAT written document date as the reference base date for all relative offset calculations (e.g. 'visit after 10 days' or 'follow up in 2 weeks' means document_date + 10 days / 14 days; 'due in 15 days' means bill_date + 15 days).\n"
            . "  3. IF NO document date is visible on the image, use the Current System Date ({$refDate}) as the reference base date for relative terms (e.g. 'today at 4pm' -> current system date at 4pm; 'tomorrow' -> current system date + 1 day; 'after 5 days' -> current system date + 5 days; 'next Monday' -> next Monday date).\n"
            . "  4. Absolute Output Format: All calculated date fields (due_date, bill_date, payment_due_date, prescription_date, report_date, date) MUST be resolved and returned strictly as absolute YYYY-MM-DD format strings. Never output relative words like 'today', 'tomorrow', or 'after 5 days' into final date fields.";
    }

    /**
     * Stage 1: Multi-Category Classifier Prompt & Schema
     */
    public static function getClassifierConfig(?string $referenceDate = null): array
    {
        $dateContext = self::getSystemDateContext($referenceDate);
        return [
            'systemInstruction' => "You are an expert document classification engine for EarlySnap. Analyze the provided image of a paper document, receipt, or handwritten note, identify all applicable categories it belongs to, and generate a concise summary. Read ONLY what is visible in the image.\n\n" . $dateContext,
            'prompt' => 'Analyze this document image. Determine which of the target categories apply: prescription, labreport, plan, bills, deadline. A single document MAY belong to multiple categories simultaneously. IMPORTANT: 1. If this document is a prescription, medical note, or handwritten doctor sheet AND mentions any follow-up visit, appointment date, refill deadline, or future timeframe (whether in handwriting or print, e.g. "follow up after 2 weeks", "visit in 10 days", "refill by August 15", "appointment on..."), you MUST include the "deadline" category in addition to "prescription". 2. If this document is a bill, invoice, or receipt AND mentions any payment due date or due phrase (e.g. "payment due by Aug 30", "due in 15 days", "pay within 30 days"), you MUST include the "deadline" category in addition to "bills". Write a summary of the document in EXACTLY 10 to 20 words. If a date or appointment schedule is mentioned, resolve it to an explicit calendar date based on the document date or current system date.',
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
    public static function getBillsConfig(?string $referenceDate = null): array
    {
        $dateContext = self::getSystemDateContext($referenceDate);
        return [
            'systemInstruction' => "You are a high-precision invoice and receipt parser for EarlySnap. Extract vendor details, line items, prices, tax, total amounts, and payment due dates from the document image. Read ONLY visible text. Do NOT estimate prices or assume currency symbols.\n\n" . $dateContext,
            'prompt' => 'Extract all bill/invoice details: vendor name, bill date, invoice number, currency (ONLY if explicitly written on document image e.g. $, €, £, ₹, USD; return null if no currency symbol or code is written), itemized line items (description, quantity, unit price, total price), subtotal, tax, grand total, and payment due date (resolve any relative due phrase like "due in 15 days", "pay within 30 days" into an absolute YYYY-MM-DD date based on bill date or current system date).',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'vendor_name' => ['type' => 'string', 'nullable' => true],
                    'bill_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD format if available on document', 'nullable' => true],
                    'invoice_number' => ['type' => 'string', 'nullable' => true],
                    'currency' => ['type' => 'string', 'description' => 'Exact currency symbol or code visible on document (e.g. $, €, £, ₹, USD, EUR, INR). If NO explicit currency symbol or code is visible in the image, return null — DO NOT guess or default to any currency symbol.', 'nullable' => true],
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
                    'payment_due_date' => ['type' => 'string', 'description' => 'Absolute YYYY-MM-DD format. Calculate relative terms (e.g. "due in 15 days") using bill date if present, or current system date if not.', 'nullable' => true],
                    'extraction_confidence' => ['type' => 'number']
                ],
                'required' => ['items']
            ]
        ];
    }

    /**
     * Stage 2: Deadline Extractor Schema (`deadline`)
     */
    public static function getDeadlineConfig(?string $referenceDate = null): array
    {
        $dateContext = self::getSystemDateContext($referenceDate);
        return [
            'systemInstruction' => "You are a time-sensitive task, bill payment due date, doctor appointment, and deadline extractor for EarlySnap. Extract key date commitments, payment due dates, submission deadlines, doctor follow-up appointments, handwritten prescription visit dates, and appointment schedules from the document image.\n\n" . $dateContext,
            'prompt' => 'Extract all deadline, appointment, and bill payment details from this document (including bill/invoice payment due dates, vendor deadlines, doctor follow-up dates, handwritten appointment notes on prescriptions, or submission deadlines): title/event (e.g., "Vendor Payment Due", "Doctor Follow-up", "Prescription Refill", or deadline title), due date (resolve any relative phrase like "due in 15 days", "follow up in 2 weeks", "visit after 10 days", "today", "tomorrow" into an absolute YYYY-MM-DD date based on document/bill/prescription writing date or current system date), due time, priority, issuer or organization (e.g., vendor name, prescribing doctor, clinic name, or issuing organization), and action required (e.g., "Pay bill invoice", "Follow up appointment with doctor", "Submit payment").',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string', 'description' => 'Title of deadline, bill payment, task, doctor appointment, or prescription follow-up', 'nullable' => true],
                    'due_date' => ['type' => 'string', 'description' => 'Absolute YYYY-MM-DD format. Resolve relative terms (e.g. "due in 15 days", "follow up in 2 weeks", "after 5 days") based on document writing date if present, or current system date if not.', 'nullable' => true],
                    'due_time' => ['type' => 'string', 'description' => 'HH:MM 24-hr or 12-hr format (e.g. 16:00 or 4:00 PM)', 'nullable' => true],
                    'priority' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                    'issuer_or_organization' => ['type' => 'string', 'description' => 'Issuer, doctor name, clinic, or organization', 'nullable' => true],
                    'action_required' => ['type' => 'string', 'description' => 'Specific action or appointment instructions', 'nullable' => true],
                    'extraction_confidence' => ['type' => 'number']
                ],
                'required' => ['priority']
            ]
        ];
    }

    /**
     * Stage 2: Prescription Extractor Schema (`prescription`)
     */
    public static function getPrescriptionConfig(?string $referenceDate = null): array
    {
        $dateContext = self::getSystemDateContext($referenceDate);
        return [
            'systemInstruction' => "You are a specialized medical prescription digitizer for EarlySnap. Extract doctor details, patient info, and prescribed medications accurately from the document image. Ground all extractions strictly in the source text.\n\n" . $dateContext,
            'prompt' => 'Extract patient details, prescribing doctor name, clinic/hospital name, prescription date (prescription/visit date in YYYY-MM-DD format), and all prescribed medications (name, dosage, frequency, duration, special instructions). If handwritten or typed notes mention follow-up visits, doctor appointments, or durations (e.g., "visit after 10 days", "follow up in 2 weeks"), calculate absolute dates from the prescription/visit date.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'doctor_name' => ['type' => 'string', 'nullable' => true],
                    'clinic_hospital' => ['type' => 'string', 'nullable' => true],
                    'prescription_date' => ['type' => 'string', 'description' => 'Absolute YYYY-MM-DD format of prescription/visit date', 'nullable' => true],
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
    public static function getLabReportConfig(?string $referenceDate = null): array
    {
        $dateContext = self::getSystemDateContext($referenceDate);
        return [
            'systemInstruction' => "You are a diagnostic lab report extraction engine for EarlySnap. Extract test panel results, numerical values, units, reference ranges, and status flags from the document image. Do NOT calculate or modify values — record only what is visible.\n\n" . $dateContext,
            'prompt' => 'Extract diagnostic lab report details: lab name, report date (YYYY-MM-DD format), patient name, and all test results (test name, observed value, unit, reference range, status flag).',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'lab_name' => ['type' => 'string', 'nullable' => true],
                    'report_date' => ['type' => 'string', 'description' => 'Absolute YYYY-MM-DD format', 'nullable' => true],
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
    public static function getPlanConfig(?string $referenceDate = null): array
    {
        $dateContext = self::getSystemDateContext($referenceDate);
        return [
            'systemInstruction' => "You are an actionable plan and task list parser for EarlySnap. Extract goals, step-by-step action items, assignees, and notes from handwritten or typed document notes.\n\n" . $dateContext,
            'prompt' => 'Extract action plan details: plan title, note date (resolve relative phrases like "today", "after 5 days" into absolute YYYY-MM-DD date based on document writing date or current system date), sequential action items (step number, task description, assigned to, status), and extra notes.',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'plan_title' => ['type' => 'string', 'nullable' => true],
                    'date' => ['type' => 'string', 'description' => 'Absolute YYYY-MM-DD format. Calculate relative terms based on document date if present, or current system date if not.', 'nullable' => true],
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
    public static function getConfigForCategory(string $category, ?string $referenceDate = null): array
    {
        return match ($category) {
            'bills', 'bill', 'receipt' => self::getBillsConfig($referenceDate),
            'deadline' => self::getDeadlineConfig($referenceDate),
            'prescription' => self::getPrescriptionConfig($referenceDate),
            'labreport', 'lab_report' => self::getLabReportConfig($referenceDate),
            'plan', 'handwritten_note' => self::getPlanConfig($referenceDate),
            default => self::getPlanConfig($referenceDate),
        };
    }

    /**
     * Backward compatibility alias for getConfigForCategory
     */
    public static function getConfigForDocType(string $docType, ?string $referenceDate = null): array
    {
        return self::getConfigForCategory($docType, $referenceDate);
    }

    /**
     * Stage 3: Dynamic Category-Aware Suggested Questions Config Generator
     */
    public static function getSuggestedQuestionsConfig(array $categories, array $extractedData, ?string $referenceDate = null): array
    {
        $dateContext = self::getSystemDateContext($referenceDate);
        $catsList = implode(', ', $categories);
        $extractedJsonStr = json_encode($extractedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $systemInstruction = <<<EOT
You are an expert document intelligence prompt designer for EarlySnap. Your goal is to analyze the document image and its extracted data, and generate top 3 highly specific, document-grounded questions that will amaze the user with their precise accuracy.

CRITICAL INSTRUCTION — MAKE QUESTIONS DEEPLY SPECIFIC (NOT GENERIC):
Every generated question MUST explicitly mention actual entities extracted from this document — such as specific vendor names, item descriptions, values, medication names, doctor/clinic names, test panel names, deadline titles, or task names!

❌ FORBIDDEN GENERIC QUESTIONS (DO NOT GENERATE THESE):
- "What is the grand total and payment due date?"
- "What are the prescribed medications and dosages?"
- "What is the deadline date and required action?"
- "Are any lab test results flagged as high or low?"
- "What are the key action steps and notes?"

✅ DEEPLY SPECIFIC EXAMPLES (FOLLOW THIS PATTERN):
- **bills**: "What is the tax amount and payment due date for the [Grand Total] bill from [Vendor Name]?", "Can you list the individual prices for [Item Name] and other line items?", "What vendor issued this [Grand Total] invoice?"
- **deadline**: "What specific action is required before the [Due Date] deadline for [Deadline Title]?", "Who issued the [Deadline Title] notice and what is the priority?", "When is [Deadline Title] due?"
- **prescription**: "How often should I take [Medication Name] prescribed by [Doctor/Clinic Name]?", "What special instructions did [Doctor Name] give for [Medication Name]?", "What is the dosage and duration for [Medication Name]?"
- **labreport**: "Why is the [Test Name] result of [Value] flagged as abnormal/high/low by [Lab Name]?", "What is the reference range for [Test Name] on this report?", "Which lab tests were strictly within normal limits for [Patient Name]?"
- **plan**: "What is step 1 ([First Task Description]) in [Plan Title]?", "Who is assigned to complete [Task Description]?", "What are all sequential action steps outlined in [Plan Title]?"

SELECTION & CATEGORY RULES:
1. Entity Embedding: Inject real names, titles, values, or items from the provided Extracted Data Context into every question.
2. Multi-Category Balance: If multiple categories are detected (e.g. ['bills', 'deadline']), generate EXACTLY 1 highly specific question for EACH detected category (up to max 3 questions total).
3. Single Category Limit: If only 1 category is present, generate the top 3 most specific, entity-infused questions for that category.
4. Maximum 3 Items: Return strictly 3 question strings in the response schema array.

{$dateContext}
EOT;

        $userPrompt = "Document Detected Categories: {$catsList}\n\nEXTRACTED DATA CONTEXT:\n{$extractedJsonStr}\n\nGenerate top 3 deeply specific, entity-infused questions about this exact document.";

        return [
            'systemInstruction' => $systemInstruction,
            'prompt' => $userPrompt,
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'questions' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string'
                        ],
                        'description' => 'Top deeply specific questions for the document (maximum 3 items).'
                    ]
                ],
                'required' => ['questions']
            ]
        ];
    }
}
