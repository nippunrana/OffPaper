# FSE Architecture Reference — WordPress Block Themes
*Target: WordPress 7.0 (released 2026-05-20). This reference targets WP 7.0 only. There is no fallback path.*

## Table of Contents
1. [Theme Initialization Requirements](#theme-initialization-requirements)
2. [Directory Structure](#directory-structure)
3. [Template Hierarchy & Fallback Logic](#template-hierarchy--fallback-logic)
4. [Block Markup Rules](#block-markup-rules)
5. [Native-First Conversion & Block Mapping](#native-first-conversion--block-mapping)
6. [Block Locking (Client-Proofing)](#block-locking-client-proofing)
7. [Block Bindings API (Dynamic Data)](#block-bindings-api-dynamic-data)
8. [Interactivity API (Modern JS)](#interactivity-api-modern-js)
9. [Accessibility (A11y) Standards](#accessibility-a11y-standards)
10. [PHP Pattern Rules](#php-pattern-rules)
11. [Asset Pipeline (functions.php)](#asset-pipeline-functionsphp)
12. [Site Editor Sync Behaviour](#site-editor-sync-behaviour)
13. [Child Theme Rules](#child-theme-rules)

---

## Theme Initialization Requirements

A theme MUST have all three of the following to appear in the WordPress dashboard:

| File | Purpose |
|---|---|
| `style.css` | Theme metadata (Theme Name, Version, Author, etc.) |
| `theme.json` | Global settings and styles engine |
| `templates/index.html` | Absolute fallback template — required for theme validity |

`functions.php` is optional in a pure block theme but almost always needed for enqueueing custom
assets, registering pattern categories, and adding editor styles.

---

## Directory Structure

```
theme-name/
├── style.css                     ← Theme metadata header
├── theme.json                    ← Global settings & styles
├── functions.php                 ← Optional: asset enqueueing, hooks
│
├── templates/                    ← Page-level view templates
│   ├── index.html                ← Required: absolute fallback
│   ├── singular.html             ← Posts + static pages (unified)
│   ├── single.html               ← Individual posts only
│   ├── page.html                 ← Static pages only
│   ├── archive.html              ← Archive/category views
│   ├── search.html               ← Search results
│   ├── 404.html                  ← Error page
│   └── {custom-slug}.html        ← Custom templates (registered in theme.json)
│
├── parts/                        ← Reusable template parts
│   ├── header.html
│   ├── footer.html
│   └── sidebar.html
│
├── patterns/                     ← Block patterns (PHP files)
│   └── my-section.php
│
└── assets/
    ├── fonts/
    ├── images/
    └── css/
```

For **modular templates** (custom landing pages), co-locate assets in the patterns directory:

```
templates/
  my-landing-page.html            ← Template entry point
patterns/
  my-landing-page.php             ← Master pattern (assembler)
  my-section.php                  ← Sub-pattern
  my-section/
    style.css                     ← Scoped CSS for sub-pattern
    view.js                       ← Reactive Script Module (Interactivity API / shared state)
    index.js                      ← Non-reactive JS (animations, observers — no shared state)
```

---

## Template Hierarchy & Fallback Logic

WordPress resolves which template to use by walking this chain (first match wins):

```
Custom Template (page attribute) 
  → front-page.html 
  → home.html 
  → page-{slug}.html 
  → page-{ID}.html 
  → page.html 
  → singular.html 
  → index.html
```

### Template Registration API (PHP) — PLUGINS ONLY

`register_block_template()` (introduced in 6.7) registers a template from PHP. **This is for plugins only.** In a block theme, templates are placed physically in `templates/` and registered via `theme.json` `customTemplates`. Using `register_block_template()` for a template that also has a physical `templates/*.html` file is silent double-registration — one of the hardest FSE bugs to diagnose.

For plugin developers: the template name must be in the form `namespace//template_name` (two slashes). Supported `$args` keys are `title`, `description`, `content`, and `post_types`. See developer.wordpress.org/reference/functions/register_block_template/ for the full reference.

For archives:
```
category-{slug}.html → category-{ID}.html → category.html → archive.html → index.html
```

For search:
```
search.html → index.html
```

**Custom templates** (created via `customTemplates` in `theme.json`) appear in the Page
Attributes panel and bypass the hierarchy when explicitly assigned.

---

## Block Markup Rules

### The Golden Rule

Every piece of raw HTML inside a template or pattern MUST be wrapped in block comment tags.
WordPress strips any content outside recognized block boundaries when saving through the editor.

```html
<!-- Correct: raw HTML rendered by a dynamic block registered via register_block_type() -->
<!-- wp:my-theme/legacy-section /-->

<!-- Correct: native WP block — no wrapper needed -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Welcome</h1>
<!-- /wp:heading -->

<!-- WRONG: raw HTML outside any block wrapper — will be stripped -->
<section class="hero">
  <h1>Welcome</h1>
</section>
```

### Common Block Syntax Reference

```html
<!-- Pattern reference -->
<!-- wp:pattern {"slug":"theme-slug/pattern-name"} /-->

<!-- Post content (required in all page templates) -->
<!-- wp:post-content {"layout":{"type":"constrained"}} /-->

<!-- Group block (div wrapper) -->
<!-- wp:group {"tagName":"section","className":"my-section"} -->
<section class="wp-block-group my-section">
  <!-- inner blocks -->
</section>
<!-- /wp:group -->

<!-- Template part reference -->
<!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->

<!-- Image block -->
<!-- wp:image {"id":123,"sizeSlug":"full"} -->
<figure class="wp-block-image size-full">
  <img src="..." alt="..." />
</figure>
<!-- /wp:image -->

<!-- Query loop (for archive/search templates) -->
<!-- wp:query {"queryId":1,"query":{"inherit":true}} -->
<div class="wp-block-query">
  <!-- wp:post-template -->
    <!-- wp:post-title /-->
    <!-- wp:post-excerpt /-->
  <!-- /wp:post-template -->
</div>
<!-- /wp:query -->
```

### Block Attributes JSON

Block attributes go in the opening comment, not as HTML attributes:

```html
<!-- wp:group {
  "tagName": "section",
  "align": "full",
  "className": "hero-section",
  "style": {"spacing": {"padding": {"top": "4rem", "bottom": "4rem"}}},
  "lock": {"move": true, "remove": true}
} -->
```

---

## Native-First Conversion & Block Mapping

Map every part of the design to a Core Block first. Only fall back to a dynamic block (`register_block_type()` + `render_callback`) when no core block fits — that keeps colors, typography, and spacing under Site Editor control.

| HTML Tag | Core Block | Attributes to use |
|---|---|---|
| `<div>` (wrapper) | `core/group` | `tagName: "div"`, `layout: { "type": "constrained" }` |
| `<section>` | `core/group` | `tagName: "section"` |
| `<h1>` – `<h6>` | `core/heading` | `level: 1-6`. Editors can always change the level via the block toolbar. WP 7.0 also registers H1–H6 as named inserter-level variations, but the block name in markup is always `core/heading`. |
| `<p>` | `core/paragraph` | |
| `<ul>` / `<ol>` | `core/list` | |
| `<img>` | `core/image` | |
| SVG icon (core/built-in) | `core/icon` (WP 7.0) | Use `{"icon":"core/..."}` for built-in icons — always safe. For custom theme icons see the `core/icon` decision rule in the Block Bindings section. |
| 2–3 columns | `core/columns` | For 4+ columns or auto-fitting layouts, use `core/group` with `layout.type = "grid"` instead. |
| Breadcrumb trail | `core/breadcrumbs` (WP 7.0) | Auto-renders the page hierarchy. Use `block_core_breadcrumbs_items` to customise the trail (e.g. inject a custom segment, hide ancestors). |
| Video background `<section>` | `core/cover` with embedded video (WP 7.0) | Cover block accepts YouTube/Vimeo URLs as background, not just locally uploaded files. |
| Gallery with full-screen view | `core/gallery` with lightbox (WP 7.0) | Set `lightbox: true`; in 7.0 the lightbox supports arrow-key navigation between images. |
| Mobile nav close button | `core/navigation-overlay-close` (WP 7.0) | Place inside a `core/navigation` block with `"overlayMenu": "always"` or `"overlayMenu": "mobile"`. |

### Native-First Decision Algorithm

When mapping a design element to a block, walk this tree in order. Stop at the first match. Only fall back to `register_block_type()` when no branch above applies.

```
Is it text content?
  → Heading (any level)?          → core/heading  (level: 1–6; toolbar always allows level switching)
  → Paragraph?                    → core/paragraph (add textIndent / textColumns if needed)
  → List?                         → core/list + core/list-item

Is it a layout wrapper?
  → 1–3 equal columns?            → core/columns + core/column
  → 4+ columns OR auto-fit grid?  → core/group  layout.type = "grid"
  → Full-bleed section (bg image/video)?  → core/cover
  → Any other div/section?        → core/group  (tagName: "section"/"div"/"article" etc.)

Is it media?
  → Single image?                 → core/image
  → Gallery?                      → core/gallery
  → Video embed (YT/Vimeo)?       → core/embed
  → Inline SVG icon (core set)?   → core/icon (WP 7.0); for custom theme icons see decision rule in Block Bindings section

Is it navigation?
  → Site menu?                    → core/navigation
  → Mobile overlay close button?  → core/navigation-overlay-close (WP 7.0)
  → Breadcrumbs?                  → core/breadcrumbs (WP 7.0)

Is it interactive / dynamic?
  → Reads post data (title, content, meta)?  → Block Bindings (core/post-meta etc.)
  → Needs frontend JS reactivity?            → Interactivity API + store() + data-wp-* directives
  → Must react to state changes sans user event?  → watch() or data-wp-watch
  → None of the above?                       → register_block_type() + render_callback
  → Truly cannot be expressed as any block?  → register_block_type() with 'supports' => ['autoRegister' => true]
                                                (PHP-only, no block.json, no JS — WP 7.0 generates inspector UI automatically)
```

**NEVER use `<!-- wp:html -->`** on WP 7.0 targets. It is a legacy escape hatch removed from the recommended path. WordPress strips unrecognized HTML at save time; a PHP-only block with `autoRegister: true` is the correct last resort.

### Core Blocks new in WP 7.0

- **`core/breadcrumbs`** — automatic page hierarchy. The shipped trail is filterable via `block_core_breadcrumbs_items`:

  ```php
  add_filter( 'block_core_breadcrumbs_items', function( array $items, WP_Block $block ): array {
      // Each item: ['label' => string, 'url' => string, 'allow_html' => bool]
      // Sanitization: allow_html:true → wp_kses_post(); allow_html:false/omitted → esc_html()
      // Set allow_html:true only when intentionally injecting safe HTML (e.g. SVG home icon).
      return $items;
  }, 10, 2 );
  ```

- **`core/icon`** — server-side rendered, backed by `/wp/v2/icons`. Register theme icons against the Icon Registration API so they appear in the inserter alongside core's curated set. See the `core/icon` decision rule below for the definitive WP 7.0 approach.

- **`core/cover` enhancements** — embedded YouTube/Vimeo videos as background; focal-point control on fixed backgrounds.

- **`core/gallery` enhancements** — lightbox now navigates between images with the arrow keys.

### Custom Navigation Overlays

Theme developers can design mobile navigation overlays using blocks (WP 7.0). Four-step setup:

1. **Register area** — in `theme.json` under `templateParts`, add `{"area": "navigation-overlay", "name": "mobile-menu", "title": "Mobile Menu"}`.
2. **Create the HTML file** — in `/parts/mobile-menu.html`. Always include `core/navigation-overlay-close` explicitly — omitting it forces a theme-clashing fallback close button.
3. **Register as a pattern** — set `Block Types: core/template-part/navigation-overlay` in the pattern header so WordPress treats it as the active overlay for that area.
4. **Overlay attribute** — in the Navigation block's `overlay` attribute, use the **slug only** (no theme prefix, e.g. `"overlay": "mobile-menu"`) for future-proofing across theme switches.

> **Limitation:** Custom overlays are tied to the active theme and are NOT preserved during theme switches.

---

### Native Grid layout (WP 7.0 hybrid mode)

WordPress 7.0 lets the Grid layout use `columnCount` and `minimumColumnWidth` at the same time. Set both to get a responsive grid that respects a maximum column count but reflows on narrow viewports.

```html
<!-- wp:group {
  "tagName": "section",
  "layout": {
    "type": "grid",
    "columnCount": 3,
    "minimumColumnWidth": "280px"
  }
} -->
<section class="wp-block-group">
  <!-- cards go here -->
</section>
<!-- /wp:group -->
```

In WP 7.0, both `columnCount` and `minimumColumnWidth` can be set simultaneously for responsive grids.

---

## Dynamic Blocks (PHP-only) — WP 7.0

If no Core Block fits a section of the design, register a dynamic block instead of pasting raw HTML. WordPress 7.0 introduces the `autoRegister` flag, which lets you register a block entirely from PHP — no `block.json`, no JavaScript, no build step.

```php
add_action( 'init', function(): void {
    register_block_type( '{{THEME_SLUG}}/legacy-section', array(
        'title'       => __( 'Legacy Section', '{{TEXT_DOMAIN}}' ),
        'description' => __( 'A custom layout that does not fit core blocks.', '{{TEXT_DOMAIN}}' ),
        'category'    => 'theme',
        'supports'    => array(
            'autoRegister' => true,
            'html'         => false,
        ),
        'attributes'  => array(
            'heading' => array(
                'type'    => 'string',
                'default' => '',
            ),
        ),
        'render_callback' => function( array $attributes, string $content, WP_Block $block ): string {
            $heading = esc_html( $attributes['heading'] ?? '' );
            return sprintf(
                '<section class="legacy-section"><h2>%s</h2>%s</section>',
                $heading,
                wp_kses_post( $content )
            );
        },
    ) );
} );
```

With `autoRegister => true`, WordPress generates the inserter UI from the `attributes` array and renders the block via `ServerSideRender` in the editor. Use this for static or mostly-static dynamic markup that does not need interactive React controls.

**Requirements and constraints:**
- `render_callback` is **mandatory** — `autoRegister` blocks have no JS editor component; the callback is the only render path.
- Supported attribute types: `string`, `number`, `integer`, and `boolean`. Attributes that are complex objects, arrays, or other types are not auto-generated as Inspector Controls.
- **Sourced attributes are not supported.** Attributes whose value is "sourced" from the saved HTML (e.g. `"source": "html"`, `"source": "attribute"`) cannot be used with `autoRegister` — all attribute values must be stored in the block's JSON boundary (the opening comment). Do not attempt to use `"source"` keys in the attributes array of an `autoRegister` block.

The `autoRegister` flag is a WP 7.0 feature. Do not add fallback code — this skill targets WP 7.0 only.

---

## Block Locking (Client-Proofing)

Prevent clients from accidentally deleting or moving critical structural elements.

```json
"lock": {
  "move": true,
  "remove": true
}
```

- `move`: Prevents the block from being dragged to a different position.
- `remove`: Prevents the block from being deleted.

Apply this to the outermost wrapper of your Patterns and Template Parts.

### Content-Only Locking

**WP 7.0: `contentOnly` is the default for unsynced patterns.** Do NOT add `"templateLock": "contentOnly"` to unsynced patterns — it is already active, and adding it redundantly has no effect but signals a misunderstanding of the default.

**When to still apply `"templateLock": "contentOnly"` explicitly:**
- Synced patterns (where the default behaviour differs)
- Template parts where you want to prevent editors from restructuring internal layout

**When to opt OUT (allow structure editing on an unsynced pattern):**

Two scopes, choose the right one:

| Scope | Mechanism | Stability |
|---|---|---|
| Per-pattern (one pattern only) | Add `"__experimentalSettings": {"disableContentOnlyForUnsyncedPatterns": true}` to the outermost block's `attributes` | Experimental — prefix may change |
| Site-wide (all unsynced patterns) | `disableContentOnlyForUnsyncedPatterns` key inside `block_editor_settings_all` (PHP) or equivalent JS `addFilter` on `blockEditor.settings` | Stable WP 7.0 API |

Use the per-pattern approach when one specific assembler pattern needs full structure editing (e.g. a developer scaffold). Use the site-wide filter only when the client has explicitly opted the entire site out of content-only defaults — this is rare.

**Decision tree:**
1. Unsynced pattern → do nothing; `contentOnly` is already active.
2. Synced pattern or template part with locked structure → add `"templateLock": "contentOnly"` explicitly.
3. One specific unsynced pattern needs structure editing → add `"__experimentalSettings": {"disableContentOnlyForUnsyncedPatterns": true}` to its outermost block attributes.
4. All unsynced patterns on the site need structure editing → use the `disableContentOnlyForUnsyncedPatterns` key inside `block_editor_settings_all` (see `api-allowlist.md → contentOnly Pattern Opt-Out`).

### Isolated Editor Mode (WP 7.0)

WP 7.0 introduces **Isolated Editor Mode** for synced patterns and template parts. Editors can now click **Edit pattern** on any synced pattern or template part to enter a full-screen editing context — complete structure editing without navigating away from the page.

**What this means for your locking strategy:**
- `contentOnly` locking on synced patterns is still correct for day-to-day content editing (text, images, CTA copy).
- Editors no longer need to navigate to Appearance → Editor → Patterns for structural changes — **Edit pattern** is the built-in escape hatch.
- Do not add excessive locks that fight this UX. Lock what should never move (structural blocks, template part wrappers). Trust Isolated Editor Mode for intentional structural edits by editors.
- Inform clients: "To change the layout, click the pattern → Edit pattern. To change just the text or image, click directly on the content block."

### Block Author Requirements for contentOnly Compatibility (WP 7.0)

**This applies to every custom block you register.** When a custom block is nested inside a `contentOnly` pattern or template part, WordPress uses `"role": "content"` declarations in `block.json` to decide which attributes (and therefore which blocks) are visible to editors in List View.

Without these declarations, the block is **hidden from List View and non-selectable** in content-only containers — a silent breakage with no console error.

**Required in `block.json`:** Add `"role": "content"` to every attribute that editors need to see or edit:

```json
{
  "attributes": {
    "heading":     { "type": "string", "role": "content" },
    "description": { "type": "string", "role": "content" },
    "url":         { "type": "string", "role": "content" }
  }
}
```

**Alternative (when no suitable attribute exists):** Add `"contentRole": true` to the block's `supports` object. This makes the block itself visible in List View without requiring a specific content attribute, but `"role": "content"` on attributes is preferred because it exposes fine-grained overridability:

```json
{
  "supports": {
    "contentRole": true
  }
}
```

**`"listView": true` block support (WP 7.0):** For container blocks (blocks with `InnerBlocks`), declaring `"listView": true` in `supports` adds a dedicated **List View tab** to the block inspector, allowing editors to rearrange and insert inner blocks from the sidebar. Recommended for any block that wraps multiple children:

```json
{
  "supports": {
    "listView": true
  }
}
```

**Blocks to audit when working inside contentOnly patterns:** `core/buttons`, `core/list`, `core/social-links`, `core/navigation`. These core blocks already declare content roles — verify your custom wrappers that contain them do the same.

---

## Block Bindings API (Dynamic Data)

Introduced in 6.5, the Block Bindings API connects Core Blocks to dynamic data without writing custom patterns or React. In WP 7.0, any block attribute that supports bindings also supports Pattern Overrides.

Built-in sources: `core/post-meta`, `core/post-title`, `core/post-excerpt`, `core/post-date`, `core/post-author-name`, `core/site-title`, `core/site-tagline`, `core/pattern-overrides`.

```html
<!-- wp:paragraph {
  "metadata": {
    "bindings": {
      "content": {
        "source": "core/post-meta",
        "args": { "key": "my_custom_field" }
      }
    }
  }
} -->
<p>Fallback content shown if meta is empty.</p>
<!-- /wp:paragraph -->
```

### Registering a Custom Binding Source

To expose theme-specific data to the binding UI:

```php
add_action( 'init', function(): void {
    register_block_bindings_source( '{{THEME_SLUG}}/custom-source', array(
        'label'              => __( 'Custom Source', '{{TEXT_DOMAIN}}' ),
        'get_value_callback' => '{{THEME_SLUG}}_get_binding_value',
        'uses_context'       => array( 'postId' ),
    ) );
} );
```

See developer.wordpress.org/reference/functions/register_block_bindings_source/.

### `core/icon` block and theme icons (WP 7.0)

WP 7.0 ships a `core/icon` block backed by a REST endpoint at `/wp/v2/icons`. In WP 7.0, the icon registry is internal — **there is no public API for registering custom theme icons against `core/icon`**. The public registration API (`register_icon()`, `wp_register_icon_collection()`) is planned for WP 7.1.

**Decision rule — core/icon vs. custom SVG (WP 7.0):**

| Scenario | Approach |
|---|---|
| Built-in core icons (e.g. `core/star`, `core/close`) | Use `<!-- wp:icon {"icon":"core/..."} /-->` — always safe |
| Custom theme icons | Output inline SVG from a dynamic block's `render_callback`, or use an `<img>` element with `get_stylesheet_directory_uri()`. Do NOT attempt to register them against `core/icon`. |

Do NOT use `<!-- wp:icon {"icon":"{{THEME_SLUG}}/..."} /-->` — custom namespace slugs are unregistered in WP 7.0 and the block renders empty with no error.

---

## Abilities API (WP 7.0)

The Abilities API is stable in WordPress 7.0. Themes and plugins register named "abilities" — server-side callbacks with JSON Schema input/output contracts — that the WP AI Client and other tools can discover and invoke.

The registration sequence is: register an ability **category** first (on `wp_abilities_api_categories_init`), then register the ability itself (on `wp_abilities_api_init`). These are **two separate hooks** — do not register the category inside `wp_abilities_api_init` or it will not exist when the ability tries to reference it.

```php
// Step 1: register the category.
add_action( 'wp_abilities_api_categories_init', function() {
    wp_register_ability_category( '{{THEME_SLUG}}/editorial', array(
        'label'       => __( 'Editorial Tools', '{{TEXT_DOMAIN}}' ),
        'description' => __( 'AI-assisted writing helpers for this theme.', '{{TEXT_DOMAIN}}' ),
    ) );
} );

// Step 2: register the ability (category must already exist).
add_action( 'wp_abilities_api_init', function() {
    wp_register_ability( '{{THEME_SLUG}}/tone-shift', array(
        'label'              => __( 'Tone Shift', '{{TEXT_DOMAIN}}' ),
        'description'        => __( 'Rewrites the selected copy in a chosen tone.', '{{TEXT_DOMAIN}}' ),
        'category'           => '{{THEME_SLUG}}/editorial',
        'input_schema'       => array(
            'type'       => 'object',
            'properties' => array(
                'text' => array( 'type' => 'string' ),
                'tone' => array(
                    'type' => 'string',
                    'enum' => array( 'scientific', 'casual', 'formal' ),
                ),
            ),
            'required'   => array( 'text', 'tone' ),
        ),
        'output_schema'      => array(
            'type'       => 'object',
            'properties' => array(
                'rewritten' => array( 'type' => 'string' ),
            ),
        ),
        'permission_callback' => function(): bool {
            return current_user_can( 'edit_posts' );
        },
        'execute_callback'    => function( array $input ): array {
            // Call the WP AI Client (or any provider) here and return the result.
            return array( 'rewritten' => $input['text'] );
        },
    ) );
} );
```

The ability name must be lowercase, namespaced (`prefix/name`), and may contain only letters, digits, dashes, and forward slashes. See developer.wordpress.org/reference/functions/wp_register_ability/ for the full reference. Categories are documented at developer.wordpress.org/apis/abilities-api/.

### WP AI Client + Connectors (WP 7.0)

The WP AI Client is a provider-agnostic PHP wrapper around the AI providers configured at **Settings → Connectors**. Out of the box, WP 7.0 ships connectors for OpenAI, Anthropic (Claude), and Google (Gemini); third-party providers can register their own via the `wp_connectors_init` hook.

The entry point is `wp_ai_client_prompt( $text = '' )`, which returns a `WP_AI_Client_Prompt_Builder` instance. Always gate calls behind a static feature-detection method (e.g. `WP_AI_Client_Prompt_Builder::is_supported_for_text_generation()`) so you fail fast when no capable provider is configured.

Inside an ability's `execute_callback`:

```php
'execute_callback' => function( array $input ): array|\WP_Error {
    if ( ! \WP_AI_Client_Prompt_Builder::is_supported_for_text_generation() ) {
        return new \WP_Error( 'no_text_provider', __( 'No AI provider is configured for text generation.', 'my-plugin' ) );
    }

    $rewritten = wp_ai_client_prompt()
        ->using_system_instruction( 'You are an editorial assistant.' )
        ->using_temperature( 0.4 )
        ->with_text( sprintf( "Rewrite this text in a %s tone:\n\n%s", $input['tone'], $input['text'] ) )
        ->generate_text();

    if ( is_wp_error( $rewritten ) ) {
        return $rewritten;
    }
    return array( 'rewritten' => $rewritten );
},
```

For token-usage or provider-metadata access, swap `generate_text()` for `generate_text_result()` — it returns a `GenerativeAiResult` with `getTokenUsage()`, `getProviderMetadata()`, and `getModelMetadata()`. The user must enable at least one provider at Settings → Connectors for generation to succeed; otherwise the call returns `WP_Error`. See `references/ai-client.md` for the full builder surface, the Connectors registry, and credential resolution order.

---

## Interactivity API: Native Asset Loading (Script Modules)

Use the **Script Modules API** to ship Interactivity API code so it loads only when the relevant block is on the page.

**Step 1: Register the script module in PHP**

File naming convention — **must be followed consistently**:
- `view.js` → reactive Script Module (Interactivity API store, `data-wp-*` directives, shared state)
- `index.js` → non-reactive script (IntersectionObserver, GSAP, scroll animations — read-only DOM effects with no shared state)

```php
wp_register_script_module(
    id:      'my-theme/logic',
    src:     get_stylesheet_directory_uri() . '/patterns/my-pattern/view.js',  // reactive → view.js
    deps:    array( '@wordpress/interactivity' ),
    version: '1.0.0'
);
```

**Step 2: Bind to the block variation**
```php
register_block_style( 'core/group', array(
    'name'                 => 'my-pattern',
    'script_module_handle' => 'my-theme/logic',
));
```

WordPress now automatically enqueues the module only when a block with the `is-style-my-pattern` class is present.

**Step 3 (optional): Translations for the module (WP 7.0).** Script Modules use their own i18n path, separate from classic `wp_set_script_translations()`. Point a module at its JSON translation files with `wp_set_script_module_translations( string $id, string $domain = 'default', string $path = '' ): bool`, and (when loading data manually) read them with `load_script_module_textdomain( string $id, string $domain = 'default', string $path = '' ): string|false`. Both are WP 7.0+. Use `wp_set_script_module_translations()` right after registering the module; in JS the strings resolve through the standard `@wordpress/i18n` import.

```php
wp_set_script_module_translations( 'my-theme/logic', '{{TEXT_DOMAIN}}' );
```

**Elite Automation: Block Hooks**
For mandatory pattern logic that should never be missed by clients, use Block Hooks to wrap the pattern in a logic-providing block automatically:

```php
add_filter( 'hooked_block_types', function( array $hooked_blocks, string $relative_block_type, string $section, mixed $context ): array {
    if ( '{{THEME_SLUG}}/my-pattern' === $relative_block_type && 'after' === $section ) {
        $hooked_blocks[] = 'my-theme/logic-provider-block';
    }
    return $hooked_blocks;
}, 10, 4 );
```

**WP 7.0: Block Hooks across all content-like CPTs.** In WP 7.0, the Block Hooks resolver moved from individual post-type filters into the REST controller. Block Hooks now fire automatically for all custom post types registered with `'show_in_rest' => true` and `'supports' => ['editor']`. The previous workaround of filtering `wp_block_editor_settings` per-CPT is no longer needed on WP 7.0.

**Scoping which post types run Block Hooks in the REST API (WP 7.0).** Because the resolver runs inside the Posts controller, every eligible post type pays the parsing cost on each REST read/write. Use the `rest_block_hooks_post_types` filter (WP 7.0+) to widen or narrow that set — whitelist the post types that actually need injected blocks and drop the ones that don't (attachments, logs, private CPTs) to cut JSON latency on busy sites:

```php
add_filter( 'rest_block_hooks_post_types', function ( array $post_types ): array {
    $wanted = array_filter( array( 'portfolio', 'documentation' ), 'post_type_exists' );
    return array_values( array_unique( array_merge( $post_types, $wanted ) ) );
} );
```

The injection itself is done by core internals you normally don't call directly: `insert_hooked_blocks_into_rest_response()` (since 6.6) adds the first/last inner blocks to the Posts REST response, and `apply_block_hooks_to_content_from_post_object()` (since 6.8) runs the algorithm on a post object server-side. Both are marked **private** — drive behaviour through the filter above, not by calling them.

**Resolving pattern references in a block tree.** `resolve_pattern_blocks( array $blocks ): array` (since 6.6, used more widely in 7.0) walks a parsed block tree and replaces `core/pattern` references with their actual constituent blocks. Relevant for headless/agent code that consumes `parse_blocks()` output and needs a fully-expanded tree rather than unresolved pattern placeholders. It is not needed for normal template/pattern authoring — WordPress resolves patterns during render automatically.

---

## Interactivity API (Modern JS)

The Interactivity API is the modern standard for frontend logic. It uses declarative markup instead of procedural jQuery.

```html
<div 
  data-wp-interactive="my-theme/navigation" 
  data-wp-context='{ "isOpen": false }'
>
  <button 
    data-wp-on--click="actions.toggleMenu"
    data-wp-bind--aria-expanded="context.isOpen"
  >
    Toggle Menu
  </button>
  
  <nav data-wp-bind--hidden="!context.isOpen">
    <!-- Menu items -->
  </nav>
</div>
```

*Note: Requires registering the store in `view.js` using `store()`. Elite usage avoids `event.target.style` and instead toggles state attributes or CSS classes via `data-wp-class` or `data-wp-style`.*

### Reactive Signal Subscriptions: `watch()` and `data-wp-watch` (WP 7.0)

Use `watch()` when a block must react to state changes without a direct user event — e.g. syncing a cart count badge when items are added from another block.

**Programmatic (`view.js`):**

```js
import { store, watch } from '@wordpress/interactivity';

const { state } = store( 'my-theme/cart', {
    state: { count: 0 },
    callbacks: {
        onCountChange() {
            // Runs once on init and again whenever state.count changes.
            document.title = `(${state.count}) My Shop`;
        },
    },
} );

// Returns a teardown function — call it to remove the subscription.
const unwatch = watch( () => {
    console.log( 'Cart count:', state.count );
} );
```

**Declarative (markup):**

```html
<div
  data-wp-interactive="my-theme/cart"
  data-wp-watch="callbacks.onCountChange"
>
  <!-- Callback fires whenever any reactive state it reads changes. -->
</div>
```

Decision: use `data-wp-watch` when the side effect is tied to a specific DOM element. Use programmatic `watch()` when the side effect is global (e.g. updating `document.title` or writing a cookie).

#### `state.url` is server-populated (WP 7.0)

In WP 7.0 the `core/router` store exposes a `state.url` field that is **populated server-side on initial page load** — earlier versions only set it after the first client-side navigation. Combined with `watch()`, this gives a single, reliable hook for SPA-style analytics and side effects that previously needed a `DOMContentLoaded` + history-API combo:

```js
import { store, watch } from '@wordpress/interactivity';
const { state } = store( 'core/router' );

watch( () => {
    sendAnalyticsPageView( state.url );
} );
```

The callback fires once on initial load (because `state.url` is already populated) and again on every client-side route change. Do not read `window.location.href` for this purpose — it bypasses the router and misses post-route updates.

---

## Synced Pattern Overrides

For sections like Heroes or CTAs reused across multiple pages, use Synced Patterns with Overrides:

1. **Sync the design** — changing the layout/CSS in the pattern updates every instance.
2. **Override the content** — editors change text/images on a per-page basis without breaking the sync.

In WP 7.0, any block attribute that supports Block Bindings also supports Pattern Overrides, including custom blocks.

**How to mark a block as overridable.** Inside the pattern, set `metadata.bindings` to point at the `core/pattern-overrides` source and give the binding a stable `metadata.name`:

```html
<!-- wp:heading {
  "level": 1,
  "metadata": {
    "name": "hero-headline",
    "bindings": {
      "content": { "source": "core/pattern-overrides" }
    }
  }
} -->
<h1 class="wp-block-heading">Default headline</h1>
<!-- /wp:heading -->
```

**Editor UI.** In the editor, editors select the block, open the block sidebar, expand **Advanced**, and click **Enable overrides**. Once enabled, instances of the synced pattern can override the marked attributes while keeping the rest of the design in sync.

### Pattern Overrides for Custom Blocks (WP 7.0)

Prior to 7.0, Pattern Overrides were limited to core blocks that natively supported `metadata.bindings`. WP 7.0 expands this via the `block_bindings_supported_attributes` PHP filter, allowing any custom block to expose its attributes to the Pattern Overrides UI.

```php
add_filter(
    'block_bindings_supported_attributes',
    function( array $supported, string $block_name ): array {
        if ( '{{THEME_SLUG}}/product-card' === $block_name ) {
            $supported[] = 'heading';
            $supported[] = 'description';
        }
        return $supported;
    },
    10,
    2
);
```

After this filter is registered, a synced pattern containing a `{{THEME_SLUG}}/product-card` block will show the "Enable overrides" toggle for the `heading` and `description` attributes in the block sidebar. The custom block must declare these attributes in its `block.json` or `register_block_type()` `attributes` array.

---

## Viewport Block Visibility (WP 7.0)

Control which device types see a block using `metadata.blockVisibility.viewport`. This is the WordPress-native mechanism — do not use CSS `display: none` on device breakpoints for this purpose.

**Naming convention disambiguation:** The block *support* key is `visibility` (what you declare in `supports`), but the attribute *metadata* key that holds the settings is `blockVisibility`. These are two different keys — do not conflate them.

**Server-side parsing:** When parsing block markup (e.g., with the HTML API or `parse_blocks()`), the `blockVisibility` field may appear as either:
- A **boolean** (scalar) — legacy form, `true` means visibility controls are active globally.
- An **object** — WP 7.0 form, with a `viewport` array specifying device types.
Code that reads `blockVisibility` must handle both forms gracefully.

**Important (WP 7.0):** Viewport hiding is **CSS-based**. Hidden blocks are still rendered in the DOM on all devices — they are visually suppressed via an injected CSS class. This means: server-side DOM parsers will still see the block, JavaScript querying the DOM will still find it, and search engines may still index the content. Use this feature for visual responsive layout only, not for access control or security gating.

### Decision rule

| Goal | Setting |
|---|---|
| Show on all devices | Omit `blockVisibility` entirely (default) |
| Show on desktop only | `"viewport": ["desktop"]` |
| Hide on mobile only | `"viewport": ["tablet", "desktop"]` |
| Show on mobile only | `"viewport": ["mobile"]` |
| Hide on all devices | `"viewport": []` ← avoid; omit key instead |

### Enable in `theme.json`

```json
"settings": {
  "blockVisibility": { "viewport": true }
}
```

To enable only for a specific block type:

```json
"settings": {
  "blocks": {
    "core/group": { "blockVisibility": { "viewport": true } }
  }
}
```

### Block markup example

```html
<!-- wp:group {
  "metadata": {
    "blockVisibility": {
      "viewport": ["tablet", "desktop"]
    }
  }
} -->
<div class="wp-block-group">
  <!-- Visually hidden on mobile via injected CSS class; block still present in DOM. -->
</div>
<!-- /wp:group -->
```

---

## Font Library (WP 7.0)

The Font Library is now enabled for **all theme types** — block, hybrid, and classic. Site admins manage installed fonts via Appearance → Editor → Styles → Typography → Manage Fonts. Font Library fonts are stored in `wp-content/fonts/` and survive theme switches.

### What this means for theme development

- Fonts registered in `theme.json fontFamilies` (file-based, version-controlled) and fonts installed via the Font Library (database-based) both appear in the editor's Typography panel.
- There is no PHP API for the Font Library — it is editor-only.

### Agent rule

- User says "add a font" → use `theme.json fontFamilies` (version-controlled, tracked in git).
- User says "install a font" or "the font picker is missing X" → direct them to Appearance → Editor → Styles → Typography → Manage Fonts.
- Do NOT enqueue fonts via `wp_enqueue_style` that already exist in the Font Library — it creates duplicates.

### Iframed Editor Enforcement (Block API v3)

The post editor is **iframed by default** when every block in use declares `"apiVersion": 3` in its `block.json`. This eliminates the shared DOM between the editor and the frontend.

**Dynamic fallback:** If a Block API v2 (or lower) block is inserted during the editing session, the editor **dynamically switches out of iframe mode** in real time to ensure the legacy block functions correctly. This means a single v2 block anywhere on the page reverts the entire editing context to non-iframed mode — there is no per-block exception.

Consequences for theme development:
- Scripts targeting `document` or `window` directly will NOT fire inside the editor iframe. Always use Interactivity API directives (`data-wp-*`) — never bare `document.addEventListener` in theme JS.
- Custom blocks that ship with a `block.json` MUST declare `"apiVersion": 3`. Using any v1 or v2 block degrades the entire editor to the legacy non-iframed mode.
- **Exception:** PHP-only blocks registered with `'supports' => ['autoRegister' => true]` have **no `block.json`**. WordPress automatically assigns these blocks the current API version — do not generate a `block.json` alongside them. The `apiVersion: 3` requirement applies only to blocks that explicitly ship a `block.json`.
- **Editor-side JavaScript:** For custom inspector controls or sidebar panels, use `enqueue_block_editor_assets` — NOT `admin_enqueue_scripts`. Scripts on `admin_enqueue_scripts` load in the parent window frame and cannot reach the iframed editor DOM.

To verify: open a template in the Site Editor and run `window.frameElement` in the browser console. If it returns an `<iframe>` element, the iframed editor is active.

---

## Speculation Rules API (Instant Transitions)

In 2026, elite themes feel like SPAs. Use the Speculation Rules API to pre-render links:

```php
add_action( 'wp_head', function() {
    ?>
    <script type="speculationrules">
    {
      "prerender": [
        {
          "source": "list",
          "urls": ["/shop", "/contact"]
        },
        {
          "where": { "href_matches": "/*" },
          "eagerness": "moderate"
        }
      ]
    }
    </script>
    <?php
}, 1 );

/**
 * Optional: inject additional speculation rules based on the current page.
 */
add_action( 'wp_footer', function() {
    if ( is_page( 'multi-step-form' ) ) {
        // Inject extra speculation rules for the next step in the flow.
    }
} );
```

---

## Data Views

For custom post types, you can customise the Site Editor's Data View by filtering the REST query parameters. This gives editors a tailored grid/list/table view of their content.

```php
add_filter( 'block_editor_rest_api_get_items_query_params', function( $params, $post_type ) {
    if ( 'lab_result' === $post_type ) {
        // Adjust how items are fetched/displayed in the Site Editor Data View.
    }
    return $params;
}, 10, 2 );
```

---

## Accessibility (A11y)

1. **Semantic landmarks**: use `tagName: "header"`, `"footer"`, `"main"`, `"section"` on Group blocks.
2. **ARIA labels**: add `aria-label` to navigation blocks and any unlabelled interactive buttons.
3. **Alt text**: every `core/image` block needs an `alt` attribute, or a binding that supplies dynamic alt text.
4. **Focus states**: never disable focus outlines without providing a visible, high-contrast alternative.

---

## WordPress 7.0 Core Environment

| Component | Requirement / Change |
|---|---|
| **PHP minimum** | WordPress 7.0 requires PHP **7.4** as the absolute floor. **This skill always generates PHP 8.3+ code** (typed parameters, `match`, null-safe operator, `str_contains`, etc.) — the deployment server must run PHP ≥ 8.3 to execute the generated output. Never downgrade the generated code style to target a lower version. |
| **PHPMailer** | Updated to 7.0.2 in WP 7.0. |
| **JS linting** | Transitioned to **Espree** (ES6+). ESLint configs using legacy Esprima or Acorn parsers should be updated to the Espree parser for full ES6+ support. |
| **User registration security** | `Administrator` and `Editor` roles are **removed** from the default new-user registration role selector to prevent accidental privilege escalation. Use `default_role_dropdown_excluded_roles` filter (`api-allowlist.md → User registration`) to modify the exclusion list. |

---

## PHP 8.3 Requirements

Every PHP file emitted by this skill must pass all ten gates below before writing. These are pre-write checks — missing any one means the code is rejected and rewritten.

1. `<?php` on line 1.
2. `declare(strict_types=1);` on line 2 (full files only — not excerpt snippets that begin mid-function or with a bare `add_action(` call). **PHP block pattern files that contain only the docblock header and block markup with no function definitions are also exempt** — strict_types has no effect without typed function signatures, so omit it to keep the header uncluttered.
3. Every named function has typed parameters and a return type: `function my_fn( string $arg ): void {}`.
4. Anonymous callbacks in `add_action`, `add_filter`, and `register_block_type` have typed parameters and return types.
5. Use `??` (null coalescing) instead of `isset($x) ? $x : ''`.
6. Use `str_contains()`, `str_starts_with()`, `str_ends_with()` instead of `strpos() !== false`.
7. Use `match` expressions instead of `switch` when the result is a single value.
8. Use `?->` null-safe operator for optional chaining on nullable objects.
9. Apply `readonly` to class properties set once in the constructor and never mutated.
10. Use `array_is_list()` when checking whether an array is a sequential list.

WP snake_case function names and hook names are unchanged. These PHP 8.3 rules apply to function bodies and signatures only, not to the WordPress API surface.

> **House style vs. modern features:** this section answers "which PHP 8.3 features to use". For WordPress PHP formatting rules — Yoda conditions, `array()` syntax, tabs, naming conventions, `$wpdb->prepare()`, and prohibited constructs — load `references/php-coding-standards.md`. The two files are complementary.

### Required Header

Every PHP pattern file MUST start with this header comment (PHP comment, not HTML):

```php
<?php
/**
 * Title: Human Readable Title
 * Slug: theme-text-domain/pattern-slug
 * Categories: category-slug
 * Keywords: keyword1, keyword2
 * Block Types: core/group
 * Inserter: true
 */
?>
```

- `Title` — shown in the Block Inserter
- `Slug` — must be unique; format: `{text-domain}/{slug}`
- `Categories` — must be a registered category slug
- `Inserter: false` — hides the pattern from the inserter (useful for templates-only patterns)

### Image URL Pattern (always use PHP)

```php
<img
  src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/hero.webp"
  alt="<?php esc_attr_e( 'Hero image', 'text-domain' ); ?>"
>
```

Never hardcode `http://` or relative paths — they break across environments and when child
theme overrides parent.

### Pattern Category Registration

Register custom categories in `functions.php` before patterns use them:

```php
function mytheme_register_pattern_categories(): void {
    register_block_pattern_category(
        'my-theme',
        array( 'label' => __( 'My Theme', 'my-theme' ) )
    );
}
add_action( 'init', 'mytheme_register_pattern_categories' );
```

---

## Asset Pipeline (functions.php)

### Build Toolchain: `@wordpress/build` (WP 7.0)

WP 7.0 ships a rewritten `@wordpress/build` package that replaces the webpack + Babel pipeline with **esbuild**. For block theme development, this matters when you maintain a build step for:

- Custom block JavaScript (Script Modules, Interactivity API stores)
- TypeScript source files
- SCSS/PostCSS stylesheets compiled to CSS

**Key changes from WP 6.x `@wordpress/scripts`:**

| Before (webpack) | After (esbuild via `@wordpress/build`) |
|---|---|
| `npx wp-scripts build` | `npx wp-build` |
| Manual `block.json` registration | PHP registration files auto-generated from `package.json` `exports` map |
| Slow cold builds (webpack) | ~10–20× faster cold builds (esbuild) |
| Babel config required | Zero config for modern JS; TypeScript supported natively |

**Minimal `package.json` for a theme with one Script Module:**

```json
{
  "name": "{{THEME_SLUG}}",
  "scripts": {
    "build": "wp-build",
    "start": "wp-build --watch"
  },
  "exports": {
    "./patterns/my-section/index": "./patterns/my-section/index.js"
  },
  "devDependencies": {
    "@wordpress/build": "^1.0.0"
  }
}
```

When `wp-build` runs, it compiles each entry listed under `exports` and writes a companion PHP file (`patterns/my-section/index.asset.php`) with the module's dependency array and version hash — ready to pass directly to `wp_register_script_module()`.

**When you do NOT need a build step:** Pure block themes that use only PHP patterns, `theme.json`, and vanilla JS (no imports, no TypeScript, no SCSS) have no need for `@wordpress/build`. The `autoRegister` flag on dynamic blocks also eliminates JS compilation for simple server-rendered blocks.

### `wp_enqueue_block_style` vs `register_block_style` — Decision Rule

These two functions look similar but load CSS at different scopes:

| Function | When CSS loads | Use for |
|---|---|---|
| `wp_enqueue_block_style( 'core/group', [...] )` | Whenever **any** `core/group` block is on the page — regardless of variation | Global resets that apply to all instances of a block (e.g. remove default margin on all groups) |
| `register_block_style( 'core/group', ['style_handle' => ...] )` | Only when a block has the specific variation class (`is-style-{name}`) | Pattern-specific CSS — loads only on pages that use that exact variation |

**Rule:** always use `register_block_style` with `style_handle` for pattern CSS. Using `wp_enqueue_block_style` for variation-specific CSS loads it on every page that contains the block type — a guaranteed performance regression in themes with frequent Group blocks.

### Full functions.php Template

```php
<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Enqueue parent and child stylesheets.
 */
function mytheme_child_enqueue_styles(): void {
	wp_enqueue_style(
		'mytheme-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme()->parent()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'mytheme_child_enqueue_styles', 9 );

/**
 * Register pattern categories. Must run on 'init' — the canonical hook for block pattern APIs.
 */
function mytheme_child_register_pattern_categories(): void {
	register_block_pattern_category(
		'{{THEME_SLUG}}',
		array( 'label' => __( '{{THEME_NAME}}', '{{TEXT_DOMAIN}}' ) )
	);
}
add_action( 'init', 'mytheme_child_register_pattern_categories' );

/**
 * Register block styles and assets for patterns.
 */
function mytheme_child_pattern_assets(): void {
	// Use wp_enqueue_block_style for global child resets so they load after core block styles.
	wp_enqueue_block_style(
		'core/group',
		array(
			'handle' => 'mytheme-child-global-resets',
			'src'    => get_stylesheet_directory_uri() . '/assets/css/global-resets.css',
			'path'   => get_stylesheet_directory() . '/assets/css/global-resets.css',
		)
	);

	// 1. Register the stylesheet for a specific sub-pattern
	wp_register_style(
		handle: 'my-section-style',
		src:    get_stylesheet_directory_uri() . '/patterns/my-section/style.css',
		deps:   array(),
		ver:    '1.0.0'
	);
	// Passing 'path' enables WordPress's built-in CSS inlining: for files under ~2 KB,
	// WP outputs the CSS directly in <head> instead of issuing a render-blocking HTTP request.
	wp_style_add_data(
		'my-section-style',
		'path',
		get_stylesheet_directory() . '/patterns/my-section/style.css'
	);

	// 2. Register the script module for the pattern.
	//    view.js = reactive (Interactivity API, shared state, data-wp-* directives)
	//    index.js = non-reactive (IntersectionObserver, GSAP, scroll effects — no shared state)
	wp_register_script_module(
		id:      'my-section-logic',
		src:     get_stylesheet_directory_uri() . '/patterns/my-section/view.js',
		deps:    array( '@wordpress/interactivity' ),
		version: '1.0.0'
	);

	// 3. Bind it to a block style variation
	register_block_style(
		'core/group',
		array(
			'name'                 => 'my-section',
			'label'                => 'My Section',
			'style_handle'         => 'my-section-style',
			'script_module_handle' => 'my-section-logic',
		)
	);
}
add_action( 'init', 'mytheme_child_pattern_assets' );
```

### Key Notes

- `get_template_directory_uri()` → **parent** theme directory
- `get_stylesheet_directory_uri()` → **child** (or current) theme directory
- Always pass the parent theme's version as the stylesheet version to bust cache on parent updates
- Priority `9` for child enqueue ensures it loads before default priority `10` hooks

---

## Site Editor Sync Behaviour

The Site Editor stores user customisations in the **database**. If a theme file changes but the
editor isn't reflecting it, the database version is taking precedence.

### How to force file-based version

0. **Check the Revisions panel first** — Site Editor → select the template/part/pattern → ⋮ → "Revisions" → roll back to "Original". Rolling back is reversible; clearing customisations is not. A revision may be overriding the file, and rolling it back is faster and safer than clearing all customisations.
1. Open the Site Editor (Appearance → Editor)
2. Navigate to the affected template or style
3. Click the three-dot menu (⋮)
4. Select **"Reset to defaults"** or **"Clear Customizations"** (use this only if no usable revision exists)

This removes the database override and forces WordPress to re-read the file.

### When this matters most

- After editing a `templates/*.html` file
- After editing `theme.json` styles
- After moving a template part to a new area in `theme.json`
- After adding a new **Ability** to a pattern
- After enabling or disabling `blockVisibility.viewport` on a block (the editor re-reads settings on next load)
- When old content persists after file changes — check Revisions before clearing customisations

### Programmatic cache busting (development)

During active development, append a unique version string to style/script handles to force
browser cache invalidation:

```php
wp_enqueue_style( 'my-style', get_stylesheet_directory_uri() . '/style.css', array(), time() );
```

Replace `time()` with a static version string before deploying to production.

---

## Child Theme Rules

1. **Never modify the parent theme directory.** All files go in the child theme.
2. **Template override**: Copy `templates/{name}.html` from the parent to the same path in the
   child and modify. WordPress always prefers the child theme's version.
3. **Part override**: Same process — copy from parent `parts/` to child `parts/`.
4. **Pattern override**: Create a pattern with the same `Slug` in the child theme to override
   the parent's pattern.
5. **theme.json merging**: Child's `theme.json` is merged with the parent's. However, arrays like `palette` or `fontFamilies` are **overwritten entirely**, not concatenated. If you want to add a single new color, you MUST copy the parent's entire palette array and append your color, otherwise the parent colors will be wiped out.

---

## Query Loop Block

The Query Loop block powers archive, category, and search templates. The critical setting:

**"Inherit query from template"** (set in the block's sidebar)

- **On (true)**: The block reads its query parameters from the current URL (category slug,
  search term, pagination). Use this for `archive.html` and `search.html`.
- **Off (false)**: The block ignores the URL and always shows a static list of recent posts.
  This BREAKS search and archive functionality — only use it for manually curated post grids.

In block markup, this setting corresponds to `"inherit": true` in the query attribute:

```html
<!-- wp:query {"queryId":1,"query":{"inherit":true,"postType":"post"}} -->
```

---

## Diagnostic Console Snippet

When a header or footer section appears missing or invisible, paste this in browser DevTools
(Console tab) to quickly determine whether the issue is DOM-level (template part not rendered)
or CSS-level (part renders but is invisible):

```javascript
(function() {
  const header = document.querySelector('header');
  const report = {
    headerExists: !!header,
    headerInnerHTML: header ? header.innerHTML.trim().substring(0, 500) : 'N/A',
    headerChildren: header ? Array.from(header.children).map(c => ({
      tag: c.tagName, classes: c.className,
      visible: c.offsetWidth > 0 && c.offsetHeight > 0
    })) : [],
    headerStyles: header ? {
      display: getComputedStyle(header).display,
      height: getComputedStyle(header).height,
    } : null
  };
  console.log(JSON.stringify(report, null, 2));
  try { copy(JSON.stringify(report, null, 2)); } catch(e) {}
})();
```

**Interpreting results:**
- `headerChildren` with `visible: false` + empty `innerHTML` → template part not rendering
  → check `Inserter: false` on the backing pattern, and confirm `wp:template-part` is not nested inside a dynamic block's render output
- `headerChildren` with `visible: false` + non-empty `innerHTML` → CSS not loading
  → check that `register_block_style()` is firing and the block has the `is-style-...` class
- `headerChildren` with `visible: true` → working correctly
