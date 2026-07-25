---
name: gemini-integration
description: Help developers integrate Google Gemini AI API into any website, webapp, or mobile app — including Gemma open-weights models (Gemma 4, gemma-4-31b-it, gemma-4-26b-a4b-it) served through the same Gemini API and Google AI Studio. Use this skill whenever the user mentions Gemini, Gemma, Google AI, generative AI integration, LLM-powered features, AI chatbot, multimodal AI, structured AI outputs, function calling with Gemini or Gemma, grounding with Google Search, streaming AI responses, context caching, embeddings, or open-weight/open-source model integration via Google's API. Make sure to trigger this skill even if the user asks simple questions about Gemini or Gemma API setup, needs help with API key configuration, wants to add AI text generation, image analysis, or document processing to their app, mentions @google/genai or google-genai SDK, or is deciding between Gemini and Gemma for a project.
---

# Gemini AI API Integration

A skill for integrating Google Gemini AI capabilities into websites, web apps, and mobile apps — covering client setup, content generation, streaming, structured outputs, function calling, built-in tools, RAG, embeddings, and production optimization.

---

## 1. Reference Directory

Read these reference files on demand based on the task at hand:

| Reference File | When to Read |
|---|---|
| [client_setup.md](references/client_setup.md) | Starting a new integration, setting up API keys, choosing a model, initializing the SDK |
| [content_generation.md](references/content_generation.md) | Text generation, multimodal inputs, streaming responses, multi-turn chat, system instructions |
| [structured_outputs.md](references/structured_outputs.md) | JSON schema enforcement, type-safe responses, data extraction from AI outputs |
| [function_calling.md](references/function_calling.md) | Connecting Gemini to external APIs, agentic tool loops, thought signatures |
| [built_in_tools.md](references/built_in_tools.md) | Google Search grounding, Maps, code execution, URL context |
| [rag_and_embeddings.md](references/rag_and_embeddings.md) | File Search, vector embeddings, custom RAG pipelines, semantic search |
| [production_optimization.md](references/production_optimization.md) | Caching, inference tiers, safety settings, token management, cost control, error handling |
| [gemma_models.md](references/gemma_models.md) | Using Gemma 4 (open-weights) models via the Gemini API, choosing Gemma vs Gemini, Gemma-specific sampling/thinking config |

---

## 2. Core Integration Workflow

Follow this 4-phase workflow when integrating Gemini AI into an application:

### Phase 1: Requirements Discovery

Before writing code, clarify these with the developer:

1. **Use Case**: What AI capability do they need? (chat, data extraction, content analysis, search, code generation)
2. **Model Selection**: What's the priority — speed, intelligence, or cost?
   - **Gemini 3.5 Flash**: Best balance of speed and reasoning. Native thinking. Default recommendation.
   - **Gemini 3.1 Pro**: Complex multimodal synthesis, longest context (1M+ tokens).
   - **Gemini 3.1 Flash-Lite**: High-throughput, low-latency, cost-optimized.
   - **Gemma 4** (`gemma-4-31b-it` or `gemma-4-26b-a4b-it`): Open-weights (Apache 2.0) alternative on the same API, when cost or portability to self-hosting matters more than absolute frontier capability or audio support. See [gemma_models.md](references/gemma_models.md).
3. **Modality**: Text-only, or multimodal (images, video, audio, PDFs)? Note Gemma 4 does not support audio — steer to a Gemini model if audio is required.
4. **Response Pattern**: One-shot generation, streaming, or multi-turn chat?
5. **Deployment Target**: Server-side (Python/Node.js), client-side (browser), or mobile?

Consult [client_setup.md](references/client_setup.md) for model details and SDK installation.

### Phase 2: Client Setup & Authentication

