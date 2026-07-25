# OffPaper — Database Structure & Schema Reference

Database engine: **PostgreSQL**  
Database name: `offpapper`  
User: `offpapper`  
Primary Schema definition file: [`db/schema.sql`](file:///var/www/egnitech.com/html/wp-content/projects/sketch-n-ship/offpaper/db/schema.sql)

---

## Entity Relationship Summary

```mermaid
erDiagram
    users ||--o{ user_uploads : "owns"

    users {
        bigint id PK
        text email UK
        text password_hash
        text name
        text google_sub UK
        text google_access_token
        text google_refresh_token
        timestamptz google_token_expires_at
        timestamptz created_at
    }

    user_uploads {
        bigint id PK
        uuid uuid UK
        bigint user_id FK
        text filename
        text original_filename
        text file_path
        text file_size
        text mime_type
        text source
        text status
        timestamptz created_at
    }
```

---

## Tables Overview

### 1. `users`
Stores registered user credentials, user profile information, and Google OAuth tokens.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `BIGINT` | `PRIMARY KEY`, `BIGSERIAL` | Unique auto-incrementing user ID |
| `email` | `TEXT` | `NOT NULL`, `UNIQUE` | User email address (stored lowercased + trimmed) |
| `password_hash` | `TEXT` | `NULLABLE` | Password hash created via `password_hash()`. `NULL` for Google-only login |
| `name` | `TEXT` | `NULLABLE` | User display name |
| `google_sub` | `TEXT` | `UNIQUE`, `NULLABLE` | Google OAuth `sub` unique subject identifier |
| `google_access_token` | `TEXT` | `NULLABLE` | Google OAuth access token |
| `google_refresh_token` | `TEXT` | `NULLABLE` | Google OAuth refresh token for offline calendar/API access |
| `google_token_expires_at` | `TIMESTAMPTZ` | `NULLABLE` | Expiration timestamp for Google access token |
| `created_at` | `TIMESTAMPTZ` | `NOT NULL`, `DEFAULT now()` | User registration timestamp |

#### Indexes & Constraints
- `users_pkey`: `PRIMARY KEY (id)`
- `users_email_key`: `UNIQUE (email)`
- `users_google_sub_key`: `UNIQUE (google_sub)`

---

### 2. `user_uploads`
Stores metadata and disk storage paths for documents photographed via browser camera or uploaded by users.

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `BIGINT` | `PRIMARY KEY`, `BIGSERIAL` | Unique auto-incrementing record ID |
| `uuid` | `UUID` | `NOT NULL`, `UNIQUE` | Public unique identifier (RFC 4122 v4 UUID) |
| `user_id` | `BIGINT` | `NOT NULL`, `FK -> users(id)` | Foreign key linking to owning user (cascades on deletion) |
| `filename` | `TEXT` | `NOT NULL` | Stored file name on disk (e.g., `daab7d07-8cfb-467d-8655-5896296582f8.jpg`) |
| `original_filename` | `TEXT` | `NOT NULL` | Original client file name (e.g., `camera_capture_1784964319555.jpg`) |
| `file_path` | `TEXT` | `NOT NULL` | Relative file path from project root (e.g., `user-uploads/<uuid>.jpg`) |
| `file_size` | `BIGINT` | `NOT NULL` | File size in bytes |
| `mime_type` | `TEXT` | `NOT NULL` | MIME content type (e.g., `image/jpeg`, `image/png`, `application/pdf`) |
| `source` | `TEXT` | `NOT NULL`, `DEFAULT 'camera'` | Capture source: `'camera'` or `'file_input'` |
| `status` | `TEXT` | `NOT NULL`, `DEFAULT 'pending'` | Processing status: `'pending'`, `'processed'`, `'error'` |
| `created_at` | `TIMESTAMPTZ` | `NOT NULL`, `DEFAULT now()` | Upload timestamp |

#### Indexes & Constraints
- `user_uploads_pkey`: `PRIMARY KEY (id)`
- `user_uploads_uuid_key`: `UNIQUE (uuid)`
- `idx_user_uploads_user_id`: B-tree index on `user_id`
- `idx_user_uploads_uuid`: B-tree index on `uuid`
- `user_uploads_user_id_fkey`: `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE`

---

## Future Schema Extensions (Planned)

When AI extraction is enabled, the `user_uploads` table will be extended with:
- `doc_type` (`TEXT`): Categorized document type (`'bill'`, `'prescription'`, `'handwritten_note'`, etc.)
- `extracted_json` (`JSONB`): Extracted structured JSON data returned from Google Gemini API.
