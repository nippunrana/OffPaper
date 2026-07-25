# WordPress API Allowlist

*Before writing any WordPress function or class name in skill output, confirm it appears here.* Every entry below has been verified against developer.wordpress.org. If a name you need is not on this list, look it up before writing — do not invent.

This allowlist targets WordPress 7.0 only. All entries below are confirmed stable in WP 7.0.

## Themes & templates

| Function / class | Notes |
|---|---|
| `register_block_template( $name, $args )` | Available since 6.7. `$args` keys: `title`, `description`, `content`, `post_types`. No `is_ai_ready`. |
| `wp_register_block_template_part()` | Available since 6.7. Companion to `register_block_template` for parts. |
| `WP_Block_Templates_Registry` | Class behind the registration API. |
| `register_block_pattern()` | Registers a pattern from PHP. |
| `register_block_pattern_category()` | Registers a category before patterns reference it. |
| `unregister_block_pattern()` / `unregister_block_pattern_category()` | Removes registrations. |
| `get_template_directory_uri()` | Returns the **parent** theme URI. |
| `get_stylesheet_directory_uri()` | Returns the **child** (or current) theme URI. |
| `get_stylesheet_directory()` | Returns the child theme absolute path (used for `wp_style_add_data( …, 'path', … )`). |
| `wp_get_image_alttext( $attachment_id )` | WP 7.0+. Returns the alt text stored in attachment meta. Use when importing images to pre-populate `core/image` `alt` attributes. |

## Block registration

| Function / class | Notes |
|---|---|
| `register_block_type( $name_or_path, $args )` | Server-side block registration. In WP 7.0, set `'supports' => array( 'autoRegister' => true )` to register a block from PHP alone (no `block.json` / JS). **`autoRegister` supported attribute types:** `string`, `number`, `integer`, `boolean`. **`autoRegister` constraint:** attributes with a `source` key (`"source": "html"`, `"source": "attribute"`, etc.) are NOT supported — `autoRegister` only works with attributes stored in the block's JSON comment boundary. Use a classic dynamic block (`block.json` + `render_callback`) when you need sourced attributes. |
| `register_block_type_from_metadata()` | Older form; registers from a `block.json` file path. |
| `register_block_style( $block, $args )` | Registers a block style variation. `$args` accepts `name`, `label`, `style_handle`, `script_module_handle` (since 6.7). |
| `unregister_block_style()` | Removes a previously-registered variation. |
| `wp_enqueue_block_style( $block, $args )` | Enqueues a stylesheet only when the named block is on the page. |
| `core/heading` (WP 7.0 level variations) | WP 7.0 registers H1–H6 as named inserter-level variations of `core/heading`. The block name in markup is always `core/heading` — never `core/headings`. Use `level: 1–6` in attributes; editors can switch levels via the toolbar at any time. |
| `core/navigation-overlay-close` | WP 7.0. Close button for mobile navigation overlays. Must be placed inside a `core/navigation` block that has `overlayMenu` set to `"always"` or `"mobile"`. Always include this block explicitly in custom overlay patterns — omitting it forces a theme-clashing fallback button. |
| `core/accordion` | WP 7.0. Multi-item collapsible disclosure widget. Structure: `core/accordion` → one or more `core/accordion-item` children, each containing `core/accordion-heading` and `core/accordion-panel`. Use for FAQ-style groups with multiple items. For a single collapsible disclosure, use `core/details` instead. |
| `core/details` | WP 6.3+. Single collapsible disclosure using native `<details>`/`<summary>` HTML. Use for one-off expandable sections. For multi-item accordion UIs, use `core/accordion` (WP 7.0). |
| `core/playlist` | WP 7.0. Native audio playlist block with waveform visualization. Use when converting designs that include podcast players or audio track lists. |

## Template Part Areas (WP 7.0)

Valid `area` values for `templateParts` entries in `theme.json` and for `wp:template-part` block attributes:

