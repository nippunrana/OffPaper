# Semantic Search Reference

*CPU-friendly, no paid API. Models download from HuggingFace once, then run fully offline.*

---

## Model Selection

| Model | Dims | Size | Best for |
|---|---|---|---|
| `all-MiniLM-L6-v2` | 384 | ~22 MB | **Default CPU choice.** Symmetric QA, fast general search. |
| `multi-qa-mpnet-base-dot-v1` | 768 | ~420 MB | Asymmetric QA — dedicated encode_document/encode_query prompts. Better quality, slower. |
| `all-mpnet-base-v2` | 768 | ~420 MB | Highest quality symmetric search; slowest of the three. |
| `minishlab/potion-base-8M` (model2vec) | 256 | ~30 MB | **Pure-CPU, attention-free, ~5–20× faster than MiniLM.** Use when latency dominates. |

**Rule of thumb:** start with `all-MiniLM-L6-v2`. Switch to model2vec (static embeddings)
when you need encode() to complete in <1 ms per sentence on CPU. Switch to a 768-dim model
when quality wins over speed.

---

## Symmetric vs Asymmetric Search

**Symmetric:** query and corpus sentences are the same type and length.
*Example: "What is Python?" searching for similar questions.*

**Asymmetric:** query is a short question; corpus items are long documents.
*Example: "How does GIL work?" searching paragraphs.*

For asymmetric search, use a model that has prompt/instruction support (see
`encode_query` / `encode_document` below) *or* prepend the task prefix manually.

---

## Core API — Encoding

### Baseline: `.encode()` — works on all versions

```python
from sentence_transformers import SentenceTransformer, util

model = SentenceTransformer("all-MiniLM-L6-v2")

# Always pass normalize_embeddings=True for cosine-based retrieval:
# normalized vectors make dot-product == cosine similarity, saving a step.
corpus_embs = model.encode(
    corpus,                      # list[str]
    batch_size=32,
    convert_to_tensor=True,      # keeps on device (CPU or GPU)
    normalize_embeddings=True,   # unit-length vectors
    show_progress_bar=False,
)
query_emb = model.encode(
    query,                       # str or list[str]
    convert_to_tensor=True,
    normalize_embeddings=True,
)
```

### Optional: `encode_query` / `encode_document` (sentence-transformers v5+)

Some models (e.g., `multi-qa-mpnet-base-dot-v1`) carry asymmetric encoding prompts. If
the installed version supports it and the model defines query/document prompts, use:

```python
query_emb  = model.encode_query(query, convert_to_tensor=True, normalize_embeddings=True)
corpus_embs = model.encode_document(corpus, convert_to_tensor=True, normalize_embeddings=True)
```

**Fall back to `.encode()` if the model or version does not support these methods.** They
are syntactic sugar over `.encode()` with a task prefix; they do not change the model
architecture. Do NOT use `model.rank()` on a `SentenceTransformer` — that method belongs
to `CrossEncoder`.

---

## Retrieval

### Top-k semantic search

```python
hits = util.semantic_search(
    query_emb,
    corpus_embs,
    top_k=5,
    score_function=util.dot_score,  # dot_score on normalized vecs == cosine
)
# hits[0] is a list of dicts: [{"corpus_id": int, "score": float}, …]
# already sorted descending by score
for hit in hits[0]:
    print(corpus[hit["corpus_id"]], f"  score={hit['score']:.4f}")
```

### Manual cosine similarity (single query vs single doc)

```python
score = util.cos_sim(query_emb, doc_emb)  # returns a tensor; .item() to get float
```

### Batch multi-query

```python
query_embs = model.encode(queries, convert_to_tensor=True, normalize_embeddings=True)
all_hits = util.semantic_search(query_embs, corpus_embs, top_k=5)
# all_hits[i] → hits for queries[i]
```

---

## Static Embeddings (model2vec) — Pure-CPU Speed

When sub-millisecond encode latency on CPU is required (e.g., real-time suggestions, no
GPU, tight SLAs), use static embedding models. They skip the transformer attention stack:

```python
# pip install model2vec sentence-transformers
from sentence_transformers import SentenceTransformer

model = SentenceTransformer("minishlab/potion-base-8M")  # static model, 256-dim, ~30 MB
emb = model.encode("Hello world", normalize_embeddings=True)
```

Same `util.semantic_search` API works. Quality is lower than transformer models; use it
only when CPU speed is the binding constraint.

---

## Cosine vs Dot Product

On **unit-length (normalized) vectors**: `cos_sim(a, b) = dot(a, b)`. They are identical.
Always normalize at encode time (`normalize_embeddings=True`) so you can use either.

On **un-normalized vectors**: dot product ≠ cosine similarity. The two will rank
differently. Always normalize before comparing — never skip this step.

---

## Persistence — Cache Embeddings to Disk

Re-encoding a large corpus on every run is wasteful. Persist embeddings:

```python
import numpy as np

# Save
np.save("corpus_embs.npy", corpus_embs.cpu().numpy())

# Load (standard — full matrix into RAM)
import torch
corpus_embs = torch.from_numpy(np.load("corpus_embs.npy"))

# Load as memory-mapped array (large corpora that exceed RAM)
# The OS pages in only the slices that are actually read — no full copy into RAM.
corpus_np = np.load("corpus_embs.npy", mmap_mode="r")  # (M, D) float32
```

Use `mmap_mode="r"` when the corpus embedding matrix is too large to fit in RAM.
For scoring a memory-mapped matrix against queries, see `references/performance-at-scale.md`
(chunked matmul top-k works transparently with mmap arrays).

Invalidate the cache if the corpus changes or the model is swapped.

---

## Full Working Example (Asymmetric Search)

```python
from sentence_transformers import SentenceTransformer, util

corpus = [
    "Python's GIL prevents true multi-threading for CPU-bound tasks.",
    "NumPy uses BLAS routines for fast array operations.",
    "The walrus operator := assigns and returns a value in one expression.",
]
queries = [
    "Why is Python slow for CPU tasks?",
    "How does Python handle matrix multiplication efficiently?",
]

model = SentenceTransformer("all-MiniLM-L6-v2")
corpus_embs = model.encode(corpus, convert_to_tensor=True, normalize_embeddings=True)
query_embs  = model.encode(queries, convert_to_tensor=True, normalize_embeddings=True)

results = util.semantic_search(query_embs, corpus_embs, top_k=2)
for i, query in enumerate(queries):
    print(f"\nQuery: {query}")
    for hit in results[i]:
        print(f"  [{hit['score']:.3f}] {corpus[hit['corpus_id']]}")
```

---

## What Belongs in `references/scaling.md` (Not Here)

The following are deliberately excluded from this core reference:
- FAISS / HNSW / Annoy for approximate nearest-neighbor search
- Cross-encoder (reranker) pipeline: retrieve with bi-encoder, rerank with CrossEncoder
- int8 / binary quantization of embeddings
- Matryoshka embeddings (variable-length truncation)
- Fine-tuning `SentenceTransformer` on domain data

Load `references/scaling.md` when any of these are explicitly requested.
