---
name: trugen-ai
description: Help developers build, configure, and optimize applications using TruGen AI. Use this skill whenever the user mentions TruGen, conversational video agents, real-time avatar pipelines (STT/TTS/Video rendering), Huma-1, Hawkeye-1 vision perception, or LiveKit avatar streams. Make sure to trigger this skill even if the user asks simple API questions about TruGen, needs help designing system prompts, or wants to set up webhook notifications.
---

# TruGen AI Developer Assistant

A specialized skill for building, configuring, and optimizing human-like conversational video agents using the TruGen AI platform.

---

## 1. Reference Directory

This skill uses specialized reference files. Read them on demand when executing relevant tasks:

- **API Specifications**: Refer to [api_reference.md](file:///Users/nippunrana/Library/CloudStorage/Dropbox/office/Projects/skills/trugen-ai/references/api_reference.md) for endpoints, request schemas, BYO LLM constraints, and custom tools JSON formats.
- **Prompting & Voice Rules**: Refer to [prompting_and_voice.md](file:///Users/nippunrana/Library/CloudStorage/Dropbox/office/Projects/skills/trugen-ai/references/prompting_and_voice.md) for TTS-safe formatting rules, supported languages, and ElevenLabs Voice IDs.
- **Integrations & Event Logic**: Refer to [integrations.md](file:///Users/nippunrana/Library/CloudStorage/Dropbox/office/Projects/skills/trugen-ai/references/integrations.md) for LiveKit setups, webhook payload schemas, and conversational interruption triggers.
- **Persona Blueprints**: Refer to [use_cases.md](file:///Users/nippunrana/Library/CloudStorage/Dropbox/office/Projects/skills/trugen-ai/references/use_cases.md) for pre-optimized system prompts for common roles (sales, support, health intake).

---

## 2. Core Development Workflow

Follow this systematic 4-phase workflow when building TruGen AI integrations:

### Phase 1: Architecture & Requirements Discovery
Before writing code, clarify the following with the developer:
1. **Agent Role & Persona**: What is the target behavior? (Consult [use_cases.md](file:///Users/nippunrana/Library/CloudStorage/Dropbox/office/Projects/skills/trugen-ai/references/use_cases.md) for templates).
2. **LLM Provider**: Are they using default models (OpenAI/Groq) or bringing their own custom LLM (BYO LLM)? (Consult [api_reference.md](file:///Users/nippunrana/Library/CloudStorage/Dropbox/office/Projects/skills/trugen-ai/references/api_reference.md)).
3. **Deployment Strategy**: iFrame embed, frontend widget, or a custom LiveKit Python WebRTC agent? (Consult [integrations.md](file:///Users/nippunrana/Library/CloudStorage/Dropbox/office/Projects/skills/trugen-ai/references/integrations.md)).
4. **Data Grounding**: Do they need Agentic RAG or Traditional RAG? (Consult [api_reference.md](file:///Users/nippunrana/Library/CloudStorage/Dropbox/office/Projects/skills/trugen-ai/references/api_reference.md)).

### Phase 2: System Prompt & Guardrails Engineering
Write the agent's system prompt using the structured template in [prompting_and_voice.md](file:///Users/nippunrana/Library/CloudStorage/Dropbox/office/Projects/skills/trugen-ai/references/prompting_and_voice.md).
> [!IMPORTANT]
> Keep the LLM's responses short and conversational (1-3 sentences).
> You MUST enforce **TTS-Safe formatting rules**:
> - NO Emojis.
> - NO Markdown headers, bold text, or asterisks in spoken responses.
> - NO Bullet points or hyphens; use spoken transitions.
> - Spell out symbols (`%` becomes "percent", `$` becomes "dollars", `&` becomes "and").

### Phase 3: API & Configuration Assembly
- Construct the agent creation payload for `POST /ext/agent`. Ensure `avatars` array and LLM settings are properly formatted.
- Configure tools or MCP bindings if external data fetching is required.
- Set up Knowledge Base mappings using the `/ext/kb` APIs.

### Phase 4: Integration & Event Handlers
- Set up Webhook receivers to listen to critical event callbacks like `utterance_committed`.
- Handle active interruptions (checking if the application captures the `user.started_speaking` or `agent.interrupted` signals).
- Set up frontend widget JS embeds or LiveKit plugins. Ensure `ctx.room.disconnect()` is called on agent shutdown to stop video rendering.
- When embedding the agent via an iFrame, use the `/embed/` path prefix (not `/agent/`) and pass the candidate's name and email in the query parameters (`username=<NAME>&id=<EMAIL>`) to link the session context.

---

## 3. Key Design Patterns

### Graceful Interruption Pattern
When building custom WebRTC clients:
1. Listen for the `user.started_speaking` event.
2. Immediately trigger a client-side command or send an interruption webhook to stop Huma-1 rendering.
3. Pause or discard the current TTS queue to let the user speak uninterrupted.

### CRM Post-Turn Sync Pattern
1. Configure webhooks to listen for the `utterance_committed` event.
2. In your webhook handler, parse `payload.text` for qualifying intent.
3. Push structured transcription snippets asynchronously to your database or CRM (like HubSpot or Salesforce) to keep customer logs updated in real-time.

### Programmatic Session Cleanup Pattern
When implementing backend-driven completion or termination (e.g., due to conduct policy violations, timeouts, or admin override):
1. Execute the TruGen conversation termination API call (`DELETE /v1/conversation/{id}`) unconditionally upon completion or report generation.
2. Do NOT gate the termination API call behind checking whether the database session state is already marked as `COMPLETED`, since the state may have transitioned earlier in the lifecycle.
3. Clear the saved `trugen_conversation_id` from the session record once deleted to prevent redundant API calls on subsequent page reloads.

---

## 4. BYO LLM Connection & Troubleshooting Checklist

Ensure these checklist items are followed when configuring a **Bring Your Own LLM (BYO LLM)** provider to prevent the agent from hanging on the "Connecting to Agent" spinner or returning HTTP 500 errors:

### 1. Non-Empty API Key Required
TruGen's internal LLM client code requires a non-empty string in the **API Key** field in the provider settings. If your endpoint does not validate keys, enter a dummy value (e.g. `123` or `dummy`). Leaving it blank will cause connection setup failures.

### 2. SSE Streaming Support
The custom completions endpoint must support Server-Sent Events (SSE) streaming (`Content-Type: text/event-stream`). It must return chunked JSON data matching standard OpenAI chunk structure, followed by `data: [DONE]`.

### 3. Disable Nginx Buffering
Nginx by default buffers FastCGI responses, which prevents real-time chunk delivery. Set the `X-Accel-Buffering: no` response header in your PHP completions handler to disable this buffering.

### 4. Robust URL Path Routing
HTTP clients and SDKs concatenate base URLs differently (sometimes appending `/v1/chat/completions`, sometimes `/chat/completions`, and sometimes omitting trailing slashes). Use a robust regex matching block in Nginx:
```nginx
location ~ ^/(truinterview)?/?(v1/)?chat/completions$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME /var/www/codepane.com/html/truinterview/chat_completions.php;
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
}
```

### 5. Concurrent Session Limits
Developer/Free accounts restrict concurrent sessions to exactly 1. If the agent's simulator playground panel in the TruGen developer console is open or was recently closed, it holds an active WebRTC room connection that blocks any external embed connections with `concurrent session limit reached` errors. Always stop the console playground before testing the application embed.
