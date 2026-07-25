# OffPaper — Project Context

## What this is
OffPaper is a web app where users photograph paper documents (bills, prescriptions, lab reports, deadlines, plans/notes), and the app automatically classifies them, generates a 10–20 word summary, and converts them into structured digital records. Users can also chat with an AI about any scanned document, and sync detected deadlines straight to Google Calendar.

## Status
Active implementation. Working end-to-end: email/password + Google login, camera/file-upload capture, 2-pass Gemini AI pipeline, multi-category dashboard with filtering, per-document AI chat (split-panel layout with plan finalisation and Gemini Flash Lite speech-to-text mic input), Google Calendar sync for deadlines, and document deletion.

## Tech Stack
- **Backend:** Plain PHP (no framework, no build step).
- **Database:** PostgreSQL (`offpapper` DB via PHP PDO `pdo_pgsql`).
- **Frontend:** Vanilla HTML5, CSS, JS (no bundlers/frameworks).
- **AI Engine:** Google Gemini REST API (`gemini-3.5-flash-lite`) via PHP cURL — the only AI provider used anywhere in this project (classification, extraction, document chat, plan synthesis, and speech-to-text voice input transcription).

## Architecture & Request Flow
- `public/` is the web docroot; `src/` and `views/` live outside it and are never web-reachable.
- Every page requires `src/bootstrap.php` first, which loads `.env`, sets error/session config, and pulls in `db.php`, `helpers.php`, `auth.php`, `google_auth.php`, `google_calendar.php`, and `src/ai/ai.php`.
- Pages set `$page_title` / `$page_nav` / `$page_css` / `$page_js` then require `views/header.php` ... `views/footer.php`.
- `url()` / `base_path()` (`src/helpers.php`) auto-detect a subdirectory deployment (e.g. `/offpaper`) from `SCRIPT_NAME`/`REQUEST_URI`, or `APP_BASE_PATH` env override — all internal links go through `url()`.
- Uploaded files are stored on disk under `user-uploads/` (relative to `APP_ROOT`) and served back through `public/file.php`, which checks session ownership before streaming the file (never linked directly).
- Auth: `src/auth.php` handles email/password (bcrypt via `password_hash()`) and Google OAuth account linking/creation, keyed by `google_sub` or matching email. Sessions use `session_regenerate_id()` on login and strict-mode cookies.

## Google OAuth & Calendar Integration
- `src/google_auth.php` — authorization-code flow (plain cURL, no SDK). Requests scopes `openid email profile` **plus** `https://www.googleapis.com/auth/calendar.events`, with `access_type=offline` + `prompt=consent` so a refresh token is always issued.
- `public/auth/google/start.php` / `callback.php` — CSRF-protected via a random `state` stored in session.
- `src/google_calendar.php` — `google_get_valid_access_token()` transparently refreshes the access token from the stored refresh token when expired; `google_calendar_add_event()` / `google_calendar_delete_event()` call the Calendar v3 REST API directly.
- `public/api/add_to_calendar.php` — builds a calendar event from a document's `deadline` category data, blocks adding events whose due date has already passed, and persists `calendar_event_id` / `calendar_html_link` back into `extracted_json` so the dashboard can show "In Google Calendar" instead of "Add to Calendar".
- The dashboard (`public/dashboard.php`) computes `isPast` for deadline documents (comparing `due_date`/`due_time` to `time()`) to show a "Passed" badge and disable calendar sync on expired deadlines.

## AI Pipeline Architecture (2-Pass)
Implemented in `src/ai/document_upload/DocumentPipeline.php` + `DocumentSchemas.php`, driven by `src/ai/GeminiClient.php` (raw cURL, `responseSchema` for structured JSON output).
1. **Pass 1 (Classifier):** Analyzes the document image → returns a multi-label `categories` array (`prescription`, `labreport`, `plan`, `bills`, `deadline`) and a strict **10 to 20 word summary**. All schemas share a system-date context block that resolves relative dates ("today", "after 5 days") to absolute `YYYY-MM-DD`, anchored to the document's own visible date when present, else the current system date.
2. **Pass 2 (Extractors):** Runs one category-specific extraction call per detected category → structured JSON per category, merged into `extracted_json` as `{ summary, categories, data: { <category>: {...} } }` and saved on the `user_uploads` row (`status`, `doc_type` = primary category, `extracted_json`).
- Triggered synchronously from `public/upload.php` right after the file is saved and the DB row inserted (`ai_process_upload($dbId)`); failures set `status = 'error'` but don't fail the upload response.

