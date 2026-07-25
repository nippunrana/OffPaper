---
name: shopify-theme
description: >
  Use for any Shopify Online Store 2.0 theme work: sections ({% schema %},
  settings, blocks, presets), JSON templates, theme.liquid layout, section
  groups (header-group/footer-group), theme blocks (/blocks/,
  {% content_for %}), snippets, settings_schema.json, performance (JS ≤16KB,
  Lighthouse ≥60, asset_url cache-busting, {% stylesheet %} CSS subsetting),
  and theme editor JS (shopify:section:load, shopify:block:select,
  shopify:section:unload). Invoke when the user wants to add a section, create
  a Shopify template, build a theme block, customize the header or footer, add
  schema settings, fix theme editor behavior, improve Lighthouse scores,
  convert HTML to a section, add an app block, wire storefront events, or touch
  any Liquid theme file, section schema, settings_schema.json, or the Shopify
  CLI.
---

# Shopify Theme Developer
*Target: Shopify Online Store 2.0 (JSON templates + section/block architecture)*

## Required Context (Dynamic Variables)

Before executing, resolve these variables. Check for an `AI_CONTEXT.md` in the
project root — if found, read it. Otherwise derive from context or ask.

- `{{THEME_SLUG}}`: kebab-case directory name of the theme (e.g., `my-store`)
- `{{THEME_BASELINE}}`: base theme (Dawn / Skeleton / custom / unknown)

Never leave a `{{placeholder}}` literal in generated output.

---

## How to Approach a Request

1. **Identify the deliverable** — template, section, block, snippet, settings change,
   performance fix, or theme editor compatibility fix? Route to the matching section below.
2. **Read Required Context** — load `AI_CONTEXT.md` if present; identify `{{THEME_SLUG}}`.
3. **Pass all five Pre-write gates** (below). Stop if any gate fails.
4. **Load the relevant reference file** — each section below names which file to load.
5. **Execute** using the checklists; match the conventions of existing theme files.
6. **Verify** — list every file created/modified and confirm the gates still pass.

---

## Pre-write Gates

All five must pass before writing a single line of code. If any fails, fix it first.

**Gate 1 — Liquid Allowlist Gate**
Every Liquid object, filter, and tag used must appear in `references/liquid-allowlist.md`.
Unknown names → check shopify.dev/docs/api/liquid, don't invent. `money_amount`
is NOT in the allowlist; use `money` or `money_with_currency`.

**Gate 2 — Template Exclusivity Gate**
A URL route is served by ONE file: either a JSON template or a Liquid template, never both.
JSON templates = data/orchestration only (no HTML, no Liquid logic). All valid template
types are listed in `references/templates.md`.

**Gate 3 — File-placement Gate**
No custom subdirectories. Allowed paths:
`assets/`, `blocks/`, `config/`, `layout/`, `locales/`, `sections/`, `snippets/`,
`templates/`, `templates/customers/`, `templates/metaobject/`.
Any other subdirectory breaks the theme.

**Gate 4 — Schema Gate**
- Exactly **one** `{% schema %}` per section file; never nested inside Liquid tags.
- Schema contains valid static JSON only — no Liquid variables or logic inside it.
- `enabled_on` and `disabled_on` are mutually exclusive; never both in same schema.
- A section needs at least one `presets` entry to appear in the "Add Section" picker.
- Section blocks and `@theme` type cannot coexist in the same section.

**Gate 5 — Performance Gate**
- Total minified JS ≤ 16 KB.
- All scripts use `defer` or `async` attributes; wrap logic in an IIFE.
- All asset references use `asset_url` or `image_url` (never hardcode paths).
- Never lazy-load above-the-fold images.
- `{% javascript %}` and `{% stylesheet %}` tags for section-scoped assets.

---

## Core Architectural Rules

**Directory structure (read `references/architecture.md` for the full map):**
```
layout/         → theme.liquid (ONLY required file for upload)
templates/      → *.json (preferred) or *.liquid; customers/ and metaobject/ subdirs OK
sections/       → *.liquid section files + section-group JSON files
blocks/         → *.liquid theme block files
snippets/       → *.liquid reusable fragments (invisible to theme editor)
assets/         → CSS, JS, images, fonts
config/         → settings_schema.json, settings_data.json, markets.json
locales/        → translation JSON files
```

**Rendering hierarchy:** Layout → Template → Section Group → Section → Block → Snippet

**theme.liquid mandatory objects** (missing either = broken theme):
```liquid
{{ content_for_header }}   ← inside <head>
{{ content_for_layout }}   ← inside <body>
```

