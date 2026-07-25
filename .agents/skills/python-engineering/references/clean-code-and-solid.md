# Clean Code and SOLID Reference

Derived from Robert C. Martin's Clean Code principles, adapted for Python 3.10+.
This file covers naming, function design, SOLID architecture, and DRY.

---

## 1. Naming

Names are the primary way code communicates intent. A good name eliminates the
need for a comment.

### Meaningful and pronounceable names

Names must reveal intent without encoding the type (Hungarian notation).

```python
# Bad — what is ymdstr? what does gen_list hold?
import datetime
ymdstr = datetime.date.today().strftime("%Y-%m-%d")
gen_list = []

# Good — self-explanatory
import datetime
current_date: str = datetime.date.today().strftime("%Y-%m-%d")
customers: list = []
```

### Consistent vocabulary — one name per concept

If the entity is a `User`, do not call it `Client` or `Customer` elsewhere.
Searching the codebase for `user` should find everything related to that entity.

```python
# Bad — three names for the same concept
def get_user_info(): ...
def get_client_data(): ...
def get_customer_record(): ...

# Good — one name, one concept
def get_user(): ...
```

When multiple functions share the same underlying entity, package them as a
class with property methods:

```python
class User:
    def __init__(self, name: str) -> None:
        self.name = name

    @property
    def info(self) -> dict:
        return {"name": self.name}
```

### Searchable constants — no magic numbers

```python
# Bad — 86400 is unsearchable
time.sleep(86400)

# Good — searchable and self-documenting
SECONDS_IN_A_DAY = 60 * 60 * 24
time.sleep(SECONDS_IN_A_DAY)
```

### Explanatory variables for regex

Never leave a complex regex pattern as a raw string. Name subpatterns or extract
to named constants.

```python
# Bad — a wall of regex with no explanation
import re
address = "One Microsoft Way, Redmond, WA 98052"
city_zip = re.search(r"(\w+), [A-Z]{2} (\d{5})", address)

# Good — named subpatterns make intent explicit
CITY_ZIP_PATTERN = r"(?P<city>\w+), [A-Z]{2} (?P<zip>\d{5})"
matches = re.search(CITY_ZIP_PATTERN, address)
if matches:
    city = matches.group("city")
    zip_code = matches.group("zip")
```

### No redundant context

If the class name provides context, do not repeat it in attributes.

```python
# Bad — 'car_' prefix adds nothing inside the Car class
class Car:
    car_make: str
    car_model: str
    car_color: str

# Good
class Car:
    make: str
    model: str
    color: str
```

### Default arguments over short-circuiting

Default arguments make the expected type explicit at the function signature.

```python
# Tricky — what types does `name or "Hipster Brew"` accept?
def create_microbrewery(name):
    brewery_name = name or "Hipster Brew"

# Good — type is clear from the signature
def create_microbrewery(name: str = "Hipster Brew") -> None:
    ...
```

---

## 2. Function Design

### Single Responsibility — one function, one action

If you need "and" to describe what a function does, split it.

```python
# Bad — handles filtering AND the action of emailing in one function
def email_clients(clients):
    for client in clients:
        client_record = db.find(client)
        if client_record.is_active():
            email(client)

# Good — each function does one thing
def get_active_clients(clients):
    return (client for client in clients if is_client_active(client))

def is_client_active(client) -> bool:
    return db.find(client).is_active()

def email_clients(clients) -> None:
    for client in get_active_clients(clients):
        email(client)
```

### Two arguments or fewer

Three or more arguments usually signal that the function is doing too much, or
that its parameters belong to a single entity. Bundle them:

```python
# Bad — four positional arguments
def create_menu(title, body, button_text, cancellable):
    ...

# Good — bundle into a TypedDict or dataclass
from typing import TypedDict

class MenuConfig(TypedDict):
    title: str
    body: str
    button_text: str
    cancellable: bool

def create_menu(config: MenuConfig) -> None:
    ...
```

