---
name: python-engineering
description: >
  Use whenever the task involves writing, refactoring, or reviewing Python code.
  Invoke for: writing new Python scripts or modules, refactoring functions or
  classes to be more Pythonic or idiomatic, adding or improving type hints
  (PEP 484), implementing or reviewing SOLID principles in Python, setting up or
  configuring the quality toolchain (Ruff, Mypy, Pytest), creating or updating
  pyproject.toml, reviewing Python code for clean-code compliance (naming, SRP,
  DRY), designing Python class hierarchies or data models, converting Java-esque
  APIs to idiomatic Pythonic wrappers, implementing the Adapter pattern, writing
  context managers or magic methods, structuring a data pipeline, or any task
  where the primary output is Python source code.
  Trigger on phrases like: "write a Python script", "make this Pythonic",
  "add type hints", "refactor this Python function or class", "set up Ruff or
  Mypy or Pytest", "review my Python code", "create a pyproject.toml",
  "implement SOLID in Python", "clean up this Python class", "design a Python
  data pipeline", "this Python code is messy", "add tests to this script".
  Do NOT use for debugging a specific runtime error (use the debugger skill), or
  removing dead code from PHP or JavaScript (use code-security-and-cleanup).
---

# Python Engineering
*Target: Production-grade Python 3.10+ — idiomatic, clean, typed, and verified to exit code 0.*

---

## How to Approach a Request

Route the task to the correct section, then run the verification loop before finalizing.

1. **Writing new code** — if this is a brand-new standalone script or CLI, load `references/writing-scripts.md` first (skeleton, argparse, error handling, venv/deps, secrets); then apply inline design principles below → run verification loop → done.
2. **Modifying existing code** — read the Surgical-Edit Rule section first; touch only what the request requires.
3. **Reviewing / auditing code** — load `references/clean-code-and-solid.md` and `references/idiomatic-python.md`; report violations without rewriting unless explicitly asked.
4. **Setting up tooling or pyproject.toml** — load `references/tooling-and-packaging.md`.
5. **Adding type hints** — load `references/style-and-typing.md`.

---

## The Verification Loop

Run these steps in order. Each step must pass before the next begins. Finalize only when all three reach exit code 0.

```
Draft code
  → ruff check --fix && ruff format      # auto-heals style + removes fixable lint
  → mypy <file or package>               # STOP on static errors — resolve before tests
  → pytest                               # behavioral validation
  → Finalize
```

**Why this order matters:**
- `ruff --fix` automatically removes unused imports and fixes safe style issues, clearing the noise before you read error output.
- Mypy catches structural contract breaks — wrong argument types, missing attributes, assignment mismatches — that would make test results meaningless. Passing tests on structurally broken code is a false signal.
- Pytest validates behavior on code that is already statically sound.

**Mypy stop-codes — fix these before running Pytest:**
```
[attr-defined]   # attribute missing on the object — likely hallucinated
[arg-type]       # wrong type passed to a function
[assignment]     # type mismatch on variable assignment
[name-defined]   # undefined name
[return-value]   # actual return type doesn't match annotation
```

**Ruff configuration baseline** (add to `pyproject.toml`):
```toml
[tool.ruff]
line-length = 88
target-version = "py312"

[tool.ruff.lint]
select = ["E", "F", "I", "B"]   # errors, pyflakes, isort, bugbear

[tool.ruff.format]
quote-style = "double"
indent-style = "space"
```

---

## Inline Design Principles

These apply to all Python code produced by this skill. Each principle is: rule → why → reference for depth.

### Naming

- **Meaningful, pronounceable names** — `current_date` not `ymdstr`; `customers` not `gen_list`. Code is read far more than it is written; the name is the documentation.
- **Consistent vocabulary** — if the entity is `User`, do not call it `Client` or `Customer` elsewhere. One name per concept, used everywhere.
- **No Hungarian notation** — `name` not `name_str`. Type hints carry the type; the variable name should carry the meaning.
- **No redundant context** — inside `class Car`, use `make` not `car_make`. The class provides the context.
- **Searchable constants** — `SECONDS_IN_A_DAY = 60 * 60 * 24`, then `time.sleep(SECONDS_IN_A_DAY)`. The literal `86400` is unsearchable and unexplainable.
- **Explanatory variables for complex regex** — use named subgroups `(?P<city>...)` or extract patterns to named constants. Never leave a "wall of regex" without a label.

