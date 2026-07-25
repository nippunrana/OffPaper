# Block Theme Scaffold Trees

Copy these layouts verbatim when starting a new theme, child theme, or dynamic block. Replace `{{THEME_SLUG}}` with the actual kebab-case slug.

> **PHP 8.3 rule:** Every `.php` file containing active PHP logic (functions, classes, or `add_action`/`add_filter` calls) must begin with `<?php` followed immediately by `declare(strict_types=1);`. All named functions must have typed parameters and a return type (e.g. `function my_fn( string $arg ): void {}`). Anonymous callbacks in `add_action` / `add_filter` / `register_block_type` must also be typed. **Exception:** Pure-markup pattern files in `patterns/*.php` that contain only the block comment docblock header and HTML block markup — with no function or class definitions — are exempt from `declare(strict_types=1);` because strict_types has no effect without typed signatures. See `references/architecture.md → PHP 8.3 Requirements` for the complete ten-point checklist.

## Parent block theme

```
{{THEME_SLUG}}/
├── style.css                           ← Theme metadata header (Theme Name, Version, etc.)
├── theme.json                          ← Settings & global styles (use "version": 3, $schema: https://schemas.wp.org/wp/7.0/theme.json)
├── functions.php                       ← Asset enqueueing, register_block_style, pattern category
├── README.md                           ← Optional
│
├── templates/
│   ├── index.html                      ← Required: absolute fallback
│   ├── singular.html                   ← Posts + static pages
│   ├── page.html                       ← Static pages
│   ├── single.html                     ← Individual posts
│   ├── archive.html                    ← Archive views
│   ├── search.html                     ← Search results
│   ├── 404.html                        ← Error page
│   └── {custom-slug}.html              ← Custom templates registered in theme.json
│
├── parts/
│   ├── header.html
│   ├── footer.html
│   └── sidebar.html                    ← Optional
│
├── patterns/
│   ├── {master-slug}.php               ← Master pattern (Inserter: false)
│   ├── {section-slug}.php              ← One PHP file per section
│   └── {section-slug}/
│       ├── style.css                   ← Scoped to .is-style-{section-slug}
│       └── index.js                    ← Optional, non-reactive vanilla JS (classic conditional asset queue — not a Script Module)
│
└── assets/
    ├── fonts/                          ← Local font files referenced from theme.json
    ├── images/                         ← Referenced via get_stylesheet_directory_uri()
    └── css/
        └── global-resets.css           ← Optional, enqueued via wp_enqueue_block_style
```

## Child block theme

```
{{THEME_SLUG}}-child/
├── style.css                           ← Must declare `Template: {{PARENT_THEME_SLUG}}`
├── theme.json                          ← Merges with parent; only the diff
├── functions.php                       ← Optional child-specific asset registration
│
├── templates/                          ← Optional: copy of parent template to override
│   └── {slug}.html
│
├── parts/                              ← Optional: same idea
│
├── patterns/                           ← Child-specific patterns; slug prefix {{THEME_SLUG}}-child/
│   └── {pattern}.php
│
└── assets/
    └── images/
```

**Notes on child themes:**
- Arrays in `theme.json` (`palette`, `fontFamilies`) overwrite the parent rather than merge. Either copy the full array or set `"defaultPalette": true` to keep parent tokens accessible via `var(--wp--preset--color--{slug})`.
- `style.css` must include the parent's `Template:` header so WordPress knows which theme to inherit from.

## Dynamic block (WP 7.0 `autoRegister`)

A dynamic block registered with `'autoRegister' => true` does not need any JavaScript or `block.json`. Everything lives in PHP.

```
{{THEME_SLUG}}/
└── inc/
    └── blocks/
        └── {block-slug}.php             ← register_block_type() call lives here
```

Then in `functions.php`:

```php
require_once get_theme_file_path( 'inc/blocks/{block-slug}.php' );
```

If you need an editor-side block-style stylesheet for the dynamic block, register it the same way as for core blocks via `register_block_style()`.

