# Domain playbooks

Load the section that matches the user's task. Each one lists the failure modes that bite hardest in that domain, which of the four lenses (Context / Task direction / Verifiability / Priming) carry the most weight, a quick checklist, and a worked prompt you can adapt. The lenses themselves are defined in `SKILL.md`; this file is the depth-on-demand layer.

## Contents
1. [Document / image / video understanding (multimodal)](#1-document--image--video-understanding-multimodal)
2. [Large-text extraction](#2-large-text-extraction)
3. [High-precision arithmetic & logic](#3-high-precision-arithmetic--logic)
4. [Production prompts in agent workflows (LangChain / n8n / API)](#4-production-prompts-in-agent-workflows-langchain--n8n--api)

---

## 1. Document / image / video understanding (multimodal)

Reading prescriptions, lab reports, receipts, IDs, screenshots, or analyzing a video (e.g. someone's sports technique).

**Failure modes that bite here**
- **Confident misreads.** Vision models will read a blurry "5" as "8", invent a field that "should" be on the form, or normalize an odd value to a familiar one. The fabrication looks exactly as authoritative as a correct read.
- **Inference creep.** Asked to "analyze the report," the model starts diagnosing or recommending beyond what's visible. On medical/legal content that's both wrong and risky.
- **No uncertainty signal.** Without one, you can't tell a solid extraction from a guess — fatal for unattended pipelines.
- **Video: temporal vagueness.** "Analyze the technique" with no notion of phases, key frames, or what "good" looks like yields generic commentary.

**Lenses that carry the most weight:** Grounding (top priority), Output contract, Verification (confidence routing), Completeness (a rubric of what to look for).

**Checklist**
- Instruct: answer **only** from what's visible; mark unreadable/absent fields as `null`; never infer or "correct."
- Separate **describe** (what's literally there) from **judge** (the assessment) — and only allow judgment against an explicit rubric you supply.
- Require a **confidence** score (per-field or overall) and define the threshold that triggers human review.
- Give the model the **criteria**: for a lab report, the fields; for a sports video, the specific elements to assess (e.g. "stance width, hip rotation, follow-through") and what a good vs. poor version looks like.
- For video, anchor to structure: phases, timestamps, or "describe the motion at the moment of contact" rather than "analyze the video."

**Worked example — sports-technique video analysis (production)**
```markdown
<context>
You are a tennis coach analyzing a video of an amateur's forehand. Assess ONLY what is
visible in the footage. Describe before you judge, and judge only against the rubric below.
Do not invent detail you cannot see.
</context>

<source>{{video}}</source>

<rubric>
Assess these elements, each as observed: (1) ready-position stance, (2) backswing/unit turn,
(3) contact point relative to the body, (4) follow-through, (5) footwork/recovery.
For each: what a sound version looks like vs. a common fault.
</rubric>

<task>
For each rubric element: describe what the player actually does, rate it (good / needs-work /
not-visible), and give ONE specific, actionable cue to improve it. If an element isn't visible
in the clip, mark it not-visible — do not guess.
</task>

<output_format>
JSON: { "elements": [{ "name", "observation", "rating": "good"|"needs-work"|"not-visible",
"cue": string|null }], "top_priority": string, "confidence": number /*0–1*/ }
</output_format>

<verification>
If confidence < 0.6 (e.g. poor angle, occlusion, too few frames), say so plainly and recommend
a better camera angle rather than padding with generic advice.
</verification>
```

---

## 2. Large-text extraction

Pulling the valuable parts out of long input (transcripts, articles, support threads, contracts) per a requirement.

**Failure modes that bite here**
- **Context bloat.** Dumping the whole document when the answer lives in one section — buries the signal and costs latency. The Budget lens is the headline here.
- **Loose target.** "Get the important points" is undefined; the model picks its own notion of important.
- **Schema drift.** Free-form output that the next step can't parse; fields appearing/disappearing across runs.
- **Silent omission vs. invention.** Either it skips items that match, or it pads with plausible-but-absent ones.

**Lenses that carry the most weight:** Output contract (schema), Completeness (a precise definition of "relevant"), Budget, Priming (few-shot is unusually powerful for extraction).

**Checklist**
- Define **relevance operationally**: not "important points" but "every commitment with an owner and a due date," or "each clause that mentions termination."
- Specify the **schema** and what to do when nothing matches (empty array, not prose).
- Tell it to **quote or cite the span** it extracted from when fidelity matters — this both grounds it and lets you verify.
- Use **1–2 few-shot examples** for anything subtle; they pin down edge cases faster than rules.
- Apply Budget: feed the relevant section if you can pre-chunk, rather than the entire corpus.

**Worked example — commitment extraction from a meeting transcript**
```markdown
<context>Extract action items from a meeting transcript. An action item = an explicit
commitment by a named person to do a specific thing. Ignore hypotheticals and general
discussion.</context>

<source>{{transcript}}</source>

<task>Return every action item. For each: who owns it, what they committed to, any due date
mentioned (else null), and the exact sentence you took it from.</task>

<output_format>JSON array: [{ "owner", "task", "due": string|null, "source_quote" }].
Return [] if there are none. Do not invent items that weren't explicitly committed.</output_format>

<example>
Input: "...Priya: I'll send the revised deck by Thursday. Tom: we could maybe look at pricing..."
Output: [{ "owner": "Priya", "task": "send the revised deck", "due": "Thursday",
"source_quote": "I'll send the revised deck by Thursday" }]
// Tom's line is a hypothetical ("could maybe") — not extracted.
</example>
```

---

## 3. High-precision arithmetic & logic

Tasks where a number or a logical result must be exactly right (totals, percentages, unit conversions, eligibility rules, date math).

**Failure modes that bite here**
- **Doing the math in the LLM.** Token-by-token generation is not a calculator; it will produce a confident, slightly-wrong number, and it gets worse with more operands or more steps.
- **Hidden multi-step logic.** Chained conditions ("if A and not B, unless C…") collapse silently.
- **No check.** A wrong total looks identical to a right one.

**Lenses that carry the most weight:** Circuit fitness (route it out of the LLM), Verification, Decomposition.

**Checklist**
- The default fix: **don't ask the LLM to compute.** Have it extract the operands, then do the arithmetic in a deterministic step — a code/function tool, an n8n Code node, a spreadsheet formula, a DB query.
- If it genuinely must reason in-model, force **explicit step-by-step** and then a **self-check** ("recompute a different way; if they disagree, flag it"). This raises reliability but never matches a real calculator — prefer the tool.
- For logic, **enumerate the rules** and ask for the rule that fired alongside the verdict, so the decision is auditable.
- Add a verification step: re-derive, or assert the result is in a sane range.

**Worked example — fixing a "do the math in your head" draft**
> Original draft: *"Look at the invoice line items and tell me the grand total with 8% tax."*
>
> Rewrite the prompt so the model **extracts** the line items into structured numbers, and the
> total + tax are computed in a code step, not generated:
```markdown
<task>From the invoice, extract each line item as { description, qty, unit_price }.
Return ONLY the JSON array. Do NOT compute any totals — a downstream code step does that.</task>
<output_format>[{ "description", "qty": number, "unit_price": number }]</output_format>
```
> Then in the workflow: `subtotal = Σ(qty × unit_price)`, `total = subtotal × 1.08`, in code.
> The LLM never touches the arithmetic, so it can't get it wrong.

---

## 4. Production prompts in agent workflows (LangChain / n8n / API)

Prompts that aren't typed by a human each time — they're templates wired into an agent/automation and run unattended, often thousands of times.

**Failure modes that bite here**
- **"Stop and ask" is impossible.** No human is in the loop per run, so an instruction to ask for clarification just stalls or guesses.
- **Unvalidated output.** Free-form text that the next node can't parse breaks the pipeline silently.
- **No confidence routing.** Good and bad runs are treated identically; there's no escape hatch.
- **Template hygiene.** Unescaped/ambiguous `{{variables}}`, instructions and injected data not separated (prompt-injection risk), drift between system and user message.
- **Non-determinism.** High temperature or loose structure makes runs inconsistent — death for an automation.

**Lenses that carry the most weight:** Output contract (machine-validatable), Verification & oversight (designed-in, not interactive), Structure (separate instructions from injected data), Budget (it runs N times — every token is a recurring cost).

**Checklist**
- Replace interactive oversight with **designed-in oversight**: strict schema + validation, a **confidence field** with a threshold that branches to human review, and a **fallback** path for failures.
- Put fixed behavior in the **system message**; inject variable data in a clearly delimited block in the user message — never blend instructions and untrusted input (injection).
- Make it a clean **template**: name the `{{variables}}`, note what each contains, keep the structure identical across runs.
- Push for **determinism**: low temperature, an exact output shape, no "be creative."
- Treat it like code: keep a few fixed input→expected examples and re-test the prompt when you change it (this skill's eval loop is exactly that, but you can do it lightweight).

**Worked example — system-prompt template for an n8n classification node**
```markdown
SYSTEM:
You classify an incoming support email into exactly one category and extract its urgency.
Use only the email content provided. Output must be valid JSON matching the schema — nothing else.

Categories: ["billing","bug","feature_request","account","other"]

Output schema:
{ "category": one of the categories, "urgency": "low"|"medium"|"high",
  "reason": short string, "confidence": number /*0–1*/ }

Rules:
- If the email is ambiguous, pick the best category and lower the confidence — do NOT ask questions.
- If confidence < 0.5, the workflow escalates to a human, so report it honestly.

USER:
<email>
{{email_body}}
</email>
```
> Downstream: validate the JSON; on validation failure or `confidence < 0.5`, route to the
> human-review branch. The prompt can't "ask" — the *workflow* handles the uncertain cases.
