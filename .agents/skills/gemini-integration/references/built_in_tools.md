# Built-in Tool Integration

## Table of Contents
1. [Overview](#overview)
2. [Google Search Grounding](#google-search-grounding)
3. [Google Maps Grounding](#google-maps-grounding)
4. [Python Code Execution](#python-code-execution)
5. [URL Context Tool](#url-context-tool)
6. [Combining Multiple Tools](#combining-multiple-tools)

---

## Overview

Built-in tools run in managed environments — isolated Linux sandboxes with 4 vCPUs and 16GB RAM. Unlike function calling (where your code executes the logic), built-in tools are executed by Google's infrastructure. The model decides when to use them.

---

## Google Search Grounding

Reduces hallucinations by retrieving real-time web data. Use when the user asks about current events, facts that change over time, or topics where accuracy matters.

**Python:**
```python
response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="What are the latest developments in quantum computing?",
    config={"tools": [{"google_search": {}}]}
)

# Access grounding metadata
if response.candidates[0].grounding_metadata:
    for source in response.candidates[0].grounding_metadata.grounding_chunks:
        print(f"Source: {source.web.uri} - {source.web.title}")
```

**Node.js:**
```javascript
const result = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: "What are the latest developments in quantum computing?",
  config: { tools: [{ googleSearch: {} }] },
});
```

**REST:**
```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H 'Content-Type: application/json' \
  -X POST \
  -d '{
    "contents": [{"parts": [{"text": "Latest quantum computing news?"}]}],
    "tools": [{"google_search": {}}]
  }'
```

The response includes `groundingMetadata` with source URIs and inline citations.

---

## Google Maps Grounding

Enables location-aware workflows. Provide `user_location` (latitude/longitude) in the tool config for locally relevant results.

**Python:**
```python
response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="Find Italian restaurants near me",
    config={
        "tools": [{"google_maps": {}}],
        "tool_config": {
            "google_maps_config": {
                "user_location": {"latitude": 37.7749, "longitude": -122.4194}
            }
        }
    }
)
```

---

## Python Code Execution

Allows Gemini to write and run Python code. The model can perform calculations, data analysis, and generate visualizations (e.g., Matplotlib graphs). Output is returned as `inlineData`.

**Python:**
```python
response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="Calculate the standard deviation of [14, 22, 17, 31, 25, 19, 28]",
    config={"tools": [{"code_execution": {}}]}
)
```

**Node.js:**
```javascript
const result = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: "Calculate the standard deviation of [14, 22, 17, 31, 25, 19, 28]",
  config: { tools: [{ codeExecution: {} }] },
});
```

> **Cost note**: Intermediate tokens (code generated and executed) are billed as input tokens.

---

## URL Context Tool

Retrieves content from URLs to ground the model's response. Uses a two-step process: checks an internal index cache first, then falls back to a live fetch.

**Python:**
```python
response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="Summarize the key findings from this report",
    config={
        "tools": [{
            "url_context": {
                "urls": ["https://example.com/report.pdf"]
            }
        }]
    }
)
```

**REST:**
```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H 'Content-Type: application/json' \
  -X POST \
  -d '{
    "contents": [{"parts": [{"text": "Summarize this report"}]}],
    "tools": [{"url_context": {"urls": ["https://example.com/report.pdf"]}}]
  }'
```

Responses include `url_citation` annotations and `url_context_metadata`.

---

## Combining Multiple Tools

You can provide multiple tools in a single request. Gemini decides which ones to use based on the query.

**Python:**
```python
tools = [
    {"google_search": {}},
    {"code_execution": {}},
    {"url_context": {"urls": ["https://example.com/data.csv"]}},
]

response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="Analyze the data from this CSV and compare it with current market trends",
    config={"tools": tools}
)
```

**Node.js:**
```javascript
const tools = [
  { googleSearch: {} },
  { codeExecution: {} },
];

const result = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: "Analyze this data and compare with current market trends",
  config: { tools },
});
```

**REST:**
```json
{
  "tools": [
    {"google_search": {}},
    {"code_execution": {}},
    {"url_context": {"urls": ["https://example.com/data.csv"]}}
  ]
}
```

You can also combine built-in tools with function calling. The model will decide which tools to invoke based on the task at hand.
