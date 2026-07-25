---
name: debugger
description: Hypothesis-driven multi-domain debugger that finds the ROOT CAUSE of any bug — visual/CSS layout, code logic, async/race conditions, API/network failures, backend errors, performance issues, build/tooling problems. Use this skill whenever the user is stuck on a bug or unexpected behavior, regardless of stack or language. The skill auto-routes based on chat context, picks the lightest-weight probe (paste-ready browser-console snippet, injected debug instrumentation in source, or both), follows a strict phase-gated scientific workflow (observe → hypothesize → probe → measure → confirm → fix → cleanup), and removes every line of debug code it injects (unless the user opts to keep one) so nothing leaks into production. Trigger this skill aggressively — for phrases like "why isn't this working", "this is broken", "weird bug", "the page looks wrong", "my API returns 500" — not only when the user literally says "debug".
---

# Debugger

A general-purpose, hypothesis-driven debugging assistant. You generate the *cheapest probe that can disprove the leading hypothesis*, collect data, narrow the infection chain, and fix the bug at its root — then clean up after yourself.

This skill is designed around the scientific method (Zeller, *Why Programs Fail*): a bug is a deviation between expected and observed behavior, and finding the root cause means tracing the **infection chain** from the visible failure back to the defect that started it. Guesswork is not allowed — every fix must be backed by data.

## Why this matters

LLMs often "fix" bugs by pattern-matching on symptoms — adding null checks, wrapping things in try/catch, tweaking CSS until things look right. That makes bugs vanish without making them *understood*. This skill forces you to identify the actual cause before changing anything, which:

- Prevents the same bug from coming back in a different form
- Avoids piling on defensive code that adds complexity without value
- Builds a reusable test/regression case for every fix

---

## Part A — Read context, align with global rules & route to a domain

Before generating any output:
1. **Project context check:** Look for `ai-context.md` or `AGENTS.md` in the project root. Read them to check if there are custom logging setups or diagnostic commands before proceeding.
2. **Leftover-instrumentation check:** Search the project for the string `[DEBUG-` before starting. Any hit is a leftover from a prior interrupted session — surface it to the user before starting new work (see [references/instrumentation-protocol.md](references/instrumentation-protocol.md) §6) rather than debugging around it.
3. **Scan:** Silently scan the conversation and the user's open files for:
   - **The symptom** — what is wrong, exactly? Wrong output? Wrong layout? No response? Slow? Crashes?
   - **The expectation** — what should happen instead?
   - **Locality hints** — file paths, class names, function names, endpoints, route patterns, error messages, framework names
   - **Stack inference** — frontend/backend/full-stack/WordPress/Node/Python/PHP/etc. (from file extensions, imports, open IDE files, mentions)

Then classify the bug into **one primary domain**. Each domain has a dedicated reference file with diagnostic patterns, probe specs, and signals to look for:

| Domain | When to choose | Reference |
|---|---|---|
| **visual-ui** | Layout/CSS issues, misalignment, z-index, responsive breakage, WordPress style cascade, visual mismatch | `references/visual-ui.md` |
| **code-logic** | Wrong return value, off-by-one, conditional fails, state-management glitch, async/race condition, infinite loop, useEffect firing twice | `references/code-logic.md` |
| **api-network** | Failing fetch/AJAX/cURL, wrong payload shape, CORS, timeouts, 4xx/5xx, server-side route handler, DB query returning wrong rows | `references/api-network.md` |
| **perf-build** | Slow renders, jank, memory leaks, hot loops, plus Webpack/Vite errors, missing modules, env-var misconfig, build fatals | `references/perf-build.md` |

**Routing rules:**
- If the context is **clear** → pick the domain silently and proceed to Part B.
- If **two domains apply** (e.g., "API returns wrong data AND the UI doesn't render it") → start with the *upstream* domain. Fix the cause before the consequence.
- If the context is **unclear** → ask the user which domain applies, offering the four domains above as options. Don't guess.

---

## Part B — Run the phase-gated workflow

Every debugging session walks through these seven phases. Each phase has a logical checkpoint.

