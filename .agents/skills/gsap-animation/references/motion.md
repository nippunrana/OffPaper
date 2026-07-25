# Declarative React Animations: Motion (formerly Framer Motion)

Motion is a declarative, React-optimized animation library that integrates animations directly into the component lifecycle. It is highly suited for gesture-driven UI components and micro-interactions.

---

## The Motion Component Architecture

To animate standard HTML tags, prefix them with the `motion` object:
- `<div>` becomes `<motion.div>`
- `<h1>` becomes `<motion.h1>`

This upgrades static DOM nodes into performant, state-aware components.

---

## Core Animation Props

### 1. State Orchestration
- **`initial`**: Defines the properties (opacity, scale, translate) before mount.
- **`animate`**: Declares the destination target properties. The library handles the interpolation automatically.
- **`transition`**: Configures the duration, delay, easing, or spring physics of the transition.

```javascript
import { motion } from "motion/react"; // or "framer-motion" for legacy

<motion.div
  initial={{ opacity: 0, y: 50, scale: 0.8 }}
  animate={{ opacity: 1, y: 0, scale: 1 }}
  transition={{ duration: 0.8, ease: "anticipate" }}
/>
```
*Design Tip*: Use `ease: "anticipate"` to create a premium "wind-up" effect where the element pulls back slightly before sliding forward.

### 2. Multi-stage Keyframe Sequences
To transition an element through multiple stages, pass an array of values to the property:

```javascript
<motion.div animate={{ x: [0, 800, 800, 0] }} />
```

---

## Gestures and micro-interactions

Motion abstracts gestural events into simple visual props without the overhead of manually setting up event listeners:

- **`whileHover`**: Immediate visual feedback when the pointer enters the bounding box (e.g., `{ scale: 1.05 }`).
- **`whileTap`**: Tactile feedback for clicks/taps (e.g., `{ scale: 0.95 }` simulates pressing down a physical button).
- **`whileDrag`**: Triggered while dragging (e.g., `{ scale: 1.1, boxShadow: "0px 10px 20px rgba(0,0,0,0.3)" }` simulates lifting a card).

---

## Controlled Freedom Drag Constraints

To prevent users from dragging elements off-screen and breaking the UI, configure drag constraints:

### 1. dragConstraints
Sets strict boundaries using relative pixel values or layout references:

```javascript
<motion.div 
  drag 
  dragConstraints={{ left: 0, top: 0, right: 800, bottom: 400 }}
/>
```

### 2. dragDirectionLock
To prevent diagonal drifting where an element drifts off-axis, set `dragDirectionLock={true}`. The library detects the initial gesture direction (horizontal vs. vertical) and locks movement to that axis:

```javascript
<motion.div 
  drag
  dragDirectionLock={true}
  dragConstraints={{ left: -200, right: 200 }}
/>
```

---

## Performance-Optimized Scroll Progress

For scroll-mapped effects (like a page reading progress bar), use the `useScroll` hook.
- **MotionValues**: The hook returns `MotionValues` (such as `scrollYProgress`, a normalized `0` to `1` value).
- **No React Re-renders**: `MotionValues` do not trigger React component state updates or re-renders during scroll, making them highly performant.

```javascript
import { motion, useScroll } from "motion/react";

const ReadingProgress = () => {
  const { scrollYProgress } = useScroll();

  return (
    <motion.div
      style={{ scaleX: scrollYProgress, originX: 0 }}
      className="fixed top-0 left-0 right-0 h-1 bg-blue-500 z-50"
    />
  );
};
```

---

## Strategic Selection Matrix: GSAP vs. Motion

Use this matrix to determine the correct library for a project:

| Feature | GSAP | Motion (Framer Motion) |
| :--- | :--- | :--- |
| **Primary Paradigm** | Imperative (`gsap.to()`) | Declarative props |
| **Framework Support**| Framework-agnostic (Vanilla, React, Vue, Svelte) | React-only (`motion/react` or `framer-motion`) |
| **Best For** | Complex multi-element timelines, ScrollTrigger pin/scrub, 3D scenes | Interactive UI components, swipe gestures, exit/entry transitions |
| **DOM Manipulation** | Directly targets DOM nodes, independent of render loop | Tied to React lifecycle and DOM reconciliation |
| **Scroll Animations**| Highly customizable ScrollTrigger plugin | Simple hooks (`useScroll`, `useTransform`) |

---

## Key Best Practices

- ✅ **Map Imports Correctly**: Ensure you import from the modern `motion/react` package (or `framer-motion` for legacy configurations).
- ✅ **Prevent Offscreen Drags**: Always set `dragConstraints` to maintain layout recoverability.
- ✅ **Lock Drag Axes**: Set `dragDirectionLock={true}` on slider handles and drawers to avoid vertical/horizontal drift.
- ✅ **Use MotionValues for Scroll**: Bind properties to scroll via `scrollYProgress` style mappings to avoid frame drops.

## Do Not

- ❌ **Interleave GSAP & Motion on the Same Node**: Do not target a `<motion.div>` directly with GSAP `to()` or `from()` methods. They will compete for inline style writes, causing rendering bugs.
- ❌ **Use Motion for Complex Timelines**: Do not build long, non-linear choreographed sequences (e.g. 5+ distinct sections showing products dynamically) with Motion. Chaining transitions in React state is brittle; use GSAP timelines instead.
- ❌ **Bind Scroll to State**: Do not map `scrollYProgress` to React state values (e.g., using `useState` in an `onChange` listener) to update styles, as it triggers constant re-renders and degrades performance.
