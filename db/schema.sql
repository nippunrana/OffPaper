-- OffPaper database schema
-- Apply with: psql -h $DB_HOST -U $DB_USER -d $DB_NAME -f db/schema.sql

CREATE TABLE IF NOT EXISTS users (
    id            BIGSERIAL PRIMARY KEY,
    email         TEXT        NOT NULL UNIQUE,   -- always stored lowercased + trimmed
    password_hash TEXT,                          -- NULL = Google-only account
    name          TEXT,
    google_sub    TEXT UNIQUE,                   -- Google 'sub' claim; NULL = not linked
    google_access_token     TEXT,                -- OAuth access token
    google_refresh_token    TEXT,                -- OAuth refresh token for offline calendar access
    google_token_expires_at TIMESTAMPTZ,         -- Expiration timestamp for access token
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);