A `dataclass` (preferred for richer objects) lets you move computation into
methods on the config object, reducing external function complexity further.

### No boolean flag parameters

A boolean flag means two code paths inside one function — a Single Responsibility
violation. Split into two explicit functions instead.

```python
# Bad — temp flag forces branching
def create_file(name: str, temp: bool = False) -> None:
    if temp:
        pathlib.Path(f"./temp/{name}").touch()
    else:
        pathlib.Path(name).touch()

# Good — two explicit, single-purpose functions
import pathlib

def create_file(name: str) -> None:
    pathlib.Path(name).touch()

def create_temp_file(name: str) -> None:
    pathlib.Path(f"./temp/{name}").touch()
```

### Centralize side effects

Side effects (writing to disk, modifying global state, network calls) make code
unpredictable and hard to test. Isolate them in dedicated services; keep
business logic functions pure.

```python
# Bad — side effect (global mutation) tangled into logic
name = "Ryan McDermott"

def split_into_first_and_last_name():
    global name  # mutates external state
    name = name.split()

# Good — pure function; caller decides what to do with the result
def split_into_first_and_last_name(name: str) -> list[str]:
    return name.split()

full_name = "Ryan McDermott"
parts = split_into_first_and_last_name(full_name)
```

Centralized side-effect service:
```python
class LogService:
    def write(self, message: str) -> None:
        with open("app.log", "a") as f:
            f.write(message + "\n")

def process_order(order: "Order", logger: LogService) -> None:
    # Pure logic here; side effect is explicit and injectable
    logger.write(f"Order {order.id} processed.")
```

### One level of abstraction per function

Do not mix high-level business logic with low-level string manipulation inside
the same function.

```python
# Bad — two abstraction levels mixed
def welcome_users(users):
    for user in users:
        full_name = f"{user.first.strip()} {user.last.strip()}".upper()
        print(f"WELCOME, {full_name}!")

# Good — each function at one level
def format_name(user) -> str:
    return f"{user.first.strip()} {user.last.strip()}".upper()

def welcome_users(users) -> None:
    for user in users:
        print(f"WELCOME, {format_name(user)}!")
```

---

## 3. SOLID Principles

### S — Single Responsibility Principle

A class should have exactly one reason to change.

```python
# Bad — Comment mixes retrieval logic with presentation logic
class Comment:
    def __init__(self, content: str) -> None:
        self.content = content

    def get_version(self) -> str:
        return "1.0.1"         # retrieval logic

    def render(self) -> str:
        return f"<div>{self.content} (v{self.get_version()})</div>"  # presentation


# Good — decoupled: version retrieval and rendering are separate concerns
def get_version() -> str:
    return "1.0.1"

class Comment:
    def __init__(self, content: str, version: str) -> None:
        self.content = content
        self.version = version

    def render(self) -> str:
        return f"<div>{self.content} (v{self.version})</div>"
```

### O — Open/Closed Principle

Open for extension, closed for modification. Add new behavior by adding new
code, not by editing working code. In Python: design base-class methods as
"hooks" that subclasses can override.

```python
# Good — base class invites extension without requiring modification
class View:
    def get(self) -> str:
        return self.render_body()

    def render_body(self) -> str:
        return "plain text"


class TemplateView(View):
    def render_body(self) -> str:
        return "<html><body>HTML content</body></html>"
```

Adding a `JsonView` requires zero changes to `View` or `TemplateView`.

Use **Mixins** (classes inheriting from `object` placed before the target class
in the MRO) to compose behavior cleanly via multiple inheritance.

### L — Liskov Substitution Principle

A subtype must be substitutable for its base type without breaking callers.
Never change a method's signature in a subclass.

```python
# Bad — TemplateView changes the get() signature; callers break
class View:
    def get(self, request: str) -> str:
        return "Response"

class TemplateView(View):
    def get(self, request: str, template_name: str) -> str:  # ← signature mismatch
        return "HTML Response"

def render(view: View, request: str) -> str:
    return view.get(request)  # TypeError if view is TemplateView


# Fix — move the varying parameter to __init__ so get() stays consistent
class TemplateView(View):
    def __init__(self, template_name: str) -> None:
        self.template_name = template_name

    def get(self, request: str) -> str:
        return f"<html>{self.template_name}</html>"
```

