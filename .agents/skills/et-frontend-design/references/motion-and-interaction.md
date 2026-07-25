# Motion, Animation, and Interaction States

## Motion and Animation
Animation is a design tool, not decoration. Every animation must answer: **"Why does this animate?"** Valid reasons: spatial consistency, state indication, explanation, user feedback, preventing jarring visual changes. If you can't articulate the reason, remove the animation.

**Key principles:**
- Never animate keyboard-initiated actions (typing, tab navigation) — they repeat hundreds of times daily and animation makes them feel sluggish.
- Use spring physics for physical properties (position, scale, rotation). Use duration-based easing for non-physical properties (opacity, color, blur).
- Stagger enter animations ~100ms between sibling elements. One well-orchestrated page load with staggered reveals creates more delight than scattered micro-interactions everywhere.
- Hover transitions: ~200ms with CSS. Use interruptible CSS transitions for interactive states.
- Use `cubic-bezier(0.16, 1, 0.3, 1)` for expressive deceleration. Never use `linear` for UI motion — it feels robotic.

**Technical approach:**
- CSS for simple state transitions (hover, focus, fade, slide)
- CSS `animation-timeline: scroll()` for scroll-driven effects — zero JavaScript, zero main-thread blocking
- Motion library for React projects (formerly Framer Motion)
- GSAP for complex choreographed sequences

**Always honor `prefers-reduced-motion: reduce`.** Provide instant state changes as fallback — not "no change," but immediate transitions without animation.

---

## Interaction States
Every interactive component needs all its states designed — not just the default. AI-generated interfaces commonly ship only the "happy path" default state, which feels incomplete and unprofessional in production.

**Buttons and links:** default, hover, focus-visible, active/pressed, disabled, loading.

**Form inputs:** default, placeholder, focus, filled, error (with message), disabled, readonly.

**Data views:** loading (skeleton screen), empty state (message + illustration + CTA), error (message + retry action), populated.

**Guidelines:**
- Skeleton screens > spinners. Match the skeleton shape to the final content layout.
- Empty states are never blank — provide a helpful message and a call-to-action.
- Error states always include a recovery action (retry button, help link, alternative path).
- Loading states should appear after ~200ms delay — instant loaders for fast operations feel jittery.
