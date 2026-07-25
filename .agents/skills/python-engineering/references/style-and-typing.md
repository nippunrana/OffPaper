# Style and Typing Reference

Covers PEP 8 (physical layout and naming), PEP 20 (Zen as practical
tie-breakers), and PEP 484 (type hints). These are the standards this skill
enforces via Ruff and Mypy.

---

## 1. PEP 8 — Physical Layout

### Indentation and line length

- **4 spaces** per indentation level. Never mix tabs and spaces.
- **88 characters** maximum line length — the Ruff/Black formatter default. Let `ruff format` wrap lines automatically; do not adjust this per file.
- Comments and docstrings: aim for 72 characters for comfortable reading, but 88 is the hard cap.

### Line continuation — break before the operator (Knuth style)

Keeping operators at the start of the continuation line makes it easy to match
each operator with its operand visually.

```python
# Correct — operator at the start of the new line
total = (
    gross_pay
    + tax_deductions
    - health_insurance
)

# Incorrect — operator at the end of a line is easy to miss
total = (gross_pay +
         tax_deductions -
         health_insurance)
```

### Alignment for continuation lines

```python
# Aligned with opening delimiter
result = long_function_name(var_one, var_two,
                            var_three, var_four)

# Hanging indent (4 extra spaces to distinguish from suite)
def long_function_name(
        var_one, var_two, var_three,
        var_four):
    print(var_one)

# Multiline if — extra indent distinguishes condition from body
if (this_is_one_thing
        and that_is_another_thing):
    do_something()
```

---

## 2. PEP 8 — Imports

Group imports in this order, with a blank line between each group:

```python
"""Module docstring."""

from __future__ import annotations  # always first if present

__all__ = ["PublicClass", "public_function"]
__version__ = "1.2.3"

# Standard library
import os
import sys
from pathlib import Path

# Third-party
import requests
from pydantic import BaseModel

# Local application
from my_package.models import LocalModel
```

Rules:
- Absolute imports preferred; relative imports acceptable only for complex packages.
- `from module import *` — never. It pollutes the namespace and breaks `grep`.
- Each import on its own line.

---

## 3. PEP 8 — Naming Conventions

| Entity | Style | Notes |
|--------|-------|-------|
| Packages | `lowercase` | No underscores (strongly discouraged) |
| Modules | `lower_with_underscores` | |
| Classes | `CapWords` | No underscores |
| Functions / variables | `lower_with_underscores` | |
| Constants | `UPPER_WITH_UNDERSCORES` | Module-level only |
| Exceptions | `CapWords` | Must end with `Error` if they are errors |
| Internal (non-public) | `_single_leading_underscore` | |
| Name-mangled | `__double_leading_underscore` | Rarely needed |

**Never use** `l` (lowercase L), `O` (uppercase O), or `I` (uppercase I) as
single-character variable names — they are indistinguishable from `1` and `0`
in many fonts.

All identifiers must be ASCII-only.

---

## 4. PEP 8 — Programming Recommendations

```python
# Singletons: use `is` / `is not`, never `==`
if x is None: ...
if x is not None: ...

# `is not` instead of `not ... is`
if x is not None: ...     # readable
if not x is None: ...     # confusing

# String concatenation in loops: use join(), not +=
parts = ["a", "b", "c"]
result = "".join(parts)   # linear time, any implementation
# result = "" — then result += s in a loop — quadratic in CPython

# Empty sequences are falsy
if not seq: ...            # correct
if len(seq) == 0: ...      # redundant

# Boolean comparisons: never compare to True/False with ==
if greeting: ...           # correct
if greeting == True: ...   # wrong

# Consistent returns: if any branch returns a value, all must be explicit
def process(x: int) -> int | None:
    if x > 0:
        return x * 2
    return None            # explicit, not implicit fall-through

# Exceptions: inherit from Exception, not BaseException
class AppError(Exception): ...

# Specific exception catching — never bare except
try:
    import ujson as json
except ImportError:
    import json            # fallback

# Prefix/suffix checks
if filename.startswith("tmp_"): ...    # correct
if filename[:4] == "tmp_": ...         # fragile and unclear
```

---

## 5. PEP 20 — Zen of Python as Practical Tie-Breakers

Use the Zen when choosing between two approaches of similar complexity.

| Aphorism | Practical application |
|----------|----------------------|
| Beautiful is better than ugly | Readability is a primary constraint, not an afterthought |
| Explicit is better than implicit | Type hints, named arguments, explicit `Optional[T]` |
| Simple is better than complex | Prefer a plain function over a class with one method |
| Complex is better than complicated | One well-designed abstraction over nested spaghetti |
| Flat is better than nested | Prefer early returns and guard clauses over deep nesting |
| Sparse is better than dense | Blank lines between logical sections; named variables for subexpressions |
| Readability counts | Code is read by humans first, machines second |
| If the implementation is hard to explain, it's a bad idea | If you can't explain why a design decision was made, reconsider it |
| There should be one obvious way to do it | Resist clever one-liners when a straightforward loop is clearer |

---

