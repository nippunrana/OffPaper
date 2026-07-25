# Instrumentation Protocol

Whenever the debugger skill injects debug code into source files (rather than just generating a one-shot console snippet), it MUST follow this protocol. The point is simple: no debug instrumentation ever survives past the diagnosis. Leaked `console.log` lines and `error_log` calls in production are a real incident risk — they leak data, clutter logs, and can mask future bugs.

**Examples illustrate the pattern, not a fixed API.** Any framework/library call shown anywhere in this skill (a query-logging hook, a middleware signature, a config flag) is a stand-in — verify the current method name and signature against the project's installed version (lockfile, framework docs) before injecting it. Names drift across versions; the underlying capability (log queries, log requests, enable debug logging) does not.

---

## 1. The `[DEBUG-<id>]` tag

Every injected line carries a tag in this exact form:

```
[DEBUG-<4char-id>] <one-line purpose>
```

- `<4char-id>` — a 4-character random alphanumeric (e.g. `a3f9`, `k2pq`). Lowercase preferred. Generate with `Math.random().toString(36).slice(2, 6)` or pick one yourself.
- `<one-line purpose>` — a brief human-readable description: `log inbound payload`, `flow marker B`, `render counter`, etc.

### The Dual-Tagging Principle

Every injected instrumentation line must place the string `[DEBUG-<id>] <purpose>` in two places:
1. **Inside the printed output:** Print the tag as part of the log string so it is searchable in the running console or server log outputs.
2. **In a trailing comment:** Append the tag as a comment on the same line, using the target language's native comment syntax (e.g. `//`, `#`, `--`, `/* */`), so it is searchable in the codebase files.

By embedding the tag in both the printed log and the trailing comment, you guarantee that a single global search for the string `[DEBUG-` will find both the source file location and the runtime log entries.

**Abstract Format Blueprint:**
```text
<print_statement>('[DEBUG-<id>] <purpose>', ...) <comment_prefix> [DEBUG-<id>] <purpose>
```

Generate the equivalent statement dynamically using the standard idioms, log functions, and comment syntax of the target language you are writing in. Do not customize or deviate from the `[DEBUG-` search anchor.

**Exception for non-commentable files:** If you are injecting configuration values or logs into formats that do not support comments (e.g., JSON files, certain strict YAML structures, or raw key-value `.env` files where comments cause parse failures), omit the trailing comment. Instead, rely on the printed log tag alone, or register the probe/hook in the platform's initialization code where standard comments are valid.

---

## 2. One ID per hypothesis round

All probes generated during a single hypothesis-testing round share the same `<id>`. If H1 needs 5 logs across 3 files, they all carry `[DEBUG-a3f9]`. When you cleanup, a single search finds and removes all of them.

If you start a new hypothesis (Phase 5 → loop back to Phase 3 with H2), use a **new** id. That way you can clean up the failed H1 probes independently if you want to keep H2's around longer.

**Exception:** a console-only snippet that's discarded by page refresh (fetch wrapper, one-off inspection script) doesn't need to share an id with source-injected probes from the same round — it leaves nothing to clean up, so a fresh id per run is fine if it makes the log easier to read.

---

## 3. The debug ledger

Maintain a running ledger in the conversation as you inject probes. It lists every line you added, so nothing is forgotten in cleanup:

```markdown
## Debug ledger — investigation [DEBUG-a3f9]

| File | Line | Purpose |
|---|---|---|
| src/api/checkout.<extension> | 42 | log inbound payload |
| src/api/checkout.<extension> | 58 | log validation result |
| src/services/orders.<extension> | 17 | log order.create input |
| src/services/orders.<extension> | 26 | log order.create return value |
```

Also track anything that isn't a tagged line but still needs cleanup: whole files created for the investigation (e.g. a captured build log) and config flags flipped for diagnostics (e.g. a query-logging or debug-display flag). Add them to the same ledger with a `Line` value of `(file)` or `(config)`:

```markdown
| build-debug.log | (file) | captured build output — delete in cleanup |
| wp-config.php | (config) | WP_DEBUG flipped true — was false, revert or ask user |
```

When cleanup is done, mark the ledger CLOSED:

```markdown
## Debug ledger — investigation [DEBUG-a3f9] — CLOSED (4 lines removed, search verified clean)
```

---

## 4. Probe rules

These keep probes safe to run in real codebases — even on the user's main branch.

