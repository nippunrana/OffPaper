---
name: et-frontend-design
description: >
  Create distinctive, production-grade frontend interfaces with high design quality and
  conversion-focused UX. Use this skill when the user asks to build, style, redesign, beautify,
  or polish any web UI: WordPress templates, landing pages, product pages, homepages, dashboards,
  React/Vue/Next.js components, HTML/CSS/JS layouts, mobile app screens, forms, navigation, cards,
  heroes, or any visual frontend work. Also triggers when the user wants to improve conversion
  rates, create a theme or color scheme, fix ugly layouts, optimize for mobile, add animations or
  micro-interactions, or when they paste a design mockup and want it coded. Even if the user just
  says "make it look better" or "this looks ugly" — use this skill. Generates creative,
  production-ready code with exceptional design craft that avoids generic AI aesthetics.
license: Complete terms in LICENSE.txt
---

# ET Frontend Design

Create distinctive, production-grade frontend interfaces that avoid generic "AI slop" aesthetics.
Implement real working code with extraordinary attention to design craft and conversion psychology.
Output must be production-ready, modular, and easy for AI agents to read, edit, and debug.

Determine the mode before starting:

- **Brand mode** — the design IS the product (landing pages, portfolios, marketing sites, product
  showcases). Demands bold creative risk, strong visual identity, and memorable first impressions.
- **Product mode** — design SERVES the product (dashboards, admin panels, tools, app interfaces).
  Demands restrained precision, usability, and functional clarity.

Both modes demand excellence — they differ in where the energy goes. Brand mode pushes aesthetic
boundaries. Product mode perfects ergonomics. Never confuse the two.

---

## 1. Strategic Discovery

Unless the request qualifies as an explicitly scoped micro-edit, you MUST run through the four phases of Strategic Discovery before writing any code. Skipping this for major components or pages produces generic, low-converting layouts. The user hired a design strategist, not a code printer.

### When to Skip Strategic Discovery (STRICT CRITERIA)
You may ONLY skip Phases 1-3 and go straight to code if the user's request meets ALL of the following criteria:
1. It is a modification to an **already existing** component.
2. It does not introduce any new layouts, sections, or user flows.
3. The user explicitly dictates the exact mechanical change (e.g., "Change the header background to #111111", "Center the div on line 42", "Fix the broken margin").

**Trigger Words:** If the user's prompt includes words like *"design"*, *"build"*, *"create"*, *"improve"*, *"make it look better"*, or if they provide a mockup/screenshot, **you are strictly forbidden from skipping Phases 1-3.** You must execute the full strategic discovery process. If a request meets ALL three strict skip criteria above, the explicit mechanical instruction takes priority over trigger words.

### Phase 1 — Diagnose (silent)

Think like a conversion strategist, not a decorator. Silently analyze:

- **The actual problem:** What business or user problem does this solve? A landing page for a
  new product is a different problem than a redesign of an underperforming one.
- **Funnel position:** Where does this page sit in the user journey? Top-of-funnel (awareness)
  needs different design than bottom-of-funnel (decision). Someone landing from a Google ad has
  different intent than someone browsing from the homepage.
- **Awareness level:** Is the visitor unaware, problem-aware, solution-aware, or product-aware?
  This determines whether the design should lead with education, empathy, differentiation, or a
  direct offer.
- **Emotional landscape:** What fears, objections, or hesitations might stop the user from
  converting? What does "trust" look like in this industry?
- **Brand mode vs Product mode:** Determined from context (see mode definitions above).

Use your internal thought/scratchpad block to run through this phase. If you do not have a hidden thought block, do not output the full analysis; instead, compress it into a short summary.

### Phase 2 — Strategy Brief (silent)

Build an internal strategy brief using this structure (do NOT output it raw to the user):

1. **GET** [target audience] **WHO** [key insight about them] **TO** [desired action] **BY**
   [design approach]
2. **Aesthetic direction:** Pick a clear visual tone. Choose from flavors like:
   brutally minimal, maximalist chaos, retro-futuristic, organic/natural, luxury/refined,
   playful/toy-like, editorial/magazine, brutalist/raw, art deco/geometric, soft/pastel,
   industrial/utilitarian, dark tech, warm earthy, neo-classic, cyberpunk, Scandinavian clean.
   Use these for inspiration but design one that is true to the project's context.
   Refer to `references/design-inspiration.md` for aesthetic category guides.
