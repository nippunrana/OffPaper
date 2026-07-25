# WordPress CSS & HTML Coding Standards

**Load this file before writing any hand-authored CSS or real HTML markup** (PHP patterns,
`render_callback` output, `<img>` / `<br />` in patterns). It is *not* needed for `theme.json`
or `wp:*` block-delimiter JSON — those follow JSON syntax, not HTML attribute rules (see §3 note).

Source: the official WordPress CSS and HTML Coding Standards handbook pages.

---

## Table of Contents

1. [CSS — physical structure](#1-css--physical-structure)
2. [CSS — logical property ordering](#2-css--logical-property-ordering)
3. [CSS — value formatting](#3-css--value-formatting)
4. [CSS — vendor prefixes](#4-css--vendor-prefixes)
5. [CSS — hygiene](#5-css--hygiene)
6. [HTML — attribute rules](#6-html--attribute-rules)

---

## 1. CSS — physical structure

Use **tabs** for indentation — never spaces. This lets each developer adjust their viewing
preference without touching the file, so the codebase stays consistent across editors.

```css
/* ✅ correct */
.is-style-hero-section {
	display: flex;
	padding: 4rem 2rem;
	background: #1a1a2e;
	color: #fff;
}

/* ❌ wrong — spaces */
.is-style-hero-section {
  display: flex;
  padding: 4rem 2rem;
}
```

- Each **selector** on its own line, ending in `,` or `{`.
- Each **property-value pair** on its own line, indented one tab, ending in `;`.
- Closing `}` flush-left, matching the indentation of its selector.
- **Two blank lines** between major sections; **one blank line** between individual blocks.

---

## 2. CSS — logical property ordering

Properties are grouped by *what they do*, not alphabetically. Alphabetical ordering separates
related properties (e.g. `position` and `top`) just because of their place in the alphabet —
logical ordering creates a readable flow from the outside in.

**The five groups, in order:**

1. **Display** — `display`, `visibility`, `float`, `clear`
2. **Positioning** — `position`, `top`, `right`, `bottom`, `left`, `z-index`
3. **Box model** — `width`, `height`, `margin`, `padding`, `border`
4. **Colors & Typography** — `background` (before `color`), `color`, `font`, `font-weight`,
   `font-size`, `line-height`, `text-align`, `text-decoration`, `opacity`
5. **Other** — `transition`, `animation`, `transform`, `cursor`

**TRBL order for sides.** For `margin`, `padding`, `border-width`: Top → Right → Bottom → Left.
This matches CSS shorthand value order and keeps the code predictable.

**Clockwise corners for border-radius:** top-left → top-right → bottom-right → bottom-left.

```css
/* ✅ correct order */
.is-style-main-cta .btn-primary {
	display: inline-block;       /* 1. Display */
	padding: 1rem 2.5rem;        /* 3. Box model */
	border-radius: 8px;          /* 3. Box model */
	background: var(--wp--preset--color--accent, #e94560); /* 4. Colors */
	color: #fff;                 /* 4. Colors */
	text-decoration: none;       /* 4. Colors & Typography */
	transition: opacity 0.2s;    /* 5. Other */
}

/* ❌ wrong — border-radius after color, background after padding */
.btn-primary {
	display: inline-block;
	padding: 1rem 2.5rem;
	background: …;
	color: #fff;
	border-radius: 8px;
	text-decoration: none;
}
```

---

## 3. CSS — value formatting

| Rule | Correct | Wrong |
|------|---------|-------|
| Hex colours | `#fff`, `#e94560` | `#FFF`, `#FFFFFF` |
| Shorten hex | `#fff` | `#ffffff` |
| Rgba (no paren padding) | `rgba(0, 0, 0, 0.5)` | `rgba( 0, 0, 0, 0.5 )` |
| Leading zero | `0.5` | `.5` |
| Unitless zero | `margin: 0` | `margin: 0px` (exception: `transition-duration`) |
| Line-height | `line-height: 1.5` | `line-height: 24px` |
| Font-weight | `font-weight: 700` | `font-weight: bold` |
| Quotes | `font-family: "Helvetica Neue", sans-serif` | single quotes |
| Multi-line shadow | (see below) | `box-shadow: 0 2px 4px rgba(0,0,0,0.1), 0 8px 16px rgba(0,0,0,0.2)` |

**Multi-line `box-shadow` / `text-shadow`** — each value on its own line, indented one level:

```css
.is-style-card {
	box-shadow:
		0 2px 4px rgba(0, 0, 0, 0.1),
		0 8px 16px rgba(0, 0, 0, 0.2);
}
```

**Attribute selector quoting:** always use double quotes — `[type="text"]` not `[type=text]`.

**JSON vs HTML scope guard.** The rules in §6 (double-quote attributes, boolean bare form) apply
only to **real HTML output** — PHP patterns, `render_callback` strings, `<img>` etc. The `wp:*`
block-delimiter comments use JSON for their attributes (`{"className":"is-style-…","lock":{…}}`),
which follows JSON syntax (double-quoted keys and string values — that's already the right form).
Do not confuse the two contexts.

---

## 4. CSS — vendor prefixes

This skill's asset pipeline (`register_block_style()` + `wp_style_add_data()` pointing directly
at `patterns/*/style.css`) has **no build step** — nothing runs Autoprefixer automatically.

Write prefixes manually when needed, longest to shortest:

```css
/* Example — appearance */
-webkit-appearance: none;
-moz-appearance: none;
appearance: none;
```

In practice, the modern CSS properties this skill uses (Flexbox, Grid, CSS custom properties,
`clamp()`) are broadly supported without prefixes in browsers that support WordPress block themes.
Only reach for manual prefixes if you encounter a real browser-support issue for the specific
property in use.

---

## 5. CSS — hygiene

- **No magic numbers.** A rule like `margin-top: 37px` to "force" a layout is a sign of a broken
  container. Find and fix the root cause; don't layer on a one-off fix.
- **No over-qualified selectors.** Write `.container`, not `div.container` — the tag ties the
  selector to one element type and makes the CSS brittle.
- **Don't restate defaults.** `display: block` on a `<div>` adds noise without meaning.
- **Subtraction-first.** When debugging layout, remove conflicting code before adding new rules.
  The correct layout is usually already "in there" once the conflicting styles are gone.
- **Target elements directly.** Use `.is-style-hero h1` rather than deep descendant chains.

---

## 6. HTML — attribute rules

These apply to any **real HTML** your code emits: PHP patterns, `render_callback` strings,
`<img>` / `<input>` / `<br />` tags, inline SVGs.

**Always double-quote attributes.** This is a security requirement, not just a style preference.
An unquoted attribute value containing a space gets parsed as multiple attributes, which can be
exploited to inject malicious event handlers (XSS). `esc_attr()` / `esc_url()` escapes the
content; quoting the attribute closes the injection vector.

```php
/* ✅ correct — quoted + escaped */
<img
	src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/hero.webp"
	alt="<?php echo esc_attr( $alt_text ); ?>"
	width="1200"
	height="600"
/>

/* ❌ wrong — unquoted */
<img src=<?php echo esc_url( $src ); ?> alt=Hero />
```

**Boolean attributes — prefer the bare form:**

```html
<!-- ✅ preferred HTML5 -->
<input type="checkbox" checked>
<button disabled>Submit</button>

<!-- acceptable (XHTML-style value mirrors the attribute name) -->
<input type="checkbox" checked="checked">

<!-- ❌ invalid — HTML5 does not recognise these values -->
<input type="checkbox" checked="true">
<button disabled="false">Submit</button>
```

**Self-closing/void elements — exactly one space before `/>` :**

```html
<br />    <!-- ✅ -->
<hr />    <!-- ✅ -->
<br/>     <!-- ❌ no space -->
<br>      <!-- acceptable in HTML5, but WP style is the spaced form -->
```

**Lowercase machine-readable values, Title Case for human-readable:**

| Context | Rule | Example |
|---------|------|---------|
| Machine-interpreted (IDs, classes, types, charsets) | All lowercase | `type="text"`, `charset="utf-8"` |
| Human-readable (titles, alt text) | Title Case | `title="Back to Top"`, `alt="Company Logo"` |