| Area value | Use |
|---|---|
| `header` | Site header region |
| `footer` | Site footer region |
| `uncategorized` | General-purpose template part (default) |
| `navigation-overlay` | WP 7.0. Mobile navigation overlay. Register area in `theme.json templateParts`; create the `/parts/*.html` file; register a pattern with `Block Types: core/template-part/navigation-overlay`; use slug-only (no theme prefix) in the Navigation block's `overlay` attribute. |

## Breadcrumb Filters (WP 7.0)

| Filter | Notes |
|---|---|
| `block_core_breadcrumbs_items` | Modifies the breadcrumb trail for `core/breadcrumbs`. Receives `array $items, WP_Block $block` — 10, 2 priority. Each item in the array is an associative array with three keys: `label` (string), `url` (string), and `allow_html` (bool). **Sanitization rule:** if `allow_html` is `true` the label is sanitized via `wp_kses_post()`; if `false` or omitted it is escaped via `esc_html()`. Always set `allow_html: false` (or omit it) unless you are intentionally injecting safe HTML such as an SVG icon. |

## Block Bindings

| Function / class | Notes |
|---|---|
| `register_block_bindings_source( $name, $args )` | Registers a custom binding source. `$args` keys: `label`, `get_value_callback`, `uses_context`. |
| Built-in sources | `core/post-meta`, `core/post-title`, `core/post-excerpt`, `core/post-date`, `core/post-author-name`, `core/site-title`, `core/site-tagline`, `core/pattern-overrides`. |
| Filter: `block_bindings_supported_attributes` | WP 7.0+. Allows any custom block to expose attributes to the Pattern Overrides UI. Return an array of attribute names the block exposes. Use this instead of `metadata.bindings` for fully custom blocks. Signature: `apply_filters( 'block_bindings_supported_attributes', array $supported, string $block_name )`. **Static vs. dynamic blocks:** For static blocks (no `render_callback`), WordPress uses the **WP HTML API** to locate the bound attribute values within the persisted block markup and replace them with override values at render time. Dynamic blocks (with a `render_callback`) receive override values directly as `$attributes` — no HTML API parsing occurs. Choose accordingly: static blocks for overrides that must appear in HTML attributes (`src`, `href`, etc.); dynamic blocks when the override value drives PHP logic. |

## contentOnly Pattern Opt-Out (WP 7.0+)

| API | Notes |
|---|---|
| `disableContentOnlyForUnsyncedPatterns` key inside `block_editor_settings_all` | WP 7.0+. Stable site-wide opt-out. Hook on `block_editor_settings_all` and return `true` for this key in the settings array. **Never** use `add_filter('disableContentOnlyForUnsyncedPatterns', ...)` — that filter is never called. Correct PHP: `add_filter('block_editor_settings_all', function(array $s): array { $s['disableContentOnlyForUnsyncedPatterns'] = true; return $s; });` Use only when the entire site must bypass contentOnly — rare. |
| `disableContentOnlyForUnsyncedPatterns` JS equivalent | WP 7.0+. `wp.hooks.addFilter('blockEditor.settings', 'my-theme/disable-content-only', (settings) => ({ ...settings, disableContentOnlyForUnsyncedPatterns: true }))` |
| `"__experimentalSettings": { "disableContentOnlyForUnsyncedPatterns": true }` | Experimental per-block attribute. Set on the outermost block's attributes to opt out for one specific pattern only. Preferred for per-pattern developer scaffolds. |

## Block Supports: contentOnly & List View (WP 7.0+)

| Support key | Notes |
|---|---|
| `"role": "content"` on `attributes` | WP 7.0+. Set on individual attribute definitions in `block.json` to mark them as content-editable in contentOnly mode. Without this, the attribute (and possibly the block) is hidden from List View inside contentOnly containers. |
| `"contentRole": true` in `supports` | WP 7.0+. Alternative when no specific content attribute exists. Makes the block visible in List View inside contentOnly containers without tying visibility to a particular attribute. Prefer `"role": "content"` on attributes when possible. |
| `"listView": true` in `supports` | WP 7.0+. Adds a dedicated List View tab to the block inspector for container blocks (blocks with `InnerBlocks`). Allows editors to rearrange and insert inner blocks from the sidebar. Recommended for any custom block wrapping multiple children. |