1. Obtain an API key from [Google AI Studio](https://aistudio.google.com/apikey).
2. Install the appropriate SDK and initialize the client.
3. Store the API key securely — never expose it in client-side code. Use a backend proxy or environment variables.

Consult [client_setup.md](references/client_setup.md) for platform-specific setup code.

### Phase 3: Feature Implementation

Route to the appropriate reference file based on the feature being built:

| Building This? | Read This |
|---|---|
| Text generation or chat | [content_generation.md](references/content_generation.md) |
| Image/video/audio/PDF analysis | [content_generation.md](references/content_generation.md) |
| Generating images from a text prompt | [content_generation.md](references/content_generation.md) |
| Streaming responses for real-time UI | [content_generation.md](references/content_generation.md) |
| Extracting structured data (JSON) | [structured_outputs.md](references/structured_outputs.md) |
| Calling external APIs from Gemini | [function_calling.md](references/function_calling.md) |
| Search-grounded or location-aware answers | [built_in_tools.md](references/built_in_tools.md) |
| Document search / knowledge base | [rag_and_embeddings.md](references/rag_and_embeddings.md) |
| Semantic search / classification | [rag_and_embeddings.md](references/rag_and_embeddings.md) |
| Using Gemma 4 / open-weights models | [gemma_models.md](references/gemma_models.md) |

### Phase 4: Production Hardening

Before going to production, address these:

1. **Safety Settings**: Configure content filtering thresholds appropriate for your app.
2. **Context Caching**: Cache repeated large inputs to reduce cost and latency.
3. **Inference Tier**: Choose Priority (reliable), Standard (balanced), Flex (cheap), or Batch (async bulk).
4. **Token Management**: Use `countTokens` for pre-flight validation. Monitor `thoughts_token_count` for cost.
5. **Error Handling**: Implement retries with exponential backoff for transient failures.

Consult [production_optimization.md](references/production_optimization.md) for implementation details.

---

## 3. Key Design Patterns

### Streaming Chat Pattern
For real-time conversational UIs, use `generateContentStream` (or `Interactions API` with `stream: true`) to display tokens as they arrive. This is critical for perceived performance in chat interfaces. See [content_generation.md](references/content_generation.md) for streaming code.

### Multimodal Upload Pattern
- **Small files** (<20MB): Use inline data (base64 encoded) for images, short audio.
- **Large files**: Use the File API to upload first, then reference by URI. File API uploads expire in 48 hours.
- Use `media_resolution` parameter to balance quality vs. token cost.

### Agentic Tool Loop Pattern
When Gemini needs to call your APIs:
1. Define function schemas → 2. Gemini returns a `functionCall` → 3. Your code executes it → 4. Return `functionResponse`.
Thought signatures from Gemini 3+ models must be returned exactly as received. See [function_calling.md](references/function_calling.md).

### Hybrid RAG Pattern
- **Long context** (up to 1M tokens): Best for single-document analysis. Simple, no infra needed.
- **File Search** (managed RAG): Best for multi-document knowledge bases. Embeddings persist indefinitely.
- **Custom vector DB**: Best when you need full control over retrieval and ranking.
See [rag_and_embeddings.md](references/rag_and_embeddings.md) for trade-offs.

---

## 4. Model Selection Quick Reference

| Model | Context Window | Best For | Speed |
|---|---|---|---|
| Gemini 3.5 Flash | 1,000,000 | General purpose, reasoning + vision | Fast |
| Gemini 3.1 Pro | 1,000,000+ | Complex multimodal, long context | Moderate |
| Gemini 3.1 Flash-Lite | 1,000,000 | High-throughput, low-cost | Fastest |
| Gemma 4 31B (`gemma-4-31b-it`) | 256,000 | Open-weights, reasoning + coding, no audio | Moderate |
| Gemma 4 26B A4B (`gemma-4-26b-a4b-it`) | 256,000 | Open-weights, high-throughput MoE, no audio | Fast |

---

## 5. Security Considerations

- Never expose your API key in frontend JavaScript. Always proxy through a backend server.
- Use environment variables (`GEMINI_API_KEY`) or a secrets manager for key storage.
- Configure safety settings to filter harmful content appropriate to your use case.
- For OAuth-based auth (enterprise), see the [Google Cloud OAuth docs](https://ai.google.dev/gemini-api/docs/oauth).
