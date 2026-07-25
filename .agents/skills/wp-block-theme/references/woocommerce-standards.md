# WooCommerce Coding Standards, Safe Customization & QA

This reference covers the engineering discipline around WooCommerce work: the safe-
customization stack (child themes + hooks + snippets + prefixes), the actions-vs-filters hook
model, the dual PHP naming standard (`/src` PSR-4 vs `/includes` legacy), JS/hook naming, the
mandatory isolation rules (`function_exists`, prefixing, textdomain), build/runtime concerns
(Webpack dependency extraction, SSL behind load balancers), and the pre-deploy QA hierarchy.

This is the "how to write it safely" layer that sits under the API references
(`woocommerce-checkout-fields.md`, `woocommerce-checkout-lifecycle.md`).

## 1. When to Load This File

Load when the user asks to:

- Customize WooCommerce without losing changes on update (child theme / snippets / hooks).
- Override a **classic** PHP template (the `/woocommerce/` folder mirror).
- Name a class, function, or hook to WooCommerce standards.
- Avoid plugin conflicts / "function already declared" fatals.
- Configure the Webpack dependency-extraction build for block assets.
- Fix `is_ssl()` returning false behind a load balancer / reverse proxy.
- Set up testing or QA for a WooCommerce extension/theme.

## 2. The Safe-Customization Stack

> **Child Theme + Hooks + Snippets + Prefixes.**

Two diverging paths:

- **Dangerous path** — editing WooCommerce plugin files or parent-theme files directly. The
  next update **deletes** the old version and your code with it, and risks breaking core.
- **Safe path** — treat core as an immutable foundation; modify only through child themes and
  the hook system, so changes survive every update.

### 2.1 Hooks: Actions vs Filters

A hook is a pre-defined slot in the WooCommerce lifecycle. Two kinds:

| Type | Purpose | Use case | Requirement |
|---|---|---|---|
| **Action** | Add or move content at a point | Insert a message above the login form | No return value |
| **Filter** | Change/manipulate an existing value | Modify a currency symbol or button text | **Must return** the data |

**Priority:** default is `10`; higher numbers run **later**. To have the final word over core
logic, register at a higher priority (e.g. `20`).

### 2.2 Classic PHP Template Overrides

When no hook exposes the markup you need, copy-and-modify the template in a **child theme**.
Mirror the plugin's path but **drop the `/templates/` segment**:

```
Plugin source:  /wp-content/plugins/woocommerce/templates/emails/admin-new-order.php
Child theme:    /wp-content/themes/{{THEME_SLUG}}-child/woocommerce/emails/admin-new-order.php
```

WooCommerce loads the child-theme copy first. Use overrides sparingly and target precisely
with conditional logic — they carry the most maintenance debt of any customization.

> For **block** theme template overrides (`single-product.html`, `page-cart.html`, …) use the
> mechanism in `references/woocommerce.md → §4` instead — drop a same-named `.html` in
> `templates/`. This §2.2 path is for classic PHP templates only.

### 2.3 Code Snippets vs `functions.php`

The **Code Snippets** plugin is the safe entry point for non-structural PHP:

- **Persistence** — survives theme switches/updates.
- **Error detection** — catches fatal/syntax errors and auto-deactivates the snippet,
  preventing the White Screen of Death.
- **Organization** — name/tag individual snippets instead of one monolithic file; set scope
  ("Everywhere" vs admin-only).

Experienced developers may use the child theme's `functions.php`; both keep code out of
core/parent files.

## 3. Naming Conventions

### 3.1 PHP — the Dual-Path Standard

WooCommerce uses two standards depending on directory:

| Directory | Standard | Classes | Functions | Example |
|---|---|---|---|---|
| `/src` | PSR-4 (modern, autoloaded) | `CamelCase` | `snake_case` | `Automattic\WooCommerce\Util\get_string()` |
| `/includes` | Legacy | `Upper_Snake_Case` | `wc_`-prefixed | `WC_Cache_Helper` / `wc_get_product()` |

### 3.2 JavaScript & Hooks

- **Global classes/functions** — `WC` prefix for classes, `wc` for functions, camelCase
  (e.g. `WCOrdersTable`, `wcSettings()`).
