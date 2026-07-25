# Writing Scripts from Scratch

Covers the mechanics of standing up a runnable Python script or small project:
project layout, the canonical entry-point skeleton, CLI argument parsing, error
handling with exit codes, logging, common I/O, virtual environments, dependency
management, and secrets handling.

---

## 1. Project Layout

Pick the scale that fits the task. Grow to the next when the current starts to hurt.

### Scale 1 — Single script

Use when the task is a self-contained automation that will never be imported:

```
my_script.py
data/           # input files
output/         # generated files
.env            # secrets (never commit)
.gitignore
```

### Scale 2 — Small multi-file project

Use when the script exceeds ~200 lines or has separable responsibilities:

```
my-project/
├── main.py         # entry point only — parses args, calls helpers
├── helpers.py      # reusable logic (no I/O side-effects)
├── data/           # input files
├── output/         # generated files
├── .env            # secrets
├── .gitignore
└── pyproject.toml  # deps + tool config
```

Keep `main.py` thin: argument parsing, config loading, top-level orchestration.
Put business logic in `helpers.py` (or domain-named modules). This makes
`helpers.py` testable without mocking I/O.

Scale 3 — installable package — adds `src/my_package/`, `tests/`, and a full
`[build-system]` block. See `references/idiomatic-python.md` §6 for that layout.

---

## 2. The Script Skeleton

Start every new script with this template. Fill in sections from the top down.

```python
"""
Brief one-line description of what this script does.

Usage:
    python my_script.py <input_path> [--verbose]
"""

import argparse
import logging
from pathlib import Path

log = logging.getLogger(__name__)


def main() -> int:
    args = parse_args()
    logging.basicConfig(
        level=logging.DEBUG if args.verbose else logging.INFO,
        format="%(levelname)s: %(message)s",
    )

    # --- your logic here ---
    log.info("Processing %s", args.input_path)

    return 0  # exit code: 0 = success


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("input_path", type=Path, help="Input file to process")
    parser.add_argument(
        "--output", type=Path, default=Path("output"), help="Output directory"
    )
    parser.add_argument("--verbose", action="store_true", help="Enable debug logging")
    return parser.parse_args()


if __name__ == "__main__":
    raise SystemExit(main())
```

**Why `raise SystemExit(main())`**: `main()` returns an `int` exit code;
`SystemExit` passes it to the OS without printing a traceback. `raise SystemExit`
is preferred over `sys.exit()` — it avoids importing `sys` just for exit and
signals intent clearly to Mypy.

---

## 3. CLI Arguments with `argparse`

`argparse` is stdlib — no third-party dependency. It generates `--help`
automatically and type-converts arguments via the `type=` parameter.

```python
def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Fetch records from an API and write them to a JSON file."
    )

    # Positional — required, no -- prefix
    parser.add_argument("url", type=str, help="API endpoint to fetch")

    # Optional with a default
    parser.add_argument(
        "--output", type=Path, default=Path("output.json"), help="Output file path"
    )

    # Boolean flag — False by default, True when passed
    parser.add_argument(
        "--dry-run", action="store_true", help="Print what would happen without writing"
    )

    # Integer option
    parser.add_argument("--limit", type=int, default=100, help="Max records to fetch")

    return parser.parse_args()
```

**When to reach for `click` or `typer` instead of `argparse`:**
- The CLI has subcommands (`my-tool fetch`, `my-tool push`)
- You need shell completion
- The argument schema is large enough that decorators read more clearly than builder calls

Both are third-party; add them to `pyproject.toml` dependencies before using.

---

## 4. Error Handling and Exit Codes

`main()` returns an `int`. Catch specific exceptions at the call-site boundary;
translate them into a human-readable message and a non-zero exit code.

```python
def main() -> int:
    args = parse_args()
    logging.basicConfig(level=logging.INFO, format="%(levelname)s: %(message)s")

    try:
        records = load_records(args.input_path)
        write_output(records, args.output)
    except FileNotFoundError as exc:
        log.error("Input file not found: %s", exc.filename)
        return 1
    except PermissionError as exc:
        log.error("Permission denied: %s", exc.filename)
        return 1
    except ValueError as exc:
        log.error("Invalid data: %s", exc)
        return 2

    log.info("Done. Wrote %d records to %s", len(records), args.output)
    return 0
```

**Why specific exceptions, not bare `except`**: each `except SomeError` clause
documents a real failure mode and lets everything unexpected surface its
traceback — which is the information you need to diagnose a real bug. A bare
`except:` or `except Exception:` hides those bugs silently.

**Exit code conventions (Unix):**

| Code | Meaning |
|------|---------|
| `0` | Success |
| `1` | General / expected failure (file not found, network error, API error) |
| `2` | Misuse / bad input (invalid arguments, schema mismatch) |

Do **not** wrap the entire script body in a module-level `try/except` — that
makes unexpected errors silent and undebuggable.

---