→ Depth: `references/clean-code-and-solid.md`

### Functions

- **One action per function** — if "and" appears in a description of what the function does, split it into two functions. Single-purpose functions are trivially testable and composable.
- **Two arguments or fewer** — three or more usually signal responsibility creep. Bundle related parameters into a `dataclass` or `TypedDict`; this also lets you push logic into methods on that object.
- **No boolean flag parameters** — a `temp: bool` parameter means two code paths inside one function. Split into `create_file()` and `create_temp_file()` instead.
- **Default arguments over short-circuiting** — `def create(name: str = "default"):` explicitly declares the expected type; `name = name or "default"` is ambiguous about what types it accepts.
- **Centralize side effects** — writing to disk, modifying global state, or sending network requests should live in one dedicated service, not scattered across logic functions. Prefer pure functions (same input → same output, no external state) for business logic.

→ Depth: `references/clean-code-and-solid.md`

### Type Hints

- **Annotate all public function signatures** — both parameters and return type. Private helpers can be lighter.
- **`__init__` always returns `-> None`** — makes it a checked function rather than an unannotated one; Mypy in strict mode requires this.
- **Prefer abstract types for parameters, concrete for returns** — accept `Sequence[str]`, return `list[str]`. This maximizes caller flexibility.
- **Use `Optional[T]` or `T | None` explicitly** — a `= None` default does not imply optional to Mypy in strict mode; annotate it.
- **`TypedDict` for dict-shaped data** — especially for any JSON coming from an LLM or external API; gives Mypy a schema to enforce at the call site.

→ Depth: `references/style-and-typing.md`

### Architecture and Structure

- **Composition over inheritance** — inject behavior via constructor arguments rather than deep subclass trees. An `Order` that holds a `PaymentProcessor` is easier to test than one that inherits from it.
- **High cohesion, low coupling** — group things that change together; decouple things that don't. If changing payment logic forces a change in reporting logic, the cohesion is broken.
- **Explicit pipeline** — for data-processing work, split into `load()` → `transform()` → `export()`. This makes the flow visible, lets you call `load()` once and run multiple outputs, and isolates each stage for testing.
- **No `import *`** — it pollutes the namespace, breaks IDE navigation, and makes `grep` useless for finding where a name comes from.
- **Line length: 88 characters** — the Ruff/Black formatter default. Let `ruff format` handle wrapping; do not fiddle with line breaks manually.

→ Depth: `references/idiomatic-python.md`, `references/clean-code-and-solid.md`

---

## Modifying Existing Scripts (Surgical-Edit Rule)

When the task is to change existing Python code — not write from scratch — apply these rules first:

1. **Match existing conventions** — if the file uses single quotes, 2-space indents, or non-standard line length, do not reformat the whole file to match this skill's defaults. Every changed line should trace directly to the user's request.
2. **No wholesale reformatting** — mass style changes scramble `git blame`, make PR diffs unreadable, and can introduce subtle bugs. If the codebase genuinely needs a reformat, propose `ruff format .` as a separate, standalone commit.
3. **Clean up your own orphans** — if your change makes an import, variable, or function unused, remove it. Do not remove pre-existing dead code unless the request asks for it.
4. **State your interpretation** — if the request is ambiguous (e.g., "improve this class"), state what you understood before making any changes.

---

## Reference Files

Load the relevant file before executing a complex or unfamiliar sub-task.

| File | Load when… |
|------|-----------|
| `references/idiomatic-python.md` | Transforming non-Pythonic APIs, implementing magic methods or context managers, using the Adapter pattern, designing module/repo layout |
| `references/clean-code-and-solid.md` | Reviewing or writing functions and classes; applying SOLID principles; enforcing DRY; naming audit |
| `references/style-and-typing.md` | Adding type hints (PEP 484), applying PEP 8 layout rules, using `TypedDict` / `TypeVar` / `Generic`, writing stub files |
| `references/tooling-and-packaging.md` | Configuring Ruff / Mypy / Pytest, writing or updating `pyproject.toml`, setting up the quality toolchain |
| `references/writing-scripts.md` | Standing up a new script or CLI from scratch — project layout, entry point, argparse, error handling/exit codes, logging, venv/deps, secrets/`.env` |
