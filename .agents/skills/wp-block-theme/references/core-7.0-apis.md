# Core WP 7.0 APIs — collaboration, admin UX, security, LCP

Cross-cutting public APIs that don't belong to the theme/AI/WooCommerce reference files but ship in
WP 7.0 (a few are older but newly relevant). Each section is the developer-facing surface only —
core-private internals are called out so you don't reach for them.

**Load this file when**: the user asks about real-time collaboration, admin View Transitions, the
Command Palette, password hashing / Argon2, or image LCP / `fetchpriority` optimisation.

---

## 1. Real-time collaboration (gates)

WP 7.0 ships the foundation for multi-user editing. The persistence/transport layer
(`WP_HTTP_Polling_Sync_Server`, `WP_Sync_Post_Meta_Storage`, awareness/cursor handling) is **core
internal** — a theme or plugin doesn't implement a sync server. What you *do* touch is the small
set of public gates, so you can branch behaviour when collaboration is active.

| API | Notes |
|---|---|
| `wp_is_collaboration_enabled(): bool` | WP 7.0+. True when real-time collaboration is enabled. Checks the `WP_ALLOW_COLLABORATION` constant **and** the stored option. |
| `wp_is_collaboration_allowed(): bool` | WP 7.0+. True when the environment permits collaboration. Reads `WP_ALLOW_COLLABORATION` (defaults to allowed unless explicitly set to the string `"false"`). |
| `WP_ALLOW_COLLABORATION` | Constant (define in `wp-config.php`) that opts the environment in/out. |

Use `wp_is_collaboration_allowed()` for "could this site ever collaborate" (config) and
`wp_is_collaboration_enabled()` for "is it on right now" (runtime). Gate any UI or save logic that
assumes single-author editing behind the latter.

```php
if ( wp_is_collaboration_enabled() ) {
    // Avoid clobbering concurrent edits; defer to the sync layer.
}
```

> **Do not** call `wp_collaboration_inject_setting()` — it is marked private (core wires the
> client-side setting itself). There is no public `can_user_sync_entity_type()` global function in
> WP 7.0; entity-level sync permissions are handled inside the core sync layer.

---

## 2. Admin UX: View Transitions & Command Palette

| API | Notes |
|---|---|
| `wp_enqueue_view_transitions_admin_css(): void` | WP 7.0+. Enqueues the `wp-view-transitions-admin` stylesheet that drives cross-document View Transitions between admin screens. Core enqueues this for the dashboard; call it only if you're building a custom admin area that should match. Honors OS-level reduced-motion. |
| `wp_admin_bar_command_palette_menu( WP_Admin_Bar $bar ): void` | WP 7.0+. Core callback (hooked on `admin_bar_menu`) that renders the Command Palette trigger in the admin bar, showing ⌘K / Ctrl+K per OS. You normally don't call it directly; know it exists when customising the admin bar so you don't duplicate or remove it by accident. |

---

## 3. Security: password hashing filters (Argon2)

Two filters (since **6.8**, still current in 7.0) make the core `password_hash()` pipeline
configurable, so you can move off bcrypt without waiting for a core release.

| Filter | Notes |
|---|---|
| `wp_hash_password_algorithm` | Filters the algorithm passed to `password_hash()` / `password_needs_rehash()`. Default `PASSWORD_BCRYPT`. As of 7.0 the value is consistently a string. |
| `wp_hash_password_options` | Filters the `$options` array (e.g. `memory_cost`, `time_cost`, `threads` for Argon2) to match server hardware. |

```php
add_filter( 'wp_hash_password_algorithm', function ( string $algo ): string {
    // bcrypt is the only algorithm guaranteed available everywhere — verify before switching.
    return defined( 'PASSWORD_ARGON2ID' ) ? PASSWORD_ARGON2ID : $algo;
} );
```

> **Caveat:** Argon2I / Argon2ID are only present if PHP was compiled with libsodium/Argon2 support.
> Always guard with `defined()` (as above) so a missing algorithm doesn't fatal the login path.

---

## 4. Media & LCP optimisation

| API | Notes |
|---|---|
| `wp_get_loading_optimization_attributes( string $tag_name, array $attr, string $context ): array` | Since **6.3** (public). Returns the optimal `loading`, `decoding`, and `fetchpriority` attributes for a media tag given its context. Use when emitting `<img>`/`<iframe>` from a dynamic block's `render_callback` so your output gets the same lazy-load / LCP treatment as core. |

```php
$optimized = wp_get_loading_optimization_attributes(
    'img',
    array( 'width' => 1200, 'height' => 600 ),
    'my_theme_hero'
);
// Merge $optimized (loading, decoding, fetchpriority) into your <img> attributes.
```

> **Do not** call `wp_maybe_add_fetchpriority_high_attr()` directly — it's marked private (core-only).
> `wp_get_loading_optimization_attributes()` is the supported entry point and applies the same logic.
