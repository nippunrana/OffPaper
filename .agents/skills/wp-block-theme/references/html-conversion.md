# HTML → Block Theme Template: Conversion Reference
*Default target: WordPress 7.0.*

## Table of Contents
1. [Block Mapping (Native-First)](#block-mapping-native-first)
2. [Full Worked Example](#full-worked-example)
3. [Interactivity API (Modern JS)](#interactivity-api-modern-js)
4. [CSS Scoping Patterns](#css-scoping-patterns)
5. [Image Path Conversion](#image-path-conversion)
6. [Navigation Conversion](#navigation-conversion)
7. [Forms and Third-Party Embeds](#forms-and-third-party-embeds)
8. [Animations and Scroll Effects](#animations-and-scroll-effects)
9. [CSS Reset Handling](#css-reset-handling)
10. [Edge Cases and Gotchas](#edge-cases-and-gotchas)

---

## Block Mapping (Native-First)

Before writing any code, map the static HTML elements to native WordPress Core Blocks.

| Original HTML | Core Block | Implementation |
|---|---|---|
| `<section class="hero">` | `core/group` | Use `tagName: "section"` |
| `<h1>Title</h1>` (fixed level) | `core/heading` | Use `level: 1`. Level is fixed and cannot be changed by editors. |
| `<h1>–<h6>` (any level) | `core/heading` | Editors can always change the level via the block toolbar. Set the default with `"level": 2` in block attributes. The block name is always `core/heading`. |
| `<div class="features">` | `core/group` | Use `layout: { "type": "grid" }` |
| `<a class="btn">` | `core/button` | Use `className: "btn-primary"` |
| `<div class="accordion">` (multi-item FAQ) | `core/accordion` (WP 7.0) | Multi-item collapsible. Structure: `core/accordion` → `core/accordion-item` children, each with `core/accordion-heading` + `core/accordion-panel`. |
| `<details>/<summary>` (single toggle) | `core/details` (WP 6.3+) | Single collapsible disclosure using native `<details>`/`<summary>`. Chain multiple for simple FAQ-style lists without the full Accordion block. |
| `<audio>` list / podcast player | `core/playlist` (WP 7.0) | Native audio playlist block with waveform visualization. Replaces custom audio player markup entirely. |
| `<div class="card">` | `core/group` | Child of native Grid |
| `SVG Icon` (core set) | `core/icon` (WP 7.0) | Use `{"icon":"core/..."}` for built-in icons. |
| `SVG Icon` (custom/theme) | Inline SVG or `core/image` | WP 7.0 has no public custom icon registration API. Output custom SVGs from a dynamic block's `render_callback` or as an `<img>` via `get_stylesheet_directory_uri()`. |
| Mobile nav close button | `core/navigation-overlay-close` (WP 7.0) | Place inside `core/navigation` with `"overlayMenu": "always"` or `"overlayMenu": "mobile"`. |
| `<div class="gallery">` / custom lightbox grid | `core/gallery` | WP 7.0 ships native lightbox support — no custom JS needed. Set `"lightbox": {"enabled": true}` on the `core/gallery` block. Clicking any image opens it fullscreen. If the design uses a custom lightbox JS library, replace it entirely with this native feature. |
| `<nav class="breadcrumbs">` | `core/breadcrumbs` (WP 7.0) | Native breadcrumbs block. Filter the trail via `block_core_breadcrumbs_items` to add/remove/reorder crumbs server-side. Never hand-roll a breadcrumb pattern for a WP 7.0 target. |

Using Core Blocks lets editors change colors, typography, and spacing via the Site Editor's global styles instead of editing CSS. Only fall back to a dynamic block (`register_block_type()` + `render_callback`) for sections that genuinely cannot be expressed with core.

---

## Full Worked Example

### Input: Static HTML file

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>example Landing</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background: #0a0a1a; color: #fff; }

    .hero { padding: 120px 40px; text-align: center; background: linear-gradient(135deg, #1a1a2e, #16213e); }
    .hero h1 { font-size: 3.5rem; margin-bottom: 1rem; }
    .hero p  { font-size: 1.25rem; opacity: 0.8; }

    .features { display: grid; grid-template-columns: repeat(3,1fr); gap: 2rem; padding: 80px 40px; }
    .card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 2rem; }
  </style>
</head>
<body>
  <section class="hero">
    <h1>Unlock Peak Performance</h1>
    <p>Science-backed examples, delivered to your door.</p>
    <a href="/shop" class="btn-primary">Shop Now</a>
  </section>

  <section class="features">
    <div class="card"><h3>Purity</h3><p>99%+ certified purity.</p></div>
    <div class="card"><h3>Speed</h3><p>Next-day UK delivery.</p></div>
    <div class="card"><h3>Support</h3><p>Expert guidance.</p></div>
  </section>

  <script>
    document.querySelectorAll( '.card' ).forEach( ( card ) => {
      card.addEventListener( 'mouseenter', () => card.style.transform = 'translateY(-4px)' );
      card.addEventListener( 'mouseleave', () => card.style.transform = '' );
    } );
  </script>
</body>
</html>
```

### Slug: `example-landing`

---

### Output: `templates/example-landing.html`

> **Note: Site Editor Only Layout**
> The example below hardcodes the `example-landing` pattern directly into the `.html` template. This means the Hero and Features sections can only be edited via the global Site Editor (Appearance > Editor), not the standard Page Editor. 
> To allow content creators to edit this natively in the Page Editor instead, remove the `wp:pattern` from this file, set `Inserter: true` in your pattern header, and instruct the user to insert the pattern dynamically into the page content.

```html
<!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->
<!-- wp:pattern {"slug":"{{THEME_SLUG}}/example-landing"} /-->
<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
<!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->
```

---

### Output: `patterns/example-landing.php` (master)

```php
<?php
/**
 * Title: example Landing Page
 * Slug: {{THEME_SLUG}}/example-landing
 * Categories: {{THEME_SLUG}}
 * Keywords: example, landing, performance
 * Inserter: false
 */
?>
  <!-- wp:pattern {"slug":"{{THEME_SLUG}}/example-landing-hero"} /-->
  <!-- wp:pattern {"slug":"{{THEME_SLUG}}/example-landing-features"} /-->
```

---

### Output: `patterns/example-landing-hero.php`

```php
<?php
/**
 * Title: example Landing — Hero
 * Slug: {{THEME_SLUG}}/example-landing-hero
 * Inserter: false
 */
?>
<!-- wp:group {
    "tagName": "section",
    "className": "is-style-example-landing-hero",
    "lock": {"move": true, "remove": true}
} -->
<section class="wp-block-group is-style-example-landing-hero">
    <!-- wp:heading {"level":1} -->
    <h1 class="wp-block-heading">Unlock Peak Performance</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Science-backed examples, delivered to your door.</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons -->
    <div class="wp-block-buttons">
        <!-- wp:button {"className":"btn-primary"} -->
        <div class="wp-block-button btn-primary"><a class="wp-block-button__link wp-element-button" href="/shop">Shop Now</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</section>
<!-- /wp:group -->
```

---

### Output: `patterns/example-landing-features.php`

```php
<?php
/**
 * Title: example Landing — Features
 * Slug: {{THEME_SLUG}}/example-landing-features
 * Inserter: false
 */
?>
<!-- wp:group {
    "tagName": "section",
    "layout": { "type": "grid", "columnCount": 3 },
    "className": "is-style-example-landing-features"
} -->
<section class="wp-block-group is-style-example-landing-features">
    <!-- wp:group {"className":"card"} -->
    <div class="wp-block-group card">
        <!-- wp:heading {"level":3} -->
        <h3 class="wp-block-heading">Purity</h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>99%+ certified purity.</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"className":"card"} -->
    <div class="wp-block-group card">
        <!-- wp:heading {"level":3} -->
        <h3 class="wp-block-heading">Speed</h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>Next-day UK delivery.</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
</section>
<!-- /wp:group -->
```

---

### Output: `patterns/example-landing-hero/style.css`

```css
/* ============================================================
   Scoped styles for the Hero pattern.
   ALL rules are prefixed with .is-style-example-landing-hero
   ============================================================ */

.is-style-example-landing-hero {
	background: linear-gradient(135deg, #1a1a2e, #16213e); /* 4. Colors — background before text properties */
	text-align: center;                                     /* 4. Colors & Typography */
}

/* Note: Padding/Margin are now handled in theme.json Section Styles */

.is-style-example-landing-hero h1 {
	margin-bottom: 1rem;  /* 3. Box model */
	font-size: 3.5rem;    /* 4. Colors & Typography */
	color: #fff;          /* 4. Colors & Typography */
}

.is-style-example-landing-hero p {
	font-size: 1.25rem;  /* 4. Colors & Typography */
	color: #fff;         /* 4. Colors & Typography */
	opacity: 0.8;        /* 5. Other */
}

.is-style-example-landing-hero .btn-primary {
	display: inline-block;                                  /* 1. Display */
	margin-top: 2rem;                                       /* 3. Box model */
	padding: 1rem 2.5rem;                                   /* 3. Box model */
	border-radius: 8px;                                     /* 3. Box model */
	background: var(--wp--preset--color--accent, #e94560); /* 4. Colors */
	color: #fff;                                            /* 4. Colors */
	text-decoration: none;                                  /* 4. Typography */
}
```

---

### Output: `patterns/example-landing-features/style.css`

```css
.is-style-example-landing-features .card {
	padding: 2rem;                          /* 3. Box model */
	border-radius: 12px;                    /* 3. Box model */
	background: rgba(255, 255, 255, 0.05); /* 4. Colors */
	transition: transform 0.2s ease;        /* 5. Other */
}
```

> Note: The hover effect is now pure CSS — this is better than JavaScript for simple transforms.

---

> **Push layout to theme.json.** Padding, margin, and gap belong in `theme.json` Section Styles (variations under `styles.blocks.{block}.variations.{name}`). Reserve `style.css` for things JSON cannot express — gradients, complex transforms, scoped overrides. Pure CSS hover effects on native Grid items are preferred over JS for performance.

### Interactivity API (Modern Logic Example)

When you need reactive logic (e.g., a "Save to Favorites" toggle), use the Interactivity API with **Reactive State**:

**1. Markup (`patterns/example-landing-hero.php`):**
```html
<div data-wp-interactive="example-landing/hero" data-wp-context='{ "isSaved": false }'>
    <button 
        data-wp-on--click="actions.toggleSave"
        data-wp-class--is-active="context.isSaved"
    >
        <span data-wp-text="state.saveLabel">Save</span>
    </button>
</div>
```

**2. Logic (`patterns/example-landing-hero/index.js`):**
```js
import { store } from '@wordpress/interactivity';

store( 'example-landing/hero', {
	state: {
		get saveLabel() {
			const { isSaved } = context;
			return isSaved ? 'Saved!' : 'Save for Later';
		},
	},
	actions: {
		toggleSave: () => {
			context.isSaved = ! context.isSaved;
		},
	},
} );
```

### Accordion Conversion (WP 6.9+)

Replace static FAQ HTML with the native `core/accordion` block:

```html
<!-- wp:accordion -->
<div class="wp-block-accordion">
    <!-- wp:accordion-item {"title":"What is the purity?"} -->
    <div class="wp-block-accordion-item">
        <p>Our examples are 99% certified purity.</p>
    </div>
    <!-- /wp:accordion-item -->
</div>
<!-- /wp:accordion -->
```

---

### Output: `theme.json` addition

```json
"customTemplates": [
  {
    "name": "example-landing",
    "title": "example Landing Page",
    "postTypes": ["page"]
  }
]
```

---

### Output: `functions.php` additions

```php
<?php
declare(strict_types=1);

// New pattern asset registration:
function {{THEME_SLUG}}_example_landing_assets(): void {
    // 1. Register and bind Hero CSS
    wp_register_style(
        handle: 'example-landing-hero-style',
        src:    get_stylesheet_directory_uri() . '/patterns/example-landing-hero/style.css',
        deps:   array(),
        ver:    '1.0.0'
    );
    wp_style_add_data( 'example-landing-hero-style', 'path', get_stylesheet_directory() . '/patterns/example-landing-hero/style.css' );

    // 2. Register and bind Features CSS
    wp_register_style(
        handle: 'example-landing-features-style',
        src:    get_stylesheet_directory_uri() . '/patterns/example-landing-features/style.css',
        deps:   array(),
        ver:    '1.0.0'
    );
    wp_style_add_data( 'example-landing-features-style', 'path', get_stylesheet_directory() . '/patterns/example-landing-features/style.css' );

    // 3. Register Script Module for Interactivity logic
    wp_register_script_module(
        id:      'example-landing/hero-logic',
        src:     get_stylesheet_directory_uri() . '/patterns/example-landing-hero/index.js',
        deps:    array( '@wordpress/interactivity' ),
        version: '1.0.0'
    );

    // 4. Bind CSS + Script Module to the Hero block style (one register_block_style call with both handles)
    register_block_style( 'core/group', array(
        'name'                 => 'example-landing-hero',
        'style_handle'         => 'example-landing-hero-style',
        'script_module_handle' => 'example-landing/hero-logic',
    ) );

    // 5. Bind Features CSS
    register_block_style( 'core/group', array(
        'name'         => 'example-landing-features',
        'style_handle' => 'example-landing-features-style',
    ) );
}
add_action( 'init', '{{THEME_SLUG}}_example_landing_assets' );
```

---

## CSS Scoping Patterns

### What to strip from the original CSS

Remove these before scoping — they will conflict with WordPress global styles:

```css
/* REMOVE — resets that break WP */
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: ...; }
html { scroll-behavior: smooth; }

/* REMOVE — font-face declarations (move to theme.json fontFamilies instead) */
@font-face { font-family: 'Inter'; src: url(...); }

/* KEEP — everything else, just add the scope prefix */
.hero { ... }
```

### What to keep (just prefix it)

```css
/* Keep layout, color, spacing — just prefix */
.is-style-example-landing-hero { ... }
.is-style-example-landing-features .card { ... }
```

### Handling `:root` variables

```css
/* Original */
:root {
  --accent: #e94560;
  --bg: #0a0a1a;
}

/* Converted — move to theme.json color palette OR scope under the component */
.is-style-example-landing-hero {
  --accent: #e94560;
  --bg: #0a0a1a;
}
```

Prefer moving design tokens to `theme.json` color/spacing palettes so they become available
throughout the editor. Keep them as CSS custom properties on the wrapper only if they are
too template-specific.

### Media queries — no change needed

Media queries don't need the wrapper prefix added to the query itself, only to the selectors
within:

```css
/* Correct */
@media (max-width: 768px) {
  /* No manual grid column rules needed in 2026. */
  /* Native WP Grid handles responsiveness via layout attributes. */
}
```

### Viewport Visibility (WP 7.0)

Use `metadata.blockVisibility.viewport` to show or hide blocks by device type. Do not use CSS `display: none` on breakpoints for this — WordPress handles the suppression automatically via an injected CSS class. **Note (WP 7.0):** viewport hiding is CSS-based; hidden blocks remain in the DOM on all devices. Do not use this feature for access control or security gating — the content is still present in the page source.

**Before (CSS approach — do not use in WP 7.0):**

```css
/* Do NOT do this */
.desktop-only-section { display: none; }
@media (min-width: 1024px) { .desktop-only-section { display: block; } }
```

**After (viewport visibility — use this):**

```html
<!-- wp:group {
  "metadata": {
    "blockVisibility": {
      "viewport": ["desktop"]
    }
  }
} -->
<div class="wp-block-group">
  <!-- Renders only on desktop. Not present in mobile/tablet DOM. -->
</div>
<!-- /wp:group -->
```

Enable in `theme.json settings` before using:

```json
"settings": {
  "blockVisibility": { "viewport": true }
}
```

---

## JavaScript Patterns

### DOMContentLoaded wrapper

Always wrap in this guard — WordPress sometimes loads scripts before the DOM is fully parsed:

```js
document.addEventListener( 'DOMContentLoaded', function () {
	// your code
} );
```

### Scoping JS selectors

If the original JS uses broad selectors like `document.querySelectorAll('.card')`, scope them
to the template wrapper to avoid accidental matches in the editor sidebar:

```js
// Original
document.querySelectorAll( '.card' ).forEach( ... );

// Safer — scoped to the component
const wrapper = document.querySelector( '.is-style-example-landing-features' );
if ( ! wrapper ) return;
wrapper.querySelectorAll( '.card' ).forEach( ... );
```

### Skipping animations in the editor

Some animations look jarring inside the Site Editor iframe. Gate them:

```js
document.addEventListener( 'DOMContentLoaded', function () {
	if ( window.frameElement || document.body.classList.contains( 'editor-styles-wrapper' ) ) return;
	// animations here — won't run in editor
} );
```

### Icon Conversion (WP 7.0)

WordPress 7.0 ships a `core/icon` block backed by a REST endpoint at `/wp/v2/icons`. **WP 7.0 has no public API for registering custom theme icons against `core/icon`** — the icon registry is internal. The public registration API is planned for WP 7.1.

**For built-in core icons** (star, close, arrow, etc.), use `core/icon` directly:

```html
<!-- wp:icon {"icon":"core/star"} /-->
```

**For custom/theme icons**, output the SVG from a PHP dynamic block or as an image:

```php
// Option A — inline SVG from a render_callback (preferred for accessibility)
'render_callback' => function(): string {
    $icon_path = get_stylesheet_directory() . '/assets/icons/warning.svg';
    $svg = file_exists( $icon_path ) ? file_get_contents( $icon_path ) : '';  // phpcs:ignore
    return wp_kses( $svg, wp_kses_allowed_html( 'post' ) );
},

// Option B — img element
echo '<img src="' . esc_url( get_stylesheet_directory_uri() . '/assets/icons/warning.svg' ) . '" alt="' . esc_attr__( 'Warning', '{{TEXT_DOMAIN}}' ) . '" width="24" height="24">';
```

Do NOT use `<!-- wp:icon {"icon":"{{THEME_SLUG}}/warning"} /-->` — custom namespace slugs are unregistered in WP 7.0 and the block renders empty with no error.

---

## Image Path Conversion

### Static HTML path → PHP dynamic path

```html
<!-- Original -->
<img src="images/hero.webp" alt="Hero">
<img src="/assets/hero.webp" alt="Hero">
<img src="https://my-old-site.com/hero.webp" alt="Hero">

<!-- Converted (all cases) -->
<img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/hero.webp" alt="Hero">
```

### Background images in inline styles

```html
<!-- Original -->
<div style="background-image: url('images/bg.jpg')">

<!-- Converted -->
<div style="background-image: url('<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/bg.jpg')">
```

### Background images in CSS

Keep these in the scoped CSS file — no PHP needed there since the CSS is loaded via
`wp_enqueue_style` which resolves relative URLs relative to the stylesheet:

```css
.is-style-example-landing-hero .hero {
  background-image: url('../../../assets/images/bg.jpg');
  /* Path is relative to the CSS file location */
  /* patterns/example-landing/style.css → ../../.. = theme root */
}
```

Alternatively, use `get_stylesheet_directory_uri()` in a PHP-generated inline style to avoid
counting `../` hops.

---

## Navigation Conversion

A static `<nav>` bar in the HTML design should be evaluated case-by-case:

| Scenario | Recommended approach |
|---|---|
| Template is a full-page landing (no WP nav needed) | Render the static nav from a dynamic block registered via `register_block_type()` + `render_callback`. |
| Template should use the site's WP menus | Replace with `<!-- wp:navigation /-->` block |
| Template needs a custom fixed nav | Keep in a dedicated `{slug}-nav.php` sub-pattern |
| Template has a mobile hamburger/overlay menu (WP 7.0) | Use `core/navigation` with `"overlayMenu": "mobile"` and add `core/navigation-overlay-close` inside |

**Using native WP navigation block:**

```html
<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","justifyContent":"space-between"}} /-->
```

This renders the menu registered in WP Admin → Appearance → Menus (or created in the Site Editor).

**Mobile overlay with close button (WP 7.0):**

```html
<!-- wp:navigation {"overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"space-between"}} -->
  <!-- wp:navigation-overlay-close /-->
  <!-- wp:navigation-link {"label":"Home","url":"/"} /-->
  <!-- wp:navigation-link {"label":"About","url":"/about"} /-->
<!-- /wp:navigation -->
```

---

## Forms and Third-Party Embeds

### Contact forms (CF7, WPForms, Gravity Forms)

Do **not** paste raw form HTML into a block. Use a shortcode block instead:

```html
<!-- wp:shortcode -->
[contact-form-7 id="123" title="Contact Form"]
<!-- /wp:shortcode -->
```

### iframes (Google Maps, Calendly, etc.)

Render iframes from a dynamic block. The block's `render_callback` returns the iframe markup, and the editor shows a placeholder via `ServerSideRender`:

```html
<!-- wp:{{THEME_SLUG}}/map-embed /-->
```

```php
register_block_type( '{{THEME_SLUG}}/map-embed', array(
    'render_callback' => function( array $attributes, string $content, WP_Block $block ): string {
        return '<div class="map-wrapper"><iframe src="https://maps.google.com/..." width="100%" height="400" loading="lazy" allowfullscreen></iframe></div>';
    },
) );
```

### Video embeds

Prefer the native WP embed block for YouTube/Vimeo:

```html
<!-- wp:embed {"url":"https://youtu.be/abc123","type":"video","providerNameSlug":"youtube"} -->
<figure class="wp-block-embed is-type-video is-provider-youtube">
  <div class="wp-block-embed__wrapper">
    https://youtu.be/abc123
  </div>
</figure>
<!-- /wp:embed -->
```

For `<video>` tags with local sources, render the markup from a dynamic block's `render_callback`.

---

## Animations and Scroll Effects

### CSS animations (preferred)

Move CSS `@keyframes` and `animation` properties into the scoped stylesheet. They work fine:

```css
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0);    }
}

.is-style-example-landing-hero .hero h1 {
  animation: fadeUp 0.6s ease forwards;
}
```

### Intersection Observer (scroll-triggered)

Works fine in `index.js` when properly guarded:

```js
document.addEventListener( 'DOMContentLoaded', function () {
	if ( window.frameElement || document.body.classList.contains( 'editor-styles-wrapper' ) ) return;

	const observer = new IntersectionObserver( ( entries ) => {
		entries.forEach( ( entry ) => {
			if ( entry.isIntersecting ) {
				entry.target.classList.add( 'in-view' );
				observer.unobserve( entry.target );
			}
		} );
	}, { threshold: 0.15 } );

	const wrapper = document.querySelector( '.is-style-example-landing-features' );
	if ( ! wrapper ) return;
	wrapper.querySelectorAll( '[data-animate]' ).forEach( ( el ) => observer.observe( el ) );
} );
```

### GSAP / external animation libraries

If the HTML uses GSAP or similar, enqueue the library before your script:

```php
wp_enqueue_script(
    'gsap',
    'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js',
    array(),
    '3.12.2',
    array( 'in_footer' => true, 'strategy' => 'defer' )
);
wp_enqueue_script(
    'my-template-script',
    get_stylesheet_directory_uri() . '/patterns/my-template/index.js',
    array( 'gsap' ),  // ← depends on gsap
    '1.0.0',
    array( 'in_footer' => true, 'strategy' => 'defer' )
);
```

---

## CSS Reset Handling

The most dangerous part of any HTML→WordPress conversion is CSS resets. WordPress injects its
own global styles; a `* { margin: 0; box-sizing: border-box; }` under `.wrapper` will clobber
editor controls.

### Rules to always strip

```css
/* ALWAYS STRIP before scoping */
*, *::before, *::after { ... }
html { ... }
body { ... }
:root { ... }  /* move variables to theme.json or to .wrapper */
```

### Rules that are borderline — check before keeping

```css
/* Check: heading resets affect WP editor headings */
h1, h2, h3, h4, h5, h6 { margin: 0; }

/* Safe IF scoped */
.is-style-example-landing-hero h1 { margin: 0; }
```

### Transition for designers new to WordPress

Tell designers: "Your reset CSS is now WordPress's job. We only style what's inside our wrapper."

---

## Multi-File Input

When the user provides separate HTML, CSS, and JS files:

1. Parse the HTML to find `<link rel="stylesheet">` and `<script src="">` tags.
2. Those referenced files are the CSS/JS to convert.
3. Any additional CDN links (fonts, icon libraries) need to be enqueued in `functions.php`:

```php
// Example: user's HTML had <link href="https://fonts.googleapis.com/css2?family=Inter...">
function {{THEME_SLUG}}_my_template_fonts( string $block_content, array $block ): string {
    if ( isset( $block['attrs']['className'] ) && str_contains( $block['attrs']['className'], 'is-style-my-template' ) ) {
        wp_enqueue_style(
            'my-template-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap',
            array(),
            null
        );
    }
    return $block_content;
}
add_filter( 'render_block', '{{THEME_SLUG}}_my_template_fonts', 10, 2 );
```

Better: download the fonts locally and register them in `theme.json fontFamilies` instead.

---

## Edge Cases and Gotchas

### `<head>` meta tags

Ignore `<title>`, `<meta>`, `<link rel="icon">` etc. from the HTML — WordPress handles these.
Only take content from within the `<body>` tag.

### Inline SVGs

Prefer the `core/icon` block plus the Icon Registration API (see the icon conversion section above). If you must keep an inline SVG (e.g. a one-off decorative shape), emit it from a dynamic block's `render_callback` rather than pasting it raw into a template:

```html
<!-- wp:{{THEME_SLUG}}/decorative-svg /-->
```

### `<script type="application/ld+json">` (structured data)

Move to `functions.php` using `wp_head` hook:

```php
function my_template_structured_data( string $block_content, array $block ): string {
    static $has_run = false;
    if ( ! $has_run && isset( $block['attrs']['className'] ) && str_contains( $block['attrs']['className'], 'is-style-my-template' ) ) {
        $has_run = true;
        add_action( 'wp_footer', function(): void {
            echo '<script type="application/ld+json">' . wp_json_encode( array(
                '@context' => 'https://schema.org',
                '@type'    => 'Organization',
                'name'     => get_bloginfo( 'name' ),
            ) ) . '</script>';
        } );
    }
    return $block_content;
}
add_filter( 'render_block', 'my_template_structured_data', 10, 2 );
```

### Sections that need dynamic WP data

If a section needs the latest posts, a product list, or a custom field value, it cannot be raw
If a section needs the latest posts, a product list, or a custom field value, it cannot be raw HTML. Options:

1. Use a native WP block (Query Loop, Post Title, etc.)
2. Use a shortcode: `[my_custom_shortcode]` inside `<!-- wp:shortcode -->`
3. Register a custom block (advanced — beyond the scope of this skill)

### CSS `calc()` and `clamp()` — no changes needed

These work as-is inside scoped CSS:

```css
.is-style-example-landing-hero h1 {
  font-size: clamp(2rem, 5vw, 4rem);
}
```

### Tailwind CSS utility classes in the HTML

If the HTML uses Tailwind classes (`text-xl`, `flex`, `gap-4`), you have two options:

1. **Strip Tailwind, rewrite with vanilla CSS** — preferred for long-term maintainability.
2. **Include the Tailwind CDN** via `wp_enqueue_style` scoped to the template — acceptable for
   quick prototypes but adds a large payload.

Never include the Tailwind CDN globally — it will break the WordPress editor UI.
