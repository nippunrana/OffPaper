# Sections & Section Groups Reference

## Table of Contents
1. [Section File Structure](#section-file-structure)
2. [Schema Tag — Complete Reference](#schema-tag--complete-reference)
3. [Setting Schema Attributes](#setting-schema-attributes)
4. [Block Schema](#block-schema)
5. [Presets vs. Default](#presets-vs-default)
6. [Rendering Methods](#rendering-methods)
7. [Section Scope & Isolation](#section-scope--isolation)
8. [Section Groups](#section-groups)
9. [Section Rendering API](#section-rendering-api)
10. [Implementation Checklist](#implementation-checklist)

---

## Section File Structure

All section files live in `sections/` and have `.liquid` extension.

A section file has three parts:

```liquid
{# 1. MAIN CONTENT — HTML + Liquid logic #}
<div class="my-section">
  <h2>{{ section.settings.heading }}</h2>

  {% for block in section.blocks %}
    <div {{ block.shopify_attributes }}>
      {% case block.type %}
        {% when 'text' %}
          <p>{{ block.settings.text }}</p>
        {% when 'image' %}
          {{ block.settings.image | image_url: width: 800 | image_tag: loading: 'lazy' }}
      {% endcase %}
    </div>
  {% endfor %}
</div>

{# 2. SECTION-SCOPED ASSETS #}
{% stylesheet %}
  .my-section { padding: 2rem; }
{% endstylesheet %}

{% javascript %}
  (function() {
    // Section-scoped JS — wrapped in IIFE for safety
  })();
{% endjavascript %}

{# 3. SCHEMA — Pure static JSON, no Liquid inside #}
{% schema %}
{
  "name": "My Section",
  "tag": "section",
  "class": "my-section-wrapper",
  "limit": 1,
  "settings": [
    {
      "type": "text",
      "id": "heading",
      "label": "Heading",
      "default": "Welcome"
    }
  ],
  "blocks": [
    {
      "type": "text",
      "name": "Text",
      "settings": [
        { "type": "textarea", "id": "text", "label": "Text" }
      ]
    },
    {
      "type": "image",
      "name": "Image",
      "limit": 3,
      "settings": [
        { "type": "image_picker", "id": "image", "label": "Image" }
      ]
    }
  ],
  "max_blocks": 10,
  "presets": [
    {
      "name": "My Section",
      "category": "Custom"
    }
  ],
  "enabled_on": {
    "templates": ["*"],
    "groups": ["header", "footer"]
  }
}
{% endschema %}
```

---

## Schema Tag — Complete Reference

**Rules that cannot be broken:**
- Exactly **one** `{% schema %}` tag per file.
- Never nested inside `{% if %}`, `{% for %}`, or other Liquid tags.
- Must contain **valid static JSON only** — no Liquid variables, filters, or logic.
- Comments (`//` or `/* */`) are allowed inside `{% schema %}` and are persisted.
- Trailing commas are allowed inside `{% schema %}` and are persisted.

**All valid schema keys:**

| Key | Type | Description |
|---|---|---|
| `name` | string | Section display name in theme editor sidebar |
| `tag` | string | HTML wrapper element: `article`, `aside`, `div`, `footer`, `header`, `section` |
| `class` | string | Additional CSS class appended to `shopify-section` wrapper |
| `limit` | integer | Max instances per template; valid values: `1` or `2` |
| `settings` | array | Section-level input settings (see Setting Schema Attributes) |
| `blocks` | array | Block type definitions (see Block Schema) |
| `max_blocks` | integer | Maximum blocks a merchant can add (up to platform max of 50) |
| `presets` | array | Starter configs; required for "Add Section" picker visibility |
| `default` | object | Starting config for statically rendered sections |
| `locales` | object | Self-contained translation strings for portability |
| `enabled_on` | object | Templates/groups where section can appear |
| `disabled_on` | object | Templates/groups where section cannot appear |

**`enabled_on` / `disabled_on` structure:**
```json
"enabled_on": {
  "templates": ["product", "collection"],
  "groups": ["header"]
}
```
Use `"templates": ["*"]` as a wildcard for all templates.
`enabled_on` and `disabled_on` are **mutually exclusive** — never use both.

---

## Setting Schema Attributes

All input settings require `type`, `id`, and `label`. Optional: `info`, `default`, `placeholder`, `visible_if`.

### Basic Input Types

| type | Returns | Notes |
|---|---|---|
| `checkbox` | boolean | `default: false` if omitted |
| `number` | integer or nil | For non-range numeric inputs |
| `range` | number | Requires `min`, `max`, `step`; `default` is mandatory; add `unit` for UX |
| `radio` | string | Fixed options; small visible list |
| `select` | string | Dropdown; use `options` array |
| `text` | string or empty object | Short single-line string |
| `textarea` | string or empty object | Multi-line text |

### Specialized Input Types

**Resource pickers (return full Liquid objects):**
| type | Returns |
|---|---|
| `article` | Article object (or blank if deleted) |
| `blog` | Blog object |
| `collection` | Collection object |
| `page` | Page object |
| `product` | Product object |

**List pickers (return array, max 50 items):**
| type | Notes |
|---|---|
| `article_list` | Multiple article selection |
| `collection_list` | Multiple collection selection |
| `product_list` | Multiple product selection |
| `metaobject_list` | Multiple metaobject selection |

**Visual inputs:**
| type | Returns | Notes |
|---|---|---|
| `color` | CSS color string | Individual color picker |
| `color_background` | CSS color/gradient string | Supports gradients; can reference `color_palette` |
| `color_palette` | Palette reference | Global only (in `settings_schema.json`); 2–20 colors; replaces deprecated `color_scheme` |
| `font_picker` | Font object | Access via `font_face`, `font_url`, `font_modify` filters |
| `image_picker` | Image object | Use `image_url` filter for display |
| `video` | Video object | For Shopify-hosted video files |
| `video_url` | URL string | YouTube or Vimeo; requires `accept: ['youtube', 'vimeo']` |

**Content inputs:**
| type | Returns | Notes |
|---|---|---|
| `richtext` | HTML string | Rendered as rich text |
| `inline_richtext` | HTML string | Inline rich text (no block elements) |
| `html` | HTML string | Shopify strips `<html>/<head>/<body>` tags |
| `liquid` | Liquid string | Max 50KB; cannot use `{% section %}`, `{% schema %}`, `content_for_header` |
| `url` | URL string | Internal or external URL |
| `link_list` | Linklist object | Navigation menu selection |
| `metaobject` | Metaobject entry | Requires matching `metaobject_type` |
| `text_alignment` | string | `left`, `center`, `right` |

### Sidebar Settings (non-input, organizational)

| type | Purpose |
|---|---|
| `header` | Groups settings under a label; optional `info` attribute |
| `paragraph` | Descriptive text; uses `content` attribute (no `info`) |

### Setting Attributes

```json
{
  "type": "range",
  "id": "padding_top",
  "label": "Top padding",
  "min": 0,
  "max": 100,
  "step": 4,
  "unit": "px",
  "default": 36,
  "info": "Applies to desktop and mobile",
  "visible_if": "{another_setting_id} == true"
}
```

**`visible_if`** — conditionally show/hide a setting based on another setting's value.
Cannot access resolved dynamic data values; only evaluates static setting values.
Not supported on all types: list-based pickers (`product_list`, etc.) don't support it.

**Empty state returns:**
- `article`, `blog`, `collection`, `page`, `product` → returns `blank` if unset
- `number`, `url`, `image_picker`, `video`, `video_url` → returns `nil` if unset
- `text`, `textarea`, `html`, `liquid` → returns empty object/string if unset
- Always validate: `{% if section.settings.product != blank %}`

**Deprecated settings (never use):**
| Deprecated | Replace with |
|---|---|
| `color_scheme` / `color_scheme_group` | `color_palette` |
| `font` | `font_picker` |
| `snippet` | Move to section |

---

## Block Schema

Block definitions go inside the section schema's `blocks` array.

```json
"blocks": [
  {
    "type": "slide",
    "name": "Slide",
    "limit": 10,
    "settings": [
      { "type": "image_picker", "id": "image", "label": "Slide image" },
      { "type": "text", "id": "heading", "label": "Heading" },
      { "type": "url", "id": "button_link", "label": "Button link" }
    ]
  },
  { "type": "@app" },
  { "type": "@theme" }
]
```

**Block dynamic titles** — The theme editor auto-labels blocks using the first available
setting ID from this priority list:
1. `heading`
2. `title`
3. `text`

Name settings accordingly so the sidebar shows meaningful labels, not generic "Slide 1".

**`@app` block rules:**
- Never add `limit` to `@app` entries — causes schema validation error.
- Sections with `@app` blocks may contain only one resource setting of each type
  (one `product`, one `collection`) to prevent "autofill ambiguity".
- `@app` blocks not supported in statically rendered sections.

**Section blocks vs. `@theme` blocks — mutual exclusivity:**
You cannot define local section block types (e.g., `"type": "slide"`) AND include
`{"type": "@theme"}` in the same `blocks` array. Choose one or the other.

---

## Presets vs. Default

| | `presets` | `default` |
|---|---|---|
| **Use for** | Dynamic sections (JSON template / Section Group) | Statically rendered sections |
| **Effect** | Makes section appear in "Add Section" picker | Sets initial settings when hard-coded |
| **Required?** | Yes for picker visibility; without it, section is "hidden" | Optional |

**Preset structure:**
```json
"presets": [
  {
    "name": "Featured Collection",
    "category": "Collection",
    "settings": {
      "title": "Our Products",
      "columns_desktop": 4
    },
    "blocks": [
      { "type": "slide", "settings": { "heading": "Slide 1" } }
    ]
  }
]
```

Presets without a `category` appear at the top of the "Add Section" picker.
Presets within the same category are sorted alphabetically.

**Caution:** Sections with presets should NEVER be statically rendered (`{% section %}`).
Presets are designed for dynamic insertion only.

---

## Rendering Methods

### 1. Dynamic (preferred) — via JSON template
```json
{
  "sections": {
    "hero": { "type": "hero-banner", "settings": {} }
  },
  "order": ["hero"]
}
```
Each section instance has its own unique settings. Merchants can reorder/remove.
Supports App Blocks. Max 25 sections per template.

### 2. Static — via `{% section %}` tag
```liquid
{% section 'announcement-bar' %}
```
**Single global settings instance across the entire theme.**
A setting change on one page affects all pages using the same static section.
Cannot be reordered or removed by merchants. Does NOT support App Blocks.
Reserve for elements that must appear identically everywhere (e.g., a fixed header
that absolutely cannot be moved, though section groups are better even for this).

### 3. Section Rendering API — via AJAX
```javascript
fetch('/sections/product-recommendations?section_id=recommendations')
  .then(r => r.text())
  .then(html => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    // Extract <style data-section-stylesheet> and inject it too
    const styles = doc.querySelector('[data-section-stylesheet]');
    if (styles) document.head.appendChild(styles.cloneNode(true));
    // Then inject the content
    document.getElementById('recommendations').innerHTML = doc.querySelector('.section').innerHTML;
  });
```
When injecting only part of the response, manually extract and inject the
`<style data-section-stylesheet>` element or the section's CSS won't apply.

---

## Section Scope & Isolation

**What sections CAN access:**
- All global Liquid objects: `product`, `collection`, `cart`, `shop`, `customer`,
  `template`, `request`, `settings`, routes, etc.
- `section.settings` — their own schema settings
- `section.blocks` — their configured blocks

**What sections CANNOT access:**
- Variables defined in theme.liquid, templates, or other sections
- Variables from a parent context (sections are isolated)

**The exception:** Snippets rendered inside a section via `{% render %}` DO have
access to the `section` and `block` objects of the parent section.

---

## Section Groups

See `references/architecture.md` for the full section group JSON structure.

Key rules:
- Section groups stored in `sections/` as JSON files (e.g., `header-group.json`)
- Referenced in `layout/theme.liquid` via `{% sections 'header-group' %}`
- Max 25 sections per section group
- App blocks require sections to be in JSON templates or section groups (not static)

---

## Section Rendering API

Use for AJAX partial updates (product recommendations, faceted filtering, infinite scroll):

```
GET /sections/{section-filename}?section_id={id}&{query-params}
```

Response includes the full section HTML wrapped in:
```html
<div id="shopify-section-{id}" class="shopify-section">
  <!-- section HTML -->
  <style data-section-stylesheet>/* section CSS */</style>
</div>
```

---

## Implementation Checklist

Before marking a section complete:

- [ ] File located in `sections/` directory
- [ ] Exactly one `{% schema %}` tag; pure static JSON inside
- [ ] All setting IDs unique within the section
- [ ] All block types and names unique within the blocks array
- [ ] `{{ block.shopify_attributes }}` on outermost HTML element of each block iteration
- [ ] `presets` defined if section should appear in "Add Section" picker
- [ ] `enabled_on` OR `disabled_on` (not both)
- [ ] Section-scoped CSS in `{% stylesheet %}`, JS in `{% javascript %}`
- [ ] No cross-file CSS dependencies (CSS only references classes defined in this file)
- [ ] `{% include %}` replaced with `{% render %}`
- [ ] Logic branches on `block.type`, never on `block.id`
- [ ] Empty state checks for resource pickers: `{% if section.settings.product != blank %}`
