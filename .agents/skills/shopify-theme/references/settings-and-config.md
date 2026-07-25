# Settings & Config Reference

## Table of Contents
1. [Settings Architecture](#settings-architecture)
2. [settings_schema.json](#settings_schemajson)
3. [theme_info Category](#theme_info-category)
4. [Global Theme Settings Patterns](#global-theme-settings-patterns)
5. [Color Palette](#color-palette)
6. [Font Picker](#font-picker)
7. [Dynamic Sources](#dynamic-sources)
8. [settings_data.json](#settings_datajson)
9. [markets.json](#marketsjson)
10. [Deprecated Settings](#deprecated-settings)

---

## Settings Architecture

Settings flow from three sources, accessed via different Liquid objects:

| Liquid object | Settings source | Example |
|---|---|---|
| `settings` | `config/settings_schema.json` (global) | `{{ settings.brand_color }}` |
| `section.settings` | `{% schema %}` in section file | `{{ section.settings.heading }}` |
| `block.settings` | Block definition in `{% schema %}` | `{{ block.settings.text }}` |

**Global settings are also accessible in `.liquid` asset files** (e.g., `theme.css.liquid`),
allowing brand tokens to flow into CSS automatically. Resource objects like `product`
are NOT available in asset Liquid files — only `settings` and Liquid filters.

---

## settings_schema.json

Located at `config/settings_schema.json`. Defines the "Theme settings" panel in the
merchant's theme editor.

**Critical rules:**
- The FIRST category object must be `theme_info` (see below).
- Maximum 100 global theme settings (across all categories).
- Maximum 100 dynamic sources per general theme settings context.
- Comments (`//`, `/* */`) and trailing commas are persisted in this file.
- Setting IDs must be unique across the entire `settings_schema.json`.

**Structure:**
```json
[
  {
    /* Required first category */
    "name": "theme_info",
    "theme_name": "My Theme",
    "theme_author": "Acme Inc",
    "theme_version": "1.0.0",
    "theme_documentation_url": "https://docs.example.com",
    "theme_support_url": "https://support.example.com"
  },
  {
    "name": "Colors",
    "settings": [
      {
        "type": "color_palette",
        "id": "colors",
        "label": "Brand palette",
        "default": {
          "primary": "#000000",
          "secondary": "#FFFFFF",
          "accent": "#E63946"
        }
      },
      {
        "type": "color",
        "id": "body_text",
        "label": "Body text",
        "default": "#121212"
      }
    ]
  },
  {
    "name": "Typography",
    "settings": [
      {
        "type": "font_picker",
        "id": "body_font",
        "label": "Body font",
        "default": "sans-serif"
      },
      {
        "type": "font_picker",
        "id": "heading_font",
        "label": "Heading font",
        "default": "sans-serif"
      }
    ]
  },
  {
    "name": "Social media",
    "settings": [
      {
        "type": "header",
        "content": "Social media accounts"
      },
      {
        "type": "url",
        "id": "social_instagram_link",
        "label": "Instagram"
      },
      {
        "type": "url",
        "id": "social_twitter_link",
        "label": "X (Twitter)"
      }
    ]
  }
]
```

---

## theme_info Category

The first object in `settings_schema.json` must be the `theme_info` category.
It is not a regular settings group — it has specific required keys and no `settings` array.

**Required keys:**
| Key | Description |
|---|---|
| `name` | Must be the string `"theme_info"` |
| `theme_name` | Human-readable theme name |
| `theme_author` | Theme author name |
| `theme_version` | Semantic version string (e.g., `"1.0.0"`) |
| `theme_documentation_url` | URL to theme documentation |
| `theme_support_email` OR `theme_support_url` | One of these two (XOR — not both) |

**Valid theme_info:**
```json
{
  "name": "theme_info",
  "theme_name": "Sunrise",
  "theme_author": "Acme Studio",
  "theme_version": "2.0.0",
  "theme_documentation_url": "https://docs.acme.com/sunrise",
  "theme_support_url": "https://support.acme.com"
}
```

---

## Global Theme Settings Patterns

**Accessing in Liquid:**
```liquid
{{ settings.body_text }}           ← color value
{{ settings.heading_font.family }}  ← font object property
{{ settings.social_instagram_link }} ← URL string
```

**Accessing in CSS via `.liquid` asset:**
```css
/* assets/theme.css.liquid */
:root {
  --color-body-text: {{ settings.body_text }};
  --font-body-family: {{ settings.body_font.family }}, sans-serif;
  --font-body-weight: {{ settings.body_font.weight }};
}
```

**Checking if optional settings are set:**
```liquid
{% if settings.social_instagram_link != blank %}
  <a href="{{ settings.social_instagram_link }}">Instagram</a>
{% endif %}
```

---

## Color Palette

The `color_palette` setting is the modern replacement for the deprecated `color_scheme`.
It creates a centralized brand color system.

**Constraints:**
- Only ONE `color_palette` per theme, defined in `settings_schema.json`.
- Between **2 and 20** colors in the `default` object.
- Hex values only (3 or 6 digits); **no alpha channel** (no 8-digit hex, no rgba).
- Merchant deletes trigger required replacement; Shopify stores the deleted ID as
  a reference to the replacement, preventing broken references.

**Accessing palette colors in Liquid:**
```liquid
{{ settings.colors.primary }}   ← hex value like "#000000"
{{ settings.colors.accent }}    ← hex value like "#E63946"
```

**Cross-setting references** — set a color/color_background setting's default to a
palette color so updates propagate automatically:
```json
{
  "type": "color",
  "id": "button_background",
  "label": "Button background",
  "default": "{{ settings.colors.primary }}"
}
```

**In color_background (supports gradients):**
```json
{
  "type": "color_background",
  "id": "hero_background",
  "label": "Hero background",
  "default": "linear-gradient({{ settings.colors.primary }}, {{ settings.colors.secondary }})"
}
```

---

## Font Picker

The `font_picker` setting provides access to Shopify's font library (includes system
fonts and a curated set of Google Fonts).

**Setting definition:**
```json
{
  "type": "font_picker",
  "id": "body_font",
  "label": "Body font",
  "default": "sans-serif"
}
```

**Using the font in CSS (via Liquid asset):**
```liquid
{# In assets/theme.css.liquid #}
{{ settings.body_font | font_face }}

:root {
  --font-body-family: {{ settings.body_font.family }}, sans-serif;
  --font-body-style: {{ settings.body_font.style }};
  --font-body-weight: {{ settings.body_font.weight }};
}

{# Generate the bold variant #}
{% assign body_font_bold = settings.body_font | font_modify: 'weight', 'bold' %}
{% if body_font_bold %}
  {{ body_font_bold | font_face }}
{% endif %}
```

**Font filter reference:**
| Filter | Returns |
|---|---|
| `font_face` | `@font-face` CSS declaration |
| `font_url` | CDN URL for the font file (for manual `@font-face`) |
| `font_modify: 'weight', 'bold'` | Bold variant of the font family |

**Font hosting distinction:**
- Fonts in `assets/` (theme package) → use `asset_url` filter
- Fonts uploaded via Shopify Admin (Content > Files) → use `file_url` filter
  (using `asset_url` for admin-uploaded fonts causes file corruption)

**System fonts performance advantage:** System fonts (san-serif, serif, monospace,
system-ui) require zero download. Always offer system font options alongside custom fonts.

---

## Dynamic Sources

Settings can connect to resource attributes or metafields for contextual content.

**Compatible settings and metafield types:**
| Setting type | Compatible metafield types |
|---|---|
| `image_picker` | `file_reference` |
| `product` | `product_reference` |
| `collection` | `collection_reference` |
| `text` | `single_line_text_field`, `number_integer`, `date`, `rating`, `money` |
| `richtext` | `single_line_text_field`, `multi_line_text_field`, `rich_text_field`, `number_integer`, `date`, `link` |
| `inline_richtext` | `single_line_text_field`, `number_integer`, `date`, `rating`, `money`, `link` |
| `video` | `file_reference` (video) |
| `url` | `url` |
| `color` | `color` |
| `metaobject` | `metaobject_reference` (must match schema `metaobject_type`) |
| `metaobject_list` | `list.metaobject_reference` |

**Platform limits for dynamic sources:**
| Context | Limit |
|---|---|
| JSON template | 100 |
| Section group | 100 |
| General theme settings | 100 |
| Static section | 50 |
| Single setting | 50 |

---

## settings_data.json

Located at `config/settings_data.json`. Stores the merchant's current settings values.
**Do not hand-edit this file** — it is managed by the theme editor.

- Comments and trailing commas are **stripped** (not persisted).
- Shopify adds an autogenerated comment header (Admin API 2024-10+).
- In code: this file feeds the `settings` global object.

---

## markets.json

Located at `config/markets.json`. Used for contextual settings per Shopify Market
(e.g., different headers/footers for different regions).

Structure varies by Markets setup. Verify the current schema at shopify.dev when
working with multi-market stores.

---

## Deprecated Settings

Never use these — they are no longer supported:

| Deprecated | Replace with | Notes |
|---|---|---|
| `color_scheme` | `color_palette` | Section-level color schemes replaced by palette |
| `color_scheme_group` | `color_palette` | Group variant also deprecated |
| `font` | `font_picker` | Old font setting |
| `snippet` (setting type) | Section with schema | Move logic to section blocks |
| `templates` (schema attr) | `enabled_on` / `disabled_on` | Old template restriction method |

**Deprecated Liquid tags:**
| Deprecated | Replace with |
|---|---|
| `{% include 'snippet' %}` | `{% render 'snippet' %}` |
