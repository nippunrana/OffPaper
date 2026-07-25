# Idiomatic Python Reference

Pythonic code leverages the language's native protocols so that business logic
floats to the top and implementation plumbing stays hidden. This file covers the
key idioms, the Adapter pattern for wrapping non-Pythonic SDKs, magic methods,
pipeline design, and repository layout.

---

## 1. The Adapter Pattern — Wrapping Non-Pythonic SDKs

When forced to use a Java-esque SDK (verbose getters/setters, manual resource
management, index-based iteration), **do not let the bad API leak into business
logic**. Wrap it in a thin adapter that presents a native-feeling surface.

**Before — Java-esque API used directly:**
```python
from java_sdk.network import Element
from java_sdk.network.errors import ConnectionError

e = Element("10.0.0.1")
try:
    e.connect()
    table = e.get_routing_table()
    for i in range(table.get_size()):
        route = table.get_route_by_index(i)
        print(f"Route: {route.get_name()} at {route.get_ip()}")
    e.commit()
except ConnectionError as err:
    print(f"Failed: {err}")
finally:
    e.disconnect()
```

**After — Pythonic adapter wraps the SDK:**
```python
from net_tools import NetworkElement  # adapter hides the SDK

with NetworkElement("10.0.0.1") as element:
    for route in element.routing_table:
        print(f"Route: {route.name} at {route.ip}")
```

The adapter handles `connect`/`disconnect`, translates cryptic SDK errors into
descriptive application-level exceptions, and exposes the routing table as a
native iterable — the business logic above never touches the SDK directly.

---

## 2. Magic Methods — Making Objects Behave Like Native Types

Implementing Python's data model lets objects participate in standard language
constructs (`len()`, indexing, `with`, `repr`). This removes the need for
bespoke method calls and makes objects composable with built-in functions.

### `__len__` and `__getitem__` — Sequence protocol

Implement `__len__` and `__getitem__` (raising `IndexError` at the boundary)
and the object automatically becomes iterable — no `__iter__` needed.

```python
from collections import namedtuple

Route = namedtuple("Route", ["name", "ip"])


class RoutingTable:
    def __init__(self, legacy_table):
        self._table = legacy_table

    def __len__(self) -> int:
        return self._table.get_size()

    def __getitem__(self, index: int) -> Route:
        if index >= self._table.get_size():
            raise IndexError("route index out of range")
        res = self._table.get_route_by_index(index)
        return Route(res.name, res.ip)
```

Because `__getitem__` raises `IndexError`, `for route in table` works without
any additional code. `len(table)` and `table[i]` also work.

### `__enter__` and `__exit__` — Context manager

Factor out repetitive `try/finally` setup-and-teardown into a context manager.
Resources are always cleaned up, even if an exception occurs mid-block.

```python
class NetworkElementContext:
    def __init__(self, ip: str) -> None:
        self.ip = ip
        self._element = None

    def __enter__(self) -> "NetworkElementAdapter":
        self._element = connect_to_device(self.ip)
        return NetworkElementAdapter(self._element)

    def __exit__(self, exc_type, exc_val, exc_tb) -> bool:
        try:
            if exc_type:
                self._element.rollback()
            else:
                self._element.commit()
        finally:
            self._element.disconnect()
        return False  # let exceptions propagate
```

Usage is then simply:
```python
with NetworkElementContext("10.0.0.1") as element:
    ...  # setup and teardown are invisible
```

### `@property` — Logic-backed attribute access

Replace `get_value()` / `set_value()` methods with `@property`. This lets you
add validation or transformation later without changing the caller's syntax.

```python
class NetworkElementAdapter:
    def __init__(self, raw_sdk_obj) -> None:
        self._sdk = raw_sdk_obj

    @property
    def routing_table(self) -> RoutingTable:
        """Wrap legacy get_routing_table() as a Pythonic property."""
        try:
            return RoutingTable(self._sdk.get_routing_table())
        except Exception as exc:
            raise TableFault(f"Routing data unavailable: {exc}") from exc
```

Caller: `element.routing_table` — reads like an attribute, but can hold logic.

### `__repr__` — Unambiguous string representation

Every class should implement `__repr__` so objects are interpretable in logs
and interactive sessions. Avoid hardcoding the class name — use
`self.__class__.__name__` so subclasses represent themselves accurately.

```python
class Route:
    def __init__(self, name: str, ip: str) -> None:
        self.name = name
        self.ip = ip

    def __repr__(self) -> str:
        return f"{self.__class__.__name__}(name={self.name!r}, ip={self.ip!r})"
```