> **Fast-track bypass:** Skip the 7 phases only when the observation *deterministically* names the defect — specifically, a syntax error, a typo pointed at by a compiler/linter error, a missing import, or an unresolved merge-conflict marker — i.e., there is no hypothesis to form because nothing needs disproving. If forming the fix requires *any* inference, tracing of execution state, or logic debugging, this is NOT a trivial fix — the bypass is forbidden and you must run the phases. **Exception:** if your platform's enforced plan/approval mode is active, that approval gate still applies — describe the trivial fix in the plan and get approval first rather than applying it directly.

> [!IMPORTANT]
> **Planning Mode Compliance:** 
> - If you are in Planning Mode, you must draft your platform's implementation plan document first. 
> - Define the **Hypothesis (Phase 2)** and the proposed **Probe (Phase 3)** inside that plan, under a dedicated "Debugging Hypothesis & Probes" section.
> - **Handling Single-Gate Platforms:** In platforms that enforce a single planning/approval loop before execution, define the debugging workflow as a single, multi-step plan in your implementation plan: (1) Inject probe X, (2) Execute and gather data, (3) Restore code & apply the fix based on the gathered data. This allows the user to approve the *workflow* once. You do not need to re-request plan approval to transition from Probe (Phase 3) to Fix (Phase 6) unless the final fix requires a major architectural change. Do not make unapproved source edits outside the approved workflow.

### Phase 1 — Observe

**1a — Check existing signals first (before adding anything new)**

Probes are for filling gaps in what's already observable. Before generating any instrumentation, scan what's already there:

- Browser console — any errors, warnings, or logged values?
- Network tab — any failed requests, unexpected status codes, wrong payloads?
- Server / application logs — tail the log file or check the log aggregator (Sentry, Datadog, Papertrail, `wp-content/debug.log`, etc.)
- Existing monitoring dashboards — error rate spike? Latency anomaly?

If existing signals already identify the failure point, still state the one-line symptom + expectation (1c) before jumping — the delta check (1b) may be skipped. Then go directly to Phase 5 with that data, treating the observed signal itself as the working hypothesis (Phase 2's ranked-hypothesis list is skipped, but still open the relevant domain reference file first — Phase 5/6's signal matching and fix discipline depend on it). In Planning Mode, fill the "Debugging Hypothesis & Probes" section with the observed signal and proposed fix instead of ranked hypotheses. Don't add probes for things you can already see.

**1b — Delta check: is this a regression?**

Ask or infer: did this ever work? If yes, narrow the window immediately by reading recent changes (do not check out or modify the git tree):

```bash
git log --oneline -20          # what shipped recently?
git diff HEAD~5 -- <dependency-manifest-files>  # dependency bumps?
```

If it's a confirmed regression with a reliable reproduction step, inspecting the recent commit diffs (`git log -p`) or path-specific diffs (`git diff HEAD~5 -- path/to/file`) helps spot the defect by direct reading — see [references/code-logic.md](references/code-logic.md) for the workflow.

**1c — State the symptom and expectation**

Now state both in one sentence each, in your own words. Vague problems lead to vague fixes.

> Symptom: The Save button stays disabled even after all required fields are filled.
> Expectation: It should enable as soon as the form is valid.

