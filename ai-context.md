# OffPaper — Project Context

## What this is
OffPaper is a web app where users photograph paper documents (bills, prescriptions, lab reports, deadlines, plans/notes), and the app automatically classifies them, generates a 10–20 word summary, and converts them into structured digital records.

## Status
Active implementation — 2-pass Gemini AI pipeline and multi-category dashboard are functional.

## Tech Stack
- **Backend:** Plain PHP (no framework, no build step).
- **Database:** PostgreSQL (`offpapper` DB via PHP PDO `pdo_pgsql`).
- **Frontend:** Vanilla HTML5, CSS, JS (no bundlers/frameworks).
- **AI Engine:** Google Gemini REST API (`gemini-3.5-flash-lite`) via PHP cURL.

## AI Pipeline Architecture (2-Pass)
1. **Pass 1 (Classifier):** Analyzes document image $\rightarrow$ returns multi-label `categories` array (`prescription`, `labreport`, `plan`, `bills`, `deadline`) and a strict **10 to 20 word summary**.
2. **Pass 2 (Extractors):** Runs category-specific extraction prompt(s) $\rightarrow$ returns structured JSON schema for each detected category.

## Categories & Output Contracts
- **`bills`:** Vendor, bill date, invoice #, currency, line items (qty, price), subtotal, tax, total, due date.
- **`deadline`:** Title, due date, due time, priority (high/med/low), issuer, action required.
- **`prescription`:** Doctor, clinic/hospital, Rx date, patient name, medications list.
- **`labreport`:** Lab name, report date, patient, test results (value, unit, reference range, status flag).
- **`plan`:** Plan title, date, sequential action items checklist, notes.

## Schemas Reference
- [`public/document_ui_schemas.json`](file:///var/www/egnitech.com/html/wp-content/projects/sketch-n-ship/offpaper/public/document_ui_schemas.json)
- [`src/ai/document_upload/DocumentSchemas.php`](file:///var/www/egnitech.com/html/wp-content/projects/sketch-n-ship/offpaper/src/ai/document_upload/DocumentSchemas.php)
- [`src/ai/document_upload/DocumentPipeline.php`](file:///var/www/egnitech.com/html/wp-content/projects/sketch-n-ship/offpaper/src/ai/document_upload/DocumentPipeline.php)
