# Behavioral Psychology for Product-Mode Flows

Read this reference when designing onboarding, trials, paywalls, pricing decisions,
booking/checkout flows, empty states, or anything else where a user has to make a
repeated or high-stakes decision inside a product — not just land on a page and bounce.
Section 2 of SKILL.md covers the psychology of a single high-intent conversion moment
(a landing page hero, a pricing table). This file covers the psychology of *flows*:
sequences of screens where trust, momentum, and perceived effort compound over time.

The seven levers below share one root idea: **every screen implicitly asks the user a
question, and the harder that question is to answer, the more likely the user is to
default to "I'll think about it later" — which is abandonment.** Your job is not to
manipulate the answer, it's to make the honest answer easy to give.

---

## 1. The Question Audit

Before designing a screen, name the question it's actually asking the user. Screens
fail when they ask a **hard evaluative question** at the moment of lowest trust — e.g.
a paywall that asks "Is this worth $19/month?" to someone who hasn't used the product
yet forces a value judgment they have no data for. The brain's default response to an
unanswerable question is to defer, and deferral is a lost user.

Reframe the same moment around an **easy action question** instead — "Can I try this
for free?" is a yes/no the user can answer immediately, because it carries no
long-term evaluation. This isn't about hiding the real commitment (see Reciprocity and
the honest-framing rule under Loss Aversion below) — it's about sequencing: let the
user answer easy questions first, and only ask the hard one once they have enough
first-hand experience to answer it confidently.

**Audit checklist for any screen in a flow:**
- What question is this screen actually asking, in the user's head?
- Does answering it require mental math, comparison, or guesswork the interface
  could have done for them?
- Is there a "smart choice" marker (a badge, a default, a pre-filled value) that
  removes the need to evaluate from scratch?
- Has the user already committed to something upstream that makes this screen a
  smaller, secondary decision rather than a fresh one? (see Commitment/Consistency
  below)
- Does the screen proactively disclose the thing the user is most afraid of, before
  they have to ask?

---

## 2. Transparency as the Trust Layer

Users arrive at high-friction moments (paywalls, checkout, subscription upsells) with
a skepticism filter built from years of billing surprises and dark patterns. The
single highest-leverage move at these moments is to proactively disclose the thing
that would normally be hidden — not because it's required, but because disclosing it
*before* the user has to ask for it is what separates "safety net" design from
"sales pitch" design.

**Apply this as a timeline, not a bullet list.** Feature bullets read as marketing
noise; a chronological sequence reads as a plan the user can trust because they can
see what happens and when:
- **Today:** what the user gets immediately, in concrete terms
- **Before the risk point:** an explicit promise to warn them (e.g. "we'll remind you
  3 days before your trial ends") — this single element does more to neutralize
  billing anxiety than any amount of reassuring copy
- **At the risk point:** name exactly what happens (the charge, the renewal) and give
  a one-tap way out

**Ground trust in specificity, not adjectives.** "Fast," "easy," and "quick" are
vague enough that the reader supplies their own (usually skeptical) interpretation. A
number removes the ambiguity and, with it, the objection:
- Weak: "Quick setup" → Strong: "Start in 2 taps"
- Weak: "Fast delivery" → Strong: "Arrives in 23 minutes"
- Weak: "Easy cancellation" → Strong: "Cancel anytime before Day 7 — no phone call"

**Show the actual thing, not a mood board.** Decorative illustrations and abstract
imagery ask the user to imagine what they're getting. Product screenshots, real
photos of the actual space/item/interface, or real characters from the actual app let
the user evaluate what's true rather than what's implied. You cannot commit to
something you cannot see.

---

## 3. Smart Defaults and Choice Architecture

Every blank field or open-ended choice is a small tax on the user's attention. Stack
enough of them and the cumulative cost of deciding exceeds the perceived value of
finishing — the well-known finding that shoppers presented with a large assortment
convert at a much lower rate than those shown a curated handful holds broadly across
digital choice architecture, not just literal product selection.

Counter this by defaulting to the most likely answer and letting the user edit rather
than create from scratch. This shifts their task from "fill in five unknowns" to
"scan and confirm one thing," which is a categorically lighter cognitive load — most
users keep the default, and that's not laziness, it's them reading the default as a
recommendation from a system that already knows what's common.

- Pre-fill fields from the user's own history first, then from general popularity —
  a suggestion tied to their own past behavior reads as personalized, not generic.
- Where a count or result set already exists, put it in the action label instead of a
  generic verb: "View 12 results waiting" tells the user value is already there,
  versus a bare "Search" which asks them to do work before seeing anything.
- Cap the choices shown up front. If there are more than ~6-8 meaningfully different
  options, group or filter before presenting — this is the same logic as Hick's Law
  already in SKILL.md Section 2, applied to in-flow decisions rather than nav items.

---

## 4. Goal Gradient: Never Start at Zero

People accelerate as they perceive themselves nearing a finish line — and,
critically, *where the finish line starts* is a design decision, not a fact. A
progress indicator that begins at 0% reads as "you haven't started," which is
deflating and gives the user permission to abandon before investing anything.

Reframe the first thing the user has already done — creating an account, submitting
an initial input, opening the app — as the first completed step, not a prerequisite
to the real journey. A profile-strength meter that starts at 20% because account
creation already counts as "Step 1: done" creates a different psychological starting
point than the same meter starting at 0%, even though the remaining work is identical.

- Never render a progress state of literally 0%. If the user has taken any action to
  get here, that action is worth crediting.
- Keep the remaining distance visible at all times — momentum requires seeing the
  gradient, not just a static percentage.
- Use this for onboarding, profile completion, multi-step checkout, and loyalty
  mechanics alike — the mechanism is the same regardless of domain.

---

## 5. Reciprocity: Value Before the Ask

Asking for an email address, a credit card, or a signup before the user has
experienced anything of value puts the relationship in debt from the user's side —
they're being asked to trust before they've been given a reason to. Flip the order:
give something real and usable first, and the request for data or commitment lands as
a fair continuation of a relationship that already has some trust in it, not a toll
gate.

- Avoid "hostage" patterns — a blurred report, a locked result set, a form that
  requires a credit card before any usage — these signal "we don't trust you" and the
  user mirrors it back.
- A partial but genuinely useful preview (a real audit result, a functional free
  tier, a usable trial with no card required) does more to earn a signup than any
  amount of persuasive copy on the paywall itself.
- This pairs directly with Transparency (§2): give value first, then be transparent
  about what happens when the free part ends.

---

## 6. Investment and Ownership (IKEA / Endowment Effects)

Users value what they've put effort into more than what they were simply handed, and
once something feels like *theirs*, leaving it behind feels like a loss rather than
just skipping a step. This is why onboarding flows that ask for a few real choices
(pick a goal, name a project, complete one real unit of the product) before account
creation retain better than flows that ask for an email address first: by the time
the signup screen appears, there's already something the user would be giving up by
leaving.

- Sequence real, meaningful choices (not filler questions) before the account-creation
  prompt — a goal, a preference, one completed unit of actual product value.
  "Meaningful" is the operative word: a captcha or a terms checkbox creates zero
  investment because it isn't the user's work.
- Reframe the CTA at that point from creation language to preservation language —
  "Continue" or "Save my progress" describes what's actually happening (the user
  already has something to save) more accurately than "Sign Up," and accuracy here is
  what makes the reframe honest rather than manipulative.

