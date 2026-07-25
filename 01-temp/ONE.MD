Add an "Improve Plan" AI assistant feature for documents in the `plan` category, using the existing (currently unused) `user_uploads_knowledgebase` table for persistence.

CONTEXT
- Read ai-context.md first.
- Existing per-document chat: views/chat_modal.php, public/assets/js/doc-chat.js, public/api/chat.php.
- Document cards + detail modal live in public/dashboard.php (search for `data-open-doc-chat`).
- Table already exists (db/schema.sql): user_uploads_knowledgebase(id, user_upload_id UNIQUE, user_id, summary TEXT, chat_history JSONB default '[]', created_at, updated_at). No migration needed.

SCOPE (only plan-category documents get this — do not change chat behavior for other categories)

1. public/api/chat.php
   - Load the document's user_uploads_knowledgebase row if one exists (by user_upload_id).
   - Accept a `mode` field in the request body; when mode === "plan_assist", use a different system prompt: instead of generic Q&A, instruct Gemini to (a) give concrete suggestions to strengthen the plan and (b) ask clarifying questions to learn more about it. Keep the existing bullet/bold formatting rules.
   - When mode === "plan_assist", include the document's prior chat_history turns (if any) in the prompt sent to Gemini, so the conversation has continuity across messages.
   - When mode === "plan_assist", after getting the Gemini reply, append {role: user, text, ts} and {role: ai, text, ts} to chat_history and upsert the user_uploads_knowledgebase row (insert if none exists yet for this user_upload_id, else update). Do NOT do this for normal (non-plan_assist) chat — leave that path untouched.

2. New endpoint public/api/chat_finalize.php
   - POST, auth + ownership checked like chat.php.
   - Given document_id/uuid, reads the doc's chat_history from user_uploads_knowledgebase. If empty/missing, no-op and return ok.
   - Sends chat_history to Gemini asking for a short (2-4 sentence) recap of the plan discussion and current suggestions/open questions.
   - Writes the result to the `summary` column and bumps updated_at (upsert, keyed by user_upload_id).

3. public/assets/js/doc-chat.js
   - Add a new trigger button "Improve Plan" (📋 icon), shown only when the document's categories include "plan" — place it next to the existing "Chat with AI" button in both the doc card (public/dashboard.php doc-card__actions-grid) and the detail modal.
   - Clicking it opens the same #docChatModal but sets an internal `chatMode = 'plan_assist'`, uses a plan-specific greeting and quick-prompt chips (e.g. "What's missing from this plan?", "Any risks I should plan for?", "Help me prioritize these steps"), and includes `mode: 'plan_assist'` in every request to chat.php.
   - On open in plan_assist mode, fetch and render any existing chat_history for that document instead of starting blank, so users can resume a prior session.
   - On closeChatModal(), if chatMode === 'plan_assist' and at least one new message was sent this session, fire a POST to chat_finalize.php (fire-and-forget is fine, don't block the close animation on it).
   - Reset chatMode to null when the modal closes/reopens for a different document or a non-plan chat.

4. public/dashboard.php — detail modal
   - DEFAULT (confirm or change): show the persisted `summary` (if present) as a small "AI Plan Notes" block near the top of the detail modal, alongside the existing doc metadata/badges — not down by the extracted checklist.
   - Only render this block for plan-category documents that have a saved summary.

VERIFICATION
- Open a plan document → click "Improve Plan" → send 2-3 messages → confirm each AI reply references earlier context (proves chat_history round-trips into the prompt).
- Close the modal → check the user_uploads_knowledgebase row in psql: chat_history has the full turn list, summary is populated, updated_at is fresh.
- Reopen "Improve Plan" on the same document → confirm prior turns render instead of a blank chat.
- Open regular "Chat with AI" on a non-plan document → confirm it behaves exactly as before (no mode field, no DB writes) — nothing regresses.
- Confirm no changes were made to chat.php's behavior when mode is absent/not "plan_assist".