**JSON template shape** (orchestration only — no HTML or Liquid logic):
```json
{
  "layout": "theme",
  "wrapper": "main",
  "sections": {
    "main_product": { "type": "main-product", "settings": {} }
  },
  "order": ["main_product"]
}
```

**Section scoping rule:** Variables inside a section cannot leak out. Variables
from outside (except globals like `product`, `collection`, `cart`) cannot enter a
section. Snippets rendered inside a section can access `section` and `block` objects.

**Static sections are dangerous:** A statically rendered section (`{% section 'name' %}`)
shares one set of settings across every page that uses it. Prefer JSON templates.
Static rendering also blocks App Block support.

**Block rendering rules:**
- Section blocks: `{% for block in section.blocks %}` + `{{ block.shopify_attributes }}`
- Theme blocks / App blocks: `{% content_for 'blocks' %}` (supports nesting; required for `@theme`/`@app`)
- Never rely on `block.id` in logic — it's dynamically generated. Branch on `block.type`.

**`{% include %}` is deprecated** — always use `{% render %}`. Variables are not
inherited by rendered snippets; pass them explicitly.

---

## 0. Layout (`layout/theme.liquid`)

Load `references/architecture.md` before modifying theme.liquid.

Key requirements:
- `{{ content_for_header }}` in `<head>` (Shopify-injected scripts)
- `{{ content_for_layout }}` in `<body>` where templates render
- `{% sections 'header-group' %}` / `{% sections 'footer-group' %}` for section groups
- `<body class="{{ template | handleize }}-template">` for per-template CSS targeting
- Always include `{% render 'cart-notification' %}` or equivalent for AJAX cart hooks

---

## 1. Templates (`templates/`)

Load `references/templates.md` before creating templates.

- Prefer **JSON templates** for all standard page types (product, collection, page, etc.).
- Use **Liquid templates** only when you need Liquid logic directly in the template
  (e.g., `gift_card.liquid`, `password.liquid`, `robots.txt.liquid`).
- Agentic commerce: `templates/agents.md.liquid`, `templates/llms.txt.liquid`,
  `templates/llms-full.txt.liquid` customize AI agent discovery.
- Alternate templates: name as `product.my-variant.json` or `page.landing.json`.
- `{% layout none %}` in a Liquid template opts out of the layout wrapper entirely.

---

## 2. Sections (`sections/*.liquid`)

Load `references/sections-and-groups.md` before writing sections.

Checklist:
- [ ] Single `{% schema %}` tag, valid JSON, not nested in Liquid
- [ ] `name`, `tag` (article/aside/div/footer/header/section), and `class` set
- [ ] Settings use unique IDs; block types/names unique within section
- [ ] `presets` defined for any section merchants can add via "Add Section" picker
- [ ] `enabled_on` OR `disabled_on` (not both)
- [ ] `{{ block.shopify_attributes }}` on outermost HTML element of each block iteration
- [ ] Section-scoped CSS in `{% stylesheet %}` tag; JS in `{% javascript %}` tag
- [ ] Never put Liquid or HTML inside `{% schema %}`

Section blocks (local, non-reusable; max 50 per section):
```liquid
{% for block in section.blocks %}
  <div {{ block.shopify_attributes }}>
    {{ block.settings.text }}
  </div>
{% endfor %}
```

---

## 3. Section Groups (`sections/*.json`)

Load `references/sections-and-groups.md`.

- Header: `sections/header-group.json`; Footer: `sections/footer-group.json`
- Referenced in `layout/theme.liquid` via `{% sections 'header-group' %}`
- Contextual section groups for Markets/B2B require unique group names
- Comments and trailing commas in section group JSON are NOT persisted

---

## 4. Blocks

Load `references/blocks.md` before writing any block code.

| Block type | Location | Rendered by | Nesting |
|---|---|---|---|
| Section block | In section schema `blocks` array | `{% for block in section.blocks %}` | No |
| Theme block | `/blocks/*.liquid` | `{% content_for 'blocks' %}` | Yes |
| App block | `@app` in schema | `{% content_for 'blocks' %}` | Yes (in supported sections) |

Theme blocks to support AI-generated content: include `{"type": "@theme"}` in section schema `blocks`.
App blocks: include `{"type": "@app"}` — never add `limit` param to `@app` entries.

Private theme blocks: prefix filename with `_` (e.g., `_slideshow-controls.liquid`).

---

## 5. Snippets (`snippets/*.liquid`)

- Rendered via `{% render 'snippet-name', variable: value %}`
- No `{% schema %}` tag; invisible to theme editor
- Variables must be passed explicitly — snippets do NOT inherit parent scope
- Use for shared logic: price formatting, icon sets, product cards, pagination

