# Design System Foundations & Aesthetics

Consistency separates professional design from decoration. Establish these systems before writing component code — they compound across every element.

## Spacing
Use a 4px base unit (`--space-1: 4px` through `--space-12: 96px`). All spacing values are multiples — no arbitrary pixel values. If 14px "looks right," use 12px or 16px. Arbitrary values create visual noise that accumulates — the eye notices even when the brain doesn't.

## Color Tokens
Use semantic naming so colors carry meaning, not just values (`--color-text-primary`, `--color-surface`, `--color-accent`). Follow the 60-30-10 rule: 60% neutral (backgrounds, body text), 30% secondary (borders, cards, muted text), 10% accent (CTAs, links, highlights). Dominant colors with sharp accents outperform timid, evenly-distributed palettes.

For dark mode: map the same semantic token names to different values. Never swap individual colors ad-hoc — remap the entire system. Use `prefers-color-scheme: dark` or a `.dark-theme` class on the root element.

## Typography Scale
Define 6-8 named sizes using `clamp()` for fluid scaling. Each carries its own line-height and letter-spacing as a triplet — never set `font-size` without its companions. Maximum 2 font families — one display, one body. Never more.

**Font loading:** Use `font-display: swap` and `<link rel="preload">` for the primary font. Define fallback font metrics with `size-adjust` and `ascent-override` to prevent layout shifts.

**Typography craft:**
- `-webkit-font-smoothing: antialiased` for consistent rendering
- `text-wrap: balance` for headings (equal line lengths)
- `text-wrap: pretty` for body text (avoids orphaned words)
- `font-variant-numeric: tabular-nums` for any number that changes dynamically

Read `references/design-tokens.md` for complete CSS custom property templates — spacing scale, color primitives, semantic tokens, typography scale, shadows, transitions, and dark mode mapping.

## Code Structure
Organize output for AI readability and maintainability:
- One component per file when possible. Clear section comments marking boundaries.
- CSS custom properties at `:root` level — never hardcode colors or spacing in component styles.
- BEM naming for vanilla CSS (`.card`, `.card__title`, `.card--featured`). Utility classes for Tailwind projects.
- Modular asset loading — each section can include its own `<style>` block or linked stylesheet.

---

## Frontend Aesthetics

This is where the creative vision meets the design system. The system provides consistency — this section provides character.

**Typography as expression:**
Choose fonts that are beautiful, distinctive, and unexpected. Pair a characterful display font with a refined body font. Use negative letter-spacing on large headings (the Vercel/Geist technique — tighter text feels more "designed"). Explore variable fonts for responsive weight and width adjustments.

**Color as atmosphere:**
Create depth and mood rather than flat backgrounds. Apply gradient meshes, noise textures (via SVG `feTurbulence` filters), layered transparencies, and contextual effects that match the aesthetic. Full-bleed backgrounds with subtle texture outperform stark white surfaces.

**Spatial composition:**
Unexpected layouts create visual interest. Asymmetry. Overlap. Diagonal flow. Grid-breaking elements that extend beyond their containers. Generous negative space OR controlled density — choose one and commit. The tension between elements is what makes a layout feel designed.

**Visual depth and texture:**
Use layered transparent `box-shadow` (2-4 layers at different offsets and blurs) instead of single solid shadows — this mimics how light actually works. Apply concentric border radii for nested elements: inner radius = `calc(var(--outer-radius) - var(--gap))`. Add grain overlays, decorative borders, and custom cursor effects where they serve the aesthetic.

### The NEVER List
These patterns are the telltale signs of generic AI output. Avoid them in Brand mode (Product mode may legitimately use neutral system fonts and restrained patterns in service of clarity):

- **Never** use Inter, Roboto, Arial, or system-ui as the primary display font
- **Never** default to purple gradients on white backgrounds
- **Never** use a SaaS card grid as the hero section
- **Never** add a carousel with no narrative purpose
- **Never** stack identical cards instead of designing a real layout
- **Never** default to a single "signature" font pair across different contexts; tailor the typography to the specific brand personality of the current workspace/brief (e.g., monospace details for tech, serif for editorial, geometric sans for modern consumer brands)
- **Never** pair a beautiful stock image with weak, generic typography
- **Never** use cookie-cutter component patterns without context-specific adaptation

Every project should feel distinct. Vary light/dark themes, font choices, color palettes, and layout approaches. Interpret creatively and make unexpected choices that feel genuinely designed for the specific context.