- **Module exports** — functions/classes exported from modern modules are **not** prefixed;
  they rely on module scoping for isolation.
- **Hooks (actions/filters)** — prefixed with `woocommerce`, camelCase in JS contexts
  (e.g. `woocommerceTracksEventProperties`); PHP hooks use the `woocommerce_` prefix.

## 4. Isolation Mandates (prevent collisions & fatals)

- **Prefix everything** — replace generic placeholders with a unique, consistent team/plugin
  identifier (`YOUR_PREFIX` → your real prefix) on every global function name. Unprefixed
  names collide with other plugins and cause fatal redeclaration errors.
- **`function_exists()` wrap** — wrap every global function so a duplicate declaration can't
  crash the site:

  ```php
  if ( ! function_exists( '{{TEXT_DOMAIN}}_modify_cart_text' ) ) {
      function {{TEXT_DOMAIN}}_modify_cart_text( string $text ): string {
          return $text;
      }
  }
  ```

- **Translate, never hard-code** — wrap user-facing strings in `__()` / `_e()` with a
  consistent textdomain (`{{TEXT_DOMAIN}}`) for proper i18n isolation. (Logger messages are
  the exception — see `references/woocommerce.md → §8`.)

## 5. Build & Runtime Concerns

### 5.1 Dependency Extraction Webpack Plugin

Use the **WooCommerce Dependency Extraction Webpack Plugin** so block-asset imports resolve to
the runtime globals WordPress provides instead of bundling them. For example,
`@woocommerce/blocks-checkout` resolves to `window.wc.wcBlocksCheckout`. Without it, you ship
a duplicate copy and risk version mismatches against core.

### 5.2 `is_ssl()` Behind Load Balancers

Behind a reverse proxy / load balancer, TLS terminates before WordPress, so `is_ssl()` can
return false even on HTTPS (Port 443 isn't seen directly). For stacks that pass
`HTTP_X_FORWARDED_PROTO`, add this **above** the `require_once` call in `wp-config.php`:

```php
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
    $_SERVER['HTTPS'] = 'on';
}
```

This is host/config code — flag to the user that it must live in `wp-config.php`, not theme code.

## 6. Quality Assurance Hierarchy

Separation of Concerns: reject "God Classes" (one monolithic file holding all logic) in favor
of self-contained modular classes. Before deploy, clear:

- [ ] **Unit tests** — validate logic in isolation (calculations, data transforms).
- [ ] **E2E tests** — verify integrated behavior (e.g. a custom field persists to order meta).
- [ ] **Static analysis** — `PHP_CodeSniffer` with **both** WooCommerce and WordPress coding standards.
- [ ] **Performance benchmarking** — `Query Monitor` to surface slow hooks / DB bottlenecks.

Use **`wc_doing_it_wrong()`** — not the logger — for developer-facing API misuse, so the
warning surfaces during development.

## 7. Common Pitfalls

| Symptom | Cause | Fix |
|---|---|---|
| Customizations vanish after an update | Edited plugin/parent-theme files directly | Move to a child theme, hooks, or Code Snippets |
| Filter has no effect | Returned nothing from a filter callback | Filters **must** return the (modified) value |
| Custom logic runs before core and gets overwritten | Default priority `10` | Register at a higher priority (e.g. `20`) |
| "Cannot redeclare function" fatal | Unprefixed/unguarded global function | Prefix + wrap in `function_exists()` |
| Classic template override ignored | Kept the `/templates/` segment in the child-theme path | Mirror the path but drop `/templates/` |
| Duplicate/incompatible block bundle | Imported `@woocommerce/*` without dependency extraction | Use the Dependency Extraction Webpack Plugin |
| `is_ssl()` false on HTTPS site | Behind a load balancer terminating TLS | Add the `HTTP_X_FORWARDED_PROTO` snippet above `require_once` in `wp-config.php` |
| Untranslatable store | Hard-coded UI strings | Wrap in `__()`/`_e()` with a consistent textdomain |

## 8. When the Reference Falls Short

These are the project's vetted standards. WooCommerce's canonical coding standards and the
`woocommerce-sniffs` ruleset evolve — when a rule here conflicts with the version the user's
`PHP_CodeSniffer` enforces, defer to their installed ruleset rather than this summary.