---

## 6. Settings & Config

Load `references/settings-and-config.md` before modifying `settings_schema.json`.

Global settings (`config/settings_schema.json`):
- First category **must** be `theme_info` with keys: `theme_name`, `theme_author`,
  `theme_version`, `theme_documentation_url`, plus XOR `theme_support_email` or
  `theme_support_url`.
- Comments and trailing commas persist here (but not in `settings_data.json`).
- Access in Liquid: `{{ settings.your_setting_id }}`

Section/block settings: accessed via `section.settings.id` / `block.settings.id`.

---

## 7. Performance

Load `references/performance.md` before any JS/CSS or image work.

Hard limits:
- Minified JS bundle ≤ **16 KB**
- Lighthouse ≥ **60** (Theme Store minimum); formula: `[(p×31)+(c×33)+(h×13)]/77`
- Pagination count of **25,001** = overflow signal; implement filters instead

---

## 8. Theme Editor Compatibility

Load `references/theme-editor.md` when writing interactive JS or custom components.

- Re-initialize scripts on `shopify:section:load` (section DOM re-rendered without reload)
- Scroll to/show active content on `shopify:block:select`
- Guard in-editor only code with `if (Shopify.designMode) { … }`
- Selected sections/blocks must scroll into view automatically in the editor

---

## 9. Agentic Commerce & Storefront Events

Load `references/storefront-events-actions.md` when implementing commerce events or AI templates.

Key: `agents.md.liquid`, `llms.txt.liquid`, `llms-full.txt.liquid` templates customize
what AI agents and crawlers discover about the store. Follow `robots.txt.liquid` pattern.

---

## Debugging Common Issues

| Symptom | Cause | Fix |
|---|---|---|
| Section not in "Add Section" picker | Missing `presets` in schema | Add at least one `presets` entry |
| Blocks not rendering | Using `{% for block %}` with `@theme`/`@app` blocks | Switch to `{% content_for 'blocks' %}` |
| Section block styles break on some pages | Cross-file CSS dependency | Move CSS into the section's `{% stylesheet %}` |
| JS stops working after merchant edits | `DOMContentLoaded` doesn't re-fire | Bind re-init to `shopify:section:load` event |
| Slider shows wrong slide in editor | `shopify:block:select` not handled | On `shopify:block:select`, scroll to `event.detail.blockId` |
| Template not rendering for product/collection | No matching template file | Create `templates/product.json` (or Liquid equivalent) |
| Settings change doesn't propagate on other pages | Static section (`{% section %}` tag) | Move to JSON template; static sections share one settings instance |
| `{% schema %}` syntax error on save | Liquid or comment inside schema | Schema must be pure static JSON; no Liquid or `//` comments |
| Assets show stale version | Using hardcoded path | Always use `asset_url` filter: `'file.css' \| asset_url` |
| JSON template not working | Template has HTML/Liquid content | JSON templates are orchestration only; move logic to sections |
| Block ID logic breaks after merchant reorder | `block.id` is dynamic | Branch on `block.type` or a custom setting; never hardcode `block.id` |
| `content_for_header` missing | Omitted from `theme.liquid` | Add `{{ content_for_header }}` inside `<head>` — mandatory |
| `enabled_on` + `disabled_on` schema error | Both set simultaneously | Use only one; they are mutually exclusive |
| App block renders empty in static section | App blocks not supported in static sections | Move section to JSON template |
| Theme upload fails | Custom subdirectory created | Remove it; only `templates/customers/` and `templates/metaobject/` are allowed |

---

## Reference Files

| File | Load when… |
|---|---|
| `references/architecture.md` | Before ANY code (directory map, render flow, mandatory layout objects) |
| `references/liquid-allowlist.md` | Before every Liquid filter/object/tag — the anti-hallucination gate |
| `references/templates.md` | Creating or modifying templates; JSON template schema; template type list |
| `references/sections-and-groups.md` | Writing sections, section groups, schema keys, presets, enabled_on |
| `references/blocks.md` | Writing theme blocks, app blocks, _blocks.liquid, apps.liquid, content_for |
| `references/settings-and-config.md` | Modifying settings_schema.json, adding settings, theme_info, color_palette |
| `references/performance.md` | Writing JS/CSS, asset references, images, Lighthouse scoring |
| `references/theme-editor.md` | Writing interactive JS, section event handling, designMode |
| `references/storefront-events-actions.md` | Implementing storefront events/actions API or agentic commerce templates |
| `references/best-practices.md` | WCAG accessibility, CLI workflow, deprecations, Theme Check |