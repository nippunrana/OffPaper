---
name: wp-block-theme
description: >
  Expert WordPress 7.0 theme AND plugin developer. Covers the full WP 7.0 surface: Full Site
  Editing block themes, templates, template parts, block patterns, theme.json design systems —
  AND the new plugin-side APIs: WP AI Client (`wp_ai_client_prompt()` with JSON schema responses
  and multimodal generation like image/audio/video), Connectors API, client-and-server Abilities API
  (`wp_register_ability` / `registerAbility`), DataViews and DataForms (`groupBy`, `onReset`, Field validation,
  `getValueFormatted`), Block Bindings, Block Hooks, PHP-only blocks (`autoRegister`), viewport `blockVisibility`,
  dimensions and textIndent supports, customisable Navigation Overlays, Interactivity API `watch()` with
  server-populated `state.url`, and Breadcrumb filters. Use this skill whenever the user wants
  to create, modify, or scaffold any part of a WP 7.0 theme OR plugin — new templates, template
  parts, block patterns, child theme extensions, custom blocks (PHP-only or classic dynamic),
  AI abilities, DataViews admin screens, custom Connectors, theme.json configuration, modular
  asset pipelines, or Site Editor debugging. CRITICALLY: use this skill whenever the user
  provides raw HTML/CSS/JS and asks to "convert it to a block theme template", or asks to
  "add an AI feature", "register an ability", "build an admin table", "call Claude/OpenAI from
  PHP", "register a custom AI provider", "manage API keys for AI", or implements anything that
  touches the AI Client, Abilities, Connectors, or DataViews APIs. ALSO use this skill for
  WP 7.0 core-platform work — real-time collaboration gates (`wp_is_collaboration_enabled` /
  `wp_is_collaboration_allowed`), AI environment checks and governance (`wp_supports_ai`,
  `wp_ai_client_prevent_prompt`, the request-timeout filter), Block Hooks REST-injection scoping
  (`rest_block_hooks_post_types`), Script Module translations (`wp_set_script_module_translations`),
  admin View Transitions and the Command Palette, password-hashing filters (Argon2 via
  `wp_hash_password_algorithm`), and image LCP / `fetchpriority` optimization. ALSO use this skill for
  WooCommerce work in a block theme — "override the WooCommerce shop / single product / cart /
  checkout / order confirmation template", "style the Mini-Cart", "customize the Add to Cart
  block", "register a Cart/Checkout filter via registerCheckoutFilters()", "use is_shop() /
  is_product() / is_cart()", "call wc_get_logger()", or any work touching `woocommerce/*` blocks
  or the `Automattic\WooCommerce` namespace. ALSO covers WooCommerce checkout extensibility —
  "add a custom checkout field", "woocommerce_register_additional_checkout_field", "validate /
  sanitize a checkout field", "conditional checkout field via JSON Schema", "onCheckoutValidation /
  onPaymentSetup / onCheckoutSuccess", "checkout SlotFill (ExperimentalOrderMeta)",
  "wc/store/checkout data store" — plus WooCommerce store performance ("exclude cart/checkout from
  cache", "fix password reset loop", "Core Web Vitals for a store") and coding standards ("safe
  WooCommerce customization", "child theme template override", "is_ssl behind a load balancer").
---

# WordPress 7.0 Theme & Plugin Developer
*Target: WordPress 7.0+ (released 2026-05-20). Earlier versions are out of scope.*

## Required Context (Dynamic Variables)

Before executing this skill, resolve the following variables. The preferred source is `AI_CONTEXT.md`
(parent theme) or `AI_CONTEXT-Child.md` (child theme) in the project root — read whichever is
present. **If neither file exists**, derive the values from context instead (theme directory name,
inline information in the user's request, or ask the user directly). Never leave a `{{placeholder}}`
literal in generated output.

- `{{THEME_SLUG}}`: The kebab-case slug of the active theme (e.g., `my-theme`).
- `{{THEME_NAME}}`: The human-readable name of the theme.
- `{{TEXT_DOMAIN}}`: The text domain used for localization.
- `{{PARENT_THEME_SLUG}}`: (Optional) The slug of the parent theme if working in a child theme.

All examples below use these placeholders. Replace them with actual values from the project context.

A skill for creating and extending WordPress Full Site Editing (FSE) block themes — templates,
template parts, block patterns, theme.json design systems, and modular asset pipelines.

The most common entry point is **converting an existing HTML/CSS/JS design into a block theme
template**. This is the primary workflow covered in Section 0. All other sections cover the
underlying building blocks used during that process.

## How to approach a request

1. **Identify the deliverable** — Is this an HTML→template conversion, a new template from
   scratch, a pattern, a template part, a theme.json change, an asset pipeline fix, **or a plugin
   feature** (an Ability, a WP AI Client call, a custom Connector, a DataViews admin screen, a
   PHP-only block via `autoRegister`)? Theme work routes through Sections 0–6; plugin work routes
   through Section 7 and the dedicated reference files (`ai-client.md`, `abilities-api.md`,
   `dataviews.md`).
2. **Determine Target Theme & Read Context** — Identify if you are developing the **Core (Parent Theme)** or an **Extension (Child Theme)**. 
   - Read `AI_CONTEXT-Child.md` if working on a child theme.
   - Read `AI_CONTEXT.md` if working on the parent theme core.
   - Always prioritize child themes for project-specific features unless you are building foundational capabilities into the parent.
3. **Follow the architecture rules** in `references/architecture.md`.
4. **Pre-write gates.** Before writing any code, all four gates must pass. If any gate fails, fix it before proceeding:
   - **API gate:** Every WordPress function or class name must appear in `references/api-allowlist.md`. The "Names that look real but are not" section lists fabricated APIs to never use (`register_block_ability`, `WP_Icons_Registry::register`, `is_ai_ready`, `__experimentalRole`, `"version": 4`). **Disambiguation:** `__experimentalSettings` (a per-block container attribute controlling content-only opt-out) is a distinct, supported escape hatch — do not conflate it with the fabricated `__experimentalRole`. Prefer the stable `disableContentOnlyForUnsyncedPatterns` key inside `block_editor_settings_all` (site-wide) over the per-block `__experimentalSettings` attribute (experimental, may rename).
   - **PHP 8.3 gate:** Every named function and anonymous callback must have typed parameters and a return type. Use `??`, `str_contains()`, `match`, and `?->` where appropriate. Full checklist: `references/architecture.md → PHP 8.3 Requirements`. For WordPress PHP house style (Yoda conditions, `array()` syntax, tabs, naming, `$wpdb->prepare()`, prohibited constructs), load `references/php-coding-standards.md` before writing. `declare(strict_types=1);` requirement by file type:

     | File path pattern | `declare(strict_types=1);` required? |
     |---|---|
     | `functions.php`, `inc/**/*.php`, `src/**/*.php` | Yes |
     | `patterns/*.php` (docblock + block markup only, no function/class) | No — strict_types has no effect without typed signatures |
     | `patterns/*.php` (contains any `function` or `class` keyword) | Yes — but move the function out; that's a smell |
     | Backing PHP for template parts (`patterns/header-*.php`, etc.) | No (same rule as patterns) |
   - **Version gate:** `"version": 3` in all `theme.json` files. Never write `"version": 4`.
   - **WP 7.0 gate:** Every feature used must exist in WP 7.0. If unsure, consult `references/api-allowlist.md`.
   - **Namespace gate:** Any call to `register_block_template()` MUST use the double-slash namespace: `{{THEME_SLUG}}//template_name`. A single slash registers silently with no error but the template never appears in Page Attributes.
5. **Execute** using the implementation checklists below. Use `references/scaffold-tree.md` for the directory layout.
6. **Verify** — run a quick filesystem audit and remind the user to check the Site Editor Revisions panel (⋮ → "Revisions") before using "Clear Customizations" if they report that file changes are not showing.

---

## Core Architectural Rules

- **Respect the Parent/Child boundary.**
  - Child Theme Context: if `AI_CONTEXT-Child.md` exists and is the active focus, never touch the parent theme directory. All modifications go in the child theme.
  - Parent Theme Context: if you are explicitly developing the parent core (active folder matches the theme folder and task is foundational), you may modify parent files. If a feature is project-specific, suggest moving it to a child theme instead.
- **Native-first conversion.** Recreate designs using Core Blocks (`core/group`, `core/columns`, `core/heading`, `core/image`, `core/icon`, etc.). Use the Grid layout (`{"layout": {"type": "grid"}}` on `core/group`) for grid layouts. This keeps colors, typography, and spacing under Site Editor control. **NEVER use `wp:html`. NEVER paste raw HTML into a template file.** When a design cannot be expressed in core blocks, use this fallback hierarchy:
  1. Compose with `core/group`, `core/columns`, and the Grid layout. *(Default.)*
  2. Register a **PHP-only block**: `register_block_type()` with `'supports' => array( 'autoRegister' => true )` and a `render_callback`. No `block.json`, no JS, no build step. WP 7.0 auto-generates Inspector Controls for string, number, integer, and boolean attributes. **Constraint:** Attributes must be stored in the block's JSON boundary; "sourced" attributes (e.g., from HTML) are not supported.
  3. **Classic dynamic block** (`register_block_type()` with full `block.json` + `render_callback`) — only when the block needs custom JS-driven editor controls beyond auto-generated ones.
  See `references/architecture.md → Native-First Decision Algorithm` for the full decision tree.
- **Template registration — use exactly one mechanism per template name:**
  - Use `theme.json` `customTemplates` if the template ships inside a theme (parent or child).
  - Use `register_block_template()` if the template ships inside a plugin or must be reused across themes.
  - **NEVER use both for the same template name** — silent double-registration is one of the hardest FSE bugs to diagnose.
  See `references/architecture.md` for the full `register_block_template()` signature.
- **Pattern types at a glance — the single most important decision before writing any pattern file:**

  | Pattern type | Outermost block? | `lock`? | `className`? | `Inserter`? |
  |---|---|---|---|---|
  | **Master / assembler** (`Inserter: false`) | ❌ No wrapper block | ❌ No | ❌ No | `false` |
  | **Sub-pattern** (standalone section) | ✅ Yes | ✅ `{move:true, remove:true}` | ✅ `is-style-{slug}` | `true` (or `false` if internal-only) |
  | **Template-part backing pattern** | ✅ Yes | ✅ `{move:true, remove:true}` | ✅ `is-style-{slug}` | `false` |

  Master patterns MUST contain only flat `<!-- wp:pattern -->` calls — no wrapper, no lock, no CSS. All layout and CSS live in sub-patterns.

- **Two independent locking axes — apply each independently:**
  - **Structural lock** (`lock` attribute): controls whether editors can move/delete the block. Apply to sub-patterns and template-part backing patterns; omit on master patterns.
  - **Content-editability lock** (`templateLock`/`role`): controls whether editors can restructure *inner* content. These are orthogonal — a block can have both, either, or neither.

- **`contentOnly` templateLock — apply by pattern type:**

  | Pattern type | `templateLock` value |
  |---|---|
  | Unsynced pattern (default) | Omit the attribute. WP 7.0 applies `contentOnly` automatically. |
  | Synced pattern that must lock structure | `"templateLock": "contentOnly"` explicit |
  | Template part backing pattern | `"templateLock": "contentOnly"` explicit |
  | Pattern with editable inner blocks (e.g., FAQ items) | Omit, then mark editable child attributes with `"role": "content"` in `block.json` (or use `"contentRole": true` in the block's `supports`). |

  To opt out site-wide: use the stable `disableContentOnlyForUnsyncedPatterns` key inside `block_editor_settings_all` (see `references/api-allowlist.md → contentOnly Pattern Opt-Out`). To opt out per-pattern: `"__experimentalSettings": {"disableContentOnlyForUnsyncedPatterns": true}` on the outermost block (experimental, may rename). **Custom blocks nested inside contentOnly patterns MUST declare `"role": "content"` on every editable attribute in `block.json` (or declare `"contentRole": true` in `supports`)** — without this the block is hidden from List View with no error. See `references/architecture.md → Content-Only Locking` for the full decision tree.
- **Block locking — three non-overlapping rules** (structural lock only; see table above):
  - **Sub-patterns and inserter-visible patterns:** outermost block MUST carry `"lock": {"move": true, "remove": true}` AND `"className": "is-style-{pattern-slug}"`.
  - **Master/assembler patterns** (`Inserter: false`, called only via `wp:pattern`): MUST contain only a flat list of `<!-- wp:pattern -->` comments. No wrapper block. No lock attribute. No styles. No `className`.
  - **Template parts** (outermost block of the backing PHP pattern): MUST carry `"lock": {"move": true, "remove": true}`.
- **CSS scoping via Block Style Variations.** Scope CSS strictly to the block's variation class (e.g. `.is-style-hero-section`) and register that style with `register_block_style()`. WordPress then injects the CSS automatically into both the frontend and the editor iframe. All hand-authored CSS must follow `references/coding-standards.md` (tabs, logical property order, value formatting).
- **Modern interactivity.** Reactive logic (state toggles, dynamic updates, user-event-driven UI) → use the Interactivity API. Register as a **Script Module** with `wp_register_script_module()` and bind via `script_module_handle` on `register_block_style()` so it loads only when the block is present. Save to `patterns/{sub-pattern}/view.js`. Non-reactive logic (IntersectionObserver animations, GSAP effects) that reads/writes no shared state → a `document.addEventListener( 'DOMContentLoaded', … )` guard is acceptable. Always gate on `if ( window.frameElement ) return;` to suppress in the editor iframe and scope all selectors to the variation class. Save to `patterns/{sub-pattern}/index.js`. Never use `document.addEventListener` for reactive state — that belongs in a Script Module. All JS (both lanes) follows `references/js-coding-standards.md`.
- **Block Hooks** can automatically attach a logic-providing block before/after a target block — useful for mandatory wiring that must not be missed by editors. In WP 7.0, hooks fire for all CPTs registered with `'show_in_rest' => true` and `'supports' => array( 'editor' )` — not just posts and pages.
- **Viewport block visibility.** Use `metadata.blockVisibility.viewport` to show/hide blocks by device type (`"mobile"`, `"tablet"`, `"desktop"`). Hiding is **CSS-based** — blocks remain in the DOM on all devices and are visually suppressed via an injected CSS class. Do not use CSS `display: none` on breakpoints for this purpose, and do not use this feature for access control. Enable with `settings.blockVisibility.viewport: true` in `theme.json`.
- **Font Library.** The Font Library is enabled for all theme types in WP 7.0 (block, hybrid, and classic). Use `theme.json fontFamilies` for version-controlled font registration. When a user says "install a font", direct them to Appearance → Editor → Styles → Typography → Manage Fonts. Do not enqueue fonts via `wp_enqueue_style` that already exist in the Font Library.
- **Synced Pattern Overrides.** For repeating sections (Hero, CTA), use synced patterns and mark overridable blocks with `metadata.bindings` + `metadata.name`. Set `Inserter: true` on these patterns so editors can re-insert them if deleted. Custom blocks must explicitly opt into Overrides via the `block_bindings_supported_attributes` filter.
- **Block Bindings API** wires Post Meta and other dynamic sources directly into Core Blocks — no custom PHP patterns required.
- **Template Parts for global areas** (Headers, Footers). Register them in `theme.json` under `templateParts`. If a part needs PHP (image URLs, dynamic content), make the `.html` file a thin pointer that calls a PHP pattern.
- **System vs. functional patterns.** Use `Inserter: false` for assembler patterns that exist only to power templates. Synced patterns must keep `Inserter: true`.
- **theme.json token inheritance in child themes.** Arrays like `palette` overwrite the parent rather than merge. Either copy the parent palette and append, or set `"defaultPalette": true` and reference parent tokens via `var(--wp--preset--color--{slug})`.
- **Fluid Typography and Spacing.** Enable `typography.fluid: true` and define spacing tokens with `clamp()` so designs scale without manual media queries.
- **Slugs and text domains.** Prefix pattern slugs and categories with the current theme's directory name (e.g., `my-theme/` for parent, `my-theme-child/` for child). Use the corresponding text domain for localization.
- **Push layout to theme.json.** Padding, margin, and gap belong in `theme.json` Section Styles (variations under `styles.blocks.{block}.variations.{name}`). Reserve `style.css` for things JSON cannot express (gradients, complex transforms, scoped overrides).

---

## 0. Converting HTML/CSS/JS into a Block Theme Template

This is the primary workflow when the user provides a finished design (HTML export, static page,
Figma handoff, etc.) and wants it running inside WordPress FSE.

WordPress's block editor strips any raw HTML it doesn't recognise as a registered block. A
static HTML file dropped straight into a template file will be silently gutted. The conversion
process wraps the HTML correctly, scopes the CSS so it doesn't pollute the Site Editor, and
wires up asset loading so everything works on the frontend AND inside the editor iframe.

**Read `references/html-conversion.md` now** — it contains the full 10-step process with a
complete worked example (input HTML → all output files). Use it as your primary guide.

### Quick-reference checklist

Before handing off, verify every item:

#### Structural (hard requirements)
- [ ] Agreed on a kebab-case **slug** with the user
- [ ] `templates/{slug}.html` exists — opens with `wp:template-part` header, calls master pattern + `wp:post-content`, closes with `wp:template-part` footer (decide which header/footer variant to use per-template)
- [ ] `patterns/{slug}.php` (master) exists with `Inserter: false` and contains only flat `<!-- wp:pattern -->` calls — no wrapper block, no lock, no CSS
- [ ] All logical sections have sub-pattern files in `patterns/`
- [ ] Design mapped to Core Blocks (Group, Columns, etc.); fallback to PHP-only block (`autoRegister: true`) before classic dynamic block; NEVER use `wp:html`

#### CSS scoping (conventions)
- [ ] CSS + HTML markup follows `references/coding-standards.md` (tabs not spaces, logical property order, quoted attributes, correct value formatting)
- [ ] Layout CSS is strictly scoped to the block style variation class (e.g. `.is-style-hero-section`), avoiding a catch-all page wrapper to prevent style bleed into independent sub-patterns
- [ ] Modular patterns (CTA, Header, FAQ, etc.) use their own separate CSS class (e.g. `.is-style-main-cta`) and are not placed inside a page-specific wrapper that alters their layout
- [ ] Master patterns (assemblers) have no layout CSS — all styles live in sub-patterns
- [ ] Sub-pattern CSS saved to its own directory (e.g., `patterns/faq-section/style.css`)
- [ ] Every sub-pattern's outermost block has `"lock": {"move": true, "remove": true}` AND a unique variation class (e.g. `is-style-{pattern-slug}`)
- [ ] Reactive JS registered as a Script Module (`wp_register_script_module()`) and bound via `register_block_style()` — saved to `patterns/{sub-pattern}/view.js`; non-reactive scripts (animations, observers) use a `DOMContentLoaded` guard gated on `window.frameElement`, scoped selectors — saved to `patterns/{sub-pattern}/index.js`; JS style follows `references/js-coding-standards.md`
- [ ] Image paths use `get_stylesheet_directory_uri()` — no hardcoded URLs

#### Configuration (hard requirements)
- [ ] `theme.json` has the `customTemplates` entry
- [ ] `theme.json` uses `"version": 3` and `"$schema": "https://schemas.wp.org/wp/7.0/theme.json"`
- [ ] JS handled by **Script Modules API** (`wp_register_script_module()`, enqueued via block presence or `viewScriptModule`)
- [ ] Pattern styles registered in `functions.php` using `register_block_style()` (WordPress injects the CSS into the editor iframe automatically)

### Common conversion pitfalls

| Problem | Symptom | Fix |
|---|---|---|
| "Black box" templates | Can't edit colors/text in the Site Editor | Rebuild custom sections using Core Blocks; only fall back to a dynamic block (`register_block_type()` + `render_callback`) when the design truly cannot map to core. |
| Raw HTML stripped | Content disappears in editor | Wrap in a dynamic block registered via `register_block_type()`; do not paste raw HTML into a template. |
| CSS bleeds into editor UI | Styles affect menus/toolbars | Scope rules strictly to `.is-style-{slug}` variation class |
| Template not accessible | A11y warnings/fail | Use semantic tags (nav, main) and ARIA labels |
| Client breaks layout | Sections deleted accidentally | Apply `"lock": {"move": true, "remove": true}` to structural blocks |
| Editor doesn't match frontend | WYSIWYG mismatch | Ensure CSS is loaded via `register_block_style()` |
| Animations fire in editor | Jarring editor experience | Gate logic on `window.frameElement` or use Interactivity API state |
| theme.json colors missing | Parent palette deleted | Child `theme.json` must include parent palette entries before appending |

---

## 1. Creating a New Custom Page Template (from scratch)

### Files to create / modify

| File | Action |
|---|---|
| `templates/{slug}.html` | New — the template entry point |
| `patterns/{slug}.php` | New — the main pattern (assembler) |
| `theme.json` | Modify — add entry under `customTemplates` |
| `functions.php` | Modify — register block styles for pattern assets |

### Step-by-step checklist

- [ ] **Determine the slug** — kebab-case, e.g. `my-landing-page`.
- [ ] **Create `templates/{slug}.html`**: Choose the correct architecture before writing a single line.

  **OPTION A — Site Editor–managed layout** (pattern hardcoded in template):
  Use when the layout is structural chrome that editors should never accidentally delete. The pattern is locked to this template. Content creators edit text/images *inside* the pattern via the Site Editor — they cannot restructure it from the Page Editor.
  ```html
  <!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->
  <!-- wp:pattern {"slug":"{{THEME_SLUG}}/{slug}"} /-->
  <!-- wp:post-content {"layout":{"type":"constrained"}} /-->
  <!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->
  ```

  **OPTION B — Page Editor–editable layout** (no pattern in template; set `Inserter: true` on the pattern):
  Use when editors must freely add, remove, or reorder sections in the normal Page Editor. The pattern goes directly into page content, not the template. Editors insert it from the Block Inserter.
  ```html
  <!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->
  <!-- wp:post-content {"layout":{"type":"constrained"}} /-->
  <!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->
  ```

  **Decision rule:** if the answer to "should an editor ever restructure this from the Page Editor?" is yes → Option B. Otherwise → Option A.
- [ ] **Create `patterns/{slug}.php`** — see pattern header format below.
- [ ] **Create sub-pattern CSS/JS** as needed in `patterns/{sub-pattern}/`
- [ ] **Register in `theme.json`** under `customTemplates`:
  ```json
  { "name": "{slug}", "title": "Human-Readable Title", "postTypes": ["page"] }
  ```
- [ ] **Enqueue assets in `functions.php`** — Use `register_block_style()` for CSS. For JS, apply this two-lane decision:

  | JS lane | When | File | Registration |
  |---|---|---|---|
  | Reactive (state, directives, user-driven UI) | Shared state or Interactivity API directives | `patterns/{slug}/view.js` | Script Module via `wp_register_script_module()`, bound through `script_module_handle` on `register_block_style()` |
  | Non-reactive (IntersectionObserver, GSAP, scroll animations) | Read-only DOM effects, no shared state | `patterns/{slug}/index.js` | Enqueued conditionally; always gate on `if ( window.frameElement ) return;` |

  > JS style for both lanes (tabs, single quotes, spaces inside parens/brackets): see `references/js-coding-standards.md`.

  For mandatory logic injection regardless of the selected variation, use **Block Hooks**.

### Pattern PHP header format

```php
<?php
/**
 * Title: My Landing Page
 * Slug: {{THEME_SLUG}}/{slug}
 * Categories: {{THEME_SLUG}}
 * Keywords: landing, page, {keywords}
 * Inserter: false
 */
?>
<!-- No global wrapper. Master patterns only assemble sub-patterns to prevent CSS bleed. -->
<!-- wp:pattern {"slug":"{{THEME_SLUG}}/hero-section"} /-->
<!-- wp:pattern {"slug":"{{THEME_SLUG}}/another-section"} /-->
```

See `references/architecture.md` (Asset Pipeline section) for the full `functions.php` template.

---

## 2. Creating a Template Part (Header / Footer / etc.)

Template parts live in `parts/` and must be registered in `theme.json` under `templateParts`.

### Single-file vs. Two-file Architecture

| Template part needs… | Architecture | Files |
|---|---|---|
| Static markup only (no PHP, no theme-relative image paths) | Single-file | `parts/{name}.html` only |
| PHP (image URLs via `get_stylesheet_directory_uri()`, dynamic content, icon helpers) | Two-file: thin `.html` pointer → PHP pattern | `parts/{name}.html` + `patterns/{name}.php` |

Most real-world template parts need PHP for image paths or dynamic content — default to two-file unless the part is truly static markup.

### Files

| File | Action |
|---|---|
| `parts/{name}.html` | New — the part markup (or pointer to PHP pattern) |
| `patterns/{name}.php` | New — **only if PHP is needed** (image URLs, dynamic content) |
| `patterns/{name}/style.css` | New — scoped CSS for this component |
| `patterns/{name}/index.js` | New (if non-reactive JS needed) |
| `theme.json` | Modify — add to `templateParts` |
| `functions.php` | Modify — register block styles for template part backing patterns |

### The Correct Two-File Architecture

For header/footer components that need PHP (image paths, icon helpers, etc.), use a **two-file
approach**: a thin `.html` template part that delegates to a PHP pattern.

**`parts/header-top-bar-variant-a.html`** — just a pointer:
```html
<!-- wp:pattern {"slug":"{{THEME_SLUG}}/header-top-bar-variant-a"} /-->
```

**`patterns/header-top-bar-variant-a.php`** — the real content with PHP:
```php
<?php
/**
 * Title: Header Top Bar Variant A
 * Slug: {{THEME_SLUG}}/header-top-bar-variant-a
 * Categories: {{THEME_SLUG}}
 * Inserter: false
 */
?>
<!-- wp:group {"className":"is-style-header-top-bar-variant-a","lock":{"move":true,"remove":true}} -->
<div class="wp-block-group is-style-header-top-bar-variant-a">
    <!-- wp:icon {"icon":"warning"} /-->
    <p>FOR LABORATORY RESEARCH USE ONLY.</p>
</div>
<!-- /wp:group -->
```

> **Why this works:** WordPress resolves `wp:pattern` through its block pattern registry, which
> executes the PHP file. The `.html` template part itself has no PHP, but the pattern it calls does.

> 💡 **System Patterns & `Inserter: false`:** Always add `Inserter: false` to patterns that act
> as backend logic for template parts or master templates. This keeps the user's UI clean.
> WordPress perfectly renders these hidden patterns whenever they are called via `wp:pattern`
> inside your `.html` template parts.

### Placing Template Parts in the Root `.html` Template

Always call template parts directly in the root `templates/{slug}.html` file — never nest them inside a master pattern:

```html
<!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->
<!-- wp:pattern {"slug":"{{THEME_SLUG}}/{slug}"} /-->
<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
<!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->
```

This ensures true modularity and lets the developer choose which header/footer variant to use per-template.

### Asset Enqueuing for Template Parts

Because the template part relies on a backing PHP pattern, you register its CSS exactly the same way as any standard pattern: by registering a block style variation for the outermost block of the PHP pattern. This eliminates the need for custom asset loaders or bare-slug normalisation arrays.

### theme.json registration

```json
"templateParts": [
  {
    "name": "header-top-bar-variant-a",
    "title": "Header Top Bar Variant A",
    "area": "header"
  }
]
```

Valid `area` values: `header`, `footer`, `uncategorized`, `navigation-overlay` (WP 7.0 — for mobile menu overlays).

**Navigation Overlay setup (WP 7.0):** To register a custom mobile overlay:
1. Add `{"area": "navigation-overlay", "name": "mobile-menu"}` to `templateParts` in `theme.json`.
2. Create the HTML file in `/parts/` — **always include `core/navigation-overlay-close`** to avoid design-clashing fallbacks.
3. Register a pattern with `Block Types: core/template-part/navigation-overlay` to surface it as the active overlay.
4. In the Navigation block's `overlay` attribute, use the **slug only** (no theme prefix) for future-proofing across theme switches.

See `references/architecture.md → Custom Navigation Overlays` for the full implementation reference.

---

## 3. Creating a Standalone Block Pattern (Section / Component)

Patterns are reusable sections inserted via the Block Inserter or called from templates.

### Pattern PHP header (required)

```php
<?php
/**
 * Title: Hero Section
 * Slug: {{THEME_SLUG}}/hero-section
 * Categories: {{THEME_SLUG}}
 * Keywords: hero, banner, header
 * Block Types: core/group
 */
?>
```

All five header fields are recommended. `Block Types` is optional but improves discoverability.

### Required attributes on the outermost block

Every standalone/sub-pattern's outermost block MUST have all three:

1. `"className": "is-style-{pattern-slug}"` — registers the variation class that scopes its CSS.
2. `"lock": {"move": true, "remove": true}` — prevents accidental deletion.
3. CSS registered via `register_block_style()` in `functions.php` — never `wp_enqueue_style()` directly for pattern-level CSS.

### Image paths in patterns

Always use PHP to output image URLs — never hardcode paths:

```php
<img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/hero.png" alt="Hero">
```

---

## 4. theme.json Design System

Read `references/theme-json.md` for a full reference on color palettes, typography, spacing,
and block-specific targeting. Key rules:

- **`"version": 3`** is current (introduced in 6.6, still current in 7.0). Always use `"$schema": "https://schemas.wp.org/wp/7.0/theme.json"` (pinned to the released version; `trunk` points to the development branch and may expose unreleased schema features).
- **CSS variable naming**: `var(--wp--preset--color--{slug})`, `var(--wp--preset--spacing--{slug})`.
- **Inject raw CSS**: Use `styles.css` property in `theme.json` for global rules, or
  `styles.blocks["core/group"].css` for block-scoped rules.
- **Child theme inheritance**: The child's `theme.json` merges with the parent's at the object/key level — it does not replace the whole file. Only override what you need. **However, array values (e.g. `palette`, `fontSizes`, `spacingSizes`) overwrite the parent array entirely rather than merging.** Either copy the parent array entries and append your additions, or set `"defaultPalette": true` and reference parent tokens via `var(--wp--preset--color--{slug})`. See also the equivalent warning in Core Architectural Rules above.
- **Text indent (WP 7.0)**: `styles.typography.textIndent` controls paragraph indentation at Global Styles level. Values: `"subsequent"` (default — only paragraphs following another paragraph) or `"all"` (every paragraph). See `references/api-allowlist.md → Typography & Dimensions Supports`.
- **Dimension presets (WP 7.0)**: `settings.dimensions.dimensionSizes` defines width/height presets. UI renders a slider for fewer than 8 presets and a dropdown for 8 or more. See `references/api-allowlist.md → Typography & Dimensions Supports`.

---

## 5. Debugging Common Issues

| Symptom | Cause | Fix |
|---|---|---|
| Template not in Page Attributes dropdown | Not registered in `theme.json` `customTemplates` | Add the entry; check the `name` matches the filename (without `.html`) |
| Raw HTML disappears in editor | Non-block HTML pasted directly into a template | Register a dynamic block (`register_block_type()` + `render_callback`) and emit the HTML from the callback |
| CSS not loading on frontend | Block Style not registered | Check `functions.php` to ensure `register_block_style()` is hooked correctly and the variation class (e.g. `is-style-my-pattern`) is applied to the pattern's outermost block. |
| Editor doesn't match frontend | Styles not enqueued via block styles | Ensure CSS is loaded via `register_block_style()`; do not rely on global `add_editor_style()` for pattern-level CSS. |
| theme.json changes ignored | Database override active | 1. Check Revisions panel first (Site Editor → ⋮ → "Revisions") — roll back to "Original" if a revision is overriding. 2. If no revision, go to Site Editor → Styles → ⋮ → "Reset to defaults" / "Clear Customizations". |
| Old content persists after file change | Database revision overriding file | Open Site Editor → select template/part/pattern → ⋮ → "Revisions" → roll back to "Original". Use "Clear Customizations" only if no usable revision exists — it is not reversible. |
| Block hidden on all devices unexpectedly | `blockVisibility.viewport` set to empty array | Check `metadata.blockVisibility.viewport` on the block — `[]` hides everywhere. Remove the key entirely to show on all devices. |
| Pattern images broken | Hardcoded path | Use `get_stylesheet_directory_uri()` in PHP pattern |
| Block validation error | Nesting issue or stray HTML | Validate markup; ensure every chunk of raw HTML lives inside a registered dynamic block, not loose in the template |
| **Template part renders as empty `<div class="wp-block-template-part">`** | `wp:template-part` placed inside a dynamic block's `render_callback` output | Move `wp:template-part` comments to the root of the `.html` template, outside any dynamic block |
| **Template part exists in DOM but content is empty** | Pattern is missing or typo in the slug | Check the `slug` inside the `wp:pattern` comment matches the PHP registration exactly |
| **Template part CSS not loading** | Outermost block missing style class | Ensure the PHP pattern powering the template part has a variation class like `is-style-header` on its root block. |
| **PHP functions not running in template part** | Trying to put PHP directly in `.html` file | `.html` files cannot run PHP — put PHP in a `.php` pattern and call it via `<!-- wp:pattern {"slug":"..."} /-->` |
| **`blockVisibility` PHP parsing error / type mismatch** | Treating `metadata.blockVisibility` as always an object — it can also be a scalar boolean | Check for boolean first: `if ( false === $visibility ) { /* hidden everywhere */ } elseif ( is_array( $visibility ) && isset( $visibility['viewport'] ) ) { /* viewport rules */ }`. Note: the block **support** key is `visibility`; the **metadata attribute** key is `blockVisibility` — these are different. |
| **Editor iframe breaks / styles bleed** | A legacy Block API v2 block was inserted | WordPress 7.0 dynamically un-iframes the editor if an API v2 block is present. Upgrade the custom block to API v3+ to restore iframed mode. |

For DOM-level diagnostics, see the **Diagnostic Console Snippet** in `references/architecture.md`.

---

## 6. Modular Sub-Pattern Architecture

When a template is complex (e.g. a full landing page), decompose it into sub-patterns. WordPress requires the `.php` file for each pattern to exist in the `patterns/` directory to automatically register them:

```
patterns/
  my-landing-page.php        ← master pattern (assembles sub-patterns)
  faq-section.php            ← FAQ PHP pattern (name MUST match directory)
  cta-section.php            ← CTA PHP pattern
  hero-section.php           ← Hero PHP pattern
  faq-section/
    style.css                ← Scoped CSS for FAQ only
    index.js                 ← FAQ JS (e.g. accordion)
  cta-section/
    style.css                ← Scoped CSS for CTA only
  hero-section/
    style.css                ← Scoped CSS for Hero only
```

This ensures that if you use the `faq-section` pattern on a different page, only the FAQ CSS/JS
loads, not the entire landing page bundle.

The master pattern calls each sub-pattern:

```php
<!-- wp:pattern {"slug":"{{THEME_SLUG}}/hero-section"} /-->
<!-- wp:pattern {"slug":"{{THEME_SLUG}}/features-section"} /-->
<!-- wp:pattern {"slug":"{{THEME_SLUG}}/cta-section"} /-->
```

This keeps individual files manageable and makes each section independently editable in the
Site Editor.

---

## 7. Plugin & AI Workflows (WP 7.0+)

WordPress 7.0 ships a full plugin-side API surface alongside the theme features above. This section is the entry point for plugin work — each subsection is a thin pointer to a dedicated reference file. Load the matching reference **before** writing code.

### 7.1 Registering an Ability (server + client)

Abilities are the standard way to expose a capability — to AI agents, to the REST API, and to plugin JS — through one unified registry. Server-side registration lives in `references/architecture.md` (Abilities API section). Client-side registration and the **REST method-mapping rules** (`readonly`/`destructive`/`idempotent` → GET / DELETE / POST) live in `references/abilities-api.md`. Always annotate `meta.annotations` honestly: AI agents read those flags to decide when to prompt for confirmation.

Read `references/abilities-api.md` when:
- The user wants to register an ability from JS (`@wordpress/abilities` `registerAbility`).
- You need to know which HTTP method the auto-generated REST route will use.
- You're pairing a server `permission_callback` with a client `permissionCallback`.

### 7.2 Calling the WP AI Client

Entry point is `wp_ai_client_prompt( $text = '' )` → `WP_AI_Client_Prompt_Builder`. **Always feature-detect first** (`WP_AI_Client_Prompt_Builder::is_supported_for_text_generation()`, `is_supported_for_image_generation()`, etc.) — these are pure capability checks with no API cost. For structured JSON data, strictly use `->as_json_response( $schema_array )`. For metadata (token usage, provider, model), use the `*_result()` variant which returns a `GenerativeAiResult`.

Common shape inside an ability's `execute_callback`:

```php
if ( ! \WP_AI_Client_Prompt_Builder::is_supported_for_text_generation() ) {
    return new \WP_Error( 'no_text_provider', __( 'No AI provider is configured.', '{{TEXT_DOMAIN}}' ) );
}
$result = wp_ai_client_prompt()
    ->using_system_instruction( '…' )
    ->using_temperature( 0.4 )
    ->with_text( $input['prompt'] )
    ->as_json_response( array(
        'type'       => 'object',
        'properties' => array(
            'summary' => array( 'type' => 'string' ),
        ),
    ) )
    ->generate_text();
if ( is_wp_error( $result ) ) {
    return $result;
}
```

Read `references/ai-client.md` when:
- The user wants to call an LLM from PHP (summarise, translate, classify, generate text/image/audio/video).
- The user mentions OpenAI / Anthropic / Gemini, API keys, or `Settings → Connectors`.
- The user wants to register a custom Connector via `wp_connectors_init`.
- The user asks where to put API keys (env var vs PHP constant vs database).

### 7.3 Building a DataViews-powered admin screen

DataViews drives modern admin listings; DataForms is the matching form layer. Key WP 7.0 changes: `groupBy` is now an object (`{ field, direction, label }`), `onReset` has three meaningful values (`undefined` / `false` / `function`), field validation moved into the field definition (`pattern`, `minLength`/`maxLength`, `min`/`max`), and `getValueFormatted` separates the stored value from the displayed value.

Read `references/dataviews.md` when:
- The user is building or modifying a DataViews admin screen.
- The user mentions `groupBy`, `onReset`, field validation, or formatting bytes / dates / role labels.
- You see legacy string-form `groupBy: 'status'` in code — it must be migrated.

### 7.4 Core Environment & Utilities

- **Core Environment Changes:** WordPress 7.0 requires a minimum of PHP 7.4, uses PHPMailer 7.0.2, and transitioned JS linting to Espree. *(Note: Regardless of WP 7.0's minimum, this AI skill strictly enforces PHP 8.3+ via the API gate above).*
- **Breadcrumb Block Filters:** The `block_core_breadcrumbs_items` filter modifies the trail. Security: If the `allow_html` flag is true, the label is sanitized via `wp_kses_post()`. If false or omitted, it is escaped via `esc_html()`.
- **User Registration Roles:** Administrator and Editor roles are removed from the default user registration selector to prevent accidental privilege escalation. Modify this list via the `default_role_dropdown_excluded_roles` filter.
- **AI environment & governance:** Use `wp_supports_ai()` as the outer gate before registering AI features, and the `wp_ai_client_prevent_prompt` / `wp_ai_client_default_request_timeout` filters for cost control and timeouts. See `references/ai-client.md` §1 and §5. Note: the prompt builder has **no** `using_abilities()` / `set_model()` / `with_options()` — expose capabilities to AI via the Abilities API instead.
- **Real-time collaboration, admin UX, security, LCP:** For collaboration gates (`wp_is_collaboration_enabled()` / `wp_is_collaboration_allowed()`), admin View Transitions, the Command Palette, password-hashing filters (Argon2), and image LCP / `fetchpriority` (`wp_get_loading_optimization_attributes()`), load `references/core-7.0-apis.md`.

---

## 8. WooCommerce Block Theme Development

If the user's task involves WooCommerce — overriding shop/cart/checkout/single-product templates, composing `woocommerce/*` blocks, styling Cart/Checkout/Mini-Cart, registering JS filters via `registerCheckoutFilters()`, using conditional tags (`is_shop()`, `is_product()`, `is_cart()`), calling `wc_get_logger()`, or anything touching the `Automattic\WooCommerce` namespace — **load `references/woocommerce.md` before writing code**.

Four additional pre-write gates apply on top of the four in "How to approach a request":

- **Block name gate** — every `woocommerce/*` block name used in template markup must exist in the registry in `references/woocommerce.md` §3. Do not invent block names (common AI failure: `woocommerce/cart-button`, `woocommerce/checkout-form` — these are composed of smaller blocks, not single units).
- **Ancestor gate** — product element blocks (price, image, button, rating, sale-badge, sku, stock, summary, title, etc.) only render when nested inside a declared ancestor (`woocommerce/single-product`, `woocommerce/product-template`, `core/post-template`, etc.). Placing them at template root silently renders empty.
- **Internal privacy gate** — CSS selectors targeting the internal DOM of a WooCommerce block are unsupported and break on update. Style via block style variations (`register_block_style()`) or the documented Cart/Checkout filter registry only.
- **Conditional tag timing gate** — `is_shop()` / `is_product()` / `is_cart()` are only valid after the `posts_selection` action. Safe earliest hook is `wp`. Never call them at `functions.php` global scope.

Overriding a WooCommerce template (`single-product.html`, `archive-product.html`, `page-cart.html`, etc.) uses the standard template-override mechanism — drop a same-named file in `templates/` and it wins over the WooCommerce default. The master-pattern / sub-pattern architecture from Sections 0 and 6 applies unchanged; the new file just happens to override a WooCommerce default. When recomposing a Single Product template, wrap product elements in `<!-- wp:woocommerce/single-product -->` so the Ancestor gate is satisfied.

`references/woocommerce.md` covers the **rendering surface** (blocks, templates, CSS, conditional tags, logging). For deeper WooCommerce work, load the focused companion file instead:

- **`references/woocommerce-checkout-fields.md`** — adding/modifying/validating checkout fields via `woocommerce_register_additional_checkout_field` (locations, persistence, sanitize/validate, JSON-Schema conditional logic, meta-key prefixes, stale-data trap, legacy compat).
- **`references/woocommerce-checkout-lifecycle.md`** — the client-side checkout JS model: data stores (`wc/store/checkout`), status machine, observers (`onCheckoutValidation` / `onPaymentSetup` / `onCheckoutSuccess`), SlotFill components, and native DOM events.
- **`references/woocommerce-performance.md`** — store caching/cookie exclusions, DB hygiene, GZIP, asset optimization, Core Web Vitals (store/infra config, not template authoring).
- **`references/woocommerce-standards.md`** — safe customization (child themes, hooks, snippets), PHP/JS naming, isolation mandates, Webpack dependency extraction, `is_ssl()` behind load balancers, and the QA hierarchy.

---

## Reference Files

| File | Load when… |
|---|---|
| `references/php-coding-standards.md` | Before writing any PHP — `functions.php`, `inc/**/*.php`, `render_callback` closures, or any PHP pattern file that contains function/class definitions. |
| `references/coding-standards.md` | Before writing any CSS or real HTML markup (PHP patterns, render callbacks, `<img>` etc.). |
| `references/js-coding-standards.md` | Before writing any JavaScript — `view.js` Interactivity stores, `index.js` non-reactive scripts, WooCommerce checkout JS, or abilities client-side code. |
| `references/html-conversion.md` | The user provides raw HTML/CSS/JS to convert. |
| `references/architecture.md` | Before writing ANY code (mandatory pre-write read). |
| `references/theme-json.md` | Modifying `theme.json` beyond a `customTemplates` entry. |
| `references/api-allowlist.md` | Before every `register_*` call or WP-namespaced PHP function. |
| `references/scaffold-tree.md` | Creating a new theme directory or adding new top-level folders. |
| `references/ai-client.md` | The user wants to call an LLM from PHP, register a Connector, or manage AI API keys. |
| `references/abilities-api.md` | The user is registering an ability (server or client), or needs the REST method-mapping rules for `readonly` / `destructive` / `idempotent` annotations. |
| `references/dataviews.md` | The user is building a DataViews admin screen, configuring `groupBy` / `onReset`, adding field validation, or formatting displayed values with `getValueFormatted`. |
| `references/core-7.0-apis.md` | The user asks about real-time collaboration gates, admin View Transitions, the Command Palette, password hashing / Argon2, or image LCP / `fetchpriority`. |
| `references/woocommerce.md` | The user is working with WooCommerce — overriding shop / cart / checkout / single-product / order-confirmation templates, composing `woocommerce/*` blocks, styling Cart/Checkout/Mini-Cart, registering Cart/Checkout filters, using conditional tags (`is_shop()` / `is_product()` / `is_cart()`), or calling `wc_get_logger()`. |
| `references/woocommerce-checkout-fields.md` | The user is adding, modifying, validating, or reading a custom Cart/Checkout field — `woocommerce_register_additional_checkout_field`, sanitize/validate callbacks, JSON-Schema conditional fields, or checkout meta-key access. |
| `references/woocommerce-checkout-lifecycle.md` | The user is writing Cart/Checkout JS — checkout status/observers (`onCheckoutValidation` / `onPaymentSetup` / `onCheckoutSuccess`), `wc/store/checkout` data store, SlotFill (`ExperimentalOrderMeta`, …), or native cart DOM events. |
| `references/woocommerce-performance.md` | The user is configuring store caching/cookie exclusions, fixing a cache-induced cart/login bug, or optimizing a store's Core Web Vitals / assets / database. |
| `references/woocommerce-standards.md` | The user needs safe-customization guidance (child themes / hooks / snippets), WooCommerce PHP/JS naming, conflict-free isolation, the dependency-extraction build, `is_ssl()` behind a load balancer, or QA/testing setup. |
