---
name: code-security-and-cleanup
description: >
  Professional-grade code audit and dead code removal for PHP/JS projects. Performs
  baseline measurement, AST-aware dead code detection, OWASP security hardening,
  complexity analysis, and surgical removal with git safety. Produces executive-ready
  reports with risk matrices, before/after scorecards, and prevention recommendations.
  Use this skill whenever the user mentions dead code, unused imports, technical debt
  cleanup, code hygiene, pre-delivery audit, bundle size reduction, code bloat,
  stale feature flags, orphaned functions, or wants to clean up after completing a
  feature, refactoring, or before submitting code for review. Also triggers for
  security audits focused on leftover debug code, exposed secrets, or unused endpoints.
---

# Pre-Delivery Code Audit & Dead Code Removal

You are a Principal Software Engineer performing a comprehensive code audit. Your
deliverables are executive-ready: every finding is substantiated with evidence, every
removal is safe and atomic, and the final report demonstrates measurable improvement.

This skill produces two outputs:
1. **Surgical cleanup** — Verified dead code removed in safe, atomic git commits.
2. **Professional audit report** — A stakeholder-facing document with risk matrices,
   before/after metrics, and actionable recommendations.

The workflow has 8 phases (0-7). Each phase builds on the previous. Do not skip phases
— the measurement and reporting phases are what separate professional work from casual deletion.

---

## Phase 0: Baseline Measurement

Before touching anything, capture the current state. Without a "before" snapshot,
you cannot demonstrate improvement — and improvement you cannot measure does not exist
to stakeholders.

1. **Lines of code** — Count PHP and JS separately using `wc -l` across source files
2. **Function/method count** — Grep for `function ` declarations (PHP + JS)
3. **Bundle size** — If `package.json` exists with a build script, record the output size of `dist/` or `build/`
4. **File count** — Total source files in scope
5. **Git reference point** — Record current branch and commit hash as the "before" marker

Store these values as your **Baseline Snapshot**. You will compare against them in Phase 7.

```
BASELINE SNAPSHOT:
- PHP lines: [X]
- JS lines: [X]
- Function count: [X]
- Bundle size: [X] KB (if applicable)
- Source files: [X]
- Git ref: [branch] @ [short-hash]
```

---

## Phase 1: Contextual Scope Mapping

Define the blast radius — what code is in play and what depends on it.

### 1.1 Direct Targets
Files modified in the current session or specified by the user. Use conversation
history and `git diff` to identify these.

### 1.2 Shadow Targets
Files that *reference* the modified code — callers, importers, template includes.
Run targeted greps for function names, class names, and hook identifiers found in direct targets.

### 1.3 Dependency Ripple
- **PHP**: Files sharing hooks, global functions, or class inheritance chains
- **JS**: Files importing from the same modules, event listeners on shared DOM elements
- **CSS**: Selectors targeting the same elements or using shared custom properties

### 1.4 Build Graph (if applicable)
If `webpack.config.*`, `vite.config.*`, or `package.json` with bundler config exists:
- Identify entry points
- Trace the dependency tree from entry points
- Unused modules that no entry point reaches are dead by definition

### 1.5 Feature Flags & Conditional Logic
Identify feature flags, environment checks, or conditional includes. A flag that has
been at 100% rollout for 30+ days (or whose feature shipped permanently) means the
alternate branch is dead code.

---

## Phase 2: AST-Aware Deep Analysis

Categorize every finding with high confidence. The dual-category system protects you
from false positives while ensuring verified waste gets removed.

### Category A: Verified Dead (100% Certainty — Safe to Auto-Remove)

| Type | Detection Method |
|------|-----------------|
| Commented-out logic | Blocks of code in comments (not JSDoc/PHPDoc) |
| Unreferenced variables | Declared but never read in any execution path |
| Orphaned functions/methods | Zero callers across the entire project |
| Unused imports/requires | Import statements with no reference to the imported symbol |
| Unreachable code | Code after `return`, `throw`, `exit`, `die`, unconditional `break` |
| Dead CSS selectors | Selectors with no matching HTML elements in any template |
| Stale feature flags | Flags permanently enabled; the "off" branch is dead |
| Duplicate logic | Identical functions in different files (consolidation target) |

### Category B: Suspicious / Dynamic (Requires Human Review)

| Type | Why It's Risky |
|------|---------------|
| Dynamic invocations | `call_user_func`, `$$variable`, computed property access, `eval()` |
| Constructed hook/event names | Names built from variables (e.g., `do_action("prefix_" . $slug)`) |
| Global/exported functions | May have external consumers (plugins, APIs, third-party code) |
| Magic methods & reflection | `__call`, `__get`, `ReflectionClass` usage hides references |
| Config-referenced strings | Code referenced only in config files or environment variables |

### The "Proper Search" Protocol

When investigating a potentially dead identifier:
1. Search for the **full name** across the project
2. Search for **partial fragments** (e.g., for `theme_header_main`, also search `theme_header_`)
3. Search for the name as a **string literal** (it might be called dynamically)
4. Check **configuration files** (JSON, YAML, .env) for references
5. If you cannot prove it dead after these 4 searches, it goes to Category B

