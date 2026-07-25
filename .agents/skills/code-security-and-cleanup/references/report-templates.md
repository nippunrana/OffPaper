# Audit Report Templates

Use these templates to produce consistent, professional audit reports in Phase 5.
Fill in the bracketed placeholders with actual data from Phases 0-4.

---

## Table of Contents
1. [Executive Summary Template](#executive-summary-template)
2. [Verified Cleanup Manifest Template](#verified-cleanup-manifest-template)
3. [Risk Matrix Template](#risk-matrix-template)
4. [Security Findings Template](#security-findings-template)
5. [Before/After Scorecard Template](#beforeafter-scorecard-template)
6. [Executive Statement Template](#executive-statement-template)
7. [Commit Message Templates](#commit-message-templates)
8. [Complete Report Example](#complete-report-example)

---

## Executive Summary Template

```markdown
## Executive Summary

A comprehensive code audit was performed on [project/module name] following
[trigger: feature completion / refactoring / sprint close / scheduled audit].
The audit scope covered [X] files across [Y] directories, analyzing [Z] total
lines of code.

**Key findings**: [X] verified dead code items identified for immediate removal,
[Y] suspicious items flagged for team review, and [Z] security issues requiring
attention. Projected improvement: [N]% reduction in code volume, [N]% reduction
in average complexity, and elimination of [N] attack surface vectors.

**Confidence level**: [High/Medium] — [one sentence explaining the basis for
confidence, e.g., "all removals verified via project-wide reference search with
no dynamic invocation patterns detected"].
```

---

## Verified Cleanup Manifest Template

```markdown
## Verified Cleanup Manifest

The following items have been verified as dead code with 100% confidence.
They will be removed in atomic commits unless objected to.

### [filename.php] ([N] items)

1. **[REMOVE]** `functionName()` at L45-L82
   Reason: Zero callers found project-wide. Last modified 6 months ago.
   Grep verification: `functionName` returns 0 results outside its definition.

2. **[DELETE]** Commented-out block at L120-L145
   Reason: Legacy checkout logic commented out during v2 migration.
   Not documentation — contains executable code in comments.

3. **[INLINE]** `getConfig()` at L200-L205
   Reason: Single caller. Function body is one line. Inline eliminates indirection.

### [module.js] ([N] items)

4. **[REMOVE]** `import { oldHelper } from './legacy'` at L3
   Reason: `oldHelper` is never referenced after import.

5. **[CONSOLIDATE]** `formatDate()` at L50-L75
   Reason: Identical implementation exists in `utils/dates.js:L12`.
   Action: Delete this copy, update import to use shared version.

---
Total: [X] items across [Y] files | Estimated [Z] lines removed
```

---

## Risk Matrix Template

```markdown
## Risk Matrix: Items Requiring Review

These items could not be verified as dead with 100% certainty. Each requires
human judgment or additional investigation before action.

| # | Finding | Location | Risk | Impact if Wrong | Effort to Verify | Recommendation |
|---|---------|----------|------|-----------------|------------------|----------------|
| 1 | `dynamicHandler()` appears uncalled | api.php:L89 | Medium | Could break webhook integration | 15 min | Check webhook registry and logs |
| 2 | `$theme_color` unused in templates | vars.php:L12 | Low | May be used by child theme | 5 min | Grep child theme directory |
| 3 | `initLegacyPolyfill()` no direct calls | compat.js:L30 | High | May break IE11 support | 30 min | Check browser support requirements |
| 4 | `admin_custom_menu` hook registered | functions.php:L200 | Medium | Plugin may depend on it | 20 min | Audit active plugins for this hook |

### Risk Level Definitions
- **Critical**: Removal could cause data loss, security breach, or complete feature failure
- **High**: Removal likely breaks a user-facing feature or integration
- **Medium**: Removal might break edge-case functionality or non-critical integrations
- **Low**: Removal unlikely to cause issues but cannot be 100% verified
```

---

## Security Findings Template

```markdown
## Security Findings

The following security issues were identified during the audit. Items in dead code
are prioritized for removal (attack surface reduction). Items in live code are
flagged for immediate remediation.

| # | Finding | Location | OWASP | Severity | In Dead Code? | Action |
|---|---------|----------|-------|----------|---------------|--------|
| 1 | Hardcoded API key | config.php:L15 | A02 | Critical | No | Rotate key, move to .env |
| 2 | SQL injection in unused handler | legacy-api.php:L45 | A03 | Critical | Yes | Remove entire file |
| 3 | XSS via unescaped output | old-template.php:L30 | A07 | High | Yes | Remove (dead template) |
| 4 | Debug phpinfo() endpoint | debug.php:L1 | A05 | High | Yes | Delete file |
| 5 | Missing CSRF on form handler | ajax.php:L80 | A01 | Medium | No | Add nonce verification |

### Summary
- **Dead code security issues**: [X] (will be removed in Phase 6)
- **Live code security issues**: [X] (flagged for team remediation)
- **Attack surface reduction**: Removing dead code eliminates [X] exploitable vectors
```

---

## Before/After Scorecard Template

```markdown
## Impact Scorecard

| Metric | Before | After | Delta | % Change | Status |
|--------|--------|-------|-------|----------|--------|
| PHP Lines of Code | [X] | [Y] | -[Z] | -[N]% | Improved |
| JS Lines of Code | [X] | [Y] | -[Z] | -[N]% | Improved |
| Total Functions | [X] | [Y] | -[Z] | -[N]% | Improved |
| Avg Cyclomatic Complexity | [X] | [Y] | -[Z] | -[N]% | Improved |
| Bundle Size (KB) | [X] | [Y] | -[Z] | -[N]% | Improved |
| Security Issues | [X] | [Y] | -[Z] | -[N]% | Improved |
| Maintainability Index | [X] | [Y] | +[Z] | +[N]% | Improved |
| Duplication Ratio | [X]% | [Y]% | -[Z]% | — | Improved |
| Files Affected | — | — | [Z] | — | — |
| Commits Created | — | — | [Z] | — | — |
```

---

## Executive Statement Template

For copy-paste into PR descriptions, status updates, or stakeholder communications:

```markdown
**Code Audit Summary — [Date]**

This pre-delivery audit of [project/module] removed [X] lines of dead code
across [Y] files in [Z] atomic commits. Average cyclomatic complexity reduced
from [X] to [Y] (-[N]%), maintainability index improved from [X] to [Y],
and [N] security vulnerabilities were eliminated. Bundle size reduced by
[X] KB ([N]%). [X] items flagged for team review (see risk matrix).

All changes are individually revertible. No behavioral changes to live features.
```

---

## Commit Message Templates

### For commented-out code removal
```
chore(cleanup): remove commented-out legacy code from [file]

Dead code from [feature/migration] left behind during [event].
No references found. Not documentation — executable code in comments.
```

### For unused function removal
```
chore(cleanup): remove unused [functionName] from [file]

Zero callers found project-wide via grep. Function last modified [date].
No dynamic invocation patterns detected (checked partial string matches).
```

### For import/require cleanup
```
chore(cleanup): remove unused imports from [file]

[N] import(s) referencing symbols never used in this module.
Verified via AST-level reference check.
```

### For duplicate consolidation
```
chore(cleanup): consolidate duplicate [functionName] into [shared-location]

Identical implementation existed in [file1] and [file2].
Extracted to [shared-location] as single source of truth.
All [N] call sites updated.
```

### For security-related removal
```
fix(security): remove [vulnerability type] in dead [file/endpoint]

[OWASP category] vulnerability in unused code. While dead, the endpoint
remained reachable and exploitable. Removed to reduce attack surface.
```

### For dead CSS removal
```
chore(cleanup): remove orphaned CSS selectors from [file]

[N] selectors targeting elements that no longer exist in any template.
Verified via grep across all HTML/PHP template files.
```

---

## Complete Report Example

Below is a condensed example of a full audit report for reference. Adapt length
and detail level to the scope of the actual audit.

```markdown
# Code Audit Report — PaymentGateway Module
**Date**: 2026-05-10 | **Auditor**: AI Principal Engineer | **Scope**: src/payments/

## Executive Summary
A comprehensive audit of the PaymentGateway module was performed following the
migration from Stripe v2 to v3 SDK. The audit covered 12 files (2,847 lines).
14 verified dead items identified for removal, 3 items flagged for review,
and 2 security issues found. Projected: 18% code reduction, 31% complexity
improvement.

## Verified Cleanup Manifest
[... items listed per template above ...]

## Risk Matrix
[... table per template above ...]

## Security Findings
[... table per template above ...]

## Impact Scorecard
[... table per template above ...]

## Prevention Recommendations
1. Add ESLint `no-unused-imports` rule to CI pipeline
2. Set bundle budget at 245KB (current - 15%) in vite.config
3. Create JIRA ticket for quarterly dead code audits
4. Add "no dead code" checkbox to PR template
```