Use Mypy to catch signature mismatches automatically (`[override]` error code).

### I — Interface Segregation Principle

Keep interfaces small. Do not force a class to implement methods it does not
use — split fat ABCs into focused ones.

```python
# Bad — PDF documents don't need save(), but must implement it anyway
from abc import ABC, abstractmethod

class Document(ABC):
    @abstractmethod
    def open(self) -> None: ...
    @abstractmethod
    def save(self) -> None: ...  # PDFs are read-only; this is a dummy method


# Good — segregated interfaces; classes implement only what they need
class Readable(ABC):
    @abstractmethod
    def open(self) -> None: ...

class Writable(ABC):
    @abstractmethod
    def save(self) -> None: ...

class PdfDocument(Readable):
    def open(self) -> None:
        print("Reading PDF")
```

### D — Dependency Inversion Principle

Depend on abstractions, not concrete implementations. High-level logic should
not be wired to specific low-level classes.

The classic Python example: `csv.writer` only needs an object with a `.write()`
method — it doesn't care whether it's talking to a real file, a `StringIO`, or
a custom streaming response.

```python
from typing import Protocol

class Writable(Protocol):
    def write(self, data: str) -> None: ...


class CSVWriter:
    def write_rows(self, output: Writable, rows: list[list[str]]) -> None:
        for row in rows:
            output.write(",".join(row) + "\n")


# Works with any object that has .write() — file, StringIO, or custom stream
class StreamingResponse:
    def write(self, data: str) -> None:
        print(f"Streaming: {data!r}")

writer = CSVWriter()
writer.write_rows(StreamingResponse(), [["col1", "col2"], ["a", "b"]])
```

In Python, prefer `typing.Protocol` for structural typing (duck typing) over
`abc.ABC` for nominal typing. Use `ABC` when you want to enforce that subclasses
implement specific methods; use `Protocol` when you want to accept any object
that has the right shape.

**Composition over deep inheritance** is the practical form of DIP:

```python
from typing import Protocol

class Authorizer(Protocol):
    def is_authorized(self) -> bool: ...

class SmsAuthorizer:
    def is_authorized(self) -> bool:
        return True  # checks SMS confirmation

class PaymentProcessor:
    def __init__(self, authorizer: Authorizer) -> None:
        self._auth = authorizer  # depends on the abstraction, not SmsAuthorizer

    def pay(self, order: "Order") -> None:
        if not self._auth.is_authorized():
            raise PermissionError("Payment not authorized")
        order.status = "paid"
```

Swapping `SmsAuthorizer` for `RobotAuthorizer` requires zero changes to
`PaymentProcessor`.

---

## 4. DRY — Don't Repeat Yourself

Duplicate code means two sources of truth. When a business rule changes, you
must find and update all copies — and the chance of missing one grows with each
duplicate.

```python
# Bad — connection logic duplicated; changing it requires editing both functions
def load_manager_data():
    conn = db.connect()
    data = conn.fetch("SELECT * FROM managers")
    conn.close()
    return data

def load_employee_data():
    conn = db.connect()
    data = conn.fetch("SELECT * FROM employees")
    conn.close()
    return data

# Good — one source of truth
def load_data(table: str) -> list[dict]:
    conn = db.connect()
    data = conn.fetch(f"SELECT * FROM {table}")
    conn.close()
    return data
```

**Warning: a wrong abstraction is worse than duplication.** Only merge code
when the logic is genuinely identical in concept. If you force two different
business concerns into one function to avoid repetition, you create a tangled
abstraction that is harder to change than the original duplicates.

The test: can you describe what the merged function does without using "and"
or "depending on"? If not, the abstraction is too broad.
