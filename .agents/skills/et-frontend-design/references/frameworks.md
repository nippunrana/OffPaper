# Framework-Specific Implementation Guide

Reference this file when the stack is known. Default to HTML/CSS/JS when unclear — it covers
~70% of projects and requires no build tooling.

---

## HTML/CSS/JS (default)

- Vanilla CSS with custom properties. BEM naming for class structure
  (`.card`, `.card__title`, `.card--featured`).
- Modular stylesheets: one `<link>` per major section, or scoped `<style>` blocks.
- WordPress-ready structure: wrap sections in unique class names for style scoping
  (e.g., `.hero-section-wrapper`). Structure assets to be compatible with
  `wp_enqueue_style` / `wp_enqueue_script`.
- Vanilla JavaScript with `defer` attribute. No framework overhead for simple interactions.

---

## React / Next.js

- CSS Modules or Tailwind for style scoping. One component per file.
- Motion library (formerly Framer Motion) for animations.
- Server components by default. Client components (`'use client'`) only when interactive
  state or browser APIs are needed.
- Use `next/image` for automatic image optimization — handles responsive sizing and modern
  formats (AVIF/WebP) automatically.

---

## Tailwind CSS

- Use `@apply` sparingly — prefer direct utility classes in markup.
- Define design tokens (colors, spacing, fonts) in `tailwind.config.js` as the single source
  of truth — not scattered across components.
- Extract repeated utility classes into component CSS (using `@apply`) only if the exact same
  combination of 4+ utilities is repeated across 3+ separate components. Otherwise keep
  utilities inline to preserve readability and design flexibility.

---

## Mobile App Screens (Android / iOS)

- Respect platform conventions: iOS safe area insets (`env(safe-area-inset-*)`), Android
  material elevation patterns.
- Use native-feeling navigation: bottom tab bars, stack navigation with back gestures.
- Touch targets: 44pt minimum on iOS, 48dp minimum on Android.
- Design for both portrait and landscape where applicable.