---

## 3. Self-Documenting Idioms

### Named tuples — semantic data without class overhead

A plain `tuple` like `route[0]` is opaque. A `namedtuple` or `dataclass` gives
fields names without any runtime cost increase.

```python
from collections import namedtuple

Route = namedtuple("Route", ["name", "ip"])

# Before: route[0] — what is index 0?
# After:  route.name — self-explanatory
```

For mutable data or computed fields, prefer `dataclass`:

```python
from dataclasses import dataclass, field
from typing import Optional


@dataclass
class ReportConfig:
    encoding: str = "utf-8"
    delimiter: str = ","
    threshold: float = 0.0
    output_path: Optional[str] = None
```

Bundling config into a `dataclass` is also the right solution when a function
would otherwise accept 4+ arguments (see clean-code-and-solid.md).

### Keyword arguments for clarity

For calls with multiple numbers or booleans, keyword arguments prevent
"mystery argument" confusion:

```python
# Opaque: what is 20? what is False?
search("Python", 20, False)

# Clear: intent is explicit
search(topic="Python", count=20, retweets=False)
```

### Custom exceptions

Raise application-specific exceptions rather than generic `RuntimeError` or
`ValueError`. They make logs interpretable and let callers catch selectively.

```python
class TableFault(Exception):
    """Raised when routing table data is unavailable."""
```

---

## 4. The Explicit Pipeline Pattern

For data-processing scripts, make the pipeline visible by splitting into
distinct stages. Monolithic `run()` functions hide the flow and force redundant
I/O when you need multiple outputs.

```python
def load_sales(path: str) -> list[dict]:
    """Stage 1: Read raw data from disk."""
    with open(path, encoding="utf-8") as f:
        return list(csv.DictReader(f))


def filter_valid(rows: list[dict]) -> list[dict]:
    """Stage 2: Apply business logic."""
    return [r for r in rows if float(r["amount"]) > 0]


def export_json(rows: list[dict], out_path: str) -> None:
    """Stage 3: Write results."""
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(rows, f, indent=2)


def main() -> None:
    raw = load_sales("sales.csv")       # load once
    valid = filter_valid(raw)
    export_json(valid, "output.json")   # can add more exports here without re-loading
```

Each stage is independently testable. If the output format changes, only
`export_json` needs updating. If the business rule changes, only `filter_valid`
changes.

---

## 5. Composition Over Inheritance

Prefer injecting behavior via constructor arguments over deep inheritance trees.
Deep hierarchies create tight coupling; composition keeps components replaceable.

```python
from typing import Protocol


class PaymentProcessor(Protocol):
    def pay(self, order: "Order") -> None: ...


class DebitProcessor:
    def pay(self, order: "Order") -> None:
        print("Processing debit payment")
        order.status = "paid"


class Order:
    def __init__(self, processor: PaymentProcessor) -> None:
        self.items: list = []
        self.status = "open"
        self._processor = processor

    def checkout(self) -> None:
        self._processor.pay(self)
```

Swapping from `DebitProcessor` to `PayPalProcessor` requires no changes to
`Order` — just pass a different object at construction time.

---

## 6. Module and Repository Layout

A clear file structure communicates intent to contributors and tools. Follow
the flat, transparent layout recommended by the Hitchhiker's Guide to Python:

```
my_project/
├── my_package/          # Core module (keep at root — don't nest in src/ unnecessarily)
│   ├── __init__.py      # Keep lean; avoid logic here
│   └── core.py
├── tests/
│   ├── conftest.py      # Shared fixtures
│   └── test_core.py
├── docs/
├── pyproject.toml       # Single source of truth for deps + tool config
├── LICENSE
└── README.md
```

Rules:
- `from sdk.utils.net.elements.routing import Table` — avoid deep dot paths. Flatten your package if needed.
- Never `import *` — explicit imports only.
- Keep `__init__.py` lean; it is an API surface, not a logic file.
- Use `pyproject.toml` as the single config file for all tools (Ruff, Mypy, Pytest).

---

## 7. Pure Functions vs. Classes

Use **pure functions** (same input → same output, no side effects) for
stateless computation — data transformation, filtering, formatting. They are
easy to test and compose.

Use **classes** when state and behavior must be tied together over a longer
lifetime — a `NetworkElementContext` that holds a connection, a `ReportRunner`
that accumulates results across multiple calls.

Avoid classes purely for organization; a module is already a namespace.
