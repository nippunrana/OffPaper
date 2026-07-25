# Domain: Visual / UI Layout

Use this reference when the bug is a visible mismatch between expected and actual rendering — misaligned elements, CSS not applying, flexbox/grid layout broken, responsive breakage, z-index stacking, WordPress global styles overriding custom ones, hidden/clipped elements.

The probe mode for this domain is almost always a **browser-console snippet** — read-only, paste-and-run, copies a JSON diagnostic report to the clipboard. No source edits needed.

---

## 1. Classify the issue

Sub-category (drives which `computed` block to include in the snippet):

- `positioning` — wrong position, offset, overlap, z-index, transform
- `layout` — flexbox/grid not behaving, items not aligning, wrapping unexpectedly
- `spacing` — margin/padding/gap wrong, box-model issues
- `responsive` — mobile layout broken, media queries not triggering
- `cascade` — a rule exists but is being overridden (very common with WordPress global styles, Tailwind utilities, framework defaults)
- `visibility` — element hidden, clipped, zero-size, or behind something else

If you can't identify a specific element, use the **click-to-inspect** pattern (see bottom of this file).

---

## 2. Report spec

Generate a self-contained, paste-ready browser console snippet (an IIFE, so it doesn't leak variables into the global scope) that builds and prints one JSON diagnostic report. Always include the **Core sections**. Add the **Targeted fields** matching the sub-category. Keep the final snippet under ~100 lines.

### Core sections (always include)

**Environment detection** — flag WordPress (check for a `window.wp` global, a `wp-content` link, or a `wp-*` body class) and capture viewport width/height, device pixel ratio, and the page URL. When WordPress is detected, also collect the body's class list and the list of loaded stylesheets (id + filename) — WordPress global styles are a common cascade offender and you'll want this list to cross-reference later.

**Element targeting** — resolve the actual CSS selector from context (chat, open file, or the click-to-inspect fallback below) and query for it. **Never leave a placeholder like `SELECTOR` unresolved in the snippet you hand the user** — if you can't identify a selector, use the click-to-inspect pattern instead. If the query finds nothing, warn and stop rather than continuing with a null element.

**Box model** — collect the element's offset width/height, scroll width/height, and its bounding-client-rect (top/right/bottom/left/width/height, rounded to 1 decimal). Derive an `inViewport` boolean from the rect (non-zero size, and it overlaps the current viewport vertically) — this instantly answers "is it even on screen."

**DOM ancestry** — walk up to 5 parent levels (stop at `<body>`), and for each ancestor record: tag name, id, up to 6 classes, computed `display`, `position`, and overflow (shorthand + x + y). When an ancestor's display includes `flex`, also record its flex properties (direction, align-items, justify-content, wrap, gap); when it includes `grid`, record grid template columns/rows and gap. Layout context almost always lives in a parent, not the element itself, so this is not optional.

**CSS cascade** — determine which stylesheet rules actually match the target element and where each one comes from. Walk every loaded stylesheet (skip any that throw on access — cross-origin stylesheets block script reads), recursively descend into nested rule groups (media queries, layers), and for every rule whose selector matches the element, record the selector text, its source (stylesheet filename or "inline"), and its declared CSS text. Wrap each per-rule check in error handling — a single malformed selector or an inaccessible sheet shouldn't abort the whole scan.

**Report assembly + delivery** — combine env, selector, box, the sub-category's targeted fields (see below), ancestry, and cascade into one object; pretty-print it as JSON to the console. Use these top-level key names so the signals in §3 resolve against the report you generate: `env` (with `wpStyles` when WordPress is detected), `selector`, `box` (including the `rect` bounding-client-rect), `ancestry`, `cascade` (each entry's origin under `source`), and, for the `responsive` sub-category, `mq` for the media-query block (with an `active` list of matching breakpoints). Then attempt to copy it to the clipboard, trying methods in this order and falling back silently: (1) the DevTools-console-only `copy()` helper if present, (2) the standard async Clipboard API, (3) if neither is available or both fail, tell the user to copy the printed JSON manually. Wrap each clipboard attempt in a try/catch — clipboard access can be denied by the browser and that must not throw past the probe.

### Targeted fields by sub-category

Add these computed-style fields to the report on top of the Core sections, matching the sub-category from §1:

- **`positioning`** — position, top/right/bottom/left, inset, transform, z-index, all four margins, float, clear.
- **`layout`** — display, flex-direction, align-items, justify-content, flex-wrap, gap, align-self, justify-self, flex-grow/shrink/basis, grid-column, grid-row, order.
- **`spacing`** — box-sizing, width/min-width/max-width, height/min-height/max-height, all four paddings, all four margins, gap.
- **`responsive`** — include the `spacing` and `layout` fields above, plus a media-query block: current viewport width, which breakpoints from the common set (375/480/640/768/1024/1280/1440) currently match, and whether the viewport counts as mobile (< 768px).
- **`cascade`** — no extra computed-style fields; instead, after building the cascade list, score each matched rule's specificity (ID selectors weigh most, class/attribute/pseudo-class selectors next, bare element selectors least) and sort the list highest-first, so the winning rule is obvious at a glance. *Note: a simple regex-based specificity estimator can overweight comma-separated grouping selectors and may mistake pseudo-elements for pseudo-classes — treat the score as a strong hint, not ground truth.*
- **`visibility`** — display, visibility, opacity, overflow, clip, clip-path, pointer-events, z-index, position, width, height.

### Click-to-inspect pattern (when no selector is identifiable)

When you can't determine a specific selector from context, generate a snippet that prompts the user to click the element, then runs the full report against whatever they clicked: attach a one-time, capture-phase click listener to the document that prevents the click's default action and stops it from propagating (so the click doesn't trigger the site's own handlers), removes itself after firing once, and feeds the clicked element into the rest of the inspection logic above.

---

## 3. Signals to look for in the returned data

- **Cascade conflicts** — Is a higher-specificity rule in `cascade` overriding the expected one? Check its `source` — WordPress global stylesheets like `global-styles-inline-css` and `wp-block-library` are common offenders.
- **Layout parent** — Is a parent's `display`, `overflow`, or `flex`/`grid` property in `ancestry` the actual problem? The element you targeted may be a victim, not the cause.
- **Box model** — Is the element zero-sized, off-viewport (`rect`), or clipped by an ancestor's `overflow: hidden`?
- **WordPress overrides** — Check `wpStyles` against the `cascade` `source` field. WP's `global-styles-inline-css` has very high specificity and will quietly win.
- **Responsive misfire** — In `mq.active`, is the expected breakpoint missing? Or is the viewport reporting a different size than you expected?
- **Stacking** — `zIndex: auto` on a positioned ancestor traps children in that stacking context. Look at the whole ancestry, not just the element.

Once the signal is identified, find the rule and either raise its specificity, scope it more tightly, or remove the competing rule — at the source, not by piling `!important` on top.