## 5. Logging over `print`

`print` is for program **output** — data written to stdout that the caller may
pipe or redirect. `logging` is for **diagnostics** — messages about what the
script is doing.

```python
import logging

log = logging.getLogger(__name__)

# Configure once, at the entry point
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s: %(message)s",
)

log.debug("Loaded %d rows", len(rows))       # visible only at DEBUG level
log.info("Processing file %s", path)         # normal progress messages
log.warning("Rate limit approaching")        # something worth noticing
log.error("Failed to connect: %s", err)      # a recoverable error
```

Use `%s`-style formatting in log calls (not f-strings) — the logging module
defers string interpolation until the message will actually be emitted. When
the log level filters out the message, no string is built at all.

---

## 6. Common I/O Patterns

### File I/O with `pathlib`

```python
from pathlib import Path

input_path = Path("data/records.json")

# Check existence before reading
if not input_path.exists():
    raise FileNotFoundError(input_path)

# Read
text = input_path.read_text(encoding="utf-8")

# Write — create parent directories first if needed
output_path = Path("output/results.csv")
output_path.parent.mkdir(parents=True, exist_ok=True)
output_path.write_text(content, encoding="utf-8")
```

Prefer `pathlib.Path` over `os.path` — it is object-oriented, composable, and
self-documenting: `path / "subdir" / "file.txt"` instead of
`os.path.join(path, "subdir", "file.txt")`.

### HTTP fetch with `requests`

```python
import requests


def fetch_json(url: str, timeout: int = 10) -> dict:
    """Fetch a URL and return its JSON body. Raises on non-2xx status."""
    response = requests.get(url, timeout=timeout)
    response.raise_for_status()  # raises HTTPError on 4xx/5xx
    return response.json()
```

Always pass `timeout` — without it, the call may hang indefinitely.
`raise_for_status()` is cleaner than manually checking `response.status_code`;
it raises `requests.HTTPError` with the status code embedded in the message.

### JSON read / write

```python
import json
from pathlib import Path

# Read
data: list[dict] = json.loads(Path("input.json").read_text(encoding="utf-8"))

# Write — indent=2 keeps it human-readable in version control
Path("output.json").write_text(
    json.dumps(data, indent=2, ensure_ascii=False),
    encoding="utf-8",
)
```

---

## 7. Virtual Environment and Dependencies

Isolate every project in its own virtual environment. This prevents package
version conflicts between projects and makes the dependency list explicit.

```bash
# Create and activate (Mac/Linux)
python -m venv .venv
source .venv/bin/activate

# Create and activate (Windows)
python -m venv .venv
.venv\Scripts\activate

# Install declared dependencies (after editing pyproject.toml)
pip install -e ".[dev]"
```

`uv` is a fast modern alternative (Rust-based, drop-in replacement):

```bash
uv venv
uv pip install -e ".[dev]"
```

**Declare dependencies in `pyproject.toml`** — that is the single source of
truth. Humans edit this file; tools read it.

```toml
[project]
requires-python = ">=3.10"
dependencies = [
    "requests >= 2.28.0",
    "python-dotenv >= 1.0.0",
]

[project.optional-dependencies]
dev = ["pytest >= 7.0.0", "ruff >= 0.1.0", "mypy >= 1.0.0"]
```

See `references/tooling-and-packaging.md` for the complete `pyproject.toml`
blueprint including Ruff, Mypy, and Pytest configuration.

**`requirements.txt` as an optional frozen lockfile only** — run
`pip freeze > requirements.txt` to snapshot exact installed versions for
reproducible CI deploys. It is not a substitute for declaring dependencies in
`pyproject.toml`; edit `pyproject.toml`, not `requirements.txt`.

---

## 8. Secrets and `.gitignore`

Never hardcode secrets — API keys, passwords, tokens — in source code. A single
key committed to a repository can result in unauthorized access or unexpected
charges, even if later deleted (git history retains it).

### Load from the environment

```python
import os

api_key = os.environ.get("MY_API_KEY")
if not api_key:
    raise ValueError("MY_API_KEY environment variable is not set")
```

### Or from a `.env` file using `python-dotenv`

```python
from dotenv import load_dotenv
import os

load_dotenv()  # reads .env in the current directory into os.environ

api_key = os.environ["MY_API_KEY"]
```

**.env file — never commit this:**
```
MY_API_KEY=sk-abc123
DATABASE_URL=postgresql://user:pass@localhost/mydb
```

### `.gitignore` — minimum for every Python project

```gitignore
# Secrets — never commit
.env

# Virtual environment
.venv/
venv/

# Python cache
__pycache__/
*.pyc
*.pyo
.mypy_cache/
.ruff_cache/

# Test and coverage artifacts
.pytest_cache/
.coverage
```

Add `.gitignore` before the first `git add`. Removing a secret from git history
after it has been committed requires rewriting history **and** rotating the key.
Git-ignore secrets from the start.