## Categories & Output Contracts
- **`bills`:** Vendor, bill date, invoice #, currency, line items (qty, price), subtotal, tax, total, due date.
- **`deadline`:** Title, due date, due time, priority (high/med/low), issuer, action required.
- **`prescription`:** Doctor, clinic/hospital, Rx date, patient name, medications list.
- **`labreport`:** Lab name, report date, patient, test results (value, unit, reference range, status flag).
- **`plan`:** Plan title, date, sequential action items checklist, notes.

## Document AI Chat
- `public/api/chat.php` — per-document Q&A. Loads the `user_uploads` row (auth + ownership checked), builds a system prompt (bullet-point, bolded-highlights style) plus a context block of the doc's summary/categories/`extracted_json`, re-attaches the original image file, and calls Gemini (`gemini-3.5-flash-lite`) fresh on every message. GET requests return `chat_history`, `summary`, and `plan_snapshots` (from `extracted_json`).
- **Modes:** normal chat or `plan_assist` (activated automatically for `plan`-type docs, or via `data-chat-mode="plan_assist"`). Plan assist uses a different system prompt focused on strengthening, questioning, and prioritising the plan.
- Frontend: `views/chat_modal.php` + `public/assets/js/doc-chat.js` + `public/assets/css/chat.css`. The chat API endpoint URL is overridable via `window.OFFPAPER_CHAT_URL` (defaults to `api/chat.php`). The finalise endpoint is overridable via `window.OFFPAPER_CHAT_FINALISE_URL` (defaults to `api/chat_finalise_plan.php`).
- **Plan-assist session loading:** On modal open in `plan_assist` mode, `loadPlanAssistSession()` makes a single `chat.php` GET call and renders, in order: the latest snapshot's contextual `greeting` (falling back to the generic `renderPlanAssistGreeting()` if no snapshot exists), then chat history, then the plan panel/pill/version tabs. This replaces separately calling `renderPlanAssistGreeting()` + `loadPlanSnapshot()` up front. `loadPlanSnapshot()` is retained only for non-plan-assist snapshot refreshes.
- **Speech-to-Text (STT):** A microphone button (`#docChatMicBtn`) in the chat input bar allows users to speak their prompts. Recorded audio chunks (`MediaRecorder`) are sent to `public/api/transcribe_audio.php`, which utilizes `gemini-3.5-flash-lite` for verbatim speech transcription before placing the text in the input field.
- **Note:** `chat.php` persists conversation history for **all document types** in `user_uploads_knowledgebase`. History is loaded on modal open and sent as context on subsequent messages. A `mode=clear_history` POST wipes history to support the "New Chat" button in the frontend.
- **Auto-recap on close:** `public/api/chat_finalize.php` (note: US spelling, distinct from `chat_finalise_plan.php` below) is fired fire-and-forget by `doc-chat.js` when the modal is closed in `plan_assist` mode and at least one new message was sent that session. It summarizes the chat history into a plain-text 2–4 sentence recap via Gemini and upserts it into `user_uploads_knowledgebase.summary` — a lightweight background recap, separate from the explicit "Finalise the Plan" snapshot flow.

## Plan Finalisation ("Finalise the Plan" feature)
Activated only in `plan_assist` mode (plan-type documents). Allows the user to synthesise the entire chat into a clean, versioned plan snapshot.

**Trigger:** User types `"amazing, lets update the plan"` (case-insensitive, intercepted client-side before sending to chat API) OR clicks the green **Finalise** button in the chat input bar.

**Flow:**
1. `public/api/chat_finalise_plan.php` (POST `{ document_id, uuid }`) loads the full chat history from `user_uploads_knowledgebase`.
2. Calls Gemini with a structured synthesis prompt → produces a markdown plan with `## 🎯 Goal`, `## ✅ Action Items`, `## 📅 Timeline`, `## ⚠️ Risks & Notes`, `## 💡 Next Steps` sections, followed by a `---GREETING---` delimiter and a one-sentence (max 25 words) contextual greeting referencing the plan's goal, for welcoming the user back next time.
3. Splits the response on `---GREETING---` into `planText` and `greeting`, then prepends the new snapshot `{ finalised_at, plan_text, greeting }` to `extracted_json->plan_snapshots` (max 3 kept, newest first, oldest dropped).
4. Saves updated `extracted_json` to `user_uploads`, clears `chat_history` in `user_uploads_knowledgebase`.
5. Returns `{ ok, plan_text, finalised_at, greeting, snapshot_count }`.

