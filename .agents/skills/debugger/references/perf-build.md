# Domain: Performance, Build & Tooling

Use this reference for two related-but-distinct bug families:

- **Performance** — slow renders, jank, memory leaks, hot loops, "the dashboard takes 6 seconds to open", "scrolling stutters"
- **Build / tooling / env** — Webpack/Vite errors, missing modules, env-var misconfig, PHP fatals, "it works on my machine but not in CI"

They share a reference because both need *measurement* before fixing — assumptions about what's slow or what's broken are almost always wrong.

---

## A. Performance probes

### `perf-timing` — measure a suspect block

Record a timestamp immediately before the suspect code and immediately after it, then log the difference. Use the runtime's monotonic high-resolution clock (e.g. the Performance API in the browser/Node, a monotonic perf-counter in other runtimes) rather than wall-clock time, which can jump backward or forward. If the platform has a named mark/measure API (like `performance.mark`/`performance.measure`), prefer it — the result then also shows up in the browser's Performance panel, not just the console.

### `perf-flamegraph` — find the slow function

Prefer the browser's built-in profiler over manual instrumentation when the slow path isn't obvious:

1. DevTools → Performance tab → Record
2. Trigger the slow action
3. Stop. Look at the flame graph for the widest yellow/red blocks. That's where time is being spent.

For backend profiling, run the runtime engine's native CPU profiler tool or import its profiling library, writing the results to a file to find the slow execution path.

### `perf-render-count` — find unnecessary re-renders

See `references/code-logic.md` → "Render-loop counter". The same probe applies — log the render count and the props that changed each time.

For component-based frameworks with custom DevTools, open the **Profiler** tab and record. It shows which components rendered and how long each took. Highlight "components that rendered" to spot needless re-renders.

### `perf-memory` — find a leak

Sample the process's heap size on an interval (every couple seconds) and log each sample with a timestamp, so you can watch the trend rather than a single snapshot. Check first whether the runtime actually exposes heap-size introspection (browser support varies by engine; server runtimes typically expose one via a process/runtime API) — warn and skip the probe if it doesn't, rather than producing a confusing crash. Print how to stop the sampling interval so it doesn't run forever.

If the heap climbs monotonically while the app sits idle → leak. To find what holds the references: use the platform's heap-snapshot tooling (e.g. DevTools → Memory) — take a snapshot, do the action that should free things, take another snapshot, diff them, look for objects that should have been collected.

### Signals — performance

- **One function dominates the flame graph** → optimize that function. Don't speculatively memo everything.
- **Many small functions, no single offender** → either an N+1 problem (loop calling something expensive), or render thrash (look at render counts).
- **Layout/Style work shows up huge in Performance panel** → CSS-induced reflows. Look for synchronous layout property reads in a loop after a write.
- **Memory grows without bound** → look for event listeners not removed, timers not cleared, closures capturing large objects, caches with no eviction.
- **CPU is fine but the UI feels slow** → main thread is starved by long tasks. Break work into chunks using asynchronous microtasks, timers, or background worker threads.

---

## B. Build / tooling probes

### `build-first-error` — read the FIRST error, not the last

Build tool errors cascade — the visible final error is often a downstream consequence of an earlier failure. Scroll **up** in the build output to find the first error. Capture the full output into a log file inside the workspace (not `/tmp`) so it's readable regardless of sandbox restrictions on paths outside the project. Add it to the ledger as a `(file)` entry (protocol §3) and delete it during Phase 7 cleanup along with any injected `[DEBUG-` lines.

Common cascade patterns:
- "Cannot find module X" later becomes 50 type errors that all reference X — fix X first.
- A bundler loader error early in the run causes "unexpected token" later — the early loader failure is the cause.

### `build-env-diff` — env-var misconfig

When something works locally but breaks in CI/staging/prod, diff the env. In each environment, list only the variables relevant to the stack in use (filter by a prefix convention like `NODE_`, `VITE_`, `NEXT_`, `DATABASE_`, `API_`, etc.) and truncate each value to a few characters so secrets aren't dumped in full — `<empty>` for anything unset. Use whatever's native to the runtime (an env-inspection one-liner, or the shell's own environment lookup piped through a filter).

The diff between environments is your suspect list. Common offenders: environment mode set to wrong value, API base URL pointing to localhost in prod, missing token, trailing whitespace in a copy-pasted secret.

### `build-version-check` — Engine/Manager version mismatch

Query the active version of the language runtime and package manager CLI, then compare it against the project's dependency manifest definitions (e.g. engines, requirements, or environment specifications).

Mismatches with project requirements cause cryptic errors. A runtime engine version mismatch often produces "X is not a function" or "Cannot read properties of undefined" from a transitive dependency that relies on a newer API.

### `build-lockfile-drift` — stale lockfile

Verify dependency tree drift: check if installed package trees drift from the lockfile, and run a clean-install command (which strictly fails on manifest/lockfile mismatches) from a clean state to isolate local state.

### `build-file-debug` — file-based debug logging (CMS / Server platforms)

Most platforms ship a debug-logging mode that's off by default: a config flag that turns on file-based error logging plus a companion flag to keep errors out of the rendered page. Enable it per the platform's current docs, record the original flag values in the ledger (protocol §3) so Phase 7 can revert or ask the user to keep them (protocol §5), then monitor the generated log stream while reproducing.

Look for the first Fatal error or stack trace — everything after it is fallout.

### Signals — build / tooling

- **First error references a missing file/module** → check imports, paths, case sensitivity (Linux vs macOS), the actual file's existence.
- **Error references a path with `node_modules/.cache` or `.vite` or `.next`** → stale cache. `rm -rf` it and rebuild.
- **Error appears only in CI** → env-var or version difference. Diff them.
- **Different errors on different machines with same code** → lockfile drift or postinstall side-effect. Run `npm ci` from clean.
- **Cryptic error after working for weeks** → check git log for recent dep bumps; an upstream package may have shipped a breaking change in a patch release.

---

## C. Fix discipline

- For performance: measure → fix → measure again. If the second measurement doesn't show improvement, you fixed the wrong thing.
- For build/env: fix the actual misconfig (correct version, correct env var, correct lockfile). Don't pin around it or add fallback code paths.
- After fixing, run Phase 7 cleanup. Build/tooling probes rarely leave instrumentation in source, but perf probes do — search the project for "[DEBUG-" using your native search tool to confirm it is clean.
