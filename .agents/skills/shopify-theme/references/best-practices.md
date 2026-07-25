# Best Practices Reference

## Table of Contents
1. [WCAG Accessibility Standards](#wcag-accessibility-standards)
2. [Deprecations Table](#deprecations-table)
3. [Theme Check — Common Errors](#theme-check--common-errors)
4. [Shopify CLI Workflow](#shopify-cli-workflow)
5. [Code Organization Best Practices](#code-organization-best-practices)
6. [Security Considerations](#security-considerations)
7. [Theme Store Readiness Checklist](#theme-store-readiness-checklist)

---

## WCAG Accessibility Standards

Shopify themes must be accessible. Key requirements:

### Touch Targets
- **Minimum 44×44 pixels** for all interactive elements (buttons, links, checkboxes).
- Spacing between targets should also prevent accidental taps.

```css
.button {
  min-height: 44px;
  min-width: 44px;
  padding: 12px 24px;
}

.icon-button {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
}
```

### Color Contrast
- **Minimum 4.5:1** contrast ratio for normal text.
- **3:1** for large text (18px+ regular, 14px+ bold).
- Test with browser dev tools or WebAIM Contrast Checker.

```liquid
{# Don't rely on color alone to convey meaning #}
{# Add icons or labels alongside color indicators #}
<span class="badge" aria-label="Out of stock">
  <svg aria-hidden="true">...</svg>
  Out of stock
</span>
```

### Pinch-Zoom Required
- Never use `user-scalable=no` in the viewport meta tag.
- Never use `maximum-scale=1`.

```html
{# ✅ Correct — allows pinch zoom #}
<meta name="viewport" content="width=device-width, initial-scale=1.0">

{# ❌ Wrong — blocks accessibility #}
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
```

### Focus Order
- Logical tab order following visual flow.
- Never use `tabindex` values > 0 (breaks natural focus order).
- `tabindex="0"` makes an element focusable (OK); `tabindex="-1"` for JS-managed focus (OK).

```html
{# ✅ Allow skip to main content #}
<a href="#MainContent" class="skip-link">Skip to main content</a>

<main id="MainContent" role="main" tabindex="-1">
  {# tabindex="-1" allows JS to call .focus() after skip link click #}
</main>
```

### ARIA
- Use semantic HTML first; add ARIA only when HTML semantics are insufficient.
- Never use `role` to override native semantics (don't put `role="button"` on a `<div>` when `<button>` is available).
- Always pair `aria-expanded` with the controlled element's `id`:

```liquid
<button
  id="cart-toggle"
  aria-expanded="{{ cart_open }}"
  aria-controls="cart-drawer"
>
  Cart
</button>
<div id="cart-drawer" aria-labelledby="cart-toggle">
  {# Cart content #}
</div>
```

- Required ARIA for common patterns:
  - Navigation: `<nav aria-label="Main navigation">`
  - Search: `<form role="search">` or `<input type="search">`
  - Images: always provide `alt` text; decorative images use `alt=""`
  - Icon buttons: `aria-label="Close menu"` when no visible text
  - Loading states: `aria-live="polite"` for cart count updates

### Semantic HTML
- Use `<header>`, `<nav>`, `<main>`, `<footer>`, `<article>`, `<section>`, `<aside>`.
- Headings must form a logical hierarchy (h1 → h2 → h3; never skip levels).
- Only one `<h1>` per page (the primary page title).

```liquid
{# theme.liquid body structure #}
<body>
  {% sections 'header-group' %}        {# <header> element #}

  <main id="MainContent" role="main" tabindex="-1">
    {{ content_for_layout }}
  </main>

  {% sections 'footer-group' %}        {# <footer> element #}
</body>
```

---

## Deprecations Table

| Deprecated | Replacement | Notes |
|---|---|---|
| `{% include 'snippet' %}` | `{% render 'snippet' %}` | `{% include %}` shares parent scope; `{% render %}` is isolated |
| `templates` schema attribute | `enabled_on` / `disabled_on` | Old template restriction mechanism |
| `color_scheme` setting type | `color_palette` | Section-level color schemes deprecated |
| `color_scheme_group` setting type | `color_palette` | Group variant also deprecated |
| `font` setting type | `font_picker` | Old font setting |
| `snippet` setting type | Section with schema blocks | Move logic to section blocks |
| `.css.liquid` / `.js.liquid` in assets (heavy use) | Static assets with `asset_url` | Dynamic assets add server overhead; use for small token sets only |
| `checkout.liquid` layout | Shopify-managed checkout UI | Sunset; checkout customization now via Checkout UI Extensions |
| `product.selected_variant` | `product.selected_or_first_available_variant` | If the short form is deprecated; verify at shopify.dev |

---

## Theme Check — Common Errors

Theme Check is the official linter. Run `shopify theme check` before every push.

| Check name | What it catches | Fix |
|---|---|---|
| `ValidScopedCSSClass` | Cross-file CSS dependencies | Move CSS to the file where it's used, or to `assets/` |
| `TemplateLength` | Template files > 200 lines | Break into sections and snippets |
| `AssetUrlFilters` | Hardcoded asset paths | Replace with `asset_url`, `image_url` |
| `DeprecatedFilter` | Old Liquid filters | Check the error message for the replacement |
| `DeprecatedTag` | `{% include %}` usage | Replace with `{% render %}` |
| `MissingSchema` | Section missing `{% schema %}` | Add the schema tag |
| `RequiredLayoutThemeObject` | Missing `content_for_header` or `content_for_layout` | Add both to theme.liquid |
| `ParserBlockingScript` | Script tags without `defer`/`async` | Add `defer` attribute |
| `SchemaIncludesEmptyOrInvalidType` | Empty or invalid block type | Fix block type string |

---

## Shopify CLI Workflow

```bash
# Initialize a new theme
shopify theme init <name>                  # Starts from scratch
shopify theme init <name> --clone-url URL  # Clone from a GitHub URL (e.g., Dawn)

# Connect to a store
shopify auth login --store my-store.myshopify.com

# Pull an existing theme from a store
shopify theme pull --store my-store.myshopify.com
shopify theme pull --theme THEME_ID       # Specific theme

# Local development with hot-reload
shopify theme dev                          # Uses default store
shopify theme dev --store my-store.myshopify.com

# Push changes to store
shopify theme push                         # Creates new unpublished theme
shopify theme push --theme THEME_ID        # Push to existing theme
shopify theme push --live                  # Push to currently published theme

# List themes
shopify theme list

# Publish a theme
shopify theme publish --theme THEME_ID

# Run Theme Check
shopify theme check
shopify theme check --category performance

# Package theme for distribution
shopify theme package
```

**Never push directly to the live theme** in production unless explicitly confirmed
by the user. Always push to a development/preview theme first.

---

## Code Organization Best Practices

### Naming Conventions

- **Section files:** kebab-case matching their purpose (`featured-collection.liquid`,
  `hero-banner.liquid`, `announcement-bar.liquid`)
- **Block files:** kebab-case (`/blocks/slide.liquid`, `/blocks/icon-with-text.liquid`)
- **Private blocks:** underscore prefix (`/blocks/_slideshow-controls.liquid`)
- **Snippets:** kebab-case, often prefixed with type (`card-product.liquid`, `icon-cart.liquid`)
- **Setting IDs:** snake_case (`show_vendor`, `columns_desktop`, `button_label`)

### Section Organization

Keep sections focused. A section should represent one distinct UI concept
(hero, product grid, testimonials, newsletter). If a section's schema has > 30 settings,
consider splitting it.

### JSON Template Hygiene

- Use descriptive section IDs in JSON templates: `"main_product_gallery"` not `"section_1"`
- Match section IDs to their purpose for clarity when debugging
- Keep `order` array in sync with `sections` object keys

### Asset Organization

```
assets/
  theme.css           ← Global styles (not subsetted)
  theme.js            ← Global JS loaded on every page
  component-*.css     ← Component-specific CSS for optional loading
  component-*.js      ← Component-specific JS for lazy loading
```

---

## Security Considerations

### Output Escaping
Liquid auto-escapes HTML output via `{{ }}`. Be careful with:
```liquid
{# This is UNESCAPED — only use for trusted HTML you control #}
{{ product.description }}   ← may contain HTML from merchant-entered content (trusted)

{# URL-encode user-provided values in URLs #}
<a href="{{ request.path | url_encode }}">Current page</a>

{# When using html setting type — HTML tags are sanitized by Shopify #}
{{ section.settings.custom_html }}   ← Shopify strips <script>, <html>, <head>, <body>
```

### Form Security
Always include `{% form %}` tag for forms — it automatically includes CSRF tokens:
```liquid
{% form 'product', product %}
  {{ form.errors | default_errors }}
  <button type="submit">Add to cart</button>
{% endform %}
```

### User Input
Never use raw user input in Liquid without filtering:
```liquid
{# ❌ Don't use raw search query in HTML #}
{{ request.params.q }}

{# ✅ Liquid auto-escapes {{ }} output — this is safe #}
<p>Search results for: {{ request.params.q }}</p>
```

---

## Theme Store Readiness Checklist

For themes intended for the Shopify Theme Store:

### Performance
- [ ] Lighthouse mobile score ≥ 60 (target ≥ 90)
- [ ] JS bundle ≤ 16 KB minified
- [ ] All images have `alt` text and use `image_tag` with srcset
- [ ] No parser-blocking scripts

### Accessibility
- [ ] Touch targets ≥ 44×44px
- [ ] Color contrast ≥ 4.5:1 for body text
- [ ] Pinch zoom NOT disabled in viewport meta
- [ ] Logical heading hierarchy
- [ ] Skip to main content link
- [ ] All interactive elements keyboard accessible
- [ ] ARIA used correctly and appropriately

### Code Quality
- [ ] No `{% include %}` — all converted to `{% render %}`
- [ ] No deprecated setting types (color_scheme, font, snippet)
- [ ] `enabled_on` / `disabled_on` used instead of deprecated `templates` attribute
- [ ] Theme Check passes with no errors
- [ ] All assets referenced via `asset_url` or `image_url`
- [ ] CSS subsetted correctly (no cross-file dependencies)
- [ ] All JS wrapped in IIFE

### Theme Editor
- [ ] All interactive components re-init on `shopify:section:load`
- [ ] Selected blocks scroll into view (`shopify:block:select`)
- [ ] `{{ block.shopify_attributes }}` on all block elements
- [ ] Autoplay/animation paused in design mode
- [ ] Section presets defined for all merchant-addable sections

### Content
- [ ] `theme_info` is the first object in `settings_schema.json`
- [ ] All required `theme_info` keys present
- [ ] Translations in `locales/en.default.json`
- [ ] `layout/theme.liquid` contains `content_for_header` and `content_for_layout`
