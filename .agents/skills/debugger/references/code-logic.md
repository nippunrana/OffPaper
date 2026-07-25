# Domain: Code Logic, State & Async

Use this reference when the bug is wrong behavior in the code's logic: incorrect return value, conditional fails, state-management glitch, off-by-one, infinite loop, useEffect firing twice, race condition between two awaits, promise resolving in the wrong order, callback never invoked.

The probe mode here is usually **injected debug code** tagged per `instrumentation-protocol.md`, because the relevant data isn't visible in DevTools by default. Console snippets are only useful when the logic runs in the browser AND you can re-trigger it manually.

---

## 1. Sub-categories

- `state-snapshot` — you don't know what the variables actually contain at the suspect point
- `bisect-flow` — you don't know *which branch* of a function is executing (or whether it's executing at all)
- `async-order` — you don't know in what order two or more async operations are running
- `render-loop` — a React/Vue/Svelte component is re-rendering more than expected
- `value-mutation` — a value is being changed somewhere unexpected; you need to catch the writer

---

## 2. Probe patterns

Each pattern below is a spec, not a snippet — generate the actual probe in the project's own language, following the `[DEBUG-<id>]` tagging protocol (`instrumentation-protocol.md`) for the comment syntax and dual tag placement.

### State snapshot (`state-snapshot`)

Log every variable you suspect is wrong, in one line, at the exact point you suspect things go wrong. Use a structure that preserves variable names (an object/dict/hash literal, not positional values) so the output is self-explanatory without re-reading the source. If the runtime has no built-in structured serializer, format key-value pairs manually.

### Print-trace bisection (`bisect-flow`)

When you don't know *where* the flow goes wrong, place a marker at each function boundary and major branch (if/else, switch cases, early returns), each with a distinct label (A, B, C...) so you can tell which one fired. After one round, the marker that **fails to appear** narrows the search — the marker right *before* the missing one is your new suspect zone. This is binary search applied to runtime control flow.

### Async ordering (`async-order`)

Wrap each async operation you suspect with a timestamped marker immediately before and immediately after it — use the runtime's monotonic clock (not wall-clock time, which can jump) so durations are trustworthy. If the before/after order across two or more operations is non-deterministic between runs, you've found a race; the fix is sequencing (await, lock, queue), not a retry.

### Render-loop counter (`render-loop`)

For a component (React, Vue, Svelte, or any component-based UI framework) that re-renders unexpectedly, increment a counter at the top of the render/component body and log it alongside the props/state you suspect are changing. Prefer the framework's own idiomatic per-instance counter (a ref/state primitive that survives re-renders but not remounts) if one exists; a scoped module-level or global counter keyed by the debug id is an acceptable fallback when no such primitive fits.

If the count climbs faster than expected, find what's different on each render. Common culprits: inline object/array/function literals passed as props (a new reference every render), missing memoization, a parent re-rendering unnecessarily, or a framework's development-mode double-invoke behavior — check whether the framework in use has one before treating a 2x count as a real bug.

### Value-mutation trap (`value-mutation`)

When a value changes somewhere you can't find the writer, replace direct access to it with an intercepted accessor (a property getter/setter, a descriptor, or the language's equivalent trap mechanism) that logs the new value and the call stack of the write, then delegates to the real storage.

Constraints that apply regardless of language:
- **Attach the trap at the class/prototype level, not on a single instance** — instance-level assignment just overwrites the value and traps nothing.
- **Seed the trap from existing values before attaching it.** The trap only observes writes from the moment it's installed; if the target already holds a value on live objects, the first read post-attachment can return empty/`None` until the next write, silently losing the current value. Copy the existing value into the trap's backing storage as part of installation.
- **Skip this probe where the language or runtime object-model doesn't support descriptors or dynamic wrappers** (e.g. sealed/frozen objects or structures with locked attribute spaces) — fall back to `state-snapshot` instead.
- Print or trace the full call stack on every trapped write, not just the new value — the stack tells you *which* code path is the rogue mutator.

---

## 3. When to choose what

| Situation | Tool |
|---|---|
| You can reproduce the bug at will and have an IDE attached | **Interactive debugger / breakpoint with watch expression** — fastest, no source edits, no cleanup |
| You can reproduce the bug but the codebase is server-side or hard to attach a debugger to | **Injected logs** with `[DEBUG-<id>]` tags |
| The bug is intermittent (happens 1 in 20 times) | **Injected logs** plus a counter, so the user can run it many times and you get aggregate data |
| The bug is reproducible by a specific test that fails | **Write a failing test first**, then debug inside the test runner |
| The bug is a regression (it worked before) and there's a command that reliably reproduces it | **`git log` / `git diff`** — read-only history and diff inspection; see git history inspection below |

If a project has an automated testing framework configured, prefer making the bug reproduce in a test. That gives you a tight feedback loop and the test becomes the permanent regression case.

### Git history inspection (`git-history`)

When a bug is a confirmed regression, do not check out older commits or modify the git state (no staging, no commits, no stashing). Instead, narrow down the defect by reading the recent commit logs and diffs:

1. **Scan recent commit messages:**
   ```bash
   git log --oneline -20
   ```
2. **Inspect the exact diffs of recent commits:**
   To see what code actually changed in the last few commits:
   ```bash
   git log -p -n 5
   ```
3. **Check diffs for specific suspect files/directories:**
   If the symptom points to a specific component or file, view its history over the last 5 commits:
   ```bash
   git log -p -n 5 -- path/to/file.<extension>
   # Or see the cumulative changes over the last N commits:
   git diff HEAD~5 -- path/to/file.<extension>
   ```
4. **Inspect dependency changes:**
   Check if package versions or dependencies were recently modified:
   ```bash
   git diff HEAD~5 -- <dependency-manifest-files>
   ```

By reading the diffs, you can spot the exact line that introduced the bug. This is faster and much safer than checking out older commits, which triggers dependency mismatches and state issues.

---

## 4. Signals to look for in the returned data

- **Missing log line** — execution never reached that branch. The gate is *upstream*. Look at the conditional that decides whether the missing branch runs.
- **Variable is `undefined`/`null`/empty** when it shouldn't be — trace where it was supposed to be set. Was the setter called? Did it run before or after the read?
- **Timestamps in unexpected order** — race condition. The fix is sequencing (await, lock, queue), not retry-on-failure.
- **Render count climbing** — find the prop/state that's different on each render. Inline `{}`/`[]` literals and inline arrow functions are the usual suspects.
- **Trap fires from unexpected stack frame** — that's your mutator. Fix at the writer, not by re-setting the value downstream.
- **Same probe produces different output across runs** — non-determinism. Likely an unawaited promise, a shared mutable cache, or environment-dependent input.

---

## 5. Fix discipline

- Fix the **defect that originated the bad value**, not the place where the bad value caused a visible failure.
- If the project has a test runner, write the regression test BEFORE fixing — confirm it fails, then make it pass.
- After fixing, run Phase 7 cleanup. Verify that searching the project for "[DEBUG-" returns zero matches using your native search tool.
