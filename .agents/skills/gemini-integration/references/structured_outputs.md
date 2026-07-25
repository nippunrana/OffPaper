# Structured Outputs & JSON Schema Enforcement

## Table of Contents
1. [Overview](#overview)
2. [Python (Pydantic)](#python-pydantic)
3. [Node.js (Zod)](#nodejs-zod)
4. [REST (Raw JSON Schema)](#rest-raw-json-schema)
5. [Structured Outputs vs Function Calling](#structured-outputs-vs-function-calling)
6. [Enums and Constrained Values](#enums-and-constrained-values)

---

## Overview

Structured outputs ensure Gemini returns responses that are immediately consumable by downstream systems — no brittle regex parsing required. You enforce structure via JSON Schema, and the model guarantees its response conforms to your schema.

Set `response_mime_type` to `"application/json"` and provide a `response_schema`.

---

## Python (Pydantic)

Use Pydantic models to define your schema. The SDK automatically converts them to JSON Schema.

```python
from pydantic import BaseModel
from google.genai import types

class ProductInfo(BaseModel):
    product_name: str
    price: float
    tags: list[str]

response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="Extract product details from: Ultra-Widget, $49.99, categories: tools, diy.",
    config=types.GenerateContentConfig(
        response_mime_type="application/json",
        response_schema=ProductInfo,
    ),
)

# response.text is valid JSON conforming to the ProductInfo schema
import json
product = json.loads(response.text)
```

### Nested Models

```python
class Address(BaseModel):
    street: str
    city: str
    country: str

class Customer(BaseModel):
    name: str
    email: str
    address: Address
    loyalty_tier: str

response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="Extract customer info from: John Doe, john@email.com, 123 Main St, NYC, USA, Gold member",
    config=types.GenerateContentConfig(
        response_mime_type="application/json",
        response_schema=Customer,
    ),
)
```

---

## Node.js (Zod)

Use Zod schemas for type-safe structured outputs:

```javascript
import { z } from "zod";

const ProductSchema = z.object({
  product_name: z.string(),
  price: z.number(),
  tags: z.array(z.string()),
});

const result = await ai.models.generateContent({
  model: "gemini-3.5-flash",
  contents: "Extract product details from: Ultra-Widget, $49.99, categories: tools, diy.",
  config: {
    responseMimeType: "application/json",
    responseSchema: ProductSchema,
  },
});

const product = JSON.parse(result.text);
```

---

## REST (Raw JSON Schema)

Provide the schema directly in the request body:

```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H 'Content-Type: application/json' \
  -X POST \
  -d '{
    "contents": [{"parts": [{"text": "Extract product details from: Ultra-Widget, $49.99, categories: tools, diy."}]}],
    "generationConfig": {
      "responseMimeType": "application/json",
      "responseSchema": {
        "type": "object",
        "properties": {
          "product_name": {"type": "string"},
          "price": {"type": "number"},
          "tags": {"type": "array", "items": {"type": "string"}}
        },
        "required": ["product_name", "price", "tags"]
      }
    }
  }'
```

> **Note for Gemini 2.0 models**: Include an explicit `propertyOrdering` list within the JSON schema to ensure the preferred structure is maintained. This is not required for Gemini 3.x models.

---

## Structured Outputs vs Function Calling

These serve different purposes:

| Feature | Structured Outputs | Function Calling |
|---|---|---|
| **Purpose** | Format the **final response** | Trigger **intermediate actions** |
| **Use When** | Extracting data for a database or API | Querying external APIs to inform the answer |
| **Example** | "Parse this invoice into JSON" | "Check inventory for SKU-123" |
| **Control Flow** | Model → structured data → done | Model → function call → your code → model → response |

Choose structured outputs when you need a specific data shape from the model. Choose function calling when the model needs to interact with external systems during its reasoning.

---

## Enums and Constrained Values

Use enums to constrain the model to specific allowed values:

**Python:**
```python
from enum import Enum

class Sentiment(str, Enum):
    POSITIVE = "positive"
    NEGATIVE = "negative"
    NEUTRAL = "neutral"

class ReviewAnalysis(BaseModel):
    sentiment: Sentiment
    confidence: float
    summary: str

response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="Analyze: 'This product is amazing, best purchase ever!'",
    config=types.GenerateContentConfig(
        response_mime_type="application/json",
        response_schema=ReviewAnalysis,
    ),
)
```

**REST (JSON Schema enum):**
```json
{
  "type": "object",
  "properties": {
    "sentiment": {
      "type": "string",
      "enum": ["positive", "negative", "neutral"]
    },
    "confidence": {"type": "number"},
    "summary": {"type": "string"}
  },
  "required": ["sentiment", "confidence", "summary"]
}
```
