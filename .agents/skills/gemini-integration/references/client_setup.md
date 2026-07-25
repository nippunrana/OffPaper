# Client Setup & Authentication

## Table of Contents
1. [Python Setup](#python-setup)
2. [Node.js Setup](#nodejs-setup)
3. [REST / cURL Setup](#rest-setup)
4. [API Key Management](#api-key-management)
5. [Model Selection Guide](#model-selection-guide)

---

## Python Setup

Install the Gen AI SDK:

```bash
pip install google-genai
```

Initialize the client:

```python
from google import genai

client = genai.Client(api_key="GEMINI_API_KEY")
```

Or use an environment variable (recommended):

```python
import os
from google import genai

client = genai.Client(api_key=os.environ["GEMINI_API_KEY"])
```

Quick test:

```python
response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="How does AI work?"
)
print(response.text)
```

---

## Node.js Setup

Install the SDK:

```bash
npm install @google/genai
```

Initialize the client:

```javascript
import { GoogleGenAI } from "@google/genai";

const ai = new GoogleGenAI({ apiKey: process.env.GEMINI_API_KEY });
```

Quick test:

```javascript
async function main() {
  const response = await ai.models.generateContent({
    model: "gemini-3.5-flash",
    contents: "How does AI work?",
  });
  console.log(response.text);
}

await main();
```

---

## REST Setup

All Gemini API endpoints use this base URL:

```
https://generativelanguage.googleapis.com/v1beta/
```

Quick test:

```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H 'Content-Type: application/json' \
  -X POST \
  -d '{
    "contents": [{
      "parts": [{"text": "How does AI work?"}]
    }]
  }'
```

---

## API Key Management

### Getting an API Key

1. Go to [Google AI Studio](https://aistudio.google.com/apikey)
2. Click "Create API key"
3. Copy the key and store it securely

### Security Best Practices

- **Never hardcode** API keys in source code
- **Never expose** API keys in client-side JavaScript (browser)
- Use **environment variables** for local development
- Use a **secrets manager** (e.g., AWS Secrets Manager, GCP Secret Manager, HashiCorp Vault) for production
- For browser-based apps, **proxy API calls through your backend** to keep the key server-side
- For mobile apps, use a backend proxy — never embed the key in the app bundle

### Environment Variable Setup

**bash / zsh:**
```bash
export GEMINI_API_KEY="your-api-key-here"
```

**.env file (with dotenv):**
```
GEMINI_API_KEY=your-api-key-here
```

---

## Model Selection Guide

| Model | Context Window | Thinking | Best For |
|---|---|---|---|
| `gemini-3.5-flash` | 1,000,000 tokens | Native (configurable) | Default choice. Fast reasoning, vision, and text. |
| `gemini-3.1-pro` | 1,000,000+ tokens | Yes | Complex multimodal synthesis, long-document analysis. |
| `gemini-3.1-flash-lite` | 1,000,000 tokens | Limited | High-throughput, low-latency, cost-optimized tasks. |
| `gemma-4-31b-it` | 256,000 tokens | Yes (`thinking_config`) | Open-weights (Apache 2.0) dense model; cost/portability priority, no audio. |
| `gemma-4-26b-a4b-it` | 256,000 tokens | Yes (`thinking_config`) | Open-weights MoE model; high-throughput, no audio. |

Gemma 4 models share the same `generateContent` call shape as Gemini — see [gemma_models.md](gemma_models.md) for Gemma-specific guidance (sampling defaults, when to prefer it over Gemini, Interactions API support).

### Thinking Configuration

Gemini 3.5+ models have native "thinking" enabled by default. Control it with the `thinking_config`:

**Python:**
```python
from google.genai import types

response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="Solve this step by step: 15% of 340",
    config=types.GenerateContentConfig(
        thinking_config=types.ThinkingConfig(thinking_level="low")
    ),
)
```

**Node.js:**
```javascript
import { ThinkingLevel } from "@google/genai";

const response = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: "Solve this step by step: 15% of 340",
  config: {
    thinkingConfig: {
      thinkingLevel: ThinkingLevel.LOW,
    },
  },
});
```

Thinking levels: `"none"`, `"low"`, `"medium"`, `"high"`. Higher = more reasoning tokens (billed as output tokens). Use `"low"` for simple tasks, `"high"` for complex reasoning.

### Generation Config Parameters

Fine-tune generation behavior:

| Parameter | Description | Default |
|---|---|---|
| `temperature` | Randomness (0.0–2.0). Lower = more deterministic. | Model-dependent |
| `top_p` | Nucleus sampling threshold (0.0–1.0). | Model-dependent |
| `top_k` | Top-k sampling. Number of top tokens to consider. | Model-dependent |
| `max_output_tokens` | Maximum tokens in the response. | Model-dependent |
| `stop_sequences` | Strings that stop generation when encountered. | None |

**Python:**
```python
response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="Explain quantum computing",
    config=types.GenerateContentConfig(
        temperature=0.1,
        max_output_tokens=500,
    ),
)
```

**Node.js:**
```javascript
const response = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: "Explain quantum computing",
  config: {
    temperature: 0.1,
    maxOutputTokens: 500,
  },
});
```

**REST:**
```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H 'Content-Type: application/json' \
  -X POST \
  -d '{
    "contents": [{"parts": [{"text": "Explain quantum computing"}]}],
    "generationConfig": {
      "temperature": 0.1,
      "maxOutputTokens": 500
    }
  }'
```
