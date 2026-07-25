# Function Calling: Connecting Gemini to Your APIs

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Python Implementation](#python-implementation)
3. [Node.js Implementation](#nodejs-implementation)
4. [REST Implementation](#rest-implementation)
5. [Thought Signatures (Critical)](#thought-signatures)
6. [Parallel Function Calls](#parallel-function-calls)
7. [Auto vs Manual Mode](#auto-vs-manual-mode)

---

## Architecture Overview

Function calling transforms Gemini into a controller for your external systems. The model decides when to call a function and with what arguments, but your code executes the actual logic.

### The 4-Step Flow

```
1. DEFINE  → You declare function signatures via JSON Schema
2. CALL    → Gemini returns a functionCall with a unique ID
3. EXECUTE → Your application runs the actual logic
4. RESPOND → You return a functionResponse with the matching ID
```

The model never executes code directly — it only generates the intent. Your application handles execution and returns results.

---

## Python Implementation

Python supports a concise approach using native functions:

```python
def get_weather(location: str, unit: str = "celsius") -> dict:
    """Get current weather for a location.
    
    Args:
        location: City name (e.g., "San Francisco, CA")
        unit: Temperature unit, either "celsius" or "fahrenheit"
    """
    # Your actual API call here
    return {"temperature": 22, "condition": "sunny", "unit": unit}

response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="What's the weather in San Francisco?",
    config={"tools": [get_weather]}
)
```

### Full Manual Loop

For production, you typically handle the function call loop explicitly:

```python
from google.genai import types

# Step 1: Define the function declaration
weather_tool = types.Tool(
    function_declarations=[
        types.FunctionDeclaration(
            name="get_weather",
            description="Get current weather for a location",
            parameters=types.Schema(
                type="object",
                properties={
                    "location": types.Schema(type="string", description="City name"),
                    "unit": types.Schema(type="string", enum=["celsius", "fahrenheit"]),
                },
                required=["location"],
            ),
        )
    ]
)

# Step 2: Send the request
response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="What's the weather in Tokyo?",
    config=types.GenerateContentConfig(tools=[weather_tool]),
)

# Step 3: Check if the model wants to call a function
for part in response.candidates[0].content.parts:
    if part.function_call:
        call = part.function_call
        # Execute your function with the provided arguments
        result = get_weather(**call.args)
        
        # Step 4: Return the result to the model
        follow_up = client.models.generate_content(
            model="gemini-3.5-flash",
            contents=[
                types.Content(role="user", parts=[types.Part(text="What's the weather in Tokyo?")]),
                response.candidates[0].content,  # includes thought_signature
                types.Content(
                    role="user",
                    parts=[types.Part(function_response=types.FunctionResponse(
                        name=call.name,
                        id=call.id,
                        response=result,
                    ))],
                ),
            ],
            config=types.GenerateContentConfig(tools=[weather_tool]),
        )
        print(follow_up.text)
```

---

## Node.js Implementation

```javascript
const tools = [{
  functionDeclarations: [{
    name: "get_weather",
    description: "Get current weather for a location",
    parameters: {
      type: "object",
      properties: {
        location: { type: "string", description: "City name" },
        unit: { type: "string", enum: ["celsius", "fahrenheit"] },
      },
      required: ["location"],
    },
  }],
}];

const result = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: [{ role: "user", parts: [{ text: "Weather in London?" }] }],
  config: { tools },
});

// Check for function calls in the response
const functionCall = result.candidates[0].content.parts.find(p => p.functionCall);
if (functionCall) {
  const { name, args, id } = functionCall.functionCall;
  // Execute your function, then send the result back
  const weatherData = await getWeather(args.location, args.unit);
  
  const followUp = await ai.models.generateContent({
    model: "gemini-3.5-flash",
    contents: [
      { role: "user", parts: [{ text: "Weather in London?" }] },
      result.candidates[0].content,
      { role: "user", parts: [{ functionResponse: { name, id, response: weatherData } }] },
    ],
    config: { tools },
  });
  console.log(followUp.text);
}
```

---

## REST Implementation

```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H 'Content-Type: application/json' \
  -X POST \
  -d '{
    "contents": [{"role": "user", "parts": [{"text": "Weather in Paris?"}]}],
    "tools": [{
      "function_declarations": [{
        "name": "get_weather",
        "description": "Get current weather for a location",
        "parameters": {
          "type": "object",
          "properties": {
            "location": {"type": "string"},
            "unit": {"type": "string", "enum": ["celsius", "fahrenheit"]}
          },
          "required": ["location"]
        }
      }]
    }]
  }'
```

---

## Thought Signatures

> **Critical Requirement**: Gemini 3+ models use encrypted `thought_signature` to maintain reasoning state across the function call loop.

**Rules:**
1. Return the `thought_signature` exactly as received — do not modify, truncate, or omit it.
2. For parallel function calls, the signature is attached only to the **first** function call part.
3. Omitting the thought signature triggers a **400 validation error**.
4. For context transfers between models, use the dummy signature: `"skip_thought_signature_validator"`.

The SDKs handle thought signatures automatically when you pass the model's response content back. When using REST, preserve the full response content (including the thought signature) in your follow-up request.

---

## Parallel Function Calls

Gemini can request multiple function calls in a single response. Handle them all before responding:

```python
# Model may return multiple function calls
for part in response.candidates[0].content.parts:
    if part.function_call:
        # Execute each function
        result = execute_function(part.function_call.name, part.function_call.args)
        # Collect all results
        function_responses.append(
            types.Part(function_response=types.FunctionResponse(
                name=part.function_call.name,
                id=part.function_call.id,
                response=result,
            ))
        )

# Send all results back in one request
follow_up = client.models.generate_content(
    model="gemini-3.5-flash",
    contents=[
        *original_contents,
        response.candidates[0].content,
        types.Content(role="user", parts=function_responses),
    ],
    config=types.GenerateContentConfig(tools=[...]),
)
```

---

## Auto vs Manual Mode

Control how Gemini uses functions:

| Mode | Behavior |
|---|---|
| `AUTO` (default) | Model decides when to call functions |
| `ANY` | Model must call at least one function |
| `NONE` | Model cannot call functions (text-only response) |

**Python:**
```python
config = types.GenerateContentConfig(
    tools=[weather_tool],
    tool_config=types.ToolConfig(
        function_calling_config=types.FunctionCallingConfig(mode="ANY")
    ),
)
```

**Node.js:**
```javascript
config: {
  tools,
  toolConfig: {
    functionCallingConfig: { mode: "ANY" },
  },
}
```

Use `ANY` when you want to force the model to use a tool. Use `NONE` when you want the model to respond based only on context without making tool calls.
