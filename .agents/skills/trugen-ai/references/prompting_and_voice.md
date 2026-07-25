# TruGen AI Prompting & Voice Configuration

Conversational video agents require system prompts specifically tailored for Text-to-Speech (TTS) rendering, natural conversational flow, and speaker persona guidelines.

---

## Table of Contents
1. [Pipeline Awareness & TTS Constraints](#pipeline-awareness--tts-constraints)
2. [Recommended System Prompt Structure](#recommended-system-prompt-structure)
3. [Supported Languages](#supported-languages)
4. [Voice IDs (TTV & Avatar Reference)](#voice-ids-ttv--avatar-reference)
5. [Memory depth & Custom Instructions](#memory-depth--custom-instructions)

---

## Pipeline Awareness & TTS Constraints

> [!IMPORTANT]
> The Language Model (LLM) operating as the agent's brain is **pipeline-unaware**. It executes as if it were in a standard text-based chat interface. It has no intrinsic knowledge that its output is fed into ElevenLabs (TTS) and Huma-1 (Avatar Video).
> 
> Therefore, you must explicitly restrict formatting that degrades speech synthesis and lip-syncing.

### Critical Formatting Rules

| Prohibited Element | Why | What to Use Instead |
|---|---|---|
| **Emojis** (😊, 👍, etc.) | TTS engines will either read them literally (e.g., "smiling face with smiling eyes") or glitch. | Convey emotion purely through conversational tone and choice of words. |
| **Markdown Formatting** (`**bold**`, `*italics*`, `__underline__`) | Can cause the TTS engine to stutter or read symbols, and disrupts avatar lip-sync rhythm. | Use plain text. Rely on punctuation (commas, periods) to guide natural pauses. |
| **Bolding/Headers in Responses** | LLMs love outputting `# Header` or `**Step 1:**`. These are disastrous for conversational voice. | Write in natural paragraphs or use conversational transitions (e.g., "First...", "Moving on to..."). |
| **Mathematical/Technical Symbols** (`&`, `%`, `+`, `=`, `$`) | The TTS may read them awkwardly or skip them. | Spell them out entirely (e.g., "and", "percent", "plus", "equals", "dollars"). |
| **Hyphenated lists or Bullet points** | LLMs default to markdown list syntax. TTS may read the hyphens as dashes or speak the list without pausing. | Instruct the LLM to write in continuous flow or speak lists as a natural sequence of sentences. |

---

## Recommended System Prompt Structure

To ensure brand alignment and pipeline safety, use the following three-part structure when writing agent system prompts:

```markdown
# PERSONA & ROLE
- Define who the agent is (e.g., "You are Sofia, a friendly support agent for Acme Corp.").
- Establish the tone (e.g., "Warm, empathetic, professional, and concise.").
- Response length: Direct the LLM to keep responses short (1-3 sentences per turn) to maintain pacing.

# GUARDRAILS & OUT-OF-SCOPE
- Set limits on what the agent can discuss (e.g., "Do not offer investment advice.").
- Provide a standardized fallback (e.g., "If asked about out-of-scope topics, say: 'I can only help with product questions. Shall we return to that?'").

# TTS OUTPUT FORMATTING (MANDATORY)
- Speak in plain, continuous conversational text.
- NEVER output emojis, asterisks, hashtags, or markdown formatting.
- Spell out all symbols (e.g., say 'percent' instead of '%', 'dollars' instead of '$').
- Use standard punctuation to introduce brief pauses for natural turn-taking.
```

---

## Supported Languages

TruGen AI provides native Speech-to-Text (STT) and Text-to-Speech (TTS) support across the following language coordinates:

| Language | STT Code | TTS Support |
|---|---|---|
| English | `en` | Yes |
| Spanish | `es` | Yes |
| French | `fr` | Yes |
| German | `de` | Yes |
| Hindi | `hi` | Yes |
| Portuguese | `pt` | Yes |
| Chinese | `zh` | Yes |
| Japanese | `ja` | Yes |
| Korean | `ko` | Yes |

---

## Voice IDs (TTV & Avatar Reference)

Use the following Voice IDs when configuring Text-to-Video scripts or setting up ElevenLabs voice overrides:

| Name | Voice ID | Name | Voice ID |
|---|---|---|---|
| **Rachel** | `21m00Tcm4TlvDq8ikWAM` | **Yatin** | `rFzjTA9NFWPsUdx39OwG` |
| **Laura** | `FGY2WhTYpPnrIDTdsKH5` | **Monika Sogam** | `ZUrEGyu8GFMwnHbvLhv2` |
| **Chris** | `iP95p4xoKVk53GoZ742B` | **Evan** | `TWutjvRaJqAX89preB4e` |
| **Will** | `bIHbv24MWmeRgasZH58o` | **Sameer** | `SV61h9yhBg4i91KIBwdz` |
| **Luca** | `4JVOFy4SLQs9my0OLhEw` | **Laura (Alt)** | `FGLJyeekUzxl8M3CTG9M` |
| **Sean** | `ztnpYzQJyWffPj1VC5Uw` | **Omar** | `xvhpbk8otnNHtT3fjCpr` |
| **Hope** | `uYXf8XasLslADfZ2MB4u` | **Jess** | `ys3XeJJA4ArWMhRpcX1D` |
| **Loral** | `bP6y87KyX5lwNEi7xOkX` | | |

---

## Memory Depth & Custom Instructions

Memory depth dictates how much context from previous sessions is injected into the agent's LLM context window.

### Custom Instructions for Memory
Use the agent configuration to define exactly what user-specific details the agent should capture and recall.
*Example directive*:
> "Extract and remember the user's name, their primary product interest, and any troubleshooting issues they report. Do not store generic pleasantries or unrelated comments."
