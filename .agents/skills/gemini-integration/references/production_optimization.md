# Production Optimization

## Table of Contents
1. [Context Caching](#context-caching)
2. [Inference Tiers](#inference-tiers)
3. [Safety Settings](#safety-settings)
4. [Token Management](#token-management)
5. [Error Handling & Retries](#error-handling--retries)
6. [Webhooks](#webhooks)
7. [Deep Research Agent](#deep-research-agent)

---

## Context Caching

Caches minimize repetitive processing costs when you send the same large context (e.g., a system prompt, a document, or reference material) across multiple requests.

### Minimum Token Requirements
- **Gemini 3.5/3.1**: Minimum 4,096 tokens

### Creating an Explicit Cache

**Python:**
```python
cache = client.caches.create(
    model="gemini-3.1-pro",
    contents=[client.files.get(name="massive_doc")],
    config={"ttl": "3600s"}  # Cache for 1 hour
)

# Use the cache in subsequent requests
response = client.models.generate_content(
    model="gemini-3.1-pro",
    contents="Summarize the key findings.",
    config={"cached_content": cache.name}
)
```

### When to Use Caching
- Large system prompts used across many requests
- Reference documents queried repeatedly
- Few-shot examples that don't change between requests
- Cost savings: cached tokens are billed at a reduced rate compared to re-processing

---

## Inference Tiers

Choose the right tier based on your latency, cost, and reliability requirements:

| Tier | Latency | Pricing | Reliability | Use Case |
|---|---|---|---|---|
| **Priority** | Seconds | 1.75x–2x | Non-sheddable | Critical production traffic, user-facing features |
| **Standard** | Seconds/Minutes | Base | Standard | General production use |
| **Flex** | 1–15 Minutes | 0.5x | Sheddable | Background tasks, off-peak processing |
| **Batch** | <24 Hours | 0.5x | Async | Bulk processing, dataset generation |

### Batch API Usage

For processing large datasets asynchronously:

```python
# Submit a batch job
batch_job = client.batches.create(
    model="gemini-3.5-flash",
    requests=[
        {"contents": "Summarize document 1..."},
        {"contents": "Summarize document 2..."},
        # ... hundreds or thousands of requests
    ]
)

# Check status later
status = client.batches.get(name=batch_job.name)
```

---

## Safety Settings

Configure content filtering thresholds per harm category. By default, safety filters are **Off** for Gemini 3.x models.

### Harm Categories
- `HARM_CATEGORY_HATE_SPEECH`
- `HARM_CATEGORY_SEXUALLY_EXPLICIT`
- `HARM_CATEGORY_DANGEROUS_CONTENT`
- `HARM_CATEGORY_HARASSMENT`

### Threshold Levels
- `BLOCK_NONE` / `OFF` — No blocking (default for Gemini 3.x)
- `BLOCK_ONLY_HIGH` — Block only high-probability unsafe content
- `BLOCK_MEDIUM_AND_ABOVE` — Block medium and high
- `BLOCK_LOW_AND_ABOVE` — Block low, medium, and high (strictest)

### Implementation

**Python:**
```python
from google.genai import types

response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="Some content to analyze",
    config=types.GenerateContentConfig(
        safety_settings=[
            types.SafetySetting(
                category=types.HarmCategory.HARM_CATEGORY_HATE_SPEECH,
                threshold=types.HarmBlockThreshold.BLOCK_LOW_AND_ABOVE,
            ),
            types.SafetySetting(
                category=types.HarmCategory.HARM_CATEGORY_DANGEROUS_CONTENT,
                threshold=types.HarmBlockThreshold.BLOCK_MEDIUM_AND_ABOVE,
            ),
        ]
    ),
)
```

**Node.js:**
```javascript
const response = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: "Some content to analyze",
  config: {
    safetySettings: [
      { category: "HARM_CATEGORY_HATE_SPEECH", threshold: "BLOCK_LOW_AND_ABOVE" },
      { category: "HARM_CATEGORY_DANGEROUS_CONTENT", threshold: "BLOCK_MEDIUM_AND_ABOVE" },
    ],
  },
});
```

**REST:**
```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H "Content-Type: application/json" \
  -X POST \
  -d '{
    "safetySettings": [
      {"category": "HARM_CATEGORY_HATE_SPEECH", "threshold": "BLOCK_LOW_AND_ABOVE"}
    ],
    "contents": [{"parts": [{"text": "Content to analyze"}]}]
  }'
```

### Checking for Blocked Content

```python
# Check if prompt was blocked
if response.prompt_feedback and response.prompt_feedback.block_reason:
    print(f"Prompt blocked: {response.prompt_feedback.block_reason}")

# Check if response was blocked
candidate = response.candidates[0]
if candidate.finish_reason == "SAFETY":
    for rating in candidate.safety_ratings:
        if rating.blocked:
            print(f"Blocked for: {rating.category}")
```

---

## Token Management

### Pre-flight Token Counting

Always validate token counts before sending large requests:

**Python:**
```python
token_count = client.models.count_tokens(
    model="gemini-3.5-flash",
    contents="Your potentially large prompt here..."
)
print(f"Token count: {token_count.total_tokens}")
```

**Node.js:**
```javascript
const tokenCount = await ai.models.countTokens({
  model: "gemini-3.5-flash",
  contents: "Your potentially large prompt here...",
});
console.log(`Token count: ${tokenCount.totalTokens}`);
```

### Context Windows

| Model | Context Window |
|---|---|
| Gemini 3.5 Flash | 1,000,000 |
| Gemini 3.1 Pro | 1,000,000+ |
| Gemini 3.1 Flash-Lite | 1,000,000 |

### Cost Monitoring

Monitor `thoughts_token_count` — "Thinking" tokens are billable output tokens and can significantly affect cost, especially with `thinking_level: "high"`.

---

## Error Handling & Retries

### Common Error Codes

| HTTP Code | Meaning | Action |
|---|---|---|
| 400 | Bad request (invalid params, missing thought_signature) | Fix the request |
| 403 | API key invalid or quota exceeded | Check key and billing |
| 429 | Rate limited | Retry with exponential backoff |
| 500 | Server error | Retry with backoff |
| 503 | Service unavailable | Retry with backoff |

### Retry Pattern

**Python:**
```python
import time

def generate_with_retry(prompt, max_retries=3):
    for attempt in range(max_retries):
        try:
            response = client.models.generate_content(
                model="gemini-3.5-flash",
                contents=prompt,
            )
            return response
        except Exception as e:
            if attempt == max_retries - 1:
                raise
            wait_time = (2 ** attempt) + (0.1 * attempt)  # Exponential backoff
            print(f"Retry {attempt + 1}/{max_retries} in {wait_time}s: {e}")
            time.sleep(wait_time)
```

**Node.js:**
```javascript
async function generateWithRetry(prompt, maxRetries = 3) {
  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      return await ai.models.generateContent({
        model: "gemini-3.5-flash",
        contents: prompt,
      });
    } catch (error) {
      if (attempt === maxRetries - 1) throw error;
      const waitTime = Math.pow(2, attempt) * 1000;
      console.log(`Retry ${attempt + 1}/${maxRetries} in ${waitTime}ms`);
      await new Promise((r) => setTimeout(r, waitTime));
    }
  }
}
```

---

## Webhooks

Webhooks notify your application when async operations complete (batch jobs, long-running interactions).

### Static Webhooks (Project-Level)
- Configured at the project level
- Uses symmetric secrets (Standard Webhooks spec)
- Good for consistent routing of all async events

### Dynamic Webhooks (Request-Level)
- Specified per-request as an override
- Uses asymmetric JWKS signatures
- Good for job-specific routing to different endpoints

---

## Deep Research Agent

Deep Research is a managed agent that plans, iterates, and synthesizes information autonomously. It requires the **Interactions API** — it cannot be invoked via `generateContent`.

**Python:**
```python
interaction = client.interactions.create(
    model="deep-research-preview-04-2026",
    contents="Analyze the 2026 solid-state battery market landscape.",
    agent_config={
        "type": "deep-research",
        "collaborative_planning": True,
    }
)
```

**Node.js:**
```javascript
const interaction = await client.interactions.create({
  model: "deep-research-preview-04-2026",
  agentConfig: {
    type: "deep-research",
    collaborativePlanning: true,
  },
});
```

### Operational Considerations
- Deep Research tasks can exceed **600 seconds**.
- Track `interaction_id` and `last_event_id` to handle connection drops and resume streaming.
- Enable `thinking_summaries: "auto"` in the `agent_config` to surface reasoning steps during streaming.
