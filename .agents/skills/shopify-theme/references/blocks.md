# Blocks Reference

## Table of Contents
1. [Block Type Comparison](#block-type-comparison)
2. [Section Blocks](#section-blocks)
3. [Theme Blocks](#theme-blocks)
4. [App Blocks](#app-blocks)
5. [Static Blocks](#static-blocks)
6. [Wrapper Sections (_blocks.liquid & apps.liquid)](#wrapper-sections)
7. [Nesting Theme Blocks](#nesting-theme-blocks)
8. [Block Decision Guide](#block-decision-guide)

---

## Block Type Comparison

| Dimension | Section Blocks | Theme Blocks | App Blocks |
|---|---|---|---|
| **Storage** | In section schema `blocks` array | `/blocks/*.liquid` | External app extension |
| **Scope** | Local to parent section only | Global — reusable across sections | Reusable where supported |
| **Nesting** | No (single level only) | Yes (supports deep hierarchy) | Yes (in supported sections) |
| **Rendered by** | `{% for block in section.blocks %}` | `{% content_for 'blocks' %}` | `{% content_for 'blocks' %}` |
| **Merchant visibility** | High — add/reorder within section | High — add/reorder globally | High — from app |
| **Coexist with @theme** | ❌ No (mutually exclusive) | ✅ Yes | ✅ Yes |
| **In statically rendered sections** | ✅ Yes | ✅ Yes | ❌ No |

---

## Section Blocks

Section blocks are defined locally inside a section's `{% schema %}`. They are
ideal for section-specific, non-reusable content modules.

**Constraints:**
- Cannot be used alongside `@theme` type in the same section (mutually exclusive).
- No nesting — flat hierarchy only.
- Local to the defining section; cannot be shared across sections.
- Max 50 blocks per section (static blocks excluded from this count).
- Never rely on `block.id` in logic — it changes. Use `block.type`.

**Rendering pattern:**
```liquid
{% for block in section.blocks %}
  <div class="block block--{{ block.type }}" {{ block.shopify_attributes }}>
    {% case block.type %}
      {% when 'heading' %}
        <h2>{{ block.settings.heading }}</h2>
      {% when 'image' %}
        {% if block.settings.image != blank %}
          {{ block.settings.image | image_url: width: 800 | image_tag: loading: 'lazy' }}
        {% endif %}
      {% when 'text' %}
        <p>{{ block.settings.text }}</p>
      {% when '@app' %}
        {% render block %}
    {% endcase %}
  </div>
{% endfor %}
```

**`{{ block.shopify_attributes }}`** — mandatory on the outermost HTML element of each
block iteration. This is what makes the theme editor's "click to edit" work.

**Schema definition:**
```json
"blocks": [
  {
    "type": "heading",
    "name": "Heading",
    "limit": 1,
    "settings": [
      {
        "type": "text",
        "id": "heading",
        "label": "Heading",
        "default": "Section Heading"
      }
    ]
  }
]
```

---

## Theme Blocks

Theme blocks are independent Liquid files in the `/blocks/` directory. They are
globally reusable across any section that accepts `@theme` blocks.

**When to use:** Repeated UI components (buttons, headings, icon+text combinations)
that appear in multiple sections across the theme.

**File naming:** `blocks/{block-name}.liquid`
**Private blocks:** Prefix with `_` (e.g., `blocks/_slideshow-controls.liquid`) to
hide from the "Add Block" picker; they can only be added as static blocks.

**File structure:**
```liquid
{# blocks/slide.liquid #}
<div class="slide" {{ block.shopify_attributes }}>
  {% if block.settings.image != blank %}
    {{ block.settings.image | image_url: width: 1200 | image_tag:
       loading: 'lazy',
       sizes: '100vw',
       widths: '375, 550, 750, 1100, 1500'
    }}
  {% endif %}
  <div class="slide__content">
    <h3>{{ block.settings.heading }}</h3>
    {% if block.settings.link != blank %}
      <a href="{{ block.settings.link }}">{{ block.settings.button_label }}</a>
    {% endif %}
  </div>

  {# Nesting: allows child blocks to be added inside this block #}
  {% content_for 'blocks' %}
</div>

{% schema %}
{
  "name": "Slide",
  "settings": [
    { "type": "image_picker", "id": "image", "label": "Slide image" },
    { "type": "text", "id": "heading", "label": "Heading" },
    { "type": "url", "id": "link", "label": "Link" },
    { "type": "text", "id": "button_label", "label": "Button label", "default": "Shop now" }
  ],
  "blocks": [
    { "type": "@theme" },
    { "type": "@app" }
  ]
}
{% endschema %}
```

**Enabling theme blocks in a section** — add `@theme` to the section's `blocks`:
```json
"blocks": [
  { "type": "@theme" }
]
```

**Rendering in a section** (required when `@theme` or `@app` blocks are used):
```liquid
{% content_for 'blocks' %}
```

**Key differences from snippets:**
- Theme blocks expose settings in the theme editor; snippets don't.
- Theme blocks can be added/reordered by merchants; snippets are fixed.
- Theme blocks do NOT receive Liquid variables from parent code (unlike snippets).
  They rely on their own schema settings and global objects.
- Use `closest.<resource>` to access contextual data in theme blocks (see below).

**`closest` object** — theme blocks can access the nearest ancestor resource:
```liquid
{# Access the parent product in a product page context #}
{{ block.closest.product.title }}
{{ block.closest.collection.handle }}
```
The exact API spelling for `closest` — verify at shopify.dev before using.

---

## App Blocks

App blocks allow third-party apps to inject content into sections without modifying
theme code.

**To support app blocks in a section, add to schema `blocks`:**
```json
{ "type": "@app" }
```
Never add `limit` to `@app` entries — causes a schema validation error.

**Resource setting constraint:** Sections supporting `@app` can include only one
resource setting of each type (one `product`, one `collection`). Multiple of the
same type creates "autofill ambiguity" that breaks app content targeting.

**App blocks are NOT supported in:**
- Statically rendered sections (`{% section %}` tag)
- Sections that only have local section block types (not `@app` in schema)

---

## Static Blocks

Static blocks are hardcoded in the theme code and cannot be deleted by merchants
(they can be hidden but not removed). Used for required UI elements like slideshow
controls that must always exist.

**Static block syntax in a section or block file:**
```liquid
{% content_for 'block', type: 'slideshow-controls', id: 'static-controls-123' %}
```

**Rules:**
- The `id` must be a static string (not Liquid) and unique within the parent.
- Static blocks do NOT count toward the section's 50-block limit.
- The `type` must match a block defined in the schema.

---

## Wrapper Sections

When merchants add blocks directly to a template (outside existing sections),
Shopify wraps them in a "wrapper section." Provide these in `sections/` to control
the container's appearance.

### Priority order (Shopify searches in this order)

**For top-level App Blocks:**
1. `sections/apps.liquid` (if present)
2. `sections/_blocks.liquid` (if apps.liquid absent)
3. Platform default

**For AI-generated theme blocks (Sidekick):**
1. `sections/_blocks.liquid` (if present)
2. Platform default
(`apps.liquid` is NOT used for AI-generated theme blocks)

### `_blocks.liquid` requirements

Must satisfy all four criteria or the section is invalid:

1. **Block types:** Must define both `@theme` and `@app` in schema `blocks`
2. **Presets:** Must include at least one preset
3. **Rendering tag:** Must contain `{% content_for 'blocks' %}`
4. **Template restriction:** Must NOT define `templates`, `enabled_on`, or `disabled_on`

```liquid
{# sections/_blocks.liquid #}
<div class="blocks-wrapper">
  {% content_for 'blocks' %}
</div>

{% schema %}
{
  "name": "Blocks wrapper",
  "settings": [
    { "type": "select", "id": "width", "label": "Width",
      "options": [
        { "value": "narrow", "label": "Narrow" },
        { "value": "full", "label": "Full width" }
      ],
      "default": "narrow"
    }
  ],
  "blocks": [
    { "type": "@theme" },
    { "type": "@app" }
  ],
  "presets": [
    { "name": "Blocks", "category": "Layout" }
  ]
}
{% endschema %}
```

### `apps.liquid` requirements

1. Must support `@app` block type
2. Must include a preset
3. Must contain `{% content_for 'blocks' %}`
4. Must NOT define `templates`/`enabled_on`/`disabled_on`

**Manual rendering prohibition:** Both `_blocks.liquid` and `apps.liquid` are
system-level wrappers. They CANNOT be rendered manually with `{% section 'apps' %}`
and will NOT appear in the "Add Section" menu. They are invoked automatically by
the platform when needed.

---

## Nesting Theme Blocks

A theme block becomes a container when it includes `{% content_for 'blocks' %}`.
This enables hierarchical layouts where merchants can add blocks inside other blocks.

**Example: Slideshow with nested slide blocks:**
```
sections/slideshow.liquid  (supports @theme)
  └── blocks/slide.liquid  (supports @theme, @app — is a container)
      └── blocks/heading.liquid
      └── blocks/button.liquid
      └── blocks/video.liquid
```

```liquid
{# sections/slideshow.liquid #}
<div class="slideshow">
  {% content_for 'blocks' %}
</div>

{% schema %}
{ "name": "Slideshow", "blocks": [{ "type": "@theme" }], "presets": [{ "name": "Slideshow" }] }
{% endschema %}
```

```liquid
{# blocks/slide.liquid — acts as a container for child blocks #}
<div class="slide" {{ block.shopify_attributes }}>
  {% content_for 'blocks' %}
</div>

{% schema %}
{
  "name": "Slide",
  "blocks": [{ "type": "@theme" }, { "type": "@app" }]
}
{% endschema %}
```

---

## Block Decision Guide

```
Is this content reused across multiple sections?
  Yes → Theme Block (/blocks/*.liquid)
  No  → Section Block (in schema)

Does this block need to be nested inside other blocks?
  Yes → Theme Block ({% content_for 'blocks' %})
  No  → Either works

Does this block need merchant-facing settings in the editor?
  Yes → Block (section or theme)
  No  → Snippet ({% render %})

Does this block need to receive variables from parent Liquid code?
  Yes → Snippet (accepts explicit variables)
  No  → Block (blocks use schema settings and global objects)

Does this block need to stay in a fixed position (can hide but not delete)?
  Yes → Static Block ({% content_for 'block', type: '...', id: '...' %})
  No  → Dynamic Block

Is this from a third-party app?
  Yes → App Block (@app in schema, {% content_for 'blocks' %})
```