3. **Conversion architecture:** What is the single primary CTA? What objections need addressing?
   Where do trust signals go? What is the hero's one job? Commit to one strong composition — one
   headline, one supporting line, one CTA, one visual anchor. No cluttered heroes. No "everything
   above the fold" syndrome. **Narrative arc:** Choose a storytelling framework for this page —
   PAS (Pain/Agitate/Solve), Before-After Bridge, or Hero's Journey (full reference in Section 2).
   This choice determines which sections exist and in what order. A PAS page leads with pain; a
   Before-After page leads with the transformation vision; a Hero's Journey positions the brand as
   the guide, never the hero. Psychology is the highest-leverage input to conversion — lock this
   in before designing any section.
4. **Component hierarchy:** Map atoms (buttons, inputs, badges) → molecules (cards, nav items,
   form groups) → organisms (header, hero, feature grid, footer). Plan the page flow as a funnel,
   not a stack of sections.
5. **Design system seeds:** Light or dark? Font pairing direction? Color mood? Commit before
   coding — don't switch mid-implementation.

Use your internal thought/scratchpad block to run through this phase. If you do not have a hidden thought block, do not output the full raw brief; instead, compress it into a short summary for the user rather than dumping the raw internal structure.

### Phase 3 — Strategic Questions

Now share your thinking and ask the user **3-5 diagnostic questions**. These are not preference polls — they are strategic probes that change the design direction.

Exception: if the user's brief already answers every strategic question (audience, aesthetic, stack, constraints), do not invent filler questions — present your strategy summary with stated assumptions and proceed to Phase 4.

**CRITICAL INSTRUCTION FOR ASKING QUESTIONS:** You must ask these questions using standard markdown text output. **Do NOT use any interactive questioning tools (such as `ask_question`).** These questions require open-ended discussion and nuance that a rigid multiple-choice modal cannot support.

