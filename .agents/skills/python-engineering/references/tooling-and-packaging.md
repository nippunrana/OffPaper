# Tooling and Packaging Reference

Covers the three-pillar quality toolchain — Ruff (lint/format), Mypy (static
typing), Pytest (behavioral testing) — plus the `pyproject.toml` blueprint that
unifies their configuration.

---

## 1. Ruff — Linting and Formatting

Ruff is a single binary written in Rust that replaces Flake8, Black, isort,
pyupgrade, and autoflake. It is 10–100× faster than legacy toolchains and uses
a single `pyproject.toml` section for all configuration.

### Core commands

```bash
ruff check .             # lint the project
ruff check --fix .       # lint and auto-fix safe issues (unused imports, etc.)
ruff format .            # format code (Black-compatible, 88-char default)
ruff check --fix && ruff format .   # standard pre-commit sequence
```

The `--fix` flag is the "self-healing" step: Ruff resolves minor issues
automatically before you read the output. Run it first so the remaining errors
are genuinely actionable.

### Standard `pyproject.toml` configuration

```toml
[tool.ruff]
line-length = 88
target-version = "py312"

[tool.ruff.lint]
select = ["E", "F", "I", "B"]
# E = pycodestyle errors
# F = pyflakes (unused imports, undefined names)
# I = isort (import ordering)
# B = flake8-bugbear (common bug patterns)
ignore = []

[tool.ruff.format]
quote-style = "double"
indent-style = "space"
```

Add rule sets incrementally as the codebase matures. `"UP"` (pyupgrade) and
`"C90"` (mccabe complexity) are useful next steps.

### What Ruff replaces

| Legacy tool | Ruff equivalent |
|-------------|----------------|
| Flake8 + plugins | `select = ["E", "F", "B"]` |
| Black | `ruff format` |
| isort | `select = ["I"]` |
| pyupgrade | `select = ["UP"]` |
| autoflake | `ruff check --fix` removes unused imports |

---

## 2. Mypy — Static Type Checking

Mypy enforces PEP 484 type hints statically. It catches argument type mismatches,
missing attributes, and undefined names before any test runs.

### Core commands

```bash
mypy my_package/           # check a package
mypy my_script.py          # check a single file
mypy --strict my_package/  # strict mode — no untyped definitions allowed
```

### Standard configuration

```toml
[tool.mypy]
python_version = "3.12"
strict = false             # start with false; enable per-module as coverage grows
warn_return_any = true
warn_unused_ignores = true
```

Enable strict mode incrementally on stable, well-tested modules:

```toml
[[tool.mypy.overrides]]
module = "my_package.core"
strict = true
```

### Error codes to monitor in the agent loop

These are the codes that signal structural contract breaks — fix before running Pytest:

| Code | Meaning | Fix |
|------|---------|-----|
| `[attr-defined]` | Attribute doesn't exist on the type | Likely an API hallucination; check the actual API |
| `[arg-type]` | Wrong type passed to a function | Check the callee's annotation |
| `[assignment]` | Assigning incompatible type to variable | Widen the declared type or fix the assigned value |
| `[name-defined]` | Name used before it was defined or imported | Add the import or fix the variable scope |
| `[return-value]` | Return type doesn't match annotation | Fix the return statement or update the annotation |

### Gradual typing strategy

1. Annotate widely-imported modules first — core utilities give the highest ROI.
2. Run `mypy` on CI as a required check so typed modules can't regress.
3. Use `Any` as an escape hatch on dynamic data you can't annotate yet — not as a default.
4. Replace `Any` with `TypedDict`, `Protocol`, or concrete types as you gain confidence.

### Third-party stubs

If Mypy complains about an untyped third-party library:

```bash
pip install types-requests   # install stubs if available
```

For libraries without stubs, add to `pyproject.toml`:

```toml
[[tool.mypy.overrides]]
module = "some_untyped_lib"
ignore_missing_imports = true
```

---

## 3. Pytest — Behavioral Testing

Pytest uses plain `assert` statements and provides rich failure diffs that tell
you exactly what was wrong — making test output directly usable as an error signal.

### Core commands

```bash
pytest                     # auto-discover and run all tests
pytest tests/test_core.py  # run a specific file
pytest -x                  # stop on first failure
pytest -v                  # verbose output with test names
```

### Writing tests

```python
# Simple assert — pytest introspects the expression on failure
def test_sum():
    assert sum([1, 2, 3]) == 6


# Fixtures — shared, reusable test state
import pytest

@pytest.fixture
def active_user() -> dict:
    return {"id": 1, "username": "dev", "role": "admin"}

def test_user_role(active_user):
    assert active_user["role"] == "admin"


# Parametrize — test against a matrix of inputs
@pytest.mark.parametrize("value, expected", [
    (0, "zero"),
    (1, "positive"),
    (-1, "negative"),
])
def test_classify(value: int, expected: str) -> None:
    assert classify(value) == expected


# Monkeypatching — replace external dependencies without network calls
def test_fetch_user(monkeypatch):
    def fake_get(url: str) -> dict:
        return {"id": 1, "name": "Alice"}
    monkeypatch.setattr("my_package.api.http_get", fake_get)
    assert fetch_user(1)["name"] == "Alice"
```