---

## 7. Loss Aversion — Honest Framing Only

Losing something hurts roughly twice as much as gaining the equivalent thing feels
good, which makes loss-framed language ("protect your progress") a genuinely stronger
motivator than gain-framed language ("get more storage"). This is a real and useful
lever — but it is also the easiest one on this list to misuse, and misuse here does
lasting damage: users who feel manipulated at an exit point don't just churn, they
tell other people why.

**Use it only when both of these are true:**
- The thing at risk is **real** — an actual streak, actual files, actual progress the
  user actually built. If there's nothing genuinely at stake, there's nothing honest
  to frame.
- The deadline or consequence is **real** — an actual sync cutoff, an actual trial end
  date. Not a countdown timer invented to manufacture urgency.

**Do not:**
- Fabricate urgency (fake countdown timers, "only 2 left" on unlimited inventory,
  artificial scarcity).
- Use confirmshaming exit copy — labeling "No thanks" as "I'll risk it" or "I don't
  want to save money" punishes the user for making a legitimate choice, which is the
  opposite of the transparency this whole file is built around.
- Invent a threat that doesn't exist to create one. If the honest version of the
  message is boring, that's information — it means the loss-aversion lever doesn't
  apply here, not a reason to dramatize it.

Framed honestly, loss aversion looks like: "Your 14-day streak ends in 6 hours —
open the app to keep it." Framed dishonestly, it looks like invented deadlines and
punitive exit labels. Only the first is compatible with the Transparency principle
in §2 — a flow that builds trust with a Day-5 reminder and then threatens the user on
the way out is working against itself.

---

## 8. Contrast Effect for Add-Ons

People can't judge a price in isolation — they judge it against whatever number they
saw immediately before it. This is already used in SKILL.md's Price Presentation
section for showing a crossed-out original price next to a discount; the same
mechanism applies to add-on and upsell pricing, where the "anchor" is the primary
purchase the user already committed to.

- Never present an add-on cost as a standalone number. Sequence it right after the
  anchor price it's attached to.
- Where honest, reframe the add-on as a fraction of the total rather than a flat
  figure — "$50 protection plan (2.6% of your order)" gives the same information as
  "$50" but does the relative-value calculation the user would otherwise have to do
  themselves. This is the same "eliminate math" principle SKILL.md already applies to
  savings percentages, extended to add-ons.
- This only works if the anchor was genuine (an actual prior purchase, not a
  manufactured comparison price) — a fabricated anchor is the pricing equivalent of
  the fake countdown timer banned in §7.

---

## 9. Appendix: AI Implementation Blueprints

Use these concrete schemas, data structures, and logic algorithms to build out components that are programmatically and psychologically optimized for System 1 behavior.

