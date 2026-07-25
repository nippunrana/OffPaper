# Gemma 4 on the Gemini API

## Table of Contents
1. [Overview](#overview)
2. [Gemma vs Gemini: When to Choose Which](#gemma-vs-gemini-when-to-choose-which)
3. [Available Models](#available-models)
4. [Quickstart](#quickstart)
5. [Recommended Sampling Parameters](#recommended-sampling-parameters)
6. [Thinking Mode](#thinking-mode)
7. [Function Calling](#function-calling)
8. [Structured Outputs](#structured-outputs)
9. [Google Search Grounding](#google-search-grounding)
10. [Multimodal Input](#multimodal-input)
11. [Gemma on the Interactions API](#gemma-on-the-interactions-api)

---

## Overview

Gemma 4 is Google's open-weights model family (Apache 2.0 license). Two Gemma 4 variants are hosted directly on Google AI Studio and callable through the same Gemini API surface — same SDK, same `GEMINI_API_KEY`, same `generateContent` call shape as Gemini models. Swapping a `model` string is usually enough to move an integration from Gemini to Gemma.

The rest of this skill's reference files (`content_generation.md`, `function_calling.md`, `structured_outputs.md`, `built_in_tools.md`) apply to Gemma the same way they apply to Gemini, since the API contract is shared. This file covers what's specific to Gemma: which models exist, what they don't support, and Gemma-specific configuration.

---

## Gemma vs Gemini: When to Choose Which

| Choose Gemma 4 when… | Choose Gemini when… |
|---|---|
| The user wants an open-weights model (Apache 2.0) they could later self-host | Audio input/output or image generation is needed |
| Cost efficiency matters more than absolute frontier capability | The full 1M+ token context window is needed |
| 256K context is sufficient | Highest-throughput/lowest-latency tier (Flash-Lite) is the priority |
| Portability across the API and self-hosted deployments matters | No plans to ever self-host |

Ask about these trade-offs during **Phase 1: Requirements Discovery** if the user mentions Gemma, "open model," "open weights," or cost-sensitive deployment — don't assume Gemma is a drop-in Gemini replacement without checking they don't need audio or the larger context window.

---

## Available Models

Only two Gemma 4 sizes are served on the Gemini API. The other sizes in the Gemma 4 family (E2B, E4B, 12B) are open-weights downloads only — they are **not** callable model IDs on `generativelanguage.googleapis.com`. Do not invent model IDs like `gemma-4-e4b-it` or `gemma-4-12b-it` for API calls.

| Model ID | Architecture | Context Window | Image Input | Video Input | Audio Input |
|---|---|---|---|---|---|
| `gemma-4-31b-it` | Dense | 256,000 | Yes | Yes | No |
| `gemma-4-26b-a4b-it` | Mixture-of-Experts (~4B active) | 256,000 | Yes | Yes | No |

Both are instruction-tuned ("it") chat models. Neither supports audio input or output — if the task needs speech-to-text, voice input, or audio analysis, use a Gemini model instead (see `client_setup.md`'s Model Selection Guide).

---

## Quickstart

Gemma models use the exact same client and call shape as Gemini — only the `model` string changes.

**Python:**
```python
from google import genai

client = genai.Client(api_key=os.environ["GEMINI_API_KEY"])

response = client.models.generate_content(
    model="gemma-4-31b-it",
    contents="Explain the difference between a dense and MoE architecture in two sentences.",
)
print(response.text)
```

**Node.js:**
```javascript
import { GoogleGenAI } from "@google/genai";

const ai = new GoogleGenAI({ apiKey: process.env.GEMINI_API_KEY });

const response = await ai.models.generateContent({
  model: "gemma-4-26b-a4b-it",
  contents: "Explain the difference between a dense and MoE architecture in two sentences.",
});
console.log(response.text);
```

**REST:**
```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemma-4-31b-it:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H 'Content-Type: application/json' \
  -X POST \
  -d '{
    "contents": [{"parts": [{"text": "Explain the difference between a dense and MoE architecture in two sentences."}]}]
  }'
```

Which of the two to default to: `gemma-4-31b-it` for reasoning-heavy or coding tasks where quality matters most; `gemma-4-26b-a4b-it` when throughput/latency matters more and near-equivalent quality at lower active-parameter cost is an acceptable trade.

---

## Recommended Sampling Parameters

Gemma 4's documented defaults differ from Gemini's — set these explicitly rather than relying on API defaults:

```python
config=types.GenerateContentConfig(
    temperature=1.0,
    top_p=0.95,
    top_k=64,
)
```

---

## Thinking Mode

Gemma 4 supports the same `thinking_config` mechanism as Gemini's thinking models — configure it through the API rather than raw prompt tokens.

**Python:**
```python
response = client.models.generate_content(
    model="gemma-4-31b-it",
    contents="A train leaves station A at 60mph, another leaves station B (300 miles away) at 40mph toward A. When do they meet?",
    config=types.GenerateContentConfig(
        thinking_config=types.ThinkingConfig(thinking_level="high"),
    ),
)
```

Reserve `thinking_level="high"` for logic-heavy tasks — algorithmic problems, multi-step math, debugging — where extra reasoning tokens improve accuracy. For simple lookups or formatting tasks, leave thinking off (or low) to save latency and cost, same guidance as for Gemini in `client_setup.md`.

---

## Function Calling

Gemma 4 supports native function calling with the same `types.Tool` / function-declaration schema used for Gemini — see `function_calling.md` for the full agentic tool-loop pattern (define schema → model returns `functionCall` → your code executes → return `functionResponse`). No Gemma-specific changes are needed to that pattern.

```python
get_order_status = {
    "name": "get_order_status",
    "description": "Look up the current status of a customer order.",
    "parameters": {
        "type": "object",
        "properties": {
            "order_id": {"type": "string", "description": "The order ID, e.g. ORD-1234"},
        },
        "required": ["order_id"],
    },
}

response = client.models.generate_content(
    model="gemma-4-31b-it",
    contents="What's the status of order ORD-1234?",
    config=types.GenerateContentConfig(tools=[types.Tool(function_declarations=[get_order_status])]),
)

if response.function_calls:
    for fc in response.function_calls:
        print(f"Call: {fc.name}({fc.args})")
```

---

## Structured Outputs

`response_mime_type="application/json"` with a `response_schema` (see `structured_outputs.md`) is a `generateContent`-level configuration, not a Gemini-specific one, so it's expected to work with Gemma requests the same way. This isn't called out explicitly in Gemma's own documentation, though — if a task depends on strict schema conformance, test the schema against the target Gemma model before shipping it, and have a prompted-JSON-plus-parsing fallback ready in case conformance is weaker than on Gemini.

---

## Google Search Grounding

Gemma 4 supports the `google_search` built-in tool the same way Gemini does — see `built_in_tools.md` for the full pattern, including reading `grounding_metadata` for citations.

```python
response = client.models.generate_content(
    model="gemma-4-26b-a4b-it",
    contents="What are the dates for cherry blossom season in Tokyo this year?",
    config=types.GenerateContentConfig(tools=[{"google_search": {}}]),
)

for chunk in response.candidates[0].grounding_metadata.grounding_chunks:
    if chunk.web:
        print(f"Source: {chunk.web.title} — {chunk.web.uri}")
```

---

## Multimodal Input

Both API-hosted Gemma 4 models accept image and video input (no audio — see [Available Models](#available-models)). Follow the same File API / inline-data patterns as `content_generation.md`, with two Gemma-specific notes:

- **Modality ordering**: place image content before the text prompt in the `contents` list for best results.
- **Video**: processed as roughly one frame per second; keep clips short and favor a lower visual token budget per frame to control token cost.
- **Visual token budget**: Gemma 4 supports a configurable per-image token budget (documented values: 70, 140, 280, 560, 1120) to trade detail against cost — low budgets for classification or video frames, high budgets for OCR or dense document parsing. Confirm the exact config field name in the SDK version you're using before relying on it, as it isn't part of the base `GenerateContentConfig` surface documented for Gemini.

---

## Gemma on the Interactions API

Both `gemma-4-31b-it` and `gemma-4-26b-a4b-it` are listed as supported models on the Interactions API — Google's newer, stateful alternative to `generateContent` (typed `steps` instead of role-based `contents`, optional `background=True` for long-running async execution, session retention up to 55 days on paid tiers / 1 day on free). This skill's other reference files are written against `generateContent`; reach for the Interactions API with a Gemma model when the task specifically needs server-side state or backgrounded execution rather than by default.

```python
response = client.interactions.create(
    model="gemma-4-31b-it",
    background=True,
    input="Analyze this 200-page report and summarize the key risks.",
)
print(f"Interaction ID: {response.id}")
```
