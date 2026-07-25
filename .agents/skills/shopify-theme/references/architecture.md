# Shopify Theme Architecture Reference

## Table of Contents
1. [Directory Structure](#directory-structure)
2. [Required Files](#required-files)
3. [Rendering Hierarchy](#rendering-hierarchy)
4. [theme.liquid Structure](#themeliquid-structure)
5. [Section Groups](#section-groups)
6. [JSON Template Schema](#json-template-schema)
7. [JSON Comment Persistence Rules](#json-comment-persistence-rules)
8. [Dynamic Assets (.liquid extension)](#dynamic-assets-liquid-extension)
9. [CLI Lifecycle](#cli-lifecycle)

---

## Directory Structure

```
assets/              CSS, JS, images, fonts — flat, no subdirectories
blocks/              Theme block files (*.liquid) — global, reusable
config/              settings_schema.json, settings_data.json, markets.json
layout/              theme.liquid (only required file) + alt layouts
locales/             Translation JSON (en.default.json, etc.)
sections/            Liquid section files + section group JSON files
snippets/            Reusable Liquid partials (invisible to editor)
templates/           Page templates (*.json preferred, *.liquid for special cases)
  customers/         Customer account templates (login, register, account, etc.)
  metaobject/        Metaobject-specific templates
```

**Hard rules:**
- No custom subdirectories beyond `templates/customers/` and `templates/metaobject/`.
  Any other nested folder is ignored or causes upload failure.
- `layout/theme.liquid` is the ONLY file required for a valid theme upload.
- A route-specific template (e.g., `product.json`) is required for the platform to render
  that resource type; the theme still uploads without it.

---

## Required Files

| File | Required for | Notes |
|---|---|---|
| `layout/theme.liquid` | Theme to upload | Only strictly required file |
| `templates/index.json` | Homepage to render | Highly recommended |
| `templates/product.json` | Product pages | Required for product route |
| `templates/collection.json` | Collection pages | Required for collection route |
| `config/settings_schema.json` | Theme settings in editor | First object must be `theme_info` |

---

## Rendering Hierarchy

```
Layout (layout/theme.liquid)
  └── Section Groups ({% sections 'header-group' %}, {% sections 'footer-group' %})
      └── Sections (via JSON template or section group JSON)
          └── Blocks (section blocks via for loop; theme/app blocks via content_for)
              └── Snippets ({% render 'snippet-name' %})
  └── Template (templates/*.json or templates/*.liquid)
      └── Sections (defined in JSON template)
```

**Data flow:**
- Global Liquid objects (`product`, `collection`, `cart`, `shop`, `customer`, etc.) are
  accessible everywhere.
- Section-scoped variables are isolated: they cannot leak into or receive from outside
  the section (except globals).
- Snippets do NOT inherit parent scope; pass variables explicitly:
  `{% render 'card', product: product, show_vendor: true %}`

---

## theme.liquid Structure

```liquid
<!DOCTYPE html>
<html lang="{{ request.locale.iso_code }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ page_title }}</title>

  {{ content_for_header }}  ← MANDATORY: Shopify injects scripts, tracking, analytics

  {{ 'theme.css' | asset_url | stylesheet_tag }}
</head>
<body class="{{ template | handleize }}-template">

  {% sections 'header-group' %}  ← renders the header section group

  <main id="MainContent" role="main" tabindex="-1">
    {{ content_for_layout }}  ← MANDATORY: template output renders here
  </main>

  {% sections 'footer-group' %}  ← renders the footer section group

  {{ 'theme.js' | asset_url | script_tag }}
</body>
</html>
```

**`{{ content_for_header }}`** — Must appear inside `<head>`. Shopify injects:
- GDPR scripts
- Web pixel tracking
- Performance hints
- Theme editor JavaScript API
- Preview bar (in development)

**`{{ content_for_layout }}`** — Must appear inside `<body>`. This is where the template
renders. Missing this = blank page body.

---

## Section Groups

Section groups are JSON files in `sections/` that make global areas (header, footer)
merchant-editable.

**Standard section groups:**
- `sections/header-group.json` — referenced in layout via `{% sections 'header-group' %}`
- `sections/footer-group.json` — referenced via `{% sections 'footer-group' %}`

**Section group JSON shape:**
```json
{
  "type": "header",
  "name": "Header group",
  "sections": {
    "announcement_bar": {
      "type": "announcement-bar",
      "settings": { "text": "Free shipping on orders over $50" }
    },
    "header": {
      "type": "header",
      "settings": {}
    }
  },
  "order": ["announcement_bar", "header"]
}
```

**Rules:**
- `type` must match the section group name (header, footer, or custom).
- Up to 25 sections per section group.
- Comments and trailing commas in section group JSON are NOT persisted by Shopify
  (stripped on save/API write).
- Contextual section groups (Markets, B2B) use unique group names and are referenced
  conditionally in the layout.

**Rendering in layout:**
```liquid
{% sections 'header-group' %}   ← matches filename sections/header-group.json
```

---

## JSON Template Schema

JSON templates are orchestration files. They define which sections appear on a page
and in what order. They must not contain HTML or Liquid logic.

**Valid top-level keys:**
| Key | Type | Description |
|---|---|---|
| `layout` | string | Layout file to use (e.g., `"theme"`). Omit to use default. |
| `wrapper` | string | HTML wrapper element for the template output (e.g., `"main"`) |
| `sections` | object | Map of section IDs → section configuration |
| `order` | array | Ordered array of section IDs determining render order |

**Section configuration object:**
```json
{
  "type": "section-filename-without-extension",
  "disabled": false,
  "settings": {
    "setting_id": "value"
  },
  "blocks": {
    "block_id_1": {
      "type": "block-type",
      "settings": { "text": "Hello" }
    }
  },
  "block_order": ["block_id_1"]
}
```

**Full product template example:**
```json
{
  "layout": "theme",
  "wrapper": "main",
  "sections": {
    "main_product": {
      "type": "main-product",
      "settings": { "show_vendor": true }
    },
    "recommendations": {
      "type": "product-recommendations",
      "settings": {}
    }
  },
  "order": ["main_product", "recommendations"]
}
```

---

## JSON Comment Persistence Rules

Shopify has non-standard JSON support for developer comments and trailing commas in
specific files. Understanding which files persist vs. strip them is critical.

| File | Comments persist? | Trailing commas persist? |
|---|---|---|
| `config/settings_schema.json` | ✅ Yes | ✅ Yes |
| `{% schema %}` tag in Liquid | ✅ Yes | ✅ Yes |
| `templates/*.json` | ❌ Stripped | ❌ Stripped |
| `sections/*.json` (section groups) | ❌ Stripped | ❌ Stripped |
| `config/settings_data.json` | ❌ Stripped | ❌ Stripped |
| `locales/*.json` | ❌ Stripped | ❌ Stripped |

**API note:** As of Admin API 2024-10, an autogenerated comment header is added to
non-persistent files. JSON5-compatible parsers are needed when programmatically
reading these files.

---

## Dynamic Assets (.liquid extension)

Files in `assets/` can be named with a `.liquid` extension (e.g., `theme.css.liquid`,
`global.js.liquid`) to make them processed by the Liquid engine.

**What you gain:** Access to `{{ settings.* }}` and Liquid filters.
**What you lose:** Access to resource-specific objects (`product`, `collection`, etc.)
— only global `settings` and Liquid filters are available.
**Performance cost:** Server-side processing on every request; use sparingly.

**Pattern:**
```liquid
/* In assets/theme.css.liquid */
:root {
  --color-primary: {{ settings.brand_color }};
  --font-heading: {{ settings.font_heading.family }}, sans-serif;
}
```

Reference via `asset_url` as normal: `{{ 'theme.css' | asset_url | stylesheet_tag }}`

---

## CLI Lifecycle

```bash
# Create new theme from scratch
shopify theme init my-theme --clone-url https://github.com/Shopify/dawn.git

# Pull existing theme from store
shopify theme pull --store my-store.myshopify.com

# Start local development server with hot-reload
shopify theme dev --store my-store.myshopify.com

# Push local changes to store (creates new unpublished theme)
shopify theme push --store my-store.myshopify.com

# Publish a theme (make it the live theme)
shopify theme publish --theme THEME_ID --store my-store.myshopify.com

# Run Theme Check linter
shopify theme check
```

**Theme Check** — the primary linter. Run before every push. Key checks:
- `ValidScopedCSSClass` — catches cross-file CSS dependencies
- Parser-blocking scripts
- Schema errors
- Deprecated API usage (`{% include %}`, deprecated setting types)
