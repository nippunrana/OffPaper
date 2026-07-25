# Conversion Patterns by Page Type

Read this reference when designing pages where conversion is a primary goal. Each section provides
specific, research-backed patterns for different page types.

---

## Landing Page Patterns

### Hero Formula
The most effective landing page hero follows this structure (top to bottom):
1. **Eyebrow text** — category or context label (small, muted)
2. **Headline** — benefit-focused, max 10 words. Answer "what will I get?"
3. **Supporting line** — 1-2 sentences expanding the headline. Address the pain point.
4. **Primary CTA** — high-contrast button, action-oriented copy
5. **Social proof snippet** — logos, customer count, or micro-testimonial
6. **Visual anchor** — product screenshot, hero image, or illustration

Place nothing else in the hero. Every additional element dilutes attention.

### Section Sequence (proven high-conversion order)
1. Hero (problem → solution → CTA)
2. Social proof bar (logos of recognizable clients/publications)
3. Key benefits (3 max, with icons or illustrations — benefit headlines, not feature names)
4. How it works (3 steps, numbered, with supporting visuals)
5. Feature deep-dive (alternate image-left/image-right sections)
6. Testimonials (2-3 with photos, names, titles, and company)
7. Pricing or lead capture form
8. FAQ (addresses objections — shipping, refunds, security, support)
9. Final CTA (repeat the hero CTA with a different headline angle)
10. Footer

### CTA Placement Rules
- First CTA: visible without scrolling (above the fold)
- Repeat the CTA after every 2-3 content sections
- The final section before the footer should contain a CTA
- Sticky CTA bar on mobile (bottom of screen) appears after scrolling past the hero

### Form Optimization
- Every field you remove increases completion rate. Ask only what's needed for the next step.
- Use inline validation — show errors as the user types, not after submission.
  Inline validation reduces form completion time by ~22% (Nielsen Norman Group).
- Single-column layout. Multi-column forms create confusion about reading order.
- Label above field (not placeholder-only — labels disappear when typing).
- Smart defaults: pre-select the most common option, auto-detect country from IP.
  For the psychology behind why defaults work and how to sequence them, see
  `references/behavioral-psychology.md` §3.

---

## Product Page Patterns

### Above the Fold
- Product image gallery (left side, 60% width on desktop). Support zoom, multiple angles.
- Product info (right side): name, price, star rating with review count, short description,
  size/variant selector, Add to Cart button, shipping info.
- The Add to Cart button should be the most visually prominent element on the page.

### Trust Signals Near Purchase
- Star rating and review count adjacent to the price
- "Free shipping" or "30-day returns" badges near the Add to Cart button
- Security badge near payment information (max 3 badges — Norton, PayPal, Visa/Mastercard
  are the most recognized)
- "In stock" or low-stock indicator ("Only 3 left") near the button

### Price Presentation
- Show the current price prominently. If discounted, show the original price crossed out
  immediately next to it (anchoring effect).
- Per-unit or per-month pricing feels more affordable than lump sums.
- If subscription: show annual price per-month with "billed annually" underneath.
  Show the savings percentage vs. monthly plan.

### Below the Fold
- Detailed product description with benefit-focused copy
- Specifications in a collapsible table
- Customer reviews section with filtering (most helpful, most recent, by star rating)
- "Frequently bought together" or "Customers also viewed" section
- FAQ specific to the product

### Mobile Product Page
- Image gallery becomes a horizontal swipeable carousel
- Sticky bottom bar with price and Add to Cart button (always visible)
- Collapsible sections for description, specs, reviews
- "Buy now" quick-action sheet (bottom drawer pattern)

---

## Homepage Patterns

### Value Proposition Hierarchy
The homepage has one job: help visitors self-select into the right path.

1. **Hero** — Clearly state what the company does and who it's for. Visitors decide within
   3-5 seconds whether to stay. The headline should pass the "stranger test" — would someone
   with zero context understand what you do?
2. **Trust bar** — Client logos or media mentions immediately below the hero
3. **Core offerings** — 3-4 cards or sections, each leading to a dedicated page.
   Don't explain everything — entice visitors to click through.
4. **Social proof** — Case studies, testimonials, or metrics section
5. **Final CTA** — Newsletter signup, free trial, or contact form

### Navigation Psychology
- Logo top-left (clicking returns to homepage — universal convention)
- Max 5 primary nav items. Use dropdowns for subcategories, not additional top-level items.
- CTA button in the top-right corner of the nav (contrasting color, e.g., "Get Started")
- Mobile: hamburger menu. Include the CTA as the first or most prominent item inside the
  hamburger menu — it's often buried at the bottom, which kills conversions.

---

## E-Commerce Patterns

### Category Page
- Grid layout with 2 columns on mobile, 3-4 on desktop
- Each product card: image, name, price, star rating (if available)
- Quick-view or quick-add-to-cart on hover (desktop) or long-press (mobile)
- Filter sidebar (collapsible on mobile) with the most-used filters at the top
- Sort options: relevant/popular/price-low/price-high/newest

### Checkout Flow
- Progress indicator (Step 1 of 3, etc.) reduces anxiety
- Guest checkout option — forced account creation is the #1 cause of cart abandonment
- Order summary visible at all times (sticky sidebar on desktop, collapsible on mobile)
- Express payment options (PayPal, Apple Pay, Google Pay) above the standard form
- Security badges near credit card fields — customers perceive only the area immediately
  around the payment form as "secure" (Baymard Institute)