## 6. PEP 484 — Type Hints

### Annotation syntax

```python
# Function: parameters with `:`, return with `->`; spaces around `->`
def greet(name: str, repeat: int = 1) -> str:
    return name * repeat

# Variable annotation: no space before `:`, one space after
retry_limit: int = 5
active_sessions: list[str] = []

# `__init__` always returns `-> None`
class Config:
    def __init__(self, host: str, port: int = 8080) -> None:
        self.host = host
        self.port = port
```

### Core type constructs

```python
from typing import Optional, Union, Callable, TypeVar, Generic, TypedDict

# Optional[T] is Union[T, None] — be explicit, don't rely on default=None
def find_user(user_id: int) -> Optional[str]:
    ...

# Union — for multi-type parameters or returns (Python 3.10+: use X | Y)
def process(value: int | str) -> str:
    return str(value)

# Callable — for function objects
# Callable[[ArgType1, ArgType2], ReturnType]
def apply(func: Callable[[int], str], value: int) -> str:
    return func(value)

# No keyword args in Callable; use Callable[..., ReturnType] for variable args
Handler = Callable[..., None]
```

### `Any` vs `object`

These are not interchangeable — they have opposite effects:

| Type | Effect | Use when |
|------|--------|----------|
| `Any` | Allows all operations; disables type checking | Gradual typing bridge for dynamic or legacy code |
| `object` | Disallows almost all operations beyond base attrs | When you mean "literally any Python object" but still want type safety |

```python
from typing import Any

def serialize(value: Any) -> str:      # caller can pass anything
    return str(value)

def log_object(obj: object) -> None:   # will NOT allow obj.name — too restrictive
    print(repr(obj))
```

### TypedDict — dict-shaped data with a schema

Use `TypedDict` for any dict whose keys are known at design time — especially
for LLM-generated JSON or API responses.

```python
from typing import TypedDict

class UserRecord(TypedDict):
    id: int
    name: str
    email: str

def get_user(user_id: int) -> UserRecord:
    # Mypy enforces that the returned dict has exactly these keys with these types
    return {"id": user_id, "name": "Alice", "email": "alice@example.com"}
```

### TypeVar and Generic

Use `TypeVar` to write functions or classes that work across types while
maintaining type safety:

```python
from typing import TypeVar, Generic, Sequence

T = TypeVar("T")

def first(items: Sequence[T]) -> T:
    return items[0]

# Generic class
class Stack(Generic[T]):
    def __init__(self) -> None:
        self._items: list[T] = []

    def push(self, item: T) -> None:
        self._items.append(item)

    def pop(self) -> T:
        return self._items.pop()
```

**Variance rules:**
- Mutable containers (`list[T]`) are **invariant** — `list[Manager]` is NOT a `list[Employee]` because you could push a non-manager into it.
- Read-only / immutable containers (`Sequence[T_co]`) are **covariant** — `Sequence[Manager]` is a valid `Sequence[Employee]`.

### Forward references

When a class references itself (e.g., a tree node), use a string literal or
`from __future__ import annotations` to defer evaluation:

```python
from __future__ import annotations  # makes all annotations strings by default

class Node:
    def __init__(self, parent: Node | None = None) -> None:
        self.parent = parent
```

Without the future import, write: `parent: "Node | None" = None`

### `@overload` — multiple valid signatures

```python
from typing import overload

@overload
def process(value: int) -> int: ...
@overload
def process(value: str) -> str: ...

def process(value: int | str) -> int | str:
    if isinstance(value, int):
        return value * 2
    return value.upper()
```

Rules:
- `@overload` variants are for the type checker only; the runtime uses the
  non-decorated implementation.
- In stub files (`.pyi`), use only `@overload` variants (no implementation).
- The implementation must be the last in the sequence and is ignored by Mypy
  for dispatch purposes.

### `cast` vs `NewType`

```python
from typing import cast, NewType

# cast: "trust me, I know the type is X" — blind belief, no runtime effect
items: list[object] = ["a", "b"]
first: str = cast(str, items[0])

# NewType: creates a distinct subtype that Mypy treats as separate
UserId = NewType("UserId", int)

def get_user(uid: UserId) -> str: ...

get_user(UserId(42))   # correct
get_user(42)           # Mypy error — plain int is not UserId
```

Use `NewType` to prevent accidentally mixing up two `int` fields that mean
different things (user IDs vs order IDs).

### `TYPE_CHECKING` — conditional imports

Use to avoid circular imports or expensive imports at runtime while still
providing type info to the checker:

```python
from __future__ import annotations
from typing import TYPE_CHECKING

if TYPE_CHECKING:
    from my_package.models import HeavyModel  # imported only by Mypy, not at runtime

def process(model: "HeavyModel") -> None:
    ...
```

### Gradual typing strategy

1. Start with the most widely imported modules — annotating core utilities
   gives the highest return on investment immediately.
2. Annotate as you go — apply hints to new code and touched modules.
3. Run Mypy on CI to prevent regressions in already-typed modules.
4. Enable `--strict` incrementally on well-tested, stable modules.
