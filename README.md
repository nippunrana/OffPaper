# OffPaper

Snap a photo of any paper — a bill, a prescription, a lab report, a
handwritten plan — and OffPaper classifies it, summarizes it, and turns it
into a structured digital record. Chat with an AI about any scanned
document, and sync detected deadlines straight to Google Calendar. See
[`ai-context.md`](ai-context.md) for full project context.

Working end-to-end: email/password + Google login, camera/file-upload
capture, a 2-pass Gemini AI pipeline (classification + structured
extraction), a multi-category dashboard, per-document AI chat with plan
finalisation, Google Calendar sync, and document deletion.

## Requirements

- PHP 8.1+ with `pdo_pgsql` and `curl` extensions
- PostgreSQL
- A [Google Gemini API key](https://aistudio.google.com/app/apikey) (used for all AI features — classification, extraction, and chat)

```bash
php -m | grep -E 'pdo_pgsql|curl'   # both must print
```

## Setup

1. Create the database and role (adjust the password):

   ```bash
   psql -d postgres -c "CREATE ROLE offpapper LOGIN PASSWORD 'devpass';"
   psql -d postgres -c "CREATE DATABASE offpapper OWNER offpapper;"
   psql -h localhost -U offpapper -d offpapper -f db/schema.sql
   ```

2. Copy the env file and fill in your values:

   ```bash
   cp .env.example .env
   ```

3. Get a Gemini API key from [Google AI Studio](https://aistudio.google.com/app/apikey)
   and set `GEMINI_API_KEY` in `.env`. Required for document scanning, chat,
   and plan finalisation.

4. Set up Google Sign-In (skip if you only want email/password for now):
   - [console.cloud.google.com](https://console.cloud.google.com) → create a project.
   - **APIs & Services → OAuth consent screen** → User type **External** →
     fill in app name/support email → Save.
   - **Scopes** → add `openid`, `.../auth/userinfo.email`, `.../auth/userinfo.profile`,
     and `.../auth/calendar.events` (needed for syncing deadlines to Google Calendar).
   - Leave publishing status **Testing** and add your own Google account
     under **Test users**.
   - **Credentials → Create credentials → OAuth client ID → Web application.**
     Under *Authorized redirect URIs* add the exact value of
     `GOOGLE_REDIRECT_URI` from your `.env` (must match byte-for-byte,
     including the port if you're running on one).
   - Copy the Client ID and secret into `.env`.

## Run (dev)

```bash
php -S localhost:8000 -t public
```

If you run on a port other than 80, update `GOOGLE_REDIRECT_URI` in `.env`
(and the redirect URI registered in Google Cloud Console) to match, e.g.
`http://localhost:8000/auth/google/callback.php`.

## Verify

1. Visit `http://localhost:8000/` — landing page loads, header shows "Log in".
2. `curl -i http://localhost:8000/.env` → should 404 (docroot is `public/`).
3. Click "Get started" → sign up with an email + password → lands on `/dashboard.php`.
4. Refresh the dashboard URL directly — stays (session persists).
5. Log out → visiting `/dashboard.php` redirects to `/login.php`.
6. "Continue with Google" → completes consent → lands on `/dashboard.php`.
7. From the dashboard, scan/upload a document → it appears on the dashboard
   classified into a category with a generated summary.
8. Open a document → chat with the AI about it; for a `plan` document, ask it
   to finalise the plan and confirm the snapshot appears in the plan panel.
9. On a `deadline` document with a future date, click "Add to Calendar" →
   confirm the event appears in Google Calendar.

## Layout

```
db/schema.sql        users / user_uploads / user_uploads_knowledgebase DDL
src/                  config, DB, auth, Google OAuth, Calendar, AI pipeline — never web-reachable
src/ai/               GeminiClient + 2-pass document classification/extraction pipeline
views/                shared header.php / footer.php partials + chat/scan modals
public/               docroot: pages + api/{chat,calendar,delete} + assets/{css,js}
```

Each page sets `$page_title`, `$page_nav`, `$page_css`, `$page_js` before
requiring `views/header.php`, and requires `views/footer.php` at the end.
