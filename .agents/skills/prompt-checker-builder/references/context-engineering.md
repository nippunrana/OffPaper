# Context Engineering — the mental model

Background for the principles in `SKILL.md`. You don't need this to operate; read it when you want to explain *why* a recommendation matters, or when the user pushes on the reasoning. It's distilled from Andrej Karpathy's framing of "Software 3.0" and context engineering.

## Contents
1. [Software 1.0 → 3.0](#software-10--30)
2. [The LLM as an operating system](#the-llm-as-an-operating-system)
3. [Context engineering = memory management](#context-engineering--memory-management)
4. [Jagged intelligence and "circuits"](#jagged-intelligence-and-circuits)
5. [Summoning ghosts (LLM psychology)](#summoning-ghosts-llm-psychology)
6. [Director and Executor](#director-and-executor)
7. [How each idea maps to a review lens](#how-each-idea-maps-to-a-review-lens)

---

## Software 1.0 → 3.0
- **Software 1.0** — a human writes explicit, deterministic instructions in code (C++, Python).
- **Software 2.0** — the "code" is learned weights; you program by curating datasets and training.
- **Software 3.0** — the LLM is a programmable interpreter, and you program it in natural language. The **content of the context window is the lever** over what it computes.

The practical upshot for prompting: a prompt is not a request, it's a *program*. Writing it well is software engineering, just in a new substrate.

## The LLM as an operating system
A useful analogy maps the LLM stack onto a classical computer:

| Classical computer | LLM equivalent | The bottleneck |
|---|---|---|
| CPU / microcode | frozen model weights | reasoning latency |
| RAM (working memory) | **context window** | length & attention cost |
| Disk / SSD | vector DB / retrieval (RAG) | retrieval precision |
| Peripherals (keyboard, screen) | tools, APIs, deterministic code | how legible the interface is to the model |

The weights are a frozen reasoning engine — they don't change during a run, they just process whatever state is in the window. **If something isn't in the context window, the model cannot reason about it.** This is the whole reason "Completeness" matters: missing context isn't neutral, it forces fabrication.

## Context engineering = memory management
"Prompt engineering" sounds like wordsmithing a short request. **Context engineering** is the bigger discipline: managing the model's RAM so the *next token* is as well-determined as possible. That means:
- **Just enough, not maximal.** Pack what's load-bearing; evict the rest. There's a real cost curve — more tokens means more latency, more money, and *diluted attention* over the part that matters. This is the "Budget" lens, and it's the single idea most missing from naive prompting, where people assume more context is always safer.
- **Swap deliberately.** Pull in (via retrieval or by naming a file) exactly what the current task needs; leave out last week's conversation.
- **Compact.** Summarize long history instead of pasting it whole.
- **Prime with traces.** A couple of input→output examples steer the weights toward the right behavior more efficiently than paragraphs of description (the "Few-shot traces" lens).

## Jagged intelligence and "circuits"
Models are **jagged**: they can find a subtle bug in a 100k-line codebase and then miscount the letters in "strawberry" or fumble trivial arithmetic. This isn't random — it's a shape left by training. Tasks with clear verification signals during training (coding, chess) became sharp capability *peaks*; tasks that are out-of-distribution or have no clean reward stayed *valleys*.

The engineering response is not "prompt harder." It's: **detect the valley and route around it.** Hand exact counting, math, large-scale find/rename, and fresh-fact lookups to a deterministic tool, and then *verify*. That's the "Circuit fitness" and "Verification" lenses — and it's why tools exist in the OS analogy: they're the deterministic peripherals you call precisely because the stochastic CPU is a liability for that operation.

## Summoning ghosts (LLM psychology)
A model is a statistical simulation of human text, not an agent with drives. Karpathy's phrasing: you're *summoning a ghost*, not raising an animal. It has no intrinsic motivation, so the levers that work on people don't work here:
- Pleading, threats, urgency, flattery, "I'll tip you $200" — **noise.** It changes nothing except how full the window is.
- What *does* work is precise specification: state the behavior, the constraints, and the output you want.
- Genuine **role framing** ("act as a security reviewer") is different and useful — it actually conditions the distribution the model samples from. The thing to cut is *motivational theater*, not *functional persona*.

This is the "LLM psychology" lens.

## Director and Executor
As models get more capable, the human role shifts from typing syntax to **directing**: setting the spec, exercising taste and judgment, and providing oversight — owning *the why*. The model becomes the **executor** that fills in the *how*. The leverage comes from a tight loop where the human stays in the oversight seat rather than rubber-stamping.

For this skill, that has two consequences:
1. The prompt should encode **oversight** (verification, "stop and ask, don't guess"), because that's the Director's responsibility made durable.
2. Your own Plan-First step is the same pattern: you propose a spec (the outline), the human approves, then you execute (write the prompt). Don't quietly make decisions that are the human's to make.

## How each idea maps to a review lens
| Idea here | Lens in SKILL.md |
|---|---|
| Context window = RAM; missing context → fabrication | Completeness |
| Just enough, not maximal; attention dilution | Budget / "just enough" |
| Window is undifferentiated unless structured | Structure |
| Summoning ghosts; theater is noise | LLM psychology |
| Spec over coaxing; decompose big tasks | Clarity, Decomposition |
| Jagged valleys → route to tools | Circuit fitness |
| Director owns oversight | Verification & oversight |
| Underspecified output is useless output | Output contract |
| Traces prime behavior efficiently | Few-shot traces |