**Frontend UI — Split-Panel Chat Modal:**
- The modal is full-screen wide (max 1080px), 90vh tall, split into two panes:
  - **Left pane (`.chat-pane`):** conversation, prompts, input bar with Finalise + Send buttons.
  - **Right pane (`#docChatPlanPanel`, `.plan-panel`):** the finalised plan, scrollable, with heading-aware `formatPlanMarkdown()` rendering. Shown/hidden by toggling `.plan-panel--open` on `#docChatSplitBody`.
- A **1-line green pill** (`#docChatPlanPill`) in the modal header reads "● Plan saved · [date]". Clicking it opens the right panel. It only appears when at least one snapshot exists.
- **Version tabs** (`#planVersionTabs`) appear when 2–3 snapshots exist — click to switch between plan versions in the panel.
- After finalising, a compact green card appears in chat saying "Plan finalised → View it in the panel →".
- On modal open in `plan_assist` mode, the GET response from `chat.php` includes `plan_snapshots`; if any exist, the pill and panel are pre-populated automatically.

## Document Deletion
- `public/api/delete_document.php` — CSRF-protected, ownership-checked. Deletes the associated Google Calendar event (if any), removes the file from `user-uploads/` on disk, then deletes the `user_uploads` row (cascades to `user_uploads_knowledgebase` via FK).

## Database
3 tables in PostgreSQL — see [`db/schema.sql`](db/schema.sql) and [`db/database_structure.md`](db/database_structure.md) for full column/index reference:
- **`users`** — email/password auth + Google profile & OAuth tokens (`google_access_token`, `google_refresh_token`, `google_token_expires_at`) for offline Calendar access.
- **`user_uploads`** — one row per scanned document: storage path, mime/size, `status` (`pending`/`processed`/`error`), `doc_type` (primary category), `extracted_json` (full pipeline output). **`extracted_json` also holds `plan_snapshots: [{ finalised_at, plan_text, greeting }, ...]` (max 3, newest first)** for plan-type documents.
- **`user_uploads_knowledgebase`** — `chat_history` (JSONB array of `{ role, text, ts }` turns) and `summary` per document. Fully wired; history persisted on every chat message, cleared on "New Chat" or after plan finalisation.

## Key Files Reference
- [`public/document_ui_schemas.json`](public/document_ui_schemas.json) — frontend-facing copy of the extraction schemas (used for rendering the doc-detail modal's dynamic fields).
- [`src/ai/document_upload/DocumentSchemas.php`](src/ai/document_upload/DocumentSchemas.php) — prompts + JSON schemas per category.
- [`src/ai/document_upload/DocumentPipeline.php`](src/ai/document_upload/DocumentPipeline.php) — 2-pass orchestration + DB persistence.
- [`src/ai/GeminiClient.php`](src/ai/GeminiClient.php) — low-level Gemini REST client.
- [`public/upload.php`](public/upload.php) — capture/upload endpoint, triggers the AI pipeline.
- [`public/dashboard.php`](public/dashboard.php) — main UI: stats, category filter tabs, document grid/cards, detail modal, and a "Scan your first document" onboarding step indicator shown only when `!$hasUploads` (hidden once the user has any upload).
- [`public/api/chat.php`](public/api/chat.php) — document Q&A + history persistence; GET also returns `plan_snapshots`.
- [`public/api/transcribe_audio.php`](public/api/transcribe_audio.php) — Speech-to-Text audio transcription endpoint powered by Gemini 3.5 Flash Lite.
- [`public/api/chat_finalize.php`](public/api/chat_finalize.php) — fire-and-forget recap summary on modal close (plan_assist only); writes to `user_uploads_knowledgebase.summary`.
- [`public/api/chat_finalise_plan.php`](public/api/chat_finalise_plan.php) — plan synthesis endpoint; stores versioned snapshots in `extracted_json`, clears chat.
- [`public/api/add_to_calendar.php`](public/api/add_to_calendar.php), [`public/api/delete_document.php`](public/api/delete_document.php) — document-scoped JSON API endpoints.
- [`views/chat_modal.php`](views/chat_modal.php) — split-panel chat modal HTML (left chat pane + right plan panel).
- [`public/assets/js/doc-chat.js`](public/assets/js/doc-chat.js) — full chat controller: message sending, history loading, plan finalisation flow, pill/panel/version-tab wiring, `formatPlanMarkdown()`.
- [`public/assets/css/chat.css`](public/assets/css/chat.css) — all chat modal styles; split-panel layout, plan pill, plan panel, version tabs, responsive collapse.