## Viewport Block Visibility (WP 7.0+)

**Naming convention — two different keys, two different purposes:**
- Block support key (registered in `supports` in `block.json`): **`visibility`**
- Attribute metadata key (the object stored on the block): **`blockVisibility`**

These are intentionally different. Setting `"supports": { "visibility": true }` enables the feature for a block; the editor then writes the chosen settings into `metadata.blockVisibility` on the block instance.

**Server-side parsing rule:** PHP code reading block markup via `parse_blocks()` must handle **both** forms of the `blockVisibility` field in a block's `metadata` attribute — WordPress may serialize it as a scalar boolean (`false`) or as an object (`{ "viewport": ["mobile"] }`). Always check for scalar first, then check for the viewport object:

```php
$visibility = $block['attrs']['metadata']['blockVisibility'] ?? null;
if ( false === $visibility ) {
	// Block is hidden everywhere (legacy scalar form)
} elseif ( is_array( $visibility ) && isset( $visibility['viewport'] ) ) {
	// Viewport-conditional visibility
	$visible_on = $visibility['viewport']; // e.g. array( 'tablet', 'desktop' )
}
```

| Key | Notes |
|---|---|
| `metadata.blockVisibility` | Attribute on a block instance's `metadata`. Controls per-device visibility. May be a boolean scalar OR a viewport object — parse defensively. |
| `metadata.blockVisibility.viewport` | Array of strings. Allowed values: `"mobile"`, `"tablet"`, `"desktop"`. Block renders only on the listed device types. Omit the key entirely to show on all devices. An empty array `[]` hides the block everywhere. |

Enable viewport visibility in `theme.json settings` before using it (per-block or globally):

```json
// Per block
"settings": { "blocks": { "core/group": { "blockVisibility": { "viewport": true } } } }

// Globally (all blocks)
"settings": { "blockVisibility": { "viewport": true } }
```

Block markup example (hidden on mobile):

```html
<!-- wp:group {
  "metadata": {
    "blockVisibility": {
      "viewport": ["tablet", "desktop"]
    }
  }
} -->
<div class="wp-block-group"><!-- hidden on mobile --></div>
<!-- /wp:group -->
```

## Typography & Dimensions Supports (WP 7.0+)

### Text Indent

| Key | Notes |
|---|---|
| `supports.typography.textIndent` | WP 7.0+. Set to `true` in `block.json` supports to add a "Line Indent" control to the block inspector for any block. |
| `typography.textIndent` in `theme.json` | Controls paragraph indentation at the Global Styles level (applies to `core/paragraph`). Two modes: `"subsequent"` (default — indents only paragraphs that directly follow another paragraph, CSS selector `.wp-block-paragraph + .wp-block-paragraph`) and `"all"` (indents every paragraph, selector `.wp-block-paragraph`). Set under `settings.typography.textIndent`. |

`theme.json` example:

```json
{
  "settings": {
    "typography": {
      "textIndent": "subsequent"
    }
  }
}
```

### Dimensions (Width / Height)

| Key | Notes |
|---|---|
| `supports.dimensions.width` | Opt-in in `block.json`. Enables a width control in the block inspector for the registered block. |
| `supports.dimensions.height` | Opt-in in `block.json`. Enables a height control in the block inspector for the registered block. |
| `settings.dimensions.dimensionSizes` in `theme.json` | Array of preset objects `{ slug, name, size }`. Defines the available dimension presets. **UI threshold rule: fewer than 8 presets → renders a slider; 8 or more presets → renders a dropdown.** |

`theme.json` example (slider, 3 presets):

```json
{
  "settings": {
    "dimensions": {
      "dimensionSizes": [
        { "slug": "small", "name": "Small", "size": "200px" },
        { "slug": "medium", "name": "Medium", "size": "400px" },
        { "slug": "large", "name": "Large", "size": "600px" }
      ]
    }
  }
}
```

## Abilities API (WP 7.0+)