- **Observe, never mutate persistent production state.** A probe reads and prints. It never writes to a production database, never makes external mutating network calls, and never changes production control flow. In-memory, behavior-preserving interception is allowed as long as the wrapped call still does exactly what the original did to persistent state, DB writes, or flow order — those are never fair game. This covers both a browser-side fetch wrapper (undone by a page refresh) and a server-side property/descriptor trap (undone by Phase 7 removal, since there's no refresh to rely on server-side).
  - *Testing mutation path in development:* As an exception, if the bug is on an API write path, you may replay mutating requests (POST/PUT/PATCH/DELETE) only against local development or staging environments, and only after explicit confirmation from the user that it is safe to do so.
  - **Sanctioned narrow exception — a debug trace header.** Attaching a same-origin, per-session tracing header to outbound requests (see `api-network.md` → trace-correlation) is allowed even though it edits the request: it changes headers only, never the body/payload, and the server ignores it unless explicitly read. Confirm the server's CORS config already allow-lists the header (or the request is same-origin) before adding it — otherwise the probe can *create* a CORS failure or break a request signed with HMAC/SigV4, which defeats the "observe, don't change behavior" goal. Remove both client and server sides in Phase 7.
- **Probes are strictly additive.** Never modify or replace an existing line of application code — probes add new lines, they don't rewrite old ones. If a config value must change (a debug-logging flag, a query-logging flag), record the original value in the ledger so the revert steps in §5 can restore it. If the only way to observe something is to wrap an *existing* block of code (forcing it to be re-indented), that's not additive — prefer a hook that reads a post-execution buffer/log, or a middleware/handler-boundary probe that only adds new lines around the existing block, not inside it.
- **No async side effects.** Don't `await` anything new inside a probe. A probe that itself takes time changes the timing of the very thing it measures.
- **Don't log secrets.** If a payload may contain tokens, passwords, or PII, log only the structure or dictionary keys, not the raw values. When in doubt, ask the user before logging.
- **Bounded output.** If a value could be huge (full database row dump, entire DOM tree, large file), slice or truncate it (e.g. logging only the first few entries, or capping the output length) so it doesn't overflow the log size limits or clutter outputs.
- **One probe per concern.** Don't combine state snapshot + flow marker + timing in one log line — interpreting the output later is harder. Separate concerns, separate lines.

---

## 5. Cleanup checklist (Phase 7)

Before declaring the bug fixed, run this checklist:

1. **Read the ledger.** Visit every file:line listed, plus every `(file)` and `(config)` entry.
2. **Remove every tagged line.** Use Edit to delete; don't comment them out.
3. **Search the project:**
   Use your platform's built-in search tool if available; otherwise standard utilities like `grep`/`ripgrep` are a fine fallback. Search for `[DEBUG-` across the project root, excluding common build/dependency directories (node_modules, vendor, .git, etc.).
   This must return **zero matches in source files**. Matches inside documentation or skill-definition files (this protocol's own examples, READMEs, etc.) aren't leftovers — ignore those. Probes the user explicitly chose to keep (see §6) aren't leftovers either — list them as still-open in the ledger instead of removing them. Any other hit means you missed one — remove it.
4. **For the curl-wrapper / fetch-wrapper / property-trap probes** that live only in DevTools console: they're discarded by page refresh, but tell the user to refresh anyway so it's clear nothing is lingering.
5. **For any query-logging hook enabled via a config flag** (framework-provided, e.g. an ORM query-log toggle or a platform's "save all queries" setting): revert the flag using the original value recorded in the ledger.
6. **For any debug-logging flag flipped for the investigation** (e.g. a platform's verbose/debug-display setting): ask the user whether they want to keep it enabled or revert it. Don't decide for them — some sites/environments leave it on in dev.
7. **Delete any files created solely for the investigation** (e.g. a captured build-output log) per the `(file)` ledger entries.
8. **Close the ledger.** Update the ledger header to `CLOSED` with a count of removed lines. If any entries were intentionally left in place per §6, mark the ledger `PARTIAL` instead and list which entries remain open and why.
9. **Tell the user explicitly:** "All debug instrumentation removed. Search shows zero matches in source files." (If any probes were intentionally kept per §6, name them instead of claiming zero matches.)

If you can't physically search the codebase (e.g., restricted environment), ask the user to search for `[DEBUG-` and confirm it is clean. Don't skip the verification.

---

## 6. Recovery from interrupted sessions

If a session ends before cleanup (user closes the chat, context is lost), the next debugger session in the same project should start by running:

Search the project for the string `[DEBUG-` using your native search tool.

If hits appear, they're leftovers from a prior interrupted investigation. Surface them to the user before starting new work:

> I found leftover debug instrumentation from a prior session: [list]. Want me to remove these first, or keep them while we work?

This makes the cleanup contract robust across context loss.