- Remove all navigation except "back" during checkout — minimize escape routes

### Cart Abandonment Prevention
- Exit-intent modal with a discount or reminder (desktop only)
- Persistent cart indicator in the nav showing item count
- "Save for later" option on cart items
- Shipping cost shown early (not as a surprise at checkout)

---

## Mobile-Specific Conversion Patterns

### Thumb Zone Optimization
Primary CTAs go in the thumb-friendly zone — the bottom third of the screen. The top corners
and edges are the hardest to reach on modern phones (especially large screens).

### Sticky Elements
- Sticky bottom CTA bar for product and landing pages
- Sticky header with simplified nav (logo + hamburger + CTA)
- Bottom sheet patterns for forms and selection (slides up from bottom)

### Simplified Interactions
- Tap over type — use toggle buttons, selectors, and pre-filled options instead of text inputs
  wherever possible
- Auto-advance multi-step forms (move to next field after selection)
- Pull-to-refresh for dynamic content
- Swipe gestures for image galleries and carousels

### Performance as Conversion
Every 100ms of additional load time reduces conversion by ~1% (Google/Deloitte research).
On mobile, performance IS conversion:
- Preload hero images and fonts
- Lazy-load everything below the fold
- Minimize third-party scripts
- Target LCP < 2.5s on 4G connections

---

## Trial / Paywall Screens

These are product-mode, not brand-mode: the user already has the app open and is
deciding whether to commit, not whether to click through from an ad. The psychology
here is about trust at a high-friction moment — read `references/behavioral-psychology.md`
§2 and §7 for the reasoning behind these patterns before applying them.

- **Headline:** process-driven beats feature-driven. "How your free trial works"
  lowers the stakes by explaining the mechanics rather than asking the user to judge
  the value of a subscription they haven't tried yet. Save the feature-pitch headline
  ("Get access to 1,000+ games") for users who've already used the product and are
  deciding to upgrade — not for a first-touch paywall.
- **Structure: a timeline, not a bullet list.** Lay out what happens at each point
  the user cares about, in order — today's access, the reminder before anything is
  charged, and the charge date itself. A guided sequence reads as a plan; a feature
  list reads as a pitch.
- **Imagery:** use real product screenshots or actual in-app content, not abstract or
  decorative illustration. The user is trying to answer "what am I actually getting,"
  and decoration doesn't answer that.
- **CTA:** lead with a low-commitment verb ("Start" rather than "Subscribe") and pair
  it with an effort-quantifier subtext ("Start in 2 taps") that answers the unspoken
  question about how much friction is involved (credit card? long form?).
- **Cancellation:** state the cancel-anytime policy near the CTA, not buried in terms.
  Proactively disclosing the exit path is what makes the trial feel safe to enter.

---

## Booking / High-Ticket Flows

Bookings, reservations, and other high-consideration purchases need to move the user
from "evaluating a database entry" to "picturing themselves there." See
`references/behavioral-psychology.md` §1 and §8 for the underlying reasoning on
evaluative ease and anchoring.

- **Imagery:** a large hero image (dominating roughly the top half of the screen)
  outperforms a small thumbnail — the user can't commit to what they can't visualize.
- **Copy:** rewrite functional descriptions into sensory, spatial ones. "Beach house
  with garden" states a fact; "Beachside escape steps from the sand" lets the user
  picture standing there. Keep it specific and spatial, not just adjective-heavy —
  vague sensory language ("amazing," "incredible") is as empty as vague functional
  language.
- **Dates:** show the day name alongside the date ("Friday, March 28") and the
  computed duration ("5 nights"), not a bare date range the user has to do the
  subtraction on themselves.
- **Pricing:** pair a genuine strikethrough anchor price with the discounted price and
  percentage saved, and show the final inclusive total directly on or under the CTA
  button ("Reserve — $445 total") so there's no hidden-fee surprise waiting on the next
  screen.
- **Risk reversal:** place a specific, dated cancellation policy ("Free cancellation
  until March 26") near the CTA — this answers the single most common objection at
  this decision point ("what if my plans change?") before the user has to ask.
- **Social proof:** a small trust badge (host rating, verified status) near the price
  reinforces the decision at the exact moment the user is committing.

---

## Onboarding & Signup Flows

The signup wall is usually the wrong place to start extracting commitment. Structure
onboarding so the account-creation prompt arrives after the user already has
something worth keeping — see `references/behavioral-psychology.md` §4, §5, and §6 for
the full reasoning (goal gradient, reciprocity, investment/ownership).

- **Sequence:** let the user make a few real, meaningful choices — a goal, a
  preference, one completed unit of actual product value — before asking for an
  email or password. A language app that has the user pick a goal and finish one
  lesson before signup retains better than one that asks for credentials first.
- **Progress:** never render a 0% progress state. If the user has taken any real
  action to get to this screen, credit it — start the meter partway and keep the
  remaining distance visible.
- **CTA reframe:** once the user has invested real choices, the account-creation
  button should say "Continue" or "Save my progress" rather than "Sign Up" — this is
  accurate, not manipulative, because there genuinely is something to save at that
  point.
- **First screen defaults:** where the flow supports it, pre-fill the first screen
  from context (detected locale, common starting choice) so the user's first action
  is confirming rather than creating from a blank state.
