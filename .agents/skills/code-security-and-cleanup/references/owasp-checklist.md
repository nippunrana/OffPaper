# OWASP Security Checklist for PHP/JS Code Audits

This reference provides detection patterns for common security issues found during
dead code audits. Each item includes a grep pattern, severity, and remediation action.

---

## Table of Contents
1. [A01: Broken Access Control](#a01-broken-access-control)
2. [A02: Cryptographic Failures](#a02-cryptographic-failures)
3. [A03: Injection](#a03-injection)
4. [A05: Security Misconfiguration](#a05-security-misconfiguration)
5. [A07: Cross-Site Scripting (XSS)](#a07-cross-site-scripting-xss)
6. [A08: Software and Data Integrity Failures](#a08-software-and-data-integrity-failures)
7. [A09: Security Logging and Monitoring Failures](#a09-security-logging-and-monitoring-failures)

---

## A01: Broken Access Control

### Missing Authentication on Endpoints
**Severity**: High
**Detection patterns**:
```
# PHP: AJAX handlers without auth checks
wp_ajax_nopriv_
$_GET\[|$_POST\[|$_REQUEST\[  (without preceding capability/nonce check)

# JS: Fetch/XHR without auth headers
fetch\(.*\{(?!.*[Aa]uthoriz)
```
**Action**: If the endpoint is dead, remove it. If live, add authentication.

### Missing Nonce/CSRF Verification
**Severity**: Medium
**Detection patterns**:
```
# PHP: Form handlers without nonce
admin_post_  (without wp_verify_nonce nearby)
wp_ajax_     (without check_ajax_referer nearby)

# General: Form submission without token
<form.*method="post"  (without hidden csrf/nonce input)
```
**Action**: Dead handlers should be removed entirely — they are exploitable attack surface.

### Capability Checks Missing
**Severity**: Medium
**Detection patterns**:
```
# PHP WordPress: Admin actions without capability check
current_user_can  (should appear before any privileged action)
# Look for admin-panel code that lacks:
if.*current_user_can|if.*is_admin
```
**Action**: Remove dead admin code. For live code, add `current_user_can()` checks.

---

## A02: Cryptographic Failures

### Hardcoded Secrets
**Severity**: Critical
**Detection patterns**:
```
# API keys and tokens
['\"](?:sk|pk|api[_-]?key|token|secret|password|auth)['\"].*[:=].*['\"][A-Za-z0-9+/=]{16,}

# Common patterns
AKIA[0-9A-Z]{16}               (AWS access key)
sk_live_[A-Za-z0-9]{24,}       (Stripe secret key)
ghp_[A-Za-z0-9]{36}            (GitHub personal token)
-----BEGIN.*PRIVATE KEY-----    (Private key)
```
**Action**: Remove immediately. Rotate the compromised credential. Add to `.gitignore`.

### Weak Hashing
**Severity**: High
**Detection patterns**:
```
md5\(|sha1\(          (used for passwords or security tokens)
base64_encode\(       (used as "encryption" — it's not)
```
**Action**: If dead code, remove. If live, migrate to `password_hash()` / `hash('sha256')`.

---

## A03: Injection

### SQL Injection
**Severity**: Critical
**Detection patterns**:
```
# PHP: Direct variable interpolation in SQL
\$wpdb->query\(.*\$_    (direct user input in query)
"SELECT.*\$              (variable in SQL string without prepare)
'INSERT.*\$
"UPDATE.*\$
"DELETE.*\$

# Should use instead:
$wpdb->prepare\(
```
**Action**: Dead code with SQL injection must be removed — it can be reached via direct URL if the handler exists.

### Command Injection
**Severity**: Critical
**Detection patterns**:
```
exec\(.*\$|system\(.*\$|passthru\(.*\$|shell_exec\(.*\$|popen\(.*\$
`.*\$.*`                 (backtick execution with variables)
```
**Action**: Remove immediately if dead. If live, use `escapeshellarg()` / `escapeshellcmd()`.

### PHP Object Injection
**Severity**: High
**Detection patterns**:
```
unserialize\(.*\$_       (unserialize user input)
unserialize\(.*\$        (unserialize any variable — check source)
```
**Action**: Remove if dead. If live, use `json_decode()` instead or add allowed_classes.

### Unbounded Input Lengths
**Severity**: High
**Detection patterns**:
Check for untrusted user inputs (from `$_GET`, `$_POST`, `$_REQUEST`, HTTP headers, or request payloads in PHP or JS) that are processed, compared, or stored without character length limits or truncation.
```
# PHP: assignment or direct use of superglobals without length validation/truncation
\$_GET\[| \$_POST\[| \$_REQUEST\[

# JS/Node: request parameters or body used without length validation
req\.body|req\.query
```
Look for absence of character clamping/truncation functions like `mb_substr()`, `substr()`, or validator checks (`strlen()`, `mb_strlen()`, schema validation).
**Action**: If the handler is dead, remove it. If live, clamp/truncate the incoming string parameters using `mb_substr($input, 0, $max_len)` or enforce length validations at the API/schema level.

---

## A05: Security Misconfiguration

### Debug Mode in Production
**Severity**: High
**Detection patterns**:
```
# PHP
define.*WP_DEBUG.*true
define.*APP_DEBUG.*true
error_reporting\(E_ALL\)
ini_set.*display_errors.*1
phpinfo\(\)

# JS
console\.log\(|console\.debug\(|console\.trace\(
debugger;
sourceMap.*true          (in production webpack config)
```
**Action**: Remove debug statements. Ensure debug flags are driven by environment, not hardcoded.

### Exposed Configuration
**Severity**: High
**Detection patterns**:
```
# Files that should not be web-accessible
\.env$|\.env\.local$|\.env\.production$
wp-config\.php.*define.*DB_PASSWORD
config\.php.*\$password
```
**Action**: Verify `.htaccess` or server config blocks access to sensitive files.

### Default/Test Credentials
**Severity**: Critical
**Detection patterns**:
```
password.*=.*['\"](admin|test|123|password|root)
user.*=.*['\"](admin|test|root)
```
**Action**: Remove immediately. Never commit test credentials.

### Missing HTTP Security Headers
**Severity**: Medium
**Detection patterns**:
Identify where HTTP response headers are set (in PHP, JS backends, or server configs). Check if essential security headers are absent:
```
# PHP: header settings
header\(

# JS: Express/Node header settings
res\.setHeader\(|res\.header\(|res\.set\(
```
Verify the implementation or lack of:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN` or `DENY`
- `Content-Security-Policy` (CSP)
- `Referrer-Policy`
Check if the server exposes runtime signatures:
- `X-Powered-By` (should be explicitly disabled or unset)
**Action**: If dead code, remove. If live, implement the missing HTTP headers (via middleware, code, or server config) and disable runtime headers.

---

## A07: Cross-Site Scripting (XSS)

### Unescaped Output (PHP)
**Severity**: High
**Detection patterns**:
```
echo \$_|echo \$         (echo without escaping — check context)
print \$_|print \$
<?=\s*\$                 (short echo without escaping)

# Should use instead:
esc_html\(|esc_attr\(|esc_url\(|wp_kses\(|htmlspecialchars\(
```
**Action**: Dead templates with XSS should be removed. Live code must use proper escaping.

### DOM-based XSS (JavaScript)
**Severity**: High
**Detection patterns**:
```
\.innerHTML\s*=          (setting innerHTML with dynamic content)
document\.write\(
\$\(.*\)\.html\(        (jQuery .html() with dynamic content)
eval\(|Function\(        (executing dynamic strings)
```
**Action**: Remove if dead. If live, use `textContent` or sanitize with DOMPurify.

---

## A08: Software and Data Integrity Failures

### Unvalidated Redirects
**Severity**: Medium
**Detection patterns**:
```
header\(.*Location.*\$_   (redirect using user input)
wp_redirect\(.*\$_        (WordPress redirect with user input)
window\.location.*=.*(?:params|query|search|hash)
```
**Action**: Remove dead redirects. Live ones need URL validation via allowlist.

### Unsafe Deserialization
**Severity**: High
**Detection patterns**:
```
unserialize\(|yaml_parse\(|json_decode\(.*true  (with assoc but from untrusted source)
```
**Action**: Validate source before deserialization. Remove dead deserialization code.

### Weak Type Safety (PHP)
**Severity**: Low to Medium
**Detection patterns**:
Check if audited PHP files lack strict type enforcement.
```
# PHP files missing declare(strict_types=1);
# Look at the first lines of the file right after <?php.
# Flag if the following pattern is missing:
declare\(strict_types=1\);
```
**Action**: Add `declare(strict_types=1);` at the top of audited PHP files to prevent type-juggling vulnerabilities.

---

## A09: Security Logging and Monitoring Failures

### Sensitive Data in Logs
**Severity**: Medium
**Detection patterns**:
```
error_log\(.*password|error_log\(.*token|error_log\(.*secret
console\.log\(.*token|console\.log\(.*password|console\.log\(.*key
```
**Action**: Remove logging of sensitive data. Use redaction if logging is necessary.

### Missing Audit Trail
**Severity**: Low
**Detection patterns**:
```
# Privileged actions without logging
delete_user|delete_post|update_option  (without corresponding audit log call)
```
**Action**: Note in report as a recommendation, not a removal target.

---

## Usage Notes

- Run these patterns against the FULL project scope, not just dead code
- Security issues in dead code get PRIORITY removal (attack surface reduction)
- Security issues in live code go into the Risk Matrix as high-priority recommendations
- When in doubt about severity, escalate — assume the worst case
- After removing dead code with security issues, verify the endpoint is truly unreachable
