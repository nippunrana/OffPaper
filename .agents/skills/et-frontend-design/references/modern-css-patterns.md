# Modern CSS Patterns (2025-2026)

Code examples for CSS-native solutions that replace JavaScript. Use these patterns to reduce
bundle size, improve performance, and leverage browser-native capabilities.

---

## Container Queries

Components respond to their container's size, not the viewport. Essential for reusable components
that live in different contexts (sidebar vs. main content vs. full-width).

```css
.card-container {
  container-type: inline-size;
}

.card {
  display: grid;
  gap: var(--space-4);
}

@container (min-width: 400px) {
  .card {
    grid-template-columns: 200px 1fr;
  }
}

@container (min-width: 600px) {
  .card {
    grid-template-columns: 250px 1fr;
    gap: var(--space-6);
  }
}
```

**Browser support:** All modern browsers (Baseline 2023). Safe for production.

---

## :has() Selector

The "parent selector" — style elements based on what they contain or what follows them.

```css
/* Style form when it has a focused input */
.form-group:has(:focus-visible) {
  outline: 2px solid var(--color-accent);
  outline-offset: 4px;
  border-radius: var(--radius-md);
}

/* Show error styling when input has invalid state */
.form-group:has(:invalid:not(:placeholder-shown)) {
  .form-label { color: var(--color-error); }
  .form-input { border-color: var(--color-error); }
  .form-error { display: block; }
}

/* Conditional layout: card with image vs. card without */
.card:has(img) {
  grid-template-columns: 200px 1fr;
}

.card:not(:has(img)) {
  grid-template-columns: 1fr;
}

/* Style nav items that contain dropdowns */
nav li:has(ul) > a::after {
  content: '\25BC'; /* down arrow */
  margin-left: var(--space-2);
  font-size: 0.6em;
}
```

**Browser support:** 95%+ global support. Safe for production with graceful degradation.

---

## Scroll-Driven Animations

Tie animation progress to scroll position — zero JavaScript, zero main-thread blocking.

```css
/* Reading progress bar */
.progress-bar {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: var(--color-accent);
  transform-origin: left;
  animation: grow-progress linear;
  animation-timeline: scroll();
}

@keyframes grow-progress {
  from { transform: scaleX(0); }
  to   { transform: scaleX(1); }
}

/* Reveal elements on scroll into view */
.reveal-on-scroll {
  opacity: 0;
  transform: translateY(30px);
  animation: reveal linear both;
  animation-timeline: view();
  animation-range: entry 0% entry 100%;
}

@keyframes reveal {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Parallax background */
.parallax-bg {
  animation: parallax linear;
  animation-timeline: scroll();
}

@keyframes parallax {
  from { transform: translateY(0); }
  to   { transform: translateY(-100px); }
}
```

**Browser support:** Chrome 115+, Edge 115+. Use with `@supports (animation-timeline: scroll())`
for progressive enhancement — fallback to static layout in unsupported browsers.

---

## View Transitions API

Smooth page and state transitions with minimal code. Works for both SPA and multi-page apps.

```css
/* Basic cross-fade for all navigations */
@view-transition {
  navigation: auto;
}

/* Name specific elements for targeted transitions */
.hero-image {
  view-transition-name: hero;
}

.page-title {
  view-transition-name: title;
}

/* Customize the transition animation */
::view-transition-old(hero) {
  animation: fade-out 0.3s var(--ease-out-expo);
}

::view-transition-new(hero) {
  animation: fade-in 0.3s var(--ease-out-expo);
}

/* Theme toggle with view transition */
/* Trigger in JS: document.startViewTransition(() => toggleTheme()) */
::view-transition-old(root) {
  animation: none;
}

::view-transition-new(root) {
  animation: slide-from-right 0.4s var(--ease-out-expo);
}
```

**Browser support:** Chrome 111+ (same-document), Chrome 126+ (cross-document), Safari 18.2+.
Always honors `prefers-reduced-motion`.

---

## @starting-style (Animating from display: none)

Animate modals, popovers, and dropdowns when they first appear.

```css
dialog, [popover] {
  opacity: 0;
  transform: translateY(10px);
  transition: opacity 0.3s var(--ease-out-expo),
              transform 0.3s var(--ease-out-expo),
              overlay 0.3s allow-discrete,
              display 0.3s allow-discrete;
}

dialog[open], [popover]:popover-open {
  opacity: 1;
  transform: translateY(0);

  @starting-style {
    opacity: 0;
    transform: translateY(10px);
  }
}
```

**Browser support:** Chrome 117+, Safari 17.5+, Firefox 129+. Baseline Newly Available.

---

## Anchor Positioning

Position tooltips, popovers, and dropdowns relative to their trigger — pure CSS.

```css
.trigger {
  anchor-name: --my-trigger;
}

.tooltip {
  position: fixed;
  position-anchor: --my-trigger;

  /* Position below the trigger, centered */
  top: anchor(bottom);
  left: anchor(center);
  translate: -50% var(--space-2);

  /* Auto-flip if not enough space below */
  position-try-fallbacks: flip-block;
}
```

**Browser support:** Chrome 125+, Edge 125+. Baseline early 2026. Use as progressive
enhancement; fall back to JavaScript positioning in older browsers.

---

## CSS Nesting

Native nesting without preprocessors. Reduces repetition and improves readability.

```css
.card {
  padding: var(--space-6);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--duration-normal) var(--ease-out-cubic);

  &:hover {
    box-shadow: var(--shadow-md);
  }

  & .card__title {
    font-size: var(--text-xl);
    line-height: var(--leading-tight);
    letter-spacing: var(--tracking-tight);
  }

  & .card__body {
    font-size: var(--text-base);
    line-height: var(--leading-normal);
    color: var(--color-text-secondary);
  }

  /* WARNING: Native CSS does not support BEM suffix concatenation like Sass (e.g. &--featured is invalid) */
  &.card--featured {
    border: 2px solid var(--color-accent);
  }
}
```

**Browser support:** All modern browsers (Baseline 2023). Safe for production.

---

## Reduced Motion Fallback

Always include this. It's an accessibility requirement and a design decision.

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```
