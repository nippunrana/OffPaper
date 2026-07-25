# Modern CSS and Design Engineering Craft

## Modern CSS
Prefer CSS-native solutions over JavaScript wherever possible. The CSS platform in 2025-2026 provides powerful features that eliminate entire JavaScript libraries:

- **CSS nesting** — reduce selector repetition, improve readability.
- **Container queries** (`@container`) — component-level responsiveness without media queries.
- **`:has()` selector** — parent selection, form validation styling, conditional layouts based on content presence.
- **Subgrid** — align nested grid children perfectly to parent grid tracks.
- **Scroll-driven animations** (`animation-timeline: scroll()`) — parallax, progress bars, reveal-on-scroll effects without any JavaScript.
- **View Transitions API** — smooth page and state transitions with minimal code.
- **`@starting-style`** — animate elements entering from `display: none` (modals, popovers).
- **Anchor positioning** — tooltips and popovers positioned relative to triggers, pure CSS.

Read `references/modern-css-patterns.md` for code examples and browser support notes.

---

## Design Engineering Craft
These details separate good from exceptional. They're invisible individually but compound into the feeling that something was "designed by a human, not generated."

- **Optical alignment over mathematical:** Center text and icons visually, not geometrically. Play button icons need a slight right offset. Circles need padding adjustment to appear visually centered in a square container.
- **Concentric border radii:** Inner element radius = outer radius minus the gap between them. `border-radius: calc(var(--outer-radius) - var(--gap))`. Parallel curves look intentional; mismatched radii look sloppy.
- **Layered shadows:** Use 2-4 transparent `box-shadow` layers at different offsets, blurs, and opacities instead of a single solid shadow. This mimics real-world light diffusion.
- **Number formatting:** `font-variant-numeric: tabular-nums` for prices, counters, timers, or any number that updates — prevents layout jitter from variable-width digits.
- **Text wrapping:** `text-wrap: balance` for headings. `text-wrap: pretty` for paragraphs. Balanced headings prevent awkward short last lines; pretty paragraphs avoid orphaned words.
- **Easing quality:** `cubic-bezier(0.16, 1, 0.3, 1)` for expressive deceleration. Never use `linear` for UI motion. Spring-based easing for physical interactions (drag, toss, snap).
- **Accessibility basics:** Semantic HTML (`<nav>`, `<main>`, `<article>`), `:focus-visible` focus indicators (never `outline: none` without replacement), color contrast ratio 4.5:1 minimum for body text, `alt` text on all images, `aria-label` on icon-only buttons.
