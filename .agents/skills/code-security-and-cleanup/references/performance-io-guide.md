# Performance & I/O Optimization Guide

This reference provides definitions, detection patterns, and remediation strategies for common performance bottlenecks and I/O inefficiencies identified during code audits.

---

## Table of Contents
1. [High-Frequency Polling Consolidation](#high-frequency-polling-consolidation)
2. [Wildcard Queries (SELECT *)](#wildcard-queries-select)
3. [Autoloader Classmaps](#autoloader-classmaps)

---

## High-Frequency Polling Consolidation

Multi-request polling loops on the client side (frontend) create excessive HTTP connection overhead, server load, and database lock contention. Consolidating these requests minimizes backend round-trips.

### Detection Patterns
Check frontend Javascript files for repeating timers (`setInterval`, `setTimeout` recursion) that execute network requests (`fetch`, `axios`, jQuery `$.ajax`, `xmlHttpRequest`):
```javascript
# JS: setInterval with fetch/ajax
setInterval\(.*fetch|setInterval\(.*\$\.ajax|setInterval\(.*axios
```
Especially watch for multiple independent polling loops in the same component or page targeting different endpoints (e.g., polling for user status, message count, and system notifications separately).

### Remediation
1. **Batching**: Consolidate multiple endpoints into a single status/aggregation endpoint (e.g., `api/status.php` returning system status, alerts, and notifications in a single payload).
2. **WebSockets/SSE**: For real-time updates, recommend migrating from polling to WebSockets or Server-Sent Events (SSE).
3. **Adaptive Intervals**: Implement exponential backoff or dynamic intervals that slow down when the browser tab is in the background (`document.hidden`).

---

## Wildcard Queries (SELECT *)

Retrieving all columns (`SELECT *`) increases database memory utilization, slows down query execution, increases network transfer size, and prevents the query planner from using covering indexes (where all requested columns are in the index).

### Detection Patterns
Check codebases (PHP, JS backends) for SQL strings using wildcards:
```sql
# SQL query with SELECT *
SELECT\s+\*\s+FROM
```
Check if database wrapper methods or ORM queries are retrieving entire records when only a few fields are processed.

### Remediation
1. **Explicit Columns**: Replace `SELECT *` with only the columns that are actually referenced in the code.
2. **Covering Indexes**: If a query only needs a few columns (e.g., `id`, `status`), selecting only those columns allows the database engine to retrieve the data entirely from the index without reading the actual table rows.

---

## Autoloader Classmaps

In production PHP environments, Composer's default autoloader checks the file system for PHP class definitions. This results in heavy disk I/O and slower class resolution times.

### Detection Patterns
Check deployment configurations, CI/CD scripts, or `composer.json` files for autoloader settings.
Verify if optimized classmaps are generated for production environments:
```bash
# Look for composer commands in scripts or deployment pipelines
composer install  (without --optimize-autoloader or -o)
composer dump-autoload (without --optimize or -o)
```

### Remediation
1. **Optimize Autoloader**: Ensure the production build step runs with:
   ```bash
   composer install --no-dev --optimize-autoloader
   # or
   composer dump-autoload -o
   ```
2. **Classmap Authoritative**: For maximum production speed, use:
   ```bash
   composer dump-autoload -a
   ```
   This prevents the autoloader from falling back to the filesystem if a class is not found in the classmap.