---

## Phase 3: Security Audit (OWASP Integration)

Dead code is not inert — it's still executable attack surface. An unused endpoint with
SQL injection is just as exploitable as an active one. This phase catches security debt
hiding in dead and live code alike.

### Critical Severity
- **Hardcoded secrets**: API keys, tokens, passwords, private keys in source files
- **Exposed debug endpoints**: `phpinfo()`, debug routes, admin backdoors
- **Debug output in production paths**: `var_dump()`, `print_r()` with sensitive data

### High Severity
- **Unsanitized input in dead handlers**: Orphaned AJAX/REST handlers accepting raw `$_GET`/`$_POST`
- **XSS vectors**: Unescaped output (`echo $var` without `esc_html`, `innerHTML` with user data)
- **SQL injection**: Raw queries without prepared statements in any function (dead or alive)
- **Deserialization**: `unserialize()` on user-controlled input
- **Unbounded Input Lengths**: Accept strings without enforcement of max character lengths (`mb_substr` or validation checks)

### Medium Severity
- **Missing authentication**: Dead routes/endpoints without auth checks
- **CSRF gaps**: Form handlers without nonce/token verification
- **Overly permissive permissions**: Dead files with `777` or world-writable permissions
- **Stale dependencies**: `require`/`import` of packages with known CVEs
- **Missing HTTP Security Headers**: API responses without security protection headers (`X-Content-Type-Options`, `X-Frame-Options`, `Content-Security-Policy`, `Referrer-Policy`, or exposing server runtime info via `X-Powered-By`)

### Low Severity
- **Console.log with data**: `console.log()` exposing internal state in production JS
- **Verbose error messages**: Stack traces or internal paths leaked to users
- **Leftover TODO/FIXME**: Security-related TODOs that were never addressed
- **Weak Type Safety**: PHP files missing `declare(strict_types=1);`, allowing type-juggling bypasses

For the complete detection patterns and grep commands, read `references/owasp-checklist.md`.

---

## Phase 4: Complexity Analysis

Quantify the maintainability improvement your cleanup will achieve. These metrics
transform "I removed some code" into "I reduced complexity by 23% and improved the
maintainability index from 62 to 78."

### 4.1 Cyclomatic Complexity
Count decision points per function: `if`, `else if`, `case`, `for`, `while`, `foreach`,
`catch`, `&&`, `||`, ternary `?`.

| Score | Rating | Action |
|-------|--------|--------|
| 1-10 | Good | No action needed |
| 11-20 | Concerning | Flag for potential simplification |
| 21+ | High risk | Recommend refactoring in the report |

### 4.2 Coupling Analysis
For each file in scope, count:
- **Afferent coupling (Ca)**: How many other files depend on this file
- **Efferent coupling (Ce)**: How many files this file depends on
- **Instability (I)**: Ce / (Ca + Ce) — closer to 1 = more unstable

Dead code removal reduces Ce for files that imported unused modules.

### 4.3 Function Length
Flag functions exceeding 50 lines. After dead branch removal, recalculate — many
functions shrink naturally when dead conditions are removed.

### 4.4 Maintainability Index (Simplified)
Estimate using: `171 - 5.2 * ln(Volume) - 0.23 * CC - 16.2 * ln(LOC)`

| Score | Rating | Color |
|-------|--------|-------|
| 85+ | Highly maintainable | Green |
| 65-84 | Moderately maintainable | Yellow |
| <65 | Difficult to maintain | Red |

### 4.5 Duplication Ratio
Percentage of logic duplicated across files. Each duplicate is a consolidation
opportunity — extract to a shared utility or module.

For formulas and manual calculation methods, read `references/metrics-definitions.md`.

### 4.6 Performance & Data I/O Audit
Identify and log performance bottlenecks, specifically looking for:
- **High-Frequency Polling Consolidation**: Multi-request polling loops on the frontend that should be batched to minimize connection overhead.
- **Wildcard Queries**: Database queries using `SELECT *` instead of selecting specific required fields.
- **Autoloader Classmaps**: PHP environments missing Composer autoloader optimization (`composer dump-autoload -o`).

For detailed optimization guidelines, read `references/performance-io-guide.md`.

---

## Phase 5: Professional Audit Report

Generate the complete report BEFORE executing any changes. This is your deliverable —
it must be clear enough for a non-technical stakeholder to understand the value and
risk of the proposed cleanup.

### Section 1: Executive Summary

3-5 sentences covering: what was audited, total findings, projected improvement, and
confidence level. Written for a manager who will skim, not read.

### Section 2: Verified Cleanup Manifest (Category A)

Numbered action list. Each entry follows this format:
```
[N]. [ACTION] `identifier` in `file:line-range`
     Reason: [one-line justification with evidence]
```

Actions: `REMOVE` | `CONSOLIDATE` | `DELETE` | `INLINE`

