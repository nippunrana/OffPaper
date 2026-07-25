# Performance Reference

## Table of Contents
1. [Performance Hard Limits](#performance-hard-limits)
2. [JavaScript Optimization](#javascript-optimization)
3. [CSS Architecture & Stylesheet Subsetting](#css-architecture--stylesheet-subsetting)
4. [Asset Management & CDN](#asset-management--cdn)
5. [Image Optimization](#image-optimization)
6. [Liquid Performance](#liquid-performance)
7. [Lighthouse Scoring](#lighthouse-scoring)
8. [Performance Checklist](#performance-checklist)

---

## Performance Hard Limits

| Constraint | Value | Consequence of violation |
|---|---|---|
| Total minified JS bundle | **≤ 16 KB** | Theme Store rejection; slow TTI |
| Lighthouse mobile score | **≥ 60** | Theme Store rejection |
| Sections per JSON template | **25** | Exceeding causes editor instability |
| Blocks per section | **50** | Schema validation failure |
| Pagination overflow signal | **25,001** | Implement filters; don't paginate past this |
| Dynamic sources per template | **100** | Editor performance degradation |

---

## JavaScript Optimization

### Core Rules

1. **Use `defer` or `async` on all script tags.** Parser-blocking scripts directly
   harm First Contentful Paint (FCP). Never load scripts synchronously.

2. **Wrap all theme JS in an IIFE.** Prevents namespace collisions with app scripts
   and Shopify's own JS after minification renames variables.
   ```javascript
   (function() {
     'use strict';
     // All theme logic here
     const myVar = 'safe from collision';
   })();
   ```

3. **No external frameworks.** React, Vue, jQuery, Lodash, GSAP — all banned.
   Use native browser APIs and modern DOM features exclusively.
   - Shopify injects `es-module-shims` — never load your own copy.

4. **CSS-first interactivity.** If CSS can handle it (toggles, animations, transitions),
   use CSS. JavaScript should be a last resort for interactivity.

5. **Import-on-interaction pattern.** Defer loading heavy components until the user
   actually needs them:
   ```javascript
   document.querySelector('.video-trigger').addEventListener('click', async () => {
     const { VideoPlayer } = await import('./video-player.js');
     new VideoPlayer();
   });
   ```

### Section-Scoped JS

Use `{% javascript %}` tags inside section files for section-specific logic.
Shopify bundles these into a single `scripts.js` file.

```liquid
{% javascript %}
  (function() {
    // This JS only runs when the section is on the page
    const section = document.getElementById('shopify-section-{{ section.id }}');
    if (!section) return;

    // Guard for theme editor (re-runs on section load event)
    function init() {
      // Initialize component
    }

    init();
    // Listen for editor re-renders
    section.addEventListener('shopify:section:load', init);
  })();
{% endjavascript %}
```

**One `{% javascript %}` tag per file** — multiple are not supported.
No Liquid code inside the tag — purely static JavaScript.

### Global JS (layout)

For scripts needed on every page, reference from `assets/` in `theme.liquid`:
```liquid
{{ 'theme.js' | asset_url | script_tag }}
```

Or with `defer` attribute via Liquid:
```liquid
<script src="{{ 'theme.js' | asset_url }}" defer></script>
```

### Minification

Shopify auto-minifies JS to ES5 syntax. Bypass conditions:
- Files ending in `.min.js` are served as-is (not minified — use only for pre-minified files).
- ES6+ syntax: if your script uses ES6 features without a build step, it may bypass
  minification. Use IIFE patterns to keep variables safe either way.
- If the minified version is larger than the original, the original is served.

---

## CSS Architecture & Stylesheet Subsetting

### How Subsetting Works

Shopify analyzes the **render tree** (the specific sections, blocks, and snippets that
render on a given page) and bundles ONLY the CSS from `{% stylesheet %}` tags of those
files into a page-specific `styles.css`. This means:

- CSS defined in a section's `{% stylesheet %}` is only loaded when that section is
  present on the page.
- CSS defined in a snippet's `{% stylesheet %}` is only loaded when that snippet renders.

**Compatible patterns (use these):**
```liquid
{# CSS defined and used in the same section file #}
{% stylesheet %}
  .hero-banner { position: relative; }
  .hero-banner__title { font-size: 2rem; }
{% endstylesheet %}
```

**Incompatible patterns (avoid):**
```liquid
{# ❌ WRONG: CSS in header section relying on footer section class #}
{# If header.liquid defines .site-footer, footer.liquid won't get it on pages with no header #}
```

### What is NOT subsetted

| CSS location | Included via |
|---|---|
| `{% stylesheet %}` in sections/snippets | Subsetting (page-specific) |
| `assets/global.css` via `stylesheet_tag` | Always loaded (global) |
| Inline `style` attributes | Always applied |
| `{% style %}` tags in Liquid | NOT subsetted; avoid for section CSS |

**Rule of thumb:**
- Section/component CSS → `{% stylesheet %}` tag inside the file
- Global utility classes, typography, CSS resets → `assets/global.css` or `assets/theme.css`

### Section Rendering API & CSS

When using the Section Rendering API to fetch and inject section HTML dynamically:
```javascript
fetch('/sections/product-recommendations')
  .then(r => r.text())
  .then(html => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');

    // IMPORTANT: Also inject the section's CSS
    const sectionStylesheet = doc.querySelector('[data-section-stylesheet]');
    if (sectionStylesheet) {
      document.head.appendChild(sectionStylesheet.cloneNode(true));
    }

    // Then inject the section HTML
    document.getElementById('target').innerHTML = doc.querySelector('.section-content').innerHTML;
  });
```

If you inject only the HTML and skip the stylesheet element, the section renders without styles.

---

## Asset Management & CDN

### Always Use Liquid Asset Filters

Never hardcode asset paths. Always use `asset_url` (for theme files) or `image_url`
(for Shopify-hosted images) to get CDN-optimized URLs with cache-busting.

```liquid
{# CSS #}
{{ 'theme.css' | asset_url | stylesheet_tag }}

{# JS #}
{{ 'theme.js' | asset_url | script_tag }}

{# Images in Liquid templates #}
{{ 'logo.png' | asset_url | image_tag: alt: shop.name }}

{# Images in CSS (.liquid asset files) #}
.hero { background-image: url("{{ 'hero-bg.jpg' | asset_url }}"); }
```

### Cache Busting

The `asset_url` filter appends `?v=1384022871` (a versioning hash). This tells the
Shopify CDN to serve the latest version immediately after a theme push.
**Without `asset_url`, updated assets may be cached for 24+ hours.**

### CDN & Connection Reuse

Shopify serves assets from `{shop}.myshopify.com/cdn`. By keeping assets on the same
domain as the HTML, the browser reuses the existing TCP/TLS connection, avoiding DNS
lookups and handshake overhead.

**Platform CDN features (automatic, no action needed):**
- Brotli/gzip compression
- HTTP/3 and TLS 1.3
- Regional edge caching via Cloudflare
- Speculation rules for page navigation prefetching

### Preloading Critical Resources

For render-blocking critical CSS or above-the-fold images:
```liquid
{# In theme.liquid <head> #}
{{ 'critical.css' | asset_url | preload_tag: as: 'style' }}

{# Or on image_tag/stylesheet_tag #}
{{ 'critical.css' | asset_url | stylesheet_tag: preload: true }}

{# Preload the LCP hero image #}
{{ hero_image | image_url: width: 1500 | preload_tag: as: 'image' }}
```

---

## Image Optimization

### Responsive Images (mandatory)

Always use `image_tag` with width specifications. The filter automatically generates
`srcset` attributes for responsive delivery.

```liquid
{# Basic responsive image #}
{{ product.featured_image | image_url: width: 800 | image_tag:
   alt: product.title,
   loading: 'lazy',
   widths: '165, 360, 535, 750, 1070, 1500',
   sizes: '(min-width: 1200px) 700px, (min-width: 750px) 50vw, 100vw'
}}
```

### Lazy Loading Rules

| Content position | Loading attribute |
|---|---|
| Above the fold (visible on load) | **No lazy loading** — NEVER use `loading: 'lazy'` |
| Below the fold | `loading: 'lazy'` |
| LCP (Largest Contentful Paint) hero image | `loading: 'eager'` + preload |

```liquid
{# Above-the-fold hero — NO lazy loading, add preload #}
{{ section.settings.hero_image | image_url: width: 1500 | image_tag:
   alt: section.settings.hero_alt,
   loading: 'eager',
   fetchpriority: 'high',
   widths: '375, 750, 1100, 1500, 1780, 2000'
}}
```

### Placeholder Images

```liquid
{# Use when no image is set #}
{% if product.featured_image != blank %}
  {{ product.featured_image | image_url: width: 400 | image_tag: alt: product.title, loading: 'lazy' }}
{% else %}
  {{ 'product-1' | placeholder_svg_tag: 'placeholder-svg' }}
{% endif %}
```

---

## Liquid Performance

### Server-Side Optimization

1. **Never sort or transform inside a loop.** Do it once, assign to a variable, then loop.
   ```liquid
   {# ❌ Slow: sorting inside loop #}
   {% for product in collection.products %}
     {{ collection.products | sort: 'price' | first }}
   {% endfor %}

   {# ✅ Fast: sort once, then loop #}
   {% assign sorted_products = collection.products | sort: 'price' %}
   {% for product in sorted_products %}
     {{ product.title }}
   {% endfor %}
   ```

2. **Paginate large collections.** Never output more than needed per page.
   ```liquid
   {% paginate collection.products by 24 %}
     {% for product in collection.products %}
       {# render product card #}
     {% endfor %}
     {{ paginate | default_pagination }}
   {% endpaginate %}
   ```

3. **The 25,001 overflow signal.** When `collection.products_count` returns 25,001,
   the collection has more products than Shopify's 25,000 array limit. Don't try to
   paginate through them all — add filters to narrow the result set.

4. **Use the Shopify Theme Inspector** (Chrome extension) to identify which Liquid
   lines are causing render bottlenecks.

---

## Lighthouse Scoring

### Minimum Standard
- Theme Store requires **average Lighthouse mobile score ≥ 60**.
- Target ≥ 90 for competitive themes.

### Weighted Formula
Shopify calculates the speed score as a weighted average across three page types:

```
Speed Score = [(product_score × 31) + (collection_score × 33) + (home_score × 13)] / 77
```

### Audit Protocol
1. Set up a clean development store (no third-party apps).
2. Import the Shopify standard test product CSV.
3. Copy the shopifypreview.com URL from the Themes page.
4. Append `?pb=0` to all URLs to remove the admin preview bar (skews results).
5. Run Lighthouse Mobile audits for:
   - Home page (H)
   - A product page (P)
   - A collection page (C)
6. Calculate weighted score.

### Automation
Integrate the Shopify Lighthouse CI GitHub Action for automated audits on pull requests.

---

## Performance Checklist

- [ ] Total minified JS ≤ 16 KB
- [ ] All scripts use `defer` or `async`
- [ ] All theme JS wrapped in IIFE
- [ ] No external frameworks (React, jQuery, Vue, etc.)
- [ ] All asset references use `asset_url` or `image_url` (no hardcoded paths)
- [ ] Section CSS in `{% stylesheet %}` tags (not `{% style %}`, not inline)
- [ ] No cross-file CSS dependencies
- [ ] Global utilities in `assets/` (not in section stylesheet tags)
- [ ] `image_tag` used for all images with appropriate `widths` and `sizes`
- [ ] Above-the-fold images: `loading: 'eager'`, hero has `fetchpriority: 'high'`
- [ ] Below-the-fold images: `loading: 'lazy'`
- [ ] LCP hero image preloaded with `preload_tag`
- [ ] Liquid loops don't contain sorting/transformation operations
- [ ] 25,001 pagination overflow handled with filters
- [ ] Lighthouse mobile score ≥ 60 (≥ 90 preferred)
- [ ] No `.liquid` extension on large asset files unnecessarily
- [ ] `es-module-shims` not loaded by theme (Shopify provides it)
