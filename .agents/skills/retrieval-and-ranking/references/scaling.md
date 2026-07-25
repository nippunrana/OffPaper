# Scaling Reference

*Load this file only when the user explicitly asks for one of the topics below.
The core skill covers CPU-scale retrieval; this file handles beyond-CPU concerns.*

> **Boundary:** This file covers *which index or infrastructure* to adopt when exact
> search is too slow (FAISS, HNSW, vector DBs, quantization). For *how to write
> memory-safe vectorized Python* — chunked matmul, out-of-core mmap embeddings, Polars
> streaming I/O — see `references/performance-at-scale.md` instead.

---

## FAISS — Approximate Nearest-Neighbor Search

Use FAISS when the corpus has 100k+ documents and exact `util.semantic_search` becomes slow.

```python
import faiss
import numpy as np
from sentence_transformers import SentenceTransformer

model = SentenceTransformer("all-MiniLM-L6-v2")
corpus_embs = model.encode(corpus, normalize_embeddings=True).astype(np.float32)
dim = corpus_embs.shape[1]  # 384

# Flat (exact) index — baseline, no approximation
index = faiss.IndexFlatIP(dim)  # IP = inner product; same as cosine on normalized vecs
index.add(corpus_embs)

query_emb = model.encode([query], normalize_embeddings=True).astype(np.float32)
scores, ids = index.search(query_emb, k=5)
```

### HNSW — Approximate, Faster Search

```python
index = faiss.IndexHNSWFlat(dim, 32)  # M=32 graph connections
index.hnsw.efConstruction = 200       # build-time quality (higher = better, slower)
index.hnsw.efSearch = 50             # search-time quality
index.add(corpus_embs)
# search: same as above
```

HNSW trades a small recall loss for large speed gains. Tune `efSearch` to balance speed
vs. recall on your data. Persist with `faiss.write_index` / `faiss.read_index`.

---

## Cross-Encoder Reranking (Two-Stage Retrieval)

Stage 1: fast bi-encoder retrieves top-50–100 candidates.
Stage 2: cross-encoder rescores the candidates (slower, higher quality).

```python
from sentence_transformers import CrossEncoder

# Install: pip install sentence-transformers
cross_encoder = CrossEncoder("cross-encoder/ms-marco-MiniLM-L-6-v2")

# candidates: list of (query, doc) pairs from stage-1 retrieval
candidates = [(query, corpus[i]) for i in top_ids]
scores = cross_encoder.predict(candidates)  # list of floats

# Re-sort by cross-encoder score
reranked = sorted(zip(top_ids, scores), key=lambda x: x[1], reverse=True)
```

**NOTE:** `cross_encoder.rank(query, documents)` is the `CrossEncoder` API for ranking.
Do NOT call `.rank()` on a `SentenceTransformer` — that is a `CrossEncoder` method.

---

## Quantization — Smaller, Faster Embeddings

Reduces memory and speeds up ANN search at the cost of a small accuracy drop.

```python
from sentence_transformers.quantization import quantize_embeddings

# int8: 4× smaller than float32; minimal quality loss
int8_embs = quantize_embeddings(corpus_embs, precision="int8")

# binary: 32× smaller; larger quality loss; fastest
binary_embs = quantize_embeddings(corpus_embs, precision="binary")
```

Requires `sentence-transformers >= 3.0`. Benchmark your dataset before shipping —
quality loss is workload-dependent.

---

## Matryoshka Embeddings

Some models (e.g., `nomic-ai/nomic-embed-text-v1`) are trained with Matryoshka
Representation Learning (MRL): the first N dimensions are a valid embedding at lower
resolution.

```python
model = SentenceTransformer("nomic-ai/nomic-embed-text-v1", trust_remote_code=True)
embs = model.encode(corpus, normalize_embeddings=True)

# Truncate to 128 dims (instead of full 768) — trade quality for speed/memory
embs_128 = embs[:, :128]
embs_128 /= np.linalg.norm(embs_128, axis=1, keepdims=True)  # renormalize after truncation
```

Only MRL-trained models support this. On standard models, truncation is not meaningful.

---

## Production Vector Databases

| Library | Best for |
|---|---|
| [Qdrant](https://qdrant.tech) | Open-source; Python SDK; runs in-process or server mode |
| [Chroma](https://www.trychroma.com) | Embedded; easiest local-first setup; good for prototypes |
| [Weaviate](https://weaviate.io) | Hybrid BM25+vector built-in; strong filtering |
| [Pinecone](https://pinecone.io) | Managed cloud; no infra to run; requires API key |

For local-first projects, prefer Qdrant or Chroma. For production with scale requirements,
benchmark on your data before choosing.

---

## Production-Scale BM25 — `retriv`

For >1M documents, `rank_bm25` (pure Python) becomes slow. Use `retriv`:

```python
# pip install retriv
from retriv import SparseRetriever

retriever = SparseRetriever().index(
    [{"id": str(i), "text": doc} for i, doc in enumerate(corpus)]
)
results = retriever.search("quick fox", cutoff=5)
```

`retriv` uses sparse matrix operations and a FAISS backend for sub-second search on
millions of documents.

---

## Fine-Tuning SentenceTransformer

Fine-tune when the domain vocabulary is specialized and off-the-shelf models underperform.

Minimum viable training loop:
```python
from sentence_transformers import SentenceTransformer, InputExample, losses
from torch.utils.data import DataLoader

model = SentenceTransformer("all-MiniLM-L6-v2")

# training pairs: (query, positive_doc) — or triplets with a negative
examples = [InputExample(texts=["query", "relevant doc"]), ...]
loader = DataLoader(examples, shuffle=True, batch_size=16)
loss = losses.MultipleNegativesRankingLoss(model)

model.fit(train_objectives=[(loader, loss)], epochs=3, warmup_steps=100)
model.save("finetuned-model")
```

Use `MultipleNegativesRankingLoss` for in-batch negatives (fast, effective).
Use `TripletLoss` when you have explicit hard negatives.
The HuggingFace `datasets` library is useful for loading standard IR benchmarks (MS MARCO,
BEIR) for evaluation during training.