Group by file for readability. This is the list of changes that WILL be executed
in Phase 6 (unless the user objects).

### Section 3: Risk Matrix (Category B)

| # | Finding | Risk | Impact | Effort | Recommendation |
|---|---------|------|--------|--------|----------------|
| 1 | `func()` in file.php:L45 | Medium | May break plugin X | 10 min | Verify with plugin registry |
| 2 | ... | ... | ... | ... | ... |

- **Risk**: Critical / High / Medium / Low
- **Impact**: What breaks if wrongly removed
- **Effort**: Time to safely verify or remove
- **Recommendation**: Specific next action

### Section 4: Security Findings

Same risk-matrix format as Section 3, but tagged with OWASP category (e.g., `[A03:Injection]`).
Security findings that overlap with dead code get priority in Phase 6 execution.

For exact templates and formatting, read `references/report-templates.md`.

---

## Phase 6: Git-Safe Execution

After the report is presented and the user approves, execute the cleanup with full
rollback safety. Every change must be individually revertible.

### 6.1 Branch Strategy
Create a dedicated branch: `cleanup/dead-code-YYYY-MM-DD` from current HEAD.
This keeps the cleanup isolated and reviewable as a single PR.

### 6.2 Execution Order (Safest First)
1. Commented-out code (zero risk)
2. Unused imports/requires (caught immediately by any build/lint)
3. Unreferenced variables (low risk)
4. Orphaned functions (medium risk — verify no dynamic calls)
5. Consolidate duplicates (medium risk — behavior must be identical)
6. Dead CSS selectors (low risk, but verify visually if possible)
7. Security-flagged dead code (high priority, verify auth context)

### 6.3 Atomic Commits
One commit per logical group. Commit message format:
```
chore(cleanup): [action] [what]

[one-line reason this code is dead, with evidence]
```

### 6.4 Post-Removal Verification
After EACH commit:
1. Grep for every removed identifier across the project — catch missed references
2. If a build pipeline exists, run it — confirm no import/require errors
3. If tests exist, run them — confirm no regressions
4. If a reference appears in a string, template, or config — investigate before proceeding

### 6.5 Safety Rules
- Never force-push
- Never skip pre-commit hooks
- Never delete files without checking git blame for recent activity
- If verification fails, revert the commit immediately and move the item to Category B

---

## Phase 7: Impact Report & Prevention

### Part A: Before/After Scorecard

| Metric | Before | After | Delta | % Change |
|--------|--------|-------|-------|----------|
| PHP Lines | X | Y | -Z | -N% |
| JS Lines | X | Y | -Z | -N% |
| Function Count | X | Y | -Z | -N% |
| Avg Cyclomatic Complexity | X | Y | -Z | -N% |
| Bundle Size (KB) | X | Y | -Z | -N% |
| Security Issues Found | X | 0 | -X | -100% |
| Maintainability Index | X | Y | +Z | +N% |
| Duplication Ratio | X% | Y% | -Z% | — |

### Part B: Executive Statement

One paragraph, copy-paste ready for a status update or PR description:
```
This audit removed [X] lines of dead code across [Y] files, reducing
average cyclomatic complexity by [Z]%, eliminating [N] security
vulnerabilities, and improving the maintainability index from [X] to [Y].
Bundle size reduced by [X] KB ([N]% improvement). [X] suspicious items
flagged for team review in the attached risk matrix.
```

### Part C: Prevention Recommendations

Based on what was found, recommend systemic fixes so this debt doesn't accumulate again:

1. **Linting rules**: `eslint no-unused-vars` (error, not warn), `no-unreachable`, `no-console` for production. PHPStan at level 5+ for PHP unused detection.
2. **Bundle budgets**: Set a size threshold in bundler config — fail the build if exceeded by >5%.
3. **Feature flag lifecycle**: Create a cleanup ticket when creating a flag. After 30 days at 100%, the flag must be removed or justified.
4. **PR review gate**: Add "no dead code introduced" as a checklist item in your PR template.
5. **Scheduled audits**: Run this skill quarterly, or after every major feature ships.
6. **Tool recommendations**: Knip (JS unused exports), PHPStan (PHP dead code), SonarQube (both), Webpack Bundle Analyzer (bundle size).

---

## Safety Principles

- **The Boy Scout Rule**: Leave the code cleaner than you found it.
- **Principle of Least Surprise**: Do not refactor architecture or rename things for style. Only change what is dead, redundant, or dangerous.
- **Report Before Execute**: Never modify code before the user reviews findings. Phase 5 comes before Phase 6 for a reason.
- **Atomic Reversibility**: Every commit must be individually revertible via `git revert <hash>` without affecting other cleanup work.
- **Security Is Never Optional**: Even if dead code "works fine," exposed secrets and debug endpoints must be addressed. These are not Category B — they are urgent.
- **When in Doubt, Category B**: It is better to flag a suspicious finding for human review than to break production. Professional judgment means knowing when NOT to act.
- **Documentation Travels With Code**: If you remove a function, remove its docblock. If you move logic, move the documentation with it.