Present a brief strategy summary first (2-3 sentences showing you understood the problem and what direction you're leaning). Then ask your questions.

**How to structure each question:**
- **Lead with your read of the situation** — show what you concluded from Phase 1-2 and what decision you're trying to make
- **Offer 2-3 concrete options** with real-world references (name actual brands, aesthetics, techniques)
- **State the conversion/UX impact** of each option in one sentence — what changes for the end user

**The three types of questions that top designers ask** (use the right mix, not all three every
time):

1. **Conversion-diagnostic** — probes that uncover hidden conversion factors:
   - "What's the one objection your potential customers have before buying/signing up?"
   - "Where do visitors come from — are they searching for this, or do they not yet know they
     need it?"
   - "What does your highest-converting channel look like right now — what's working that we
     should amplify?"

2. **Strategic-direction** — choices that fundamentally reshape the design:
   - Aesthetic direction with named references and tradeoffs
   - Content strategy (benefit-led vs. proof-led vs. story-led hero)
   - Trust architecture (social proof heavy vs. authority-led vs. transparency-first)

3. **Constraint-uncovering** — hidden limitations that derail designs if discovered late:
   - "Do you have professional photography/video, or should I design around illustrations or
     abstract visuals?" (This changes the entire aesthetic approach)
   - "Is there existing brand guidelines or a color palette I should work within, or is this a
     blank canvas?"
   - "What's the technical environment — WordPress, static HTML, React?" (Only if not obvious
     from context)

**Question quality rules:**
- Only ask about decisions where the wrong assumption would produce a fundamentally different
  (and wrong) design
- Never ask what you can determine from context — if they said "SaaS dashboard," don't ask
  "Is this a dashboard?"
- Never ask preference questions without first proposing a direction and explaining why
- Probe for emotional motivations and objections — "What almost stops your customers?" is more
  useful than "What colors do you like?"
- 3 sharp questions beat 6 mediocre ones

**Example of a GOOD question:**

> **Trust & Conversion Strategy:** For a peptide research company, credibility is everything —
> your visitors are likely scientists or informed consumers who are skeptical by default. I see
> three approaches:
>
> - **Option A: Authority-led** — lead with published research citations, lab certifications,
>   and team credentials prominently in the hero. Feels institutional, builds deep trust. Best
>   if your audience is researchers or medical professionals who need proof before exploring.
> - **Option B: Transparency-first** — lead with third-party test results, Certificate of
>   Analysis links, and manufacturing process visuals. Feels honest and differentiated (most
>   competitors hide this). Best if purity/quality concerns are the main purchase objection.
> - **Option C: Results-led** — lead with customer outcomes, before/after data, and
>   testimonials. Feels accessible and commercial. Best if your audience already understands
>   peptides and just needs social proof to choose you over competitors.
>
> Which is closest, or is there something specific your customers always ask about before buying?

**Example of a BAD question:**

> What colors do you want? Do you prefer minimalist or modern? What's your target audience?

*(Bad because: no strategic reasoning, no options with tradeoffs, forces the user to do the
designer's job, asks for information the designer should already be able to infer or propose)*

**Complexity-based scaling:**
- Single component: 1-2 questions if ambiguity exists, otherwise execute
- Full page or section: 3-4 questions
- Full site or design system: 4-5 questions

### Phase 4 — Refine and Execute

Incorporate the user's answers into the strategy brief. State any remaining decisions you're making and why. Then proceed to implementation, actively applying the rules and guidelines found in the `references/` directory.

If the user's answers reveal a fundamentally different direction than your draft brief, rebuild the strategy before coding. Never force early assumptions onto a changed brief.

---

## 2. Conversion-Focused Design Psychology

Design that converts is not about tricks — it is about understanding what drives human decisions
and removing every barrier between the visitor and their desired outcome. Psychology, when
implemented correctly, is the single highest-leverage factor in conversion and user engagement —
more than aesthetics, more than copy alone. Apply these principles with judgment, not as rigid
formulas.

This section is weighted toward a single high-intent moment — a landing page, a hero, a pricing
table — which makes it mostly Brand-mode psychology. Product-mode work is more often a *flow*:
onboarding, a trial, a paywall, a booking sequence, where trust and momentum build across several
screens rather than one. For that, read `references/behavioral-psychology.md` alongside this
section.

### The Dream Outcome

Before designing any section, identify the singular end result the customer wants — not the product
feature, but the life change. This is the Dream Outcome. It is the North Star for every headline,
visual, and CTA on the page.

Perform a **cog-check** on every section you design: *"Does this move the visitor closer to the
Dream Outcome, or resolve a specific objection?"* If the answer is no, the section is system
leakage — cut it or redesign it around a real purpose.

Example: For a physical therapy service, the Dream Outcome is "moving freely without pain" — not
"booking a session." Every section must reinforce that transformation, not describe the service.

Pair the cog-check with a **question audit**: every screen implicitly asks the visitor a
question, and a question that requires evaluation ("Is this worth it?") stalls the visitor at
the exact moment they have the least information to answer it. Where possible, sequence an easy
action question ("Want to see how it works?") before the hard evaluative one. This principle
does most of its work in multi-screen product flows — trials, paywalls, onboarding — where
`references/behavioral-psychology.md` covers it in depth.

### Above-the-Fold (ATF) Mastery: The 5-Second Rule

Most visitors never scroll past the fold. Concentrate 80–90% of creative effort here. Visitors
process images far faster than text — the ATF must function as a visual-emotional bridge before a
single word is read.

The six ATF components in priority order:

1. **Headline** — Articulate the value proposition in 5 seconds or less. Clarity > cleverness.
2. **Sub-headline** — Clarify the "how" and reduce perceived effort with "Without [Pain Point]" framing.
3. **Hero visual** — Show the product in action or the transformation. Address all three dimensions
   of value: Emotional (how they feel), Social (how others perceive them), Functional (what it does).
4. **CTA button** — Single, obvious. Apply the **Blur Test:** if the page were blurred, the CTA
   must remain the most visually dominant element on the screen. If any other element competes, subordinate it.
5. **Social proof** — Immediate trust signals (star ratings, customer count) visible before the scroll.
   Stack proof early to build trust before the visitor evaluates the offer.
6. **FUD reduction** — Risk-reversal signals (money-back guarantee, security badge) placed directly
   below the CTA to neutralize Fear, Uncertainty, and Doubt at the point of action.

### Copywriting Psychology (Reference)

These are reference tools to apply actively when generating headlines, section copy, and CTAs
during design. The AI agent using this skill should reach for them at the right moments — not just
know they exist.

**"So That" bridge:** Connect every feature to its benefit. Every claim must survive the test:
*"This has X, so that you get Y."* If you cannot complete that sentence, the feature is not ready
to be shown on the page.
- Weak: "Cloud-native CI/CD platform with 99.99% uptime SLA"
- Strong: "Ship faster, so that you never miss a release because of downtime"

**Specificity directive:** A number or a concrete sensory detail removes ambiguity that an
adjective leaves the reader to fill in — usually with a skeptical interpretation. Reach for
this whenever copy leans on a vague qualifier like "fast," "easy," or "close by."
- Weak: "Quick setup" → Strong: "Start in 2 taps"
- Weak: "Fast delivery" → Strong: "Arrives in 23 minutes"
- Weak: "Beach house with garden" → Strong: "Beachside escape steps from the sand"

**Headline formula:** `[End Result] + [Time Period] + [Emotional Payoff]`
- "Calm Your Horse in Just 2 Weeks So You Can Enjoy Safer Rides"
- "Get a Lean Body in 45 Minutes a Day and Feel Great in Your Clothes"
- "Build a Six-Figure Practice in 30 Days and Change People's Lives"

**Hormozi Value Equation (audit tool):** After drafting copy for any section, audit it against
four variables. High-converting copy maximizes the top two and minimizes the bottom two:

| Variable | Direction | How to apply |
|---|---|---|
| Dream Outcome | ↑ Maximize | Emphasize the transformation, not the feature |
| Perceived Likelihood of Success | ↑ Maximize | Add visual proof, testimonials, before/after data |
| Time Delay | ↓ Minimize | Highlight speed — "Results in 7 days," "Installs in 24 hours" |
| Effort & Sacrifice | ↓ Minimize | Use "Without [pain]" — "Without surgery," "Without counting calories" |

### Storytelling Frameworks

To maintain focus and avoid design paralysis, select ONLY ONE storytelling framework and ONE primary psychological constraint per section. Do not attempt to satisfy all frameworks simultaneously.

Storytelling is a neurological strategy — not decoration. Choosing the right framework determines
which sections exist and in what order they appear on the page. This choice is made in Phase 2
(see Section 1) and should not change mid-implementation.

Always use second person ("you") in narrative and section copy — the customer must be the
protagonist of every sentence. The brand is always the guide, never the hero. (CTA button copy is
the exception — see CTA Psychology below, where first-person outperforms second-person.)

**PAS — Pain → Agitate → Solve:**
Best for visitors who are aware of their problem but have not found the right solution.
- **Pain:** Name the problem directly and specifically — vague pain is ignored.
- **Agitate:** Amplify the cost of inaction. What happens if they do nothing?
- **Solve:** Present the solution as the only logical exit. Don't hedge or qualify.

**Before-After Bridge:**
Best for transformation-focused products (fitness, finance, education, coaching).
- **Before:** Paint the current state — the struggle, the frustration, the limitation.
- **Bridge:** Introduce the brand as the turning point, the mechanism of change.
- **After:** Visualize the destination in vivid, specific detail. Every testimonial, visual, and
  data point on the page reinforces this "after" state.

**Hero's Journey:**
Best for high-consideration purchases and service brands.
- The customer is the **Hero** on a quest with a challenge to overcome.
- The brand is the **Mentor** — who provides the tool (the Elixir) that enables their success.
- Design "About" sections, testimonials, and brand story sections to reinforce the brand as
  a wise guide, not a self-celebrating entity.

### Visual Hierarchy

- Use the **Z-pattern** for action-oriented pages (landing pages, product pages): logo top-left →
  nav top-right → hero content center-left → CTA bottom-right. Place the primary CTA at the
  Z-pattern's terminal point.
- Use the **F-pattern** for text-heavy pages (blogs, documentation): users scan left-to-right at the
  top, then mostly down the left edge. Place key content and CTAs along this path.
- Every section needs exactly one job. If you can't state what a section does in one sentence,
  split it or cut it.
- Headlines must be 3–4× larger than body text for hierarchy to register in a scan.
  Use bullet points, icons, and bolded subheadings to anchor the scanner's eye.

### CTA Psychology

- High contrast against surrounding space — the button should be the most visually dominant
  element in its section. Isolation (generous whitespace around CTAs) draws the eye.
- Action-oriented copy: "Get Started Free" > "Submit", "See Pricing" > "Learn More".
  First-person language ("Start my free trial") outperforms second-person ("Start your free trial")
  by measurable margins.
- Verb weight matters as much as person: "Subscribe" carries the psychological weight of a
  recurring commitment and a hard-to-find cancel flow; "Start" implies a beginning with no
  baggage. Prefer "Start," "Try," or "Get" over "Subscribe" on first-touch CTAs. Where signup
  is preceded by real user investment (see product-mode onboarding in
  `references/behavioral-psychology.md` §6), "Continue" or "Save my progress" can outperform
  both — it accurately names what the user is actually doing.
- Pair the CTA with an effort-quantifier subtext when the hidden objection is about friction:
  "Start in 2 taps" answers "do I need a credit card?" before it's asked, the same way a price
  or shipping subtext answers a cost objection.
- One primary CTA per viewport. Multiple competing CTAs create decision paralysis.

### Trust Signals and FUD Reduction

Every trust signal exists to remove a specific Fear, Uncertainty, or Doubt. Name the FUD before
selecting the signal — the wrong trust signal placed in the wrong context adds noise, not confidence:
- Fear of financial risk → money-back guarantee, zero-interest financing badge
- Uncertainty about quality → third-party certifications, lab results, Certificate of Analysis
- Doubt about results → specific before/after data, named testimonials with measurable outcomes

**Placement:**
- Place testimonials near pricing. Place client logos near the hero. Place ratings and reviews
  near buy buttons. Trust signals work because they reduce perceived risk at the moment of
  highest uncertainty.
- Numbers over vague claims: "10,000+ customers" > "trusted by many."
  Specific numbers feel authentic; round numbers feel fabricated.
- Security badges near payment forms — max 3 badges, placed close to credit card fields.

**Wall of Love:**
Never hide social proof in carousels — carousel engagement is low, meaning hidden proof
is wasted proof. Display testimonials in a stacked or grid format where all proof is visible
simultaneously. Place the wall near the highest-friction point on the page — typically at or
just above pricing or the primary CTA.

### Reduce Cognitive Load

- **Hick's Law:** fewer choices = faster decisions = more conversions. Max 3–5 navigation items.
  If you have more, group them.
- **Progressive disclosure:** show only what's needed now, reveal complexity on demand.
  Multi-step forms outperform single long forms.
- Single-column form layouts outperform multi-column — they create a clear top-to-bottom flow.

### Price Presentation

- **Anchoring:** show the higher price first (crossed out or as "was"), then the current price.
- **Three-tier pricing** with the middle option highlighted and labeled "Most Popular" or
  "Recommended" leverages the decoy effect.
- **Annual/monthly toggle** with savings percentage shown on annual.
- **Eliminate math:** never make the user calculate their savings. "Save $20" outperforms
  "20% off" — do the arithmetic for them and state the result directly. The same logic applies
  to add-on pricing: state it as a fraction of a purchase the user already committed to
  ("$50 protection plan — 2.6% of your order") rather than as an isolated figure.
- **Single number over a range:** a price range (e.g. "$13-17") makes the brain anchor to the
  high end and treat the low end as unlikely, which turns a simple choice into a mental
  negotiation. Where you control the pricing model, show one fixed number — it converts "how
  much am I risking?" into the much easier "do I want this?"
- **Convenience reframing:** where relevant, attach a heuristic badge ("Cheaper," "2 min away")
  next to a price so the visitor can categorize the choice at a glance instead of comparing
  raw numbers themselves.

Read `references/conversion-patterns.md` for page-type-specific playbooks (landing pages, product
pages, homepages, e-commerce, trial/paywall screens, booking flows, onboarding). Read
`references/behavioral-psychology.md` for the psychology behind multi-screen product flows —
onboarding, trials, paywalls, and other moments where trust and momentum build across more than
one screen rather than a single conversion moment.

---

## 3. Design & Implementation Standards (MANDATORY REFERENCES)

To keep this primary skill file focused on strategy and conversion, all technical design execution standards have been extracted into the `references/` directory. 

**Before executing Phase 4, use your file reading tools to ingest ONLY the `references/` files that are strictly necessary for the chosen stack and design requirements. Do not blindly read all reference files if they are not relevant to the current task:**
- Read `references/design-system-foundations.md` for spacing, typography scale, color tokens, and aesthetic principles.
- Read `references/motion-and-interaction.md` for animation physics, interaction states, and accessibility defaults.
- Read `references/mobile-first.md` for container queries, fluid typography, touch targets, and image optimization.
- Read `references/modern-css-and-craft.md` for CSS-native features (nesting, `:has()`, view transitions) and design engineering craft (layered shadows, optical alignment).
- Read `references/frameworks.md` once the stack is known, for stack-specific rules (React vs Tailwind vs Vanilla).

For a micro-edit that skips Strategic Discovery, only the reference files relevant to the edited code apply.

---

## Closing

Match implementation complexity to the aesthetic vision. Maximalist brand-mode designs need
elaborate code with extensive animations, textures, and layered effects. Minimalist product-mode
designs need restraint, precision, and obsessive attention to spacing and typography. Both are
hard. Both are valuable. Elegance comes from executing the vision with commitment.

Production-readiness is non-negotiable — every output must be practical, deploy-ready, and
functional across devices. Structure code so AI agents can easily find, read, and modify any
section. Use clear file organization, descriptive class names, and section comments.

The AI is capable of extraordinary creative work. Don't hold back — show what can truly be created
when thinking outside the box and committing fully to a distinctive, conversion-focused vision
that serves real users on real devices.