### 9.1. Trust-First Paywall Schema
Manage trial states using a chronological sequence rather than a static feature list.

```json
{
  "paywall_schema": {
    "version": "2.0.0",
    "hero_logic": "actual_product_preview",
    "timeline_events": [
      { "day": 0, "label": "Unlock full access", "status": "immediate" },
      { "day": 5, "label": "Reminder sent", "action": "push_notification_warning" },
      { "day": 7, "label": "First charge begins", "event_id": "trial_expiry_charge" }
    ],
    "cta_configuration": {
      "primary_label": "Start my free trial",
      "subtext_label": "Start in 2 taps",
      "interaction_cost": "low"
    },
    "cancellation_policy": {
      "type": "cancel_anytime",
      "transparency_badge": true
    }
  }
}
```

### 9.2. Booking Option Data Structure
Ensure booking information contains single fixed numbers, computed durations, and sensory reframing metadata.

```typescript
interface BookingOption {
  id: string;
  anchor_price?: number; // The crossed-out comparison price (original cost)
  final_price: number; // Single integer, NO price ranges
  currency_code: "USD" | "EUR";
  utility_metrics: {
    arrival_estimate_mins?: number;
    arrival_time_label?: string; // e.g., "12:53 PM"
    computed_duration_label: string; // e.g., "Friday to Wednesday • 5 nights"
  };
  heuristic_badges: Array<"Cheaper" | "Fastest" | "Best Value">;
  commitment_state: {
    destination_locked: boolean;
    destination_label: string; // e.g., "Westin St. Francis"
  };
  risk_reversal: {
    free_cancellation: boolean;
    deadline_date_label?: string; // e.g., "March 26"
  };
}
```

### 9.3. Smart Default Fallback & Pre-filling
Pre-populate form configurations based on a prioritized sequence rather than serving a blank state.

```json
{
  "action": "determine_weighted_default",
  "parameters": {
    "field_id": "destination_city",
    "prioritized_weights": {
      "user_recency_and_history": 0.7,
      "global_popularity": 0.3
    }
  },
  "fallback_sequence": [
    "user_last_three_interactions",
    "global_top_trending",
    "expert_system_recommendation"
  ]
}
```

```javascript
// Example value-ahead CTA label formatting
const matchingCount = getInventoryCount(defaults);
const buttonLabel = `Show ${matchingCount} available options`;
```

### 9.4. Momentum Tracker (Goal Gradient progress)
Ensure the progress bar offset never registers a deflating 0% starting state, and reframes basic setup/input as Step 1.

```python
def calculate_user_momentum(completed_steps: int, total_steps: int) -> float:
    # Rule: Never start a user at 0%. Reframe account creation as step 1.
    base_momentum_percentage = 20.0
    calculated_progress = (completed_steps / total_steps) * 100.0
    
    # Final progress is the higher of the two
    return max(base_momentum_percentage, calculated_progress)
```

### 9.5. Reciprocity Value-Gate State Machine
Track value delivery to build strategic debt before invoking auth/signup barriers.

```typescript
interface UserState {
  hasReceivedFreeInsight: boolean;
  wantsFullData: boolean;
  valueDeliveryCount: number;
}

function processReciprocityFlow(state: UserState) {
  if (!state.hasReceivedFreeInsight && state.valueDeliveryCount === 0) {
    provideFreeInsightPreview(); // Provide value first (e.g. site audit results)
    state.hasReceivedFreeInsight = true;
    state.valueDeliveryCount = 1;
  }
  
  if (state.wantsFullData) {
    promptRegistration({
      title: "Save your progress and unlock the full report",
      primaryCTA: "Save My Progress", // Reframe signup as preservation
      secondaryCTA: "Not now"
    });
  } else {
    continueProvidingMicroValue(state);
  }
}
```

### 9.6. Loss Aversion Urgency & Exit Logic (Honest Framing)
Trigger urgency framing only when real progress/streaks are at stake. Frame dismissal option as active acknowledgment of risk.

```json
{
  "trigger": "exit_intent_velocity_high",
  "session_value_score": 85,
  "strategy": "Loss_Aversion_Threat_Honest",
  "content": {
    "headline": "Wait! Your progress is not saved.",
    "risk_factors": ["Project_Q3_Draft.pdf", "Custom_Palette_01"],
    "countdown_timer": null, // only populate from a real deadline — never fabricate one
    "primary_cta": "Save My Progress",
    "secondary_cta": "Not now"
  }
}
```

### 9.7. Contrast Effect Perceived Value Heuristic
Calculate marginal cost percentage to determine if an add-on or upsell displays as a negligible rounding error.

```python
def calculate_relative_value(anchor_price: float, target_price: float) -> str:
    """
    Calculates the relative perception of an add-on price based on the main anchor price.
    """
    perception_ratio = (target_price / anchor_price) * 100.0
    
    if perception_ratio <= 5.0:
        return "Rounding Error (Negligible Perception)"
    else:
        return "Significant Investment (High Perception)"
        
# Example: calculate_relative_value(1900.0, 50.0) -> "Rounding Error" (2.6%)
```
