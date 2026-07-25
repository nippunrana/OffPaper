# OffPaper

Snap a photo of any paper — a bill, a prescription, a handwritten note — and
OffPaper turns it into a reminder, a record, or editable text. See
`ai-context.md` for full project context.

This repo currently contains the foundation: a landing page, email/Google
login, and a placeholder dashboard. No document scanning yet — see
`01-temp/plan.md` for what's next.

## Requirements

- PHP 8.1+ with `pdo_pgsql` and `curl` extensions
- PostgreSQL

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

3. Set up Google Sign-In (skip if you only want email/password for now):
   - [console.cloud.google.com](https://console.cloud.google.com) → create a project.
   - **APIs & Services → OAuth consent screen** → User type **External** →
     fill in app name/support email → Save.
   - **Scopes** → add `openid`, `.../auth/userinfo.email`, `.../auth/userinfo.profile`.
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

## Layout

```
db/schema.sql       users table DDL
src/                 config, DB, auth, Google OAuth — never web-reachable
views/               shared header.php / footer.php partials
public/              docroot: pages + assets/{css,js}
```

Each page sets `$page_title`, `$page_nav`, `$page_css`, `$page_js` before
requiring `views/header.php`, and requires `views/footer.php` at the end.