### Configuration

```toml
[tool.pytest.ini_options]
testpaths = ["tests"]
addopts = "-v --tb=short"
```

### Pytest vs `unittest`

| Feature | `unittest` | `pytest` |
|---------|-----------|---------|
| Test syntax | `self.assertEqual(a, b)` | `assert a == b` |
| Failure output | Generic message | Full diff of what differed |
| Fixtures | `setUp`/`tearDown` | Composable `@pytest.fixture` |
| Parametrize | Manual `subTest` | `@pytest.mark.parametrize` |
| Discovery | Must register test classes | Automatic `test_*.py` discovery |

Use pytest. The detailed assertion introspection means failure messages are
directly interpretable without printing intermediate values.

---

## 4. `pyproject.toml` — Gold-Standard Blueprint

A single `pyproject.toml` at the project root unifies build configuration, dependency
management, and all tool settings. This eliminates `setup.py`, `.flake8`,
`mypy.ini`, and `pytest.ini` — one file, one source of truth.

```toml
# ── Build System ────────────────────────────────────────────────────────────
[build-system]
requires = ["hatchling >= 1.27.0"]
build-backend = "hatchling.build"

# ── Project Metadata ─────────────────────────────────────────────────────────
[project]
name = "my-package"
description = "A short, clear description of what this package does."
readme = "README.md"
requires-python = ">=3.10"
license = "MIT"
license-files = ["LICENSE"]
keywords = ["example", "python"]
authors = [
    { name = "Your Name", email = "you@example.com" }
]
classifiers = [
    "Development Status :: 3 - Alpha",
    "Programming Language :: Python :: 3.12",
]
# version is sourced from git tag via the backend
dynamic = ["version"]

# Runtime dependencies
dependencies = [
    "requests >= 2.28.0",
]

# Optional extras — install with: pip install my-package[dev]
[project.optional-dependencies]
dev = ["pytest >= 7.0.0", "mypy >= 1.0.0", "ruff >= 0.1.0"]

# CLI entry point — maps `my-cli` command to my_package/cli.py:main
[project.scripts]
my-cli = "my_package.cli:main"

# Project URLs for PyPI sidebar
[project.urls]
homepage = "https://example.com"
repository = "https://github.com/example/my-package"
documentation = "https://docs.example.com"

# ── Ruff ─────────────────────────────────────────────────────────────────────
[tool.ruff]
line-length = 88
target-version = "py312"

[tool.ruff.lint]
select = ["E", "F", "I", "B"]
ignore = []

[tool.ruff.format]
quote-style = "double"
indent-style = "space"

# ── Mypy ─────────────────────────────────────────────────────────────────────
[tool.mypy]
python_version = "3.12"
warn_return_any = true
warn_unused_ignores = true

# Enable strict incrementally per module
[[tool.mypy.overrides]]
module = "my_package.core"
strict = true

# ── Pytest ───────────────────────────────────────────────────────────────────
[tool.pytest.ini_options]
testpaths = ["tests"]
addopts = "-v --tb=short"
```

### Key rules for `[project]`

- `name` — ASCII letters, digits, `-`, `_`, `.` only; cannot start or end with them.
- `version` — must follow PEP 440 (e.g., `1.2.3`, `1.0.0a1`, `2.0.0.post1`). If in `dynamic`, do **not** also define it statically — that is a configuration error.
- `requires-python` — a hard constraint; prevents installation on unsupported interpreters. Classifiers are informational only.
- `license` — use an SPDX expression string: `"MIT"`, `"Apache-2.0"`, `"MIT OR Apache-2.0"`.
- `license-files` — every glob pattern must match at least one file, or the build fails.

### Versioning (PEP 440)

| Type | Example | When to use |
|------|---------|-------------|
| Release | `1.2.3` | Normal releases |
| Pre-release | `1.0.0a1`, `1.0.0b2`, `1.0.0rc1` | Alpha, beta, release candidate |
| Post-release | `1.0.0.post1` | Correcting metadata after a release |
| Dev | `1.0.0.dev1` | Development snapshots |

Semantic versioning convention: `MAJOR.MINOR.PATCH`
- MAJOR: breaking changes
- MINOR: backward-compatible new features
- PATCH: backward-compatible bug fixes

---

## 5. Integrated Quality Workflow

The complete agent loop, from draft to finalized code:

```
1. Draft
   Write the code, focused on correctness and design.

2. Ruff — lint and format
   ruff check --fix .
   ruff format .
   → Auto-fixes imports, style. Remaining errors require manual resolution.

3. Mypy — static typing
   mypy .
   → STOP on [attr-defined], [arg-type], [assignment], [name-defined], [return-value].
   → These are structural contract breaks; tests on broken contracts are false signals.

4. Pytest — behavioral validation
   pytest
   → Failures provide detailed diffs. Parse the failure to understand what the
     logic produced vs what was expected, then fix the logic or the test.

5. Finalize
   All three tools exit with code 0. Code is production-ready.
```

This loop runs locally on every significant change and in CI on every push.
Because Ruff is sub-second and Mypy/Pytest use caching, the total overhead
on incremental changes is typically under 3 seconds.
