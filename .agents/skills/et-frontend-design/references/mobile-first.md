# Mobile-First and Responsive Design

Design mobile first, always. Most web traffic is mobile — the mobile experience IS the primary experience, not an afterthought.

**Fluid typography:** Use `clamp()` for all type sizes. No rigid breakpoint jumps — text should scale smoothly between minimum and maximum sizes as the viewport changes.

**Component-level responsiveness:** Prefer container queries (`@container`) over media queries for reusable components. A card inside a sidebar should respond to its container width, not the browser window. Reserve media queries for page-level layout shifts only.

**Breakpoints** (when media queries are needed): `640px / 768px / 1024px / 1280px`, mobile-first approach using `min-width`.

**Touch and interaction:**
- Touch targets: 44px minimum for all mobile interactive elements (buttons, form inputs, navigation items). 24px is only acceptable for inline text links within paragraphs on desktop viewports. This is both a usability requirement and a conversion factor — small targets lose taps and lose customers.
- Thumb-friendly zones: place primary actions in the bottom third of the screen on mobile. The top corners are the hardest to reach on modern phones.
- Navigation: hamburger menus on mobile. Max 5 items in the visible top navigation on desktop.

**Images:**
- In Next.js projects, always use `next/image` as it handles responsive sizing and modern formats (AVIF/WebP) automatically. For vanilla HTML or other frameworks, use the `<picture>` element with AVIF/WebP sources and `<img>` fallback.
- Always include `srcset` and `sizes` for responsive resolution selection when using standard HTML tags.
- `loading="lazy"` for below-fold images. `loading="eager"` and `fetchpriority="high"` for the hero/LCP image.
- Set `width`, `height`, or `aspect-ratio` on every image to prevent layout shifts.

**Performance targets:**
- LCP (Largest Contentful Paint) < 2.5 seconds — preload hero image and primary font.
- CLS (Cumulative Layout Shift) < 0.1 — no layout shifts above the fold.
- Minimize render-blocking resources. Inline critical CSS when possible.
