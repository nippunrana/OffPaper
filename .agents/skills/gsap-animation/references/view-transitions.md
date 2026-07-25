# Native CSS View Transitions (Cross-Document)

Native cross-document view transitions allow browsers to animate navigations between separate HTML documents in Multi-Page Architectures (MPAs). This provides the visual continuity of Single Page Applications (SPAs) without JavaScript routing overhead.

---

## Core Activation and Infrastructure

To enable native transitions, the browser requires an opt-in via a global CSS at-rule:

```css
@view-transition {
  navigation: auto;
}
```

### Technical Requirements Checklist
- **Same-Origin Constraint**: Transitions only execute between pages hosted on the exact same domain.
- **Shared CSS Architecture**: Both the source and destination pages must link to the same shared CSS file so the browser can locate and map corresponding elements.
- **Silent Fallback**: Browsers that do not support the API (e.g. Firefox) will ignore these declarations and perform standard page refreshes.

---

## The Transition Pseudo-Element Tree

When navigation is triggered, the browser takes snapshots of the old and new pages, organizing them into a temporary pseudo-element sandwich hierarchy:

1. **`::view-transition-group(name)`**: The wrapper that manages timing, duration, and delay.
2. **`::view-transition-old(name)`**: A static snapshot of the outgoing page state (non-interactive).
3. **`::view-transition-new(name)`**: A live snapshot of the incoming page state.

### Styling Assignments Rule
- Assign **durations and timing functions** to the **group**.
- Assign **animation-names (keyframes)** to the **old and new snapshots**.

---

## Custom Directional Animations (Slide Transition)

To replace the default fade transition with a slide-out and slide-in effect, define keyframes and map them to the root snapshots:

```css
/* 1. Define Keyframes */
@keyframes slide-out {
  to { transform: translateX(-100vw); }
}
@keyframes slide-in {
  from { transform: translateX(100vw); }
}

/* 2. Assign animations to snapshots */
::view-transition-old(root) {
  animation-name: slide-out;
}
::view-transition-new(root) {
  animation-name: slide-in;
}

/* 3. Configure timing on the group container */
::view-transition-group(root) {
  animation-duration: 0.6s;
  animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
```

---

## Scoped Transitions and Layer Isolation

Applying animations to the `root` parameter transitions every visible pixel (including navigation bars and sidebars), causing a distracting "flicker" or exit-and-re-entry motion.
To keep persistent UI elements static, isolate changing content using the `view-transition-name` property.

```css
/* 1. Isolate the main content container in your shared CSS */
main {
  view-transition-name: page-content;
}

/* 2. Disable root motion to keep headers/navbars static */
::view-transition-old(root),
::view-transition-new(root) {
  animation: none;
}

/* 3. Apply slide animations to the isolated content layer instead */
::view-transition-old(page-content) {
  animation-name: slide-out;
}
::view-transition-new(page-content) {
  animation-name: slide-in;
}
::view-transition-group(page-content) {
  animation-duration: 0.5s;
}
```

---

## Shared Element Morphing (Hero Image Logic)

You can transition a specific element (like a card thumbnail) into its counterpart (like a hero image header) on the next page. The browser calculates the size and position difference (delta) and interpolates the transform automatically.

### 1. Naming
Assign an identical `view-transition-name` to both the source and target elements:

```css
.card-img, .hero-img {
  view-transition-name: article-hero;
}
```

### 2. Coordination
When a shared element is transitioning, it is recommended to disable or minimize parent container animations (like page slides) to avoid overlapping visual noise.

### 3. The Uniqueness Constraint
- **Rule**: A `view-transition-name` **must be unique** on any single page layout.
- **Multi-card Lists**: If you have multiple cards on a page, they cannot all share the name `card-image`. Assign names dynamically (e.g. via inline styles: `style="view-transition-name: card-7"`) and match them to the target layout. Duplicate names will cause the browser to abort the transition.

---

## Accessibility and Progressive Enhancement

### 1. Reduced Motion Compliance
To comply with WCAG Success Criterion 2.3.3, you must wrap all transition rules inside the prefers-reduced-motion media query to protect users with vestibular disorders:

```css
@media (prefers-reduced-motion: no-preference) {
  @view-transition {
    navigation: auto;
  }
  
  /* All custom pseudo-elements & keyframes reside here */
}
```

### 2. Local Testing Tip
During active local development, Chrome can experience rendering glitches or inconsistencies with live-reloading dev servers. For a stable testing experience of CSS transitions, **Microsoft Edge** is recommended as it handles reload-heavy transition loops more reliably.
