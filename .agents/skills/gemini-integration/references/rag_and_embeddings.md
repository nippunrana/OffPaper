# RAG & Embeddings

## Table of Contents
1. [File Search (Managed RAG)](#file-search-managed-rag)
2. [Embeddings API](#embeddings-api)
3. [Task Types for Embeddings](#task-types-for-embeddings)
4. [Embedding Aggregation Strategies](#embedding-aggregation-strategies)
5. [Choosing a RAG Strategy](#choosing-a-rag-strategy)

---

## File Search (Managed RAG)

File Search is Google's managed RAG solution. You upload documents, and the system handles chunking, embedding, storage, and retrieval automatically.

Key facts:
- File API uploads expire in **48 hours**, but FileSearchStore embeddings **persist indefinitely**.
- Uses `gemini-embedding-2` for multimodal embedding (text + images in documents).

### Creating a Store and Uploading Documents

**Python:**
```python
# Create a FileSearchStore
store = client.file_search.create_store(
    display_name="Engineering Docs",
    embedding_model="models/gemini-embedding-2"  # Required for multimodal
)

# Upload documents to the store
client.file_search.upload(store_name=store.name, file_path="manual.pdf")
client.file_search.upload(store_name=store.name, file_path="spec.pdf")
```

**Node.js:**
```javascript
const store = await client.fileSearch.createStore({
  displayName: "Engineering Docs",
  embeddingModel: "models/gemini-embedding-2",
});

await client.fileSearch.upload({
  storeName: store.name,
  filePath: "manual.pdf",
});
```

**REST:**
```bash
curl -X POST "https://generativelanguage.googleapis.com/v1beta/fileSearchStores" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"display_name": "Engineering Docs", "embedding_model": "models/gemini-embedding-2"}'
```

### Querying a Store

Once documents are uploaded, use the File Search tool in your generation requests:

```python
response = client.models.generate_content(
    model="gemini-3.5-flash",
    contents="What are the safety requirements in section 4.2?",
    config={
        "tools": [{
            "file_search": {
                "store_names": [store.name]
            }
        }]
    }
)
```

---

## Embeddings API

Use embeddings for semantic search, classification, clustering, and building custom RAG pipelines.

### Generate Embeddings

**Python:**
```python
result = client.models.embed_content(
    model="gemini-embedding-2",
    contents="What is the meaning of life?"
)
print(result.embeddings)
```

**Node.js:**
```javascript
const response = await ai.models.embedContent({
  model: "gemini-embedding-2",
  contents: "What is the meaning of life?",
});
console.log(response.embeddings);
```

**REST:**
```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-2:embedContent" \
  -H "Content-Type: application/json" \
  -H "x-goog-api-key: $GEMINI_API_KEY" \
  -d '{
    "model": "models/gemini-embedding-2",
    "content": {"parts": [{"text": "What is the meaning of life?"}]}
  }'
```

### Embedding Models

| Model | Modalities | Output Dim | Best For |
|---|---|---|---|
| `gemini-embedding-2` | Text, image, video, audio, docs | 768 | Multimodal search, cross-modal retrieval |
| `gemini-embedding-001` | Text only | 768 | Text-only search, classification |

### Batch Embeddings

Embed multiple texts in one call:

```python
result = client.models.embed_content(
    model="gemini-embedding-2",
    contents=[
        "What is machine learning?",
        "How do neural networks work?",
        "What is deep learning?",
    ]
)
# result.embeddings contains one embedding per input
```

---

## Task Types for Embeddings

Specifying the right task type optimizes embeddings for the intended use case.

### With gemini-embedding-2 (Prompt-Based)

Format your input with a task prefix:

**Asymmetric retrieval (query ≠ document):**
```python
# For the query
query = "task: search result | query: How does photosynthesis work?"

# For the document being searched
document = "title: Biology 101 | text: Photosynthesis is the process by which plants convert sunlight..."
```

**Symmetric tasks (query = document format):**
```python
# Classification
text = "task: classification | query: The stock market rose sharply today."

# Clustering
text = "task: clustering | query: Recent advances in renewable energy."

# Semantic similarity
text = "task: sentence similarity | query: The cat sat on the mat."
```

### With gemini-embedding-001 (Config-Based)

Use the `task_type` parameter:

```python
from google.genai import types

result = client.models.embed_content(
    model="gemini-embedding-001",
    contents=["What is AI?", "How does ML work?"],
    config=types.EmbedContentConfig(task_type="SEMANTIC_SIMILARITY")
)
```

Supported task types: `RETRIEVAL_QUERY`, `RETRIEVAL_DOCUMENT`, `SEMANTIC_SIMILARITY`, `CLASSIFICATION`, `CLUSTERING`, `QUESTION_ANSWERING`, `FACT_VERIFICATION`.

---

## Embedding Aggregation Strategies

When providing multiple inputs:

- **Aggregated Embedding**: Multiple inputs in the `contents` parameter create a single post-level representation. Use for comparing composite content.
- **Separate Embeddings**: Wrapping inputs in individual `Content` objects generates separate retrieval points. Use when each piece should be independently searchable.

---

## Choosing a RAG Strategy

| Approach | Setup Complexity | Best For | Limitations |
|---|---|---|---|
| **Long Context** (1M tokens) | None — just include text | Single document analysis, one-off queries | No persistence, cost scales with context size |
| **File Search** (managed) | Low — upload and query | Multi-document knowledge bases, production apps | Less control over retrieval logic |
| **Custom Vector DB** | High — build your own pipeline | Full control, hybrid search, metadata filtering | Requires infra (Pinecone, Weaviate, pgvector, etc.) |

**Recommendation**: Start with Long Context for prototyping. Move to File Search for production knowledge bases. Use a custom vector DB only when you need advanced retrieval features (hybrid search, metadata filtering, re-ranking).
