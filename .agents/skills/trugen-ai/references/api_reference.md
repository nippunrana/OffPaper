# TruGen AI API Reference

This document provides complete developer specifications for integrating the TruGen AI platform.

---

## Table of Contents
1. [Base URL & Authentication](#base-url--authentication)
2. [Video Agents API](#video-agents-api)
3. [Persona Templates API](#persona-templates-api)
4. [Knowledge Base API (RAG)](#knowledge-base-api-rag)
5. [Conversations & Live Control](#conversations--live-control)
6. [Text-to-Video (TTV) Generation](#text-to-video-ttv-generation)
7. [BYO LLM & Custom Tools](#byo-llm--custom-tools)

---

## Base URL & Authentication

All API requests must be sent to the following base URL:
```
https://api.trugen.ai
```

### Authentication Header
Every HTTP request must include the `x-api-key` header with a valid API key:
- **Header Key**: `x-api-key`
- **Header Value**: `<YOUR_API_KEY>`

---

## Video Agents API

The Agents API enables the creation, retrieval, modification, and deletion of conversational video agents.

### Endpoints
| Method | Path | Description |
|---|---|---|
| `POST` | `/ext/agent` | Create a new agent from scratch. |
| `POST` | `/ext/agentbytemplate` | Instantiate an agent using an existing template. |
| `GET` | `/ext/agent/{id}` | Retrieve details of a specific agent (e.g., `b63c2a53-266b-4b43-a71b-7ea8b5e2e916`). |
| `GET` | `/ext/agents` | List all agents linked to the account. |
| `PUT` | `/ext/agent/{id}` | Update agent configuration or system prompts. |
| `DELETE` | `/ext/agent/{id}` | Permanently delete an agent. |

### Create Agent (`POST /ext/agent`) Request Schema
```json
{
  "agent_name": "Sofia - Friendly Support",
  "agent_system_prompt": "Speak in a warm, engaging tone and provide clear answers.",
  "avatars": [
    {
      "avatar_id": "string",
      "config": {
        "llm": {
          "provider": "openai",
          "model": "gpt-4"
        }
      }
    }
  ],
  "config": {
    "record": true,
    "is_active": true
  },
  "knowledge_base": [],
  "tool": [],
  "mcp": [],
  "callback_url": "https://webhooks.example.com/events",
  "callback_events": ["agent.started_speaking", "call_ended"]
}
```

---

## Persona Templates API

Templates standardize behavior and serve as blueprints for brand governance and safety across multiple agents.

### Endpoints
- `POST /ext/template` — Create a new template blueprint.
- `GET /ext/template/{id}` — Retrieve template metadata.
- `GET /ext/templates` — List available blueprints.
- `PUT /ext/template/{id}` — Update rules, constraints, or system prompts.
- `DELETE /ext/template/{id}` — Delete a template.

### Configurable Template Components
- **System Prompt**: Base directives defining persona role and conversation safety guidelines.
- **Entry & Exit Messages**: Standard greetings and farewells spoken by the agent.
- **Idle Timeout**: Parameters dictating agent behavior when user interaction pauses.
- **Knowledge Base Attachments**: Pre-bound knowledge sources.
- **Behavior & Safety**: Escalation triggers, content moderation, and session length limits.

---

## Knowledge Base API (RAG)

TruGen AI supports Retrieval-Augmented Generation (RAG) using two operational modes:
1. **Agentic RAG**: The LLM dynamically determines when and which knowledge base to query based on conversation context.
2. **Traditional RAG**: Every user query triggers a search against all configured KBs, passing retrieved text to the LLM.

*Note: The name and description metadata of the Knowledge Base are the primary vectors the LLM uses to determine relevance during reasoning.*

### Endpoints
| Method | Path | Description |
|---|---|---|
| `POST` | `/ext/kb` | Create a KB. Requires `multipart/form-data` with `name`, `description`, and a file or raw text. |
| `POST` | `/ext/kb/{id}/doc` | Add document (PDF, TXT, DOCX) to an existing KB. |
| `GET` | `/ext/kb/{id}` | Retrieve details and document listing of a specific KB. |
| `GET` | `/ext/kbs` | List all account Knowledge Bases. |
| `PUT` | `/ext/kb/{id}` | Update KB metadata (name/description). |
| `DELETE` | `/ext/kb/{id}` | Permanently delete a Knowledge Base. |
| `DELETE` | `/ext/kb/doc/{id}` | Remove a single document using its UUID. |

---

## Conversations & Live Control

Manage active conversation sessions and inject programmatic directives.

### Endpoints
- `GET /ext/conversation/{id}` — Retrieve conversation state (`STARTED`, `IN_PROGRESS`, `ENDED`), streaming transcript, and `recording_url`.
- `PUT /v1/conversation/{id}/speak` — Inject programmatic text. Forces the avatar to immediately speak the text payload.
- `DELETE /v1/conversation/{id}` — Forcibly disconnect/terminate the active session.

---

## Text-to-Video (TTV) Generation

Used for asynchronous rendering of static videos from written scripts.

### Generation Workflow
1. **Initiate**: Send `POST /v1/script-to-video/createVideo` with the script and options. Returns a `generation_id`.
2. **Track**: Poll `GET /script-to-video/genStatus/{generation_id}` or configure a webhook.
3. **Download**: When status is `completed`, retrieve the final URL from the payload.

### TTV Status Values
- `processing`: Rendering is in progress.
- `completed`: Video rendering is done; the `video_url` is active.
- `failed`: Render failure.

---

## BYO LLM & Custom Tools

### Bring Your Own LLM (BYO LLM)
Developers can use custom LLMs (e.g., self-hosted Llama-3 or proprietary models) for privacy, cost control, or fine-tuning.

#### Configuration
Set `provider` to `"custom"` in the `avatars.config.llm` configuration block.

#### Integration Requirements
- **Endpoint**: The custom backend must expose a POST `/chat/completions` endpoint.
- **Payload Format**: Must support the standard `messages[]` format.
- **Streaming**: Must support Server-Sent Events (SSE) streaming for real-time latency optimization.
- **Capabilities**: Must support standard tool-calling structures if tools are used, and multimodal image input if Hawkeye-1 vision perception is active.

### Custom Tools
Expose backend functions to the agent using the Tools API.

#### Request Schema Example (`POST /ext/tool`)
```json
{
  "type": "tool.api",
  "schema": {
    "name": "get_stock",
    "parameters": {
      "symbol": "string"
    }
  },
  "request_config": {
    "method": "GET",
    "url": "https://api.stocks.com/v1"
  },
  "event_messages": {
    "loading": "Checking the market...",
    "success": "I found it!"
  }
}
```