| Function / hook | Notes |
|---|---|
| `wp_register_ability( $name, $args )` | Registers an ability (since 6.9; stable in 7.0). Returns `WP_Ability\|null`. Required `$args`: `label`, `description`, `category`, `execute_callback`, `permission_callback`. Optional: `input_schema`, `output_schema`, `meta` (annotations + `show_in_rest`), `ability_class` (custom class extending `WP_Ability`). |
| `wp_register_ability_category( $slug, $args )` | Registers an ability category. Required `$args`: `label`. Categories must exist before abilities reference them. |
| `wp_unregister_ability()` / `wp_unregister_ability_category()` | Removes registrations. |
| Hook: `wp_abilities_api_init` | Hook your registration callbacks here, not on `init`. |
| Client package: `@wordpress/abilities` | Pure JS state store. |
| Client package: `@wordpress/core-abilities` | WordPress integration layer. |
| JS function `registerAbility` | Registers a client-side ability. Imported from `@wordpress/abilities`. |

## WP AI Client (WP 7.0+)

See `references/ai-client.md` for full coverage. Quick-reference surface:

| Function / class | Notes |
|---|---|
| `wp_ai_client_prompt( $text = '' )` | Entry point. Returns `WP_AI_Client_Prompt_Builder`. Pass prompt text directly or build it via `->with_text()`. |
| `WP_AI_Client_Prompt_Builder` | Fluent builder. Configuration methods: `with_text()`, `with_file()`, `with_history()`, `using_system_instruction()`, `using_temperature()`, `using_max_tokens()`, `using_top_p()`, `using_top_k()`, `using_stop_sequences()`, `using_model_preference()`, `as_output_modalities()`, `as_output_file_type()`, `as_json_response( $schema )`. |
| Generation methods | Text and image have direct methods: `generate_text()` → `string`, `generate_texts( $n )` → `string[]`, `generate_image()` → `File` DTO (use `getDataUri()`), `generate_images( $n )` → `File[]`. Text-to-speech, native speech, and video are result-only (use the `*_result()` variants). All can return `WP_Error`. |
| Result-form variants | `generate_text_result()`, `generate_image_result()`, `convert_text_to_speech_result()`, `generate_speech_result()`, `generate_video_result()`. Return `GenerativeAiResult` with `getContent()` (returns the generated content string, file path, or object), `getTokenUsage()` (input/output/thinking), `getProviderMetadata()`, `getModelMetadata()`. |
| Feature detection (static) | `WP_AI_Client_Prompt_Builder::is_supported_for_text_generation()`, `::is_supported_for_image_generation()`, `::is_supported_for_text_to_speech_conversion()`, `::is_supported_for_speech_generation()`, `::is_supported_for_video_generation()`. Pure capability checks — no API call, no cost. **Always gate generation calls behind these.** |
| `wp_supports_ai()` | WP 7.0+. Returns `bool` — whether the environment supports AI at all (coarser than the modality `is_supported_for_*` checks). Use as the outer gate; filterable via the `wp_supports_ai` filter. |
| Filter: `wp_ai_client_prevent_prompt` | WP 7.0+. Return `true` to block a prompt before it executes (rate-limiting / budget caps). Receives a read-only builder clone; the blocked call returns `WP_Error`. |
| Filter: `wp_ai_client_default_request_timeout` | WP 7.0+. Filters the default HTTP timeout (`float`, seconds) for AI Client requests. |
| Hook: `wp_connectors_init` | Fires once with `WP_Connector_Registry`. Use to register or modify connectors. |
| `WP_Connector_Registry` | Methods: `is_registered( $id )`, `register( $id, $args )`, `unregister( $id )`. Use inside `wp_connectors_init`. |
| `AiClient` | Class containing AI Client configuration. Method: `defaultRegistry()` (returns the default connector registry for discovery/auto-discovery of connectors). |
| `wp_get_connector( $id )` | WP 7.0+. Returns a single connector's definition as `array` (or `null`). Available after `init`. |
| Connector `type` | AI connectors register with `'type' => 'ai_provider'` (underscore — **not** `ai-provider`). WP 7.0 generalised the registry so `type` is no longer AI-only. |
| Credential resolution order | 1) env var (`OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, etc.), 2) PHP constant `define( 'OPENAI_API_KEY', '…' )`, 3) DB option `connectors_{$type}_{$id}_api_key` where `{$type}` is the connector type (e.g. `ai`) and `{$id}` is the connector identifier — example: `connectors_ai_openai_api_key` (managed at **Settings → Connectors**). First match wins; subsequent sources are ignored. |
| Connectors UI | Site admin enables OpenAI / Anthropic / Google credentials at **Settings → Connectors**. Shows current credential source (env / constant / DB) per connector. |

## Script Modules & Interactivity API

| Function | Notes |
|---|---|
| `wp_register_script_module( $id, $src, $deps, $version )` | Registers a JavaScript module. |
| `wp_enqueue_script_module()` | Enqueues a registered module. Usually automatic when bound via `script_module_handle` on `register_block_style`. |
| `wp_deregister_script_module()` | Removes a registration. |
| `wp_set_script_module_translations( $id, $domain = 'default', $path = '' )` | WP 7.0+. Sets the text domain / path for a script module's JSON translations. Returns `bool`. Call right after registering the module. |
| `load_script_module_textdomain( $id, $domain = 'default', $path = '' )` | WP 7.0+. Loads translation data for a script module ID. Returns `string\|false`. Module equivalent of `load_script_textdomain()`. |
| Interactivity API store | Use `store()` from `@wordpress/interactivity` in the module. |
| HTML directives | `data-wp-interactive`, `data-wp-context`, `data-wp-on--<event>`, `data-wp-bind--<attr>`, `data-wp-class--<name>`, `data-wp-style--<prop>`, `data-wp-text`. |
| `watch( fn )` from `@wordpress/interactivity` | WP 7.0+. Subscribes to signal changes. `fn` runs once immediately and again whenever any reactive state it reads changes. Returns a teardown cleanup function. |
| HTML directive `data-wp-watch` | WP 7.0+. Declarative form of `watch()`. The value is a callback key in the store (e.g. `data-wp-watch="callbacks.onSaveChange"`). Runs whenever reactive state the callback reads changes. |

## JavaScript Packages (WP 7.0+)

| Package | Notes |
|---|---|
| `@wordpress/boot` | Enables plugins to register custom Site Editor pages with route validation. Import `boot` from this package and call it once during editor initialisation. WP 7.0+. |
| `@wordpress/grid` | Standardised grid toolkit for editor interfaces. Provides `GridLayout`, `GridItem`, and related components. WP 7.0+. |
| `@wordpress/build` | WP 7.0+ build toolchain. Replaces `@wordpress/scripts` webpack+Babel pipeline with esbuild. Run via `npx wp-build`. Auto-generates `.asset.php` registration files from `package.json` `exports` map. Use for themes that compile Script Modules or TypeScript. Not needed for pure-PHP themes. |

## Block Hooks

| Function / filter | Notes |
|---|---|
| Filter: `hooked_block_types` | Returns the list of blocks to attach to a relative block. |
| Filter: `hooked_block_{block-name}` | Per-block-specific hook variant. |
| Manifest: `blockHooks` in `block.json` | Declarative form. |
| Filter: `rest_block_hooks_post_types` | WP 7.0+. Filters the array of post-type slugs that run Block Hooks in the Posts REST controller. Whitelist post types that need injection; drop the rest to cut REST latency. Public lever for the WP 7.0 REST-based resolver. |
| `resolve_pattern_blocks( array $blocks )` | Since 6.6 (wider use in 7.0). Replaces `core/pattern` references in a parsed block tree with their constituent blocks. Returns `array`. For headless/agent block-tree work — not needed for normal authoring (render resolves patterns automatically). |
| `insert_hooked_blocks_into_rest_response()` (6.6), `apply_block_hooks_to_content_from_post_object()` (6.8) | **Private / core-only** — the mechanism behind REST Block Hooks. Do not call directly; control via `rest_block_hooks_post_types`. |

## Speculation Rules

| API | Notes |
|---|---|
| `<script type="speculationrules">` JSON | Inject via `wp_head` or `wp_footer`. Standard Web Platform feature, no WP-specific wrapper required. |

## Localisation & escaping

| Function | Notes |
|---|---|
| `__( $text, $domain )` | Translate string. |
| `esc_html__( $text, $domain )` | Translate + escape for HTML. |
| `esc_attr__()`, `esc_attr_e()` | Attribute escaping. |
| `esc_html_e()` | Translate + echo + escape. |
| `esc_url()` | Escape a URL for output. |
| `wp_kses_post()` | Sanitise HTML allowing post content tags. |
| `wp_json_encode()` | Safer JSON encoding for output. |

## DataViews & Field API (WP 7.0+)

See `references/dataviews.md` for full coverage. Quick-reference surface:

| API | Notes |
|---|---|
| DataViews `groupBy` | Object form `{ field: string, direction?: 'asc'\|'desc', label?: string }`. Legacy string-only form is deprecated — emit the object form for any new view. |
| DataViews `onReset` | `undefined` → "Reset view" button hidden (view does not persist). `false` → button shown but disabled (persistence active, currently at defaults). `function` → button active; invoked when clicked. Pick by the desired UX state, not by convenience. |
| Field validation rules | Declarative, JSON-Schema-aligned. `pattern` (text/email/tel/url), `minLength`/`maxLength` (text/email/tel/url), `min`/`max` (integer/number). Set on the field definition; validation runs before submit. |
| `getValueFormatted( item, field )` | Custom display function. Use for unit conversion (`2048` → `"2.0 KB"`), date formatting, role labels — anything where the stored value and the visible value differ. |

## User registration (WP 7.0+)

| Filter | Notes |
|---|---|
| `default_role_dropdown_excluded_roles` | Modifies the list of roles excluded from the new-user registration role selector. Default: `['administrator', 'editor']` (added in WP 7.0 to prevent accidental privilege escalation). Return the modified array. Only add a role back if you have a deliberate workflow that requires it. |

## Site Editor / sync

| Function / behaviour | Notes |
|---|---|
| Site Editor database overrides | Documented behaviour; clear via Appearance → Editor → ⋮ → "Reset to defaults" / "Clear Customizations". No PHP API. |
| Template / Template Part / Pattern Revisions | WP 7.0+. Edit history for templates, template parts, and synced patterns. Access via Site Editor → select entity → ⋮ → "Revisions". Roll back to any saved version. No PHP API — editor-only. Check revisions BEFORE clearing customisations — rolling back is reversible, clearing is not. |
| `wp_register_style()` / `wp_enqueue_style()` | Standard stylesheet registration. |
| `wp_style_add_data( $handle, 'path', $absolute_path )` | Enables WordPress's small-CSS inlining. |

## Real-Time Collaboration (WP 7.0+)

See `references/core-7.0-apis.md`. Public gates only — the sync server / storage classes are core-internal.

| API | Notes |
|---|---|
| `wp_is_collaboration_enabled()` | WP 7.0+. `bool` — collaboration is on now. Checks `WP_ALLOW_COLLABORATION` + the stored option. |
| `wp_is_collaboration_allowed()` | WP 7.0+. `bool` — environment permits collaboration. Reads `WP_ALLOW_COLLABORATION` (allowed unless set to `"false"`). |
| `WP_ALLOW_COLLABORATION` | Constant (wp-config.php) opting the environment in/out. |

## Admin UX (WP 7.0+)

See `references/core-7.0-apis.md`.

| API | Notes |
|---|---|
| `wp_enqueue_view_transitions_admin_css()` | WP 7.0+. Enqueues the `wp-view-transitions-admin` stylesheet for cross-document admin View Transitions. Honors reduced-motion. |
| `wp_admin_bar_command_palette_menu( WP_Admin_Bar $bar )` | WP 7.0+. Core `admin_bar_menu` callback that renders the Command Palette trigger (⌘K / Ctrl+K). Don't call directly; know it exists when customising the admin bar. |

## Security — password hashing (since 6.8)

See `references/core-7.0-apis.md`.

| Filter | Notes |
|---|---|
| `wp_hash_password_algorithm` | Filters the algorithm for `password_hash()` / `password_needs_rehash()`. Default `PASSWORD_BCRYPT`. Guard Argon2 with `defined( 'PASSWORD_ARGON2ID' )` — it isn't available on every PHP build. |
| `wp_hash_password_options` | Filters the `$options` array (`memory_cost`, `time_cost`, `threads`, …). |

## Media & LCP (since 6.3)

| API | Notes |
|---|---|
| `wp_get_loading_optimization_attributes( $tag_name, $attr, $context )` | Since 6.3. Public. Returns optimal `loading` / `decoding` / `fetchpriority` attributes for a media tag. Use in a dynamic block's `render_callback`. The companion `wp_maybe_add_fetchpriority_high_attr()` is **private** — do not call it directly. |

## Names that look real but are not (do not use)

| Wrong | Correct |
|---|---|
| `register_block_ability()` | `wp_register_ability()` |
| `wp_register_icons` filter | Does not exist in WP 7.0. The icon registry is internal/private. Custom theme icons cannot be registered against `core/icon` in WP 7.0 — a public API is planned for WP 7.1. Use inline SVG or `<img>` from a dynamic block instead. |
| `WP_Icons_Registry::get_instance()->register(...)` | The `WP_Icons_Registry` class **does** exist in WP 7.0 (internal singleton) but is **closed to third-party registration** — `register()` is `protected` and only core icons are registered. No public theme/plugin API to add icons in 7.0 (planned for 7.1). Use inline SVG or `<img>` from a dynamic block instead. |
| `'is_ai_ready' => true` on `register_block_template` | Not a real parameter. Use the documented `title`, `description`, `content`, `post_types` keys. |
| `__experimentalRole: 'content'` | Removed. Use `metadata.bindings` + `metadata.name` for Pattern Overrides. |
| `"version": 4` in theme.json | No such version. Use `"version": 3`. |
| `https://schemas.wp.org/trunk/theme.json` | Points to the development branch — validates against unreleased schema features. Use `https://schemas.wp.org/wp/7.0/theme.json` (pinned to the released version) instead. |
| `@wordpress/viewport-visibility` | Does not exist. Use the `metadata.blockVisibility.viewport` key directly on the block's metadata attribute. |
| `wp_register_font_library_font()` | Does not exist. The Font Library is a UI-only feature managed via Appearance → Editor → Styles → Typography → Manage Fonts, or via `theme.json fontFamilies` for version-controlled registration. |
| `$builder->using_abilities()` | Not a method on the WP 7.0 AI Client prompt builder. The builder has no ability/tool-passing surface. Expose plugin capabilities to AI via the **Abilities API** (`wp_register_ability`) — see `references/abilities-api.md`. |
| `$builder->set_model(...)` / `$builder->with_options([...])` | Not real builder methods. Use `using_model_preference( $model )` and the explicit `using_*` / `as_*` config methods in `references/ai-client.md`. |
| `$builder->exception_to_wp_error()` + snake→camel `__call` magic | Not documented in WP 7.0. Generation methods already return `WP_Error` on failure — check with `is_wp_error()`. |
| `can_user_sync_entity_type()` | No such public global function in WP 7.0. Collaboration entity permissions are handled inside the core sync layer. Gate with `wp_is_collaboration_enabled()` instead. |
| `'type' => 'ai-provider'` (hyphen) | Wrong. The connector type is `'ai_provider'` (underscore). |
| `wp_collaboration_inject_setting()`, `apply_block_hooks_to_content_from_post_object()`, `wp_maybe_add_fetchpriority_high_attr()`, `insert_hooked_blocks_into_rest_response()` | All **real but private** (core-only). Don't call directly — use the documented public lever (`wp_is_collaboration_enabled()`, `rest_block_hooks_post_types`, `wp_get_loading_optimization_attributes()`). |
| `wp_generate_uuid4()` "new in 7.0" | The function is real but exists **since 4.7** — not a WP 7.0 feature. Don't present it as a 7.0 novelty. |
