# Code Complexity Metrics: Definitions & Thresholds

This reference provides formulas, manual calculation methods, and threshold tables
for the complexity metrics used in Phase 4 of the audit.

---

## Table of Contents
1. [Cyclomatic Complexity](#cyclomatic-complexity)
2. [Maintainability Index](#maintainability-index)
3. [Coupling Metrics](#coupling-metrics)
4. [Function Length](#function-length)
5. [Duplication Ratio](#duplication-ratio)
6. [Manual Estimation Without Tooling](#manual-estimation-without-tooling)

---

## Cyclomatic Complexity

### Formula
```
CC = E - N + 2P
```
Where:
- E = number of edges in the control flow graph
- N = number of nodes
- P = number of connected components (usually 1 for a single function)

### Simplified Counting Method (Preferred for Audits)
For each function, start at 1 and add 1 for each:
- `if` / `elseif` / `else if`
- `case` (in switch)
- `for` / `foreach` / `while` / `do-while`
- `catch`
- `&&` / `||` (logical operators in conditions)
- Ternary `? :`
- `??` (null coalescing used as branching)

### Thresholds

| Score | Rating | Interpretation | Action |
|-------|--------|----------------|--------|
| 1-5 | Simple | Easy to test and maintain | None needed |
| 6-10 | Moderate | Reasonable complexity | Acceptable |
| 11-20 | Complex | Difficult to test thoroughly | Flag in report |
| 21-50 | Very complex | Error-prone, hard to maintain | Recommend refactoring |
| 50+ | Untestable | Nearly impossible to fully test | Urgent refactoring |

### Example Calculation
```php
function processOrder($order, $user) {    // Start: 1
    if (!$order) return null;              // +1 (if)
    if (!$user || !$user->isActive()) {    // +1 (if) +1 (||)
        throw new Exception('Invalid');
    }
    foreach ($order->items as $item) {     // +1 (foreach)
        if ($item->inStock()) {            // +1 (if)
            $total += $item->price;
        } else {                           // +1 (else path has different logic)
            $backorder[] = $item;
        }
    }
    return $total > 0 ? $total : 0;        // +1 (ternary)
}
// Total CC = 8 (Moderate — acceptable)
```

---

## Maintainability Index

### Formula (Microsoft variant, widely used)
```
MI = 171 - 5.2 * ln(HV) - 0.23 * CC - 16.2 * ln(LOC)
```
Where:
- HV = Halstead Volume (see below)
- CC = Cyclomatic Complexity (average per function)
- LOC = Lines of Code

### Halstead Volume (Simplified)
```
HV = N * log2(n)
```
Where:
- N = total number of operators + operands (program length)
- n = number of distinct operators + distinct operands (vocabulary)

For quick estimation without exact Halstead calculation, use:
```
HV ≈ LOC * 5  (rough approximation for typical code density)
```

### Thresholds

| Score | Rating | Color | Interpretation |
|-------|--------|-------|----------------|
| 85-171 | Highly maintainable | Green | Clean, well-structured code |
| 65-84 | Moderately maintainable | Yellow | Some complexity, manageable |
| 0-64 | Difficult to maintain | Red | Needs refactoring attention |

### Worked Example
```
Given: LOC = 200, CC_avg = 12, HV ≈ 200 * 5 = 1000

MI = 171 - 5.2 * ln(1000) - 0.23 * 12 - 16.2 * ln(200)
   = 171 - 5.2 * 6.91 - 2.76 - 16.2 * 5.30
   = 171 - 35.9 - 2.76 - 85.9
   = 46.4 (Red — difficult to maintain)
```

---

## Coupling Metrics

### Afferent Coupling (Ca)
Number of external files/modules that DEPEND ON this module.
- High Ca = many things break if you change this module
- High Ca modules should be changed cautiously

### Efferent Coupling (Ce)
Number of external files/modules that this module DEPENDS ON.
- High Ce = this module is fragile to external changes
- Dead imports artificially inflate Ce

### Instability Index
```
I = Ce / (Ca + Ce)
```
- I = 0: Maximally stable (everything depends on it, it depends on nothing)
- I = 1: Maximally unstable (depends on everything, nothing depends on it)
- Dead code removal reduces Ce, improving stability

### Thresholds

| Ce per file | Rating | Interpretation |
|-------------|--------|----------------|
| 0-5 | Low coupling | Well-encapsulated |
| 6-10 | Moderate | Normal for utility files |
| 11-20 | High | Consider refactoring dependencies |
| 20+ | Very high | Likely a "god file" — needs decomposition |

---

## Function Length

### Thresholds

| Lines | Rating | Action |
|-------|--------|--------|
| 1-20 | Ideal | No action |
| 21-50 | Acceptable | Monitor during growth |
| 51-100 | Long | Flag for potential extraction |
| 100+ | Too long | Recommend breaking into sub-functions |

### How Dead Code Affects Length
Dead branches (disabled feature flags, unreachable conditions) inflate function length
without adding value. Removing them often brings functions back under threshold naturally.

---

## Duplication Ratio

### Formula
```
Duplication % = (duplicated lines / total lines) * 100
```

### Detection Heuristic
Code blocks of 6+ consecutive lines (or 3+ consecutive statements) that appear
identically in more than one location.

### Thresholds

| Ratio | Rating | Action |
|-------|--------|--------|
| 0-3% | Excellent | Minimal duplication |
| 3-5% | Acceptable | Normal for most projects |
| 5-10% | Concerning | Consolidation opportunities exist |
| 10-20% | High | Active tech debt — plan consolidation |
| 20%+ | Critical | Major DRY violations — immediate action |

### Consolidation Strategy
When duplicates are found:
1. Verify the duplicates are truly identical in behavior (not just appearance)
2. Extract to a shared function/module in the most logical location
3. Update all call sites to use the shared version
4. Run tests to confirm behavior is preserved

---

## Manual Estimation Without Tooling

When static analysis tools (PHPStan, ESLint complexity rule, SonarQube) are not
available, use these manual methods:

### Quick CC Estimate
1. Open the function
2. Count keywords: `if`, `else`, `for`, `foreach`, `while`, `case`, `catch`, `&&`, `||`, `?`
3. Add 1 (base complexity)
4. That's your CC

### Quick MI Estimate
1. Count LOC for the file
2. Estimate average CC (pick 3 representative functions, average their CC)
3. Use `HV ≈ LOC * 5`
4. Plug into formula

### Quick Coupling Estimate
1. Count `import`/`require`/`use` statements = Ce
2. Grep for this file's name across the project = Ca

### Quick Duplication Check
1. If you see the same logic in 2+ places during the audit, it's a duplicate
2. Search for distinctive fragments (unique variable names, specific strings) from one copy
3. If they appear elsewhere, that's duplication

---

## Reporting Format

When presenting metrics in the audit report, always show:
1. The metric name and score
2. The threshold it falls into (with color)
3. The projected improvement after dead code removal
4. Specific files/functions that are the worst offenders

Example:
```
Cyclomatic Complexity:
- Project average: 14.2 (Complex — Yellow)
- Worst offender: processCheckout() in checkout.php = 34 (Very Complex — Red)
- After cleanup: projected average 9.8 (Moderate — Green)
```
