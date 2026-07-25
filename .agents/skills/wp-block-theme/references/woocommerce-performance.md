# WooCommerce Performance & Caching

This reference covers store-level performance: the multi-tier caching model, the
non-negotiable cache/cookie exclusions that keep Cart/Checkout/My-Account dynamic, database
hygiene, server-level GZIP, front-end asset optimization, and the Core Web Vitals targets a
WooCommerce store is held to.

**Scope note.** This is store and infrastructure configuration — adjacent to, but outside,
block-theme *file authoring*. Load it when the task is **store performance, caching, or
deployment behavior**, not when the task is composing templates or styling blocks (those stay
in `references/woocommerce.md`). Several items here are server/host config (`.htaccess`,
`nginx.conf`, `wp-config.php`, Varnish VCL), not theme code — flag that to the user when a
change must happen at the host level.

## 1. When to Load This File

Load when the user asks to:

- Set up or debug caching for a WooCommerce store (page, object, or server cache).
- Fix a "logged-out at checkout," "cart empties," or "password reset loop" symptom.
- Exclude Cart/Checkout/My-Account or WooCommerce cookies from a cache layer.
- Optimize Core Web Vitals / Lighthouse score for a store.
- Configure GZIP, image formats, lazy loading, or script defer for a store.
- Clean up a bloated WooCommerce database.

## 2. The Three-Tier Caching Model

Optimization is layered for redundancy and to push work as far from PHP/SQL as possible.

| Tier | Layer | Tools | Target |
|---|---|---|---|
| 1 | Server-side (infrastructure) | Varnish, NGINX FastCGI Cache, Redis | Reduce TTFB by serving pre-rendered HTML / object data from memory |
| 2 | Application (plugin) | WP Rocket, W3 Total Cache, WP Super Cache | Page/browser cache, expiration logic, minification |
| 3 | Object cache | Redis/Memcached object cache | Cache product metadata & transient fragments, offload the SQL layer |

## 3. Cache & Cookie Exclusions (the non-negotiable part)

Dynamic, user-specific data **must** be excluded from every caching layer. Caching it causes
session leakage (one shopper sees another's cart), empty carts, and the password-reset loop.

### 3.1 Page Exclusions

Exclude these from **all** cache layers (page and server):

- `/cart/*`
- `/checkout/*`
- `/my-account/*`

**Verification:** ensure `is_cart()`, `is_checkout()`, and `is_account_page()` return `FALSE`
for any response served from cache (i.e. those pages are never cached).

### 3.2 Cookie Exclusions

| Cookie | Duration | Purpose | Exclusion strategy |
|---|---|---|---|
| `woocommerce_cart_hash` | Session | Cart-contents integrity check | String match — exclude from page cache |
| `woocommerce_items_in_cart` | Session | Cart-state indicator for UI fragments | String match — exclude from page cache |
| `wp_woocommerce_session_` | 2 days | Maps customer to DB session data | Prefix/regex — `^wp_woocommerce_session_[a-f0-9]+` |
| `woocommerce_recently_viewed` | Session | Product history tracking | String match — exclude from object/page cache |
| `store_notice[notice id]` | Session | Dismissal state for global notices | Wildcard — target the specific ID variable |

### 3.3 Server & Database Layer

- **Varnish:** `vcl_recv` logic must **bypass** the cache whenever the
  `wp_woocommerce_session_` cookie is present.
- **Database/object cache:** exclude the `_wc_session_` prefix from host-level DB caching so
  session rows persist.

### 3.4 Causality — the Password-Reset Loop

If the My-Account endpoint is cached at the server level (e.g. Varnish), the cache prevents
processing of the unique reset nonce and session update, returning the user to the login UI
indefinitely. Excluding `/my-account/*` and bypassing on the session cookie fixes it.

## 4. Database Hygiene

Metadata-table bloat (`wp_postmeta`, `wp_options`) slows the index traversals behind product
filtering and order processing. Run routine maintenance (WP-CLI, WP-Optimize, or Advanced
Database Cleaner) targeting:

1. **Spam comments** — flush rows where `comment_approved = 'spam'`.
2. **Post revisions** — delete `post_type = 'revision'` rows to shrink `wp_posts`/`wp_postmeta`.
3. **Expired transients** — remove stale `wp_options` transient rows so autoloaded options stay lean.

Smaller tables keep high-frequency queries (stock checks, attribute filters) inside
memory-resident buffer pools.

## 5. Server-Level GZIP Compression

Offload text compression (HTML/CSS/JS) to the server instead of a plugin to cut payload before
data leaves the host and avoid PHP overhead. **Host-level config, not theme code.**

### 5.1 Apache (`.htaccess`)

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

### 5.2 NGINX (`nginx.conf`)

```nginx
gzip on;
gzip_disable "msie6";
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_buffers 16 8k;
gzip_http_version 1.1;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
```

## 6. Front-End Asset Optimization

Front-end assets sit on the Critical Rendering Path. Optimize for Core Web Vitals:

1. **Lazy loading** — `loading="lazy"` on all non-viewport images.
2. **Modern formats** — serve WebP/AVIF for product images; generate responsive `srcset` for all device resolutions.
3. **Script management** — defer non-critical JS; inline critical CSS to avoid render-blocking.
4. **W3 Total Cache `mfunc`** — add `mfunc` to *Ignored comment stems* in Minify settings so
   dynamic placeholders aren't stripped from minified files.
5. **Don't minify React block assets** — in WP Rocket, **avoid JS-minifying complex block
   assets**; it breaks execution in the React Cart/Checkout environment.

## 7. Core Web Vitals & Lighthouse Targets

Conversion tracks page speed. Verify with Chrome Lighthouse — target a Performance score of
**90+**:

| Metric | Target |
|---|---|
| LCP (Largest Contentful Paint) | < 2.5 s |
| FID (First Input Delay) | < 100 ms |
| CLS (Cumulative Layout Shift) | < 0.1 |

## 8. Common Pitfalls

| Symptom | Cause | Fix |
|---|---|---|
| Shopper sees another user's cart / cart empties | Cart/session cookies cached | Exclude `woocommerce_cart_hash`, `woocommerce_items_in_cart`, `wp_woocommerce_session_` from all caches |
| Password reset loops back to login | My-Account cached at server level | Exclude `/my-account/*`; Varnish `vcl_recv` bypass on `wp_woocommerce_session_` |
| Session data lost between requests | `_wc_session_` cached in object/DB cache | Exclude the `_wc_session_` prefix from host DB caching |
| Cart/Checkout JS throws after enabling minify | Minified React block assets | Disable JS minify for block assets (WP Rocket); add `mfunc` to W3TC ignored stems |
| Slow product filtering / order admin | Bloated `wp_postmeta` / `wp_options` | Purge spam, revisions, expired transients |
| Layout shift on product images | No fixed dimensions / no `srcset` | Serve WebP/AVIF with responsive `srcset`; reserve space to protect CLS |

## 9. When the Reference Falls Short

This file reflects the project's vetted performance notes. Host stacks vary — if a directive
here conflicts with the user's actual server (managed host, CDN edge rules, a different cache
plugin), confirm against that stack's docs rather than assuming. Never apply `.htaccess`,
`nginx.conf`, `wp-config.php`, or Varnish VCL changes without confirming the user controls
that layer; on managed hosting these are often vendor-owned.