**Checkpoint:** existing signals checked, delta check done (or explicitly skipped per 1a's fast path), symptom + expectation written. User confirms (implicitly via continuing, or explicitly).

### Phase 2 — Hypothesize

Before listing hypotheses, **open and read the relevant domain reference file** (e.g., [references/visual-ui.md](references/visual-ui.md), [references/code-logic.md](references/code-logic.md), [references/api-network.md](references/api-network.md), or [references/perf-build.md](references/perf-build.md)). These files contain specialized diagnostic patterns, probe specifications, and signals that you MUST follow.

Then list **2–3 ranked hypotheses** for the root cause. For each, name the **cheapest probe** (least invasive, fastest to run, easiest to interpret) that could disprove it:

> H1 (most likely): the validation function returns false because one field uses a stale ref. Probe: log the field values + validation result on each keystroke.
> H2: the disabled prop is bound to an unrelated piece of state. Probe: log the disabled prop's source on render.
> H3: a parent component is re-rendering and resetting the form state. Probe: render-count counter on the parent.

**Checkpoint:** Relevant domain reference file read; ≥ 2 hypotheses listed with probes; top one chosen.

### Phase 3 — Probe

> [!TIP]
> **Recall Domain Specs:** Re-read the selected section of the domain reference file (e.g., [references/code-logic.md](references/code-logic.md)) to ensure your probe syntax, wrappers, and constraints exactly follow the domain specs.

Generate the instrumentation for the chosen hypothesis. First, select the appropriate probe mode:

| Situation | Probe Mode |
|---|---|
| Bug reproducible at will with an IDE/debugger attached, or reproducible by a failing test | **Interactive debugger (breakpoint + watch) or failing-test-first** — no source edits, no cleanup needed. Walk the user through setting the breakpoint/watch. |
| Bug observable in the live browser without changing files (visible layout, broken click handler) | **Console snippet** — paste-ready, read-only, runs in DevTools |
| Bug is server-side, in async flow, or otherwise invisible from the browser | **Injected debug code** in source files, tagged per protocol |
| Bug spans browser ↔ server (API integration, auth flow, hydration mismatch) | **Both** — snippet for the client side, injected logs on the server side |
| User explicitly says "don't touch my files" or you're in a read-only environment | **Console snippet only**. If physically impossible to diagnose without source edits, explain why. |

If you inject code, every line MUST follow the tagging protocol in [references/instrumentation-protocol.md](references/instrumentation-protocol.md). Do not format, refactor, or touch adjacent lines when injecting debug code.

**Checkpoint:** Probe mode selected; instrumentation generated; for source injections, tagged properly.

### Phase 4 — Collect

- **Agentic Execution (Backend/Server/Build):** If the probe requires running a shell command, running a test, or reading a server log, **DO NOT ask the user to do it** (unless Planning Mode requires approval first). Execute it yourself using your native tools, analyze the output autonomously, and proceed to Phase 5.
- **User Execution (Browser/Client-side / Interactive Debugger):** If you do not have a browser-automation/devtools tool available, you must present the probe to the user in this exact format:

  ```<language>
  <the snippet, command, or debugging instructions>
  ```

  **What this collects:**
  - <bullet 1 — one brief sentence>
  - <bullet 2 — one brief sentence>

  **How to use:**
  1. <step 1>
  2. <step 2>
  3. Paste the output back here.

  Wait for the user to paste the output. If the returned output is missing or noisy, refine the probe and repeat Phase 3 and 4.

**Checkpoint:** Usable data received (collected directly or pasted by user).

### Phase 5 — Confirm or refute

> [!TIP]
> **Recall Analysis Signals:** Re-open the domain reference file and look at the "Signals to look for" section. Match your collected output against these signals to verify if the hypothesis is confirmed or refuted.

Match data against the ranked hypotheses:

- **Confirmed?** Trace the infection chain *upstream*. The first wrong value is closer to the root than the visible failure. Keep going until you find the defect that caused it.
- **Refuted?** Discard the hypothesis immediately — don't add a second probe trying to rescue it. State in one line what you now know to be true (e.g., "the validation function is *not* using stale refs — values match on every keystroke"), then pivot to H2 or form a fresh hypothesis from the new evidence. Loop back to Phase 3.

**Don't fix yet.** Premature fixes are how patches end up next to bugs instead of replacing them.

**Checkpoint:** root cause identified — a specific file, line, and reason.

### Phase 6 — Fix

**Test first (if a test suite exists).** Write the regression test *before* touching production code. Run it and confirm it fails *for the reason your root-cause analysis predicted* — a test that fails for the wrong reason is worse than no test. Only then apply the fix.

**Fix at the root, not the symptom.** If the validation function is wrong, fix the validation function — don't add an extra setState in the parent. If you find yourself wrapping the visible failure in defensive code, you haven't found the root yet; return to Phase 5.

**Address the design root for recurring bug classes.** The infection chain (Phase 5) finds *the first wrong value*. For severe, architectural, or "this keeps happening in different forms" bugs, ask one more question: *why was that value allowed to be wrong in the first place?* If the answer points at shared mutable state, a missing invariant, a leaky abstraction, or an implicit contract, the design root is where the fix belongs — not the data root. Patching only the data root means the bug will resurface under a different symptom.

> [!IMPORTANT]
> **Surgical Boundary Alignment:** If addressing the design root requires refactoring code outside the immediate defect area, do NOT execute it autonomously (which would violate Global Rule 3: Surgical Changes). Instead, apply the surgical data-root fix to resolve the immediate bug, then outline the design-root issue in your final "Root Cause & Fix" summary and ask the user if they want to schedule a separate refactoring task.

**Checkpoint:** failing test written and confirmed failing (when a test runner exists — otherwise the fix verified by directly exercising the code); fix applied; test now passes; for architectural bugs, the design-level cause is named even if a follow-up issue is filed rather than fixed in this pass.

### Phase 7 — Cleanup (NEVER SKIP THIS)

Remove every line of debug instrumentation injected during Phases 3–5. 
1. Use the debug ledger (see [references/instrumentation-protocol.md](references/instrumentation-protocol.md)) to find them.
2. **Clean up orphaned imports:** Ensure any helper libraries (e.g., logging imports, utility packages) imported solely for the probe are also removed.
3. Verify using your native codebase search/find tool for the `[DEBUG-` tag across the workspace — see the patterns in [references/instrumentation-protocol.md](references/instrumentation-protocol.md). Use your platform's built-in search tool if available; otherwise standard utilities like `grep`/`ripgrep` are a fine fallback.

The search must return **zero matches** in source files (matches inside documentation/skill files, or probes the user chose to keep per the recovery flow, are excluded and should be listed, not removed). Console snippets are discarded; the ledger is marked CLOSED (or PARTIAL, if any probes were intentionally kept) per the protocol.

**Checkpoint:** search returns nothing. Tell the user "all debug instrumentation removed."

> [!IMPORTANT]
> **Final Check:** You must complete Phase 7 cleanup BEFORE you trigger the "Final Self-Check" of the global rules. Do not summarize or end the conversation while files still contain `[DEBUG-` tags.

---

## Instrumentation Protocol Summary

The domain reference files describe probes as specs, not code. Generate probes in the target file's language.

1. **Tag every line.** Format: Embed the tag inside the printed output and as a trailing comment using the target language's native comment syntax (e.g. `//`, `#`, `--`, `/* */`), following the `[DEBUG-<4char-id>] <one-line purpose>` format (except in non-commentable formats like JSON).
2. **Use the same `<id>` for one hypothesis-testing round.** All probes generated to test the same hypothesis share an id, so a single search removes them all.
3. **Maintain a debug ledger** in the conversation — a running list of `<file>:<line>: [DEBUG-<id>] <purpose>`.
4. **Never inject probes with persistent side effects** — no DB writes, no extra network calls, no re-ordered flow. Two narrow exceptions are sanctioned in [references/instrumentation-protocol.md](references/instrumentation-protocol.md) §4: in-memory, behavior-preserving interception (fetch wrappers, property/descriptor traps) and same-origin trace headers.

---

## Analyzing Returned Data

Each domain reference file ends with a "**Signals to look for**" section. Use it. Common cross-domain signals:

- Log never fires → upstream gate.
- Unexpected `undefined`/`null` → trace original assignment.
- Wrong order → race condition.
- Higher-specificity CSS rule wins → cascade conflict (see [references/visual-ui.md](references/visual-ui.md)).
- 200 API response but empty DB → server handler short-circuit.

---

## Rules

- **No guess-fixes.** Data must confirm the cause.
- **Never leave placeholders** (e.g., `SELECTOR`, `ENDPOINT`). Resolve them from context.
- **Ask when in doubt** — a question is cheaper than a wrong path.
- **Wrap risky access in try/catch** for browser snippets — don't crash the host.
- **Planning Mode:** Implementation plans must include a "Debugging Hypothesis & Probes" section. Get approval before injecting code.

---

## Reference files

- [references/visual-ui.md](references/visual-ui.md) — browser-console report spec for CSS, layout, cascade, visibility, responsive issues.
- [references/code-logic.md](references/code-logic.md) — print-trace bisection, state snapshots, async/race probes, conditional-breakpoint hints.
- [references/api-network.md](references/api-network.md) — curl repro from network-tab data, server-log probes, DB query logging strategy, JSON shape-diff spec.
- [references/perf-build.md](references/perf-build.md) — timing/render-count/memory probe specs, profiler hints, build-error triage, env-var diffing.
- [references/instrumentation-protocol.md](references/instrumentation-protocol.md) — `[DEBUG-<id>]` tag spec, ledger template, cleanup checklist.

Read the relevant domain file *before* generating the probe in Phase 3 — it has the probe specs and analysis signals you'll need.
