# Content Generation

## Table of Contents
1. [Basic Text Generation](#basic-text-generation)
2. [System Instructions](#system-instructions)
3. [Multimodal Inputs](#multimodal-inputs)
4. [Image Generation](#image-generation)
5. [Streaming Responses](#streaming-responses)
6. [Multi-Turn Chat](#multi-turn-chat)
7. [Media Tokenization Rates](#media-tokenization-rates)

---

## Basic Text Generation

The simplest form of Gemini integration — send text, get text back.

**Python:**
```python
response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="Explain how neural networks learn"
)
print(response.text)
```

**Node.js:**
```javascript
const response = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: "Explain how neural networks learn",
});
console.log(response.text);
```

**REST:**
```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H 'Content-Type: application/json' \
  -X POST \
  -d '{"contents": [{"parts": [{"text": "Explain how neural networks learn"}]}]}'
```

---

## System Instructions

System instructions configure the model's persona and behavior. They persist across the entire conversation and are processed before any user content.

**Python:**
```python
from google.genai import types

response = client.models.generate_content(
    model="gemini-3.5-flash",
    config=types.GenerateContentConfig(
        system_instruction="You are a senior Python developer. Provide concise, production-ready code with error handling."
    ),
    contents="Write a function to validate email addresses"
)
```

**Node.js:**
```javascript
const response = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: "Write a function to validate email addresses",
  config: {
    systemInstruction: "You are a senior Python developer. Provide concise, production-ready code with error handling.",
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
    "system_instruction": {
      "parts": [{"text": "You are a senior Python developer. Provide concise, production-ready code."}]
    },
    "contents": [{"parts": [{"text": "Write a function to validate email addresses"}]}]
  }'
```

System instructions are ideal for:
- Setting the model's role/persona
- Defining output format constraints
- Establishing tone and language rules
- Providing domain-specific context

---

## Multimodal Inputs

Gemini natively processes images, video, audio, and documents. It does not rely on secondary encoders — it preserves spatial and temporal relationships within the data.

### Image Input

**Python:**
```python
from PIL import Image

image = Image.open("/path/to/photo.png")
response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents=[image, "Describe what you see in this image"]
)
```

**Node.js:**
```javascript
import { createUserContent, createPartFromUri } from "@google/genai";

const image = await ai.files.upload({ file: "/path/to/photo.png" });
const response = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: [
    createUserContent([
      "Describe what you see in this image",
      createPartFromUri(image.uri, image.mimeType),
    ]),
  ],
});
```

**Node.js (inline base64 for small images):**
```javascript
const response = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: [
    "Identify the hardware components in this photo.",
    { inlineData: { data: base64Image, mimeType: "image/png" } },
  ],
});
```

### Audio Input

Gemini accepts audio directly — transcription, summarization, and non-speech sound understanding all happen in a single call, without a separate speech-to-text step. Supported formats: WAV, MP3, AIFF, AAC, OGG, FLAC. Same 20MB inline threshold as other media — use the File API above that.

**Python (small file, inline):**
```python
with open("voice_memo.mp3", "rb") as f:
    audio_bytes = f.read()

response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents=[
        "Transcribe this audio, then give a 2-sentence summary.",
        types.Part.from_bytes(data=audio_bytes, mime_type="audio/mp3"),
    ],
)
```

**Node.js (larger file via File API):**
```javascript
const audioFile = await ai.files.upload({ file: "/path/to/meeting.wav" });
const response = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: [
    createUserContent([
      "Transcribe this audio, then give a 2-sentence summary.",
      createPartFromUri(audioFile.uri, audioFile.mimeType),
    ]),
  ],
});
```

Reference specific moments with `MM:SS` timestamps in the prompt (e.g. "what's said at 1:30?"). Combine with `structured_outputs.md`'s schema enforcement to get a transcript back as clean JSON instead of free text.

> **Gemma 4 note**: the two API-hosted Gemma 4 models do not support audio input — use a Gemini model for any audio task. See [gemma_models.md](gemma_models.md).

### PDF / Document Input

```python
response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents=[
        "Summarize this PDF.",
        client.files.get(name='uploaded_doc')
    ],
    config={"media_resolution": "high"}
)
```

### Media Resolution Control

Use `media_resolution` to balance quality vs. token cost:

- **`"low"`**: Optimized for speed and lower token consumption.
- **`"medium"`**: Standard balance for most OCR and object detection.
- **`"high"`**: Essential for fine print, small artifacts in video.

---

## Image Generation

Some Gemini models generate images as output, not just understand them. This is a separate capability from the multimodal *input* handling above — it needs a dedicated image-generation model and `response_modalities` set to request image output.

**Models**: `gemini-3-pro-image` (highest quality, complex compositions), `gemini-3.1-flash-image` (default choice — fast, high-volume), `gemini-3.1-flash-lite-image` (fastest/cheapest, simpler images).

**Python:**
```python
from google.genai import types

response = client.models.generate_content(
    model="gemini-3.1-flash-image",
    contents="Generate an infographic-style image of a butterfly life cycle.",
    config=types.GenerateContentConfig(
        response_modalities=["TEXT", "IMAGE"],
    ),
)

for part in response.parts:
    if part.text:
        print(part.text)
    elif image := part.as_image():
        image.save("butterfly.png")
```

**Node.js:**
```javascript
const response = await ai.models.generateContent({
  model: "gemini-3.1-flash-image",
  contents: "Generate an infographic-style image of a butterfly life cycle.",
  config: { responseModalities: ["TEXT", "IMAGE"] },
});

for (const part of response.candidates[0].content.parts) {
  if (part.inlineData) {
    fs.writeFileSync("butterfly.png", Buffer.from(part.inlineData.data, "base64"));
  }
}
```

`response_modalities` (or `responseModalities` in Node.js) must include `"IMAGE"` to get image output at all — omit `"TEXT"` if you only want the image with no accompanying caption. Aspect ratio and resolution are configurable per-request via an `image_config`/`imageConfig` field; check current SDK types for the exact shape, since this part of the API has moved fastest since the Gemini 3 image models launched. Multi-turn image editing (refining a previously generated image) is supported by referencing the prior response in the next turn, same as any other multi-turn chat.

> **Gemma 4 note**: image generation is a Gemini-only capability — Gemma 4 models understand images as input but do not generate them. See [gemma_models.md](gemma_models.md).

---

## Streaming Responses

Streaming delivers response tokens incrementally, critical for responsive chat UIs.

### generateContent Streaming (Simple)

**Python:**
```python
response = client.models.generate_content_stream(
    model="gemini-3.5-flash",
    contents=["Explain how AI works"]
)
for chunk in response:
    print(chunk.text, end="")
```

**Node.js:**
```javascript
const response = await ai.models.generateContentStream({
  model: "gemini-3.5-flash",
  contents: "Explain how AI works",
});
for await (const chunk of response) {
  console.log(chunk.text);
}
```

**REST (Server-Sent Events):**
```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:streamGenerateContent?alt=sse" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H 'Content-Type: application/json' \
  --no-buffer \
  -d '{"contents": [{"parts": [{"text": "Explain how AI works"}]}]}'
```

### Interactions API Streaming (Advanced)

The Interactions API provides richer streaming with step-based events (thinking, function calls, tool results):

**Python:**
```python
stream = client.interactions.create(
    model="gemini-3-flash-preview",
    input="Count from 1 to 25.",
    stream=True,
)
for event in stream:
    if event.event_type == "step.delta":
        if event.delta.type == "text":
            print(event.delta.text, end="", flush=True)
```

**Node.js:**
```javascript
const stream = await client.interactions.create({
  model: "gemini-3-flash-preview",
  input: "Count from 1 to 25.",
  stream: true,
});
for await (const event of stream) {
  if (event.event_type === "step.delta" && event.delta.type === "text") {
    process.stdout.write(event.delta.text);
  }
}
```

### Streaming Event Flow

```
interaction.created → step.start → step.delta (×N) → step.stop → interaction.completed
```

Step types: `model_output` (text/image), `thought` (thinking), `function_call`, `google_search_call`, `code_execution_call`.

---

## Multi-Turn Chat

The SDKs provide a chat interface that automatically manages conversation history.

**Python:**
```python
chat = client.chats.create(model="gemini-3.5-flash")

response = chat.send_message("I have 2 dogs in my house.")
print(response.text)

response = chat.send_message("How many paws are in my house?")
print(response.text)

# Access conversation history
for message in chat.get_history():
    print(f'{message.role}: {message.parts[0].text}')
```

**Node.js:**
```javascript
const chat = ai.chats.create({
  model: "gemini-3.5-flash",
  history: [
    { role: "user", parts: [{ text: "Hello" }] },
    { role: "model", parts: [{ text: "Hi! What would you like to know?" }] },
  ],
});

const response1 = await chat.sendMessage({ message: "I have 2 dogs." });
console.log(response1.text);

const response2 = await chat.sendMessage({ message: "How many paws total?" });
console.log(response2.text);
```

**REST (manual history management):**
```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H 'Content-Type: application/json' \
  -X POST \
  -d '{
    "contents": [
      {"role": "user", "parts": [{"text": "Hello"}]},
      {"role": "model", "parts": [{"text": "Hi! What would you like to know?"}]},
      {"role": "user", "parts": [{"text": "I have 2 dogs. How many paws total?"}]}
    ]
  }'
```

### Streaming Chat

**Python:**
```python
chat = client.chats.create(model="gemini-3.5-flash")
response = chat.send_message_stream("Tell me a story about a robot.")
for chunk in response:
    print(chunk.text, end="")
```

**Node.js:**
```javascript
const stream = await chat.sendMessageStream({
  message: "Tell me a story about a robot.",
});
for await (const chunk of stream) {
  console.log(chunk.text);
}
```

---

## Media Tokenization Rates

Understanding token costs for different modalities helps with cost estimation:

| Modality | Tokenization Rate | Notes |
|---|---|---|
| Text | ~4 chars / token | 100 tokens ≈ 60-80 English words |
| Image | 258 tokens | Base rate for ≤384px; larger images are tiled |
| Video | 263 tokens / sec | Processes up to 32 frames per video |
| Audio | 32 tokens / sec | Supported up to 180 seconds |
| PDF | 258 tokens / page + text | Combined visual rendering and OCR text tokens |
