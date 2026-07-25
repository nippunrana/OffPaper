# Performance at Scale Reference

*Load this file when the user asks about large corpora, memory usage, performance on CPU,
or preprocessing millions of documents. This covers **how to write memory-safe vectorized
Python**. For **which index or infrastructure** to choose when exact search is too slow,
see `references/scaling.md` instead.*

---

## When to Apply Each Strategy

| Corpus size | Recommended approach |
|---|---|
| < 100k docs | Default `util.semantic_search` — no special handling needed |
| 100k – ~2M docs | Chunked-matmul top-k (this file) *or* `corpus_chunk_size` param |
| > ~2M docs | ANN index (FAISS/HNSW) — see `references/scaling.md` |
| Corpus embeddings exceed RAM | `np.load(path, mmap_mode="r")` — OS pages in what's needed |
| Corpus rows exceed RAM at load time | Polars `scan_parquet` / streaming (this file) |

The threshold from exact search to ANN is workload-dependent — benchmark before switching.
The figures above are rough starting points for float32 384-dim embeddings on a typical
server CPU.

---

## The Score-Matrix Memory Trap (and the Fix)

### Why the naive approach OOMs

Scoring N queries against M documents produces an (N, M) float32 matrix.
At N=1,000 queries and M=2,000,000 docs: 1000 × 2M × 4 bytes = **8 GB**.
That exhausts RAM before a single result is returned.

### The anti-pattern: element-wise (N, M, D) broadcast

A common mistake (described in VQ/k-means literature) is computing distances via:

```python
# DO NOT DO THIS for cosine/dot-product retrieval — creates a 3D (N, M, D) array
diff = queries[:, np.newaxis, :] - corpus[np.newaxis, :, :]  # (N, M, D) — can exhaust RAM
```

For **cosine/dot-product similarity you never need this**. The score matrix is simply:

```python
scores = queries @ corpus.T  # (N, M) — single BLAS matmul, SIMD-accelerated
```

This is one matrix multiply. NumPy routes it through BLAS (OpenBLAS or Accelerate on macOS),
which uses SIMD and multi-core automatically. No Python loop, no intermediate 3D array.

### For L2/Euclidean distance: reformulate to matmul

If you need L2 distances, do NOT broadcast element-wise. Use the squared-norm identity:

```
‖a − b‖² = ‖a‖² + ‖b‖² − 2⟨a, b⟩
```

```python
# (N, M) L2-squared distances — all matmuls, no 3D array
q_sq = np.sum(queries ** 2, axis=1, keepdims=True)    # (N, 1)
c_sq = np.sum(corpus  ** 2, axis=1, keepdims=True).T  # (1, M)
l2sq = q_sq + c_sq - 2.0 * (queries @ corpus.T)       # (N, M)
```

### Fix: chunked corpus matmul with running top-k

When the full (N, M) score matrix is itself too large, process the corpus in chunks and
maintain a running top-k across chunks. Memory is bounded by O(N × chunk_size × 4 bytes)
instead of O(N × M × 4 bytes):

```python
import numpy as np

def chunked_top_k(
    query_embs: np.ndarray,   # (N, D) float32, normalized
    corpus_embs: np.ndarray,  # (M, D) float32, normalized
    k: int = 10,
    chunk_size: int = 50_000,
) -> tuple[np.ndarray, np.ndarray]:
    """Return top-k corpus indices and scores for each query.

    Memory: O(N × chunk_size) score floats at a time, not O(N × M).
    """
    n_queries, n_docs = len(query_embs), len(corpus_embs)
    best_ids = np.full((n_queries, k), -1, dtype=np.int64)
    best_scores = np.full((n_queries, k), -np.inf, dtype=np.float32)

    for start in range(0, n_docs, chunk_size):
        chunk = corpus_embs[start : start + chunk_size]           # (C, D)
        chunk_scores = query_embs @ chunk.T                        # (N, C) — BLAS matmul
        chunk_ids = np.arange(start, start + len(chunk), dtype=np.int64)

        # Merge: concatenate current best with this chunk, keep top-k
        all_scores = np.concatenate([best_scores, chunk_scores], axis=1)  # (N, k+C)
        all_ids = np.concatenate(
            [best_ids, np.broadcast_to(chunk_ids, (n_queries, len(chunk)))], axis=1
        )  # (N, k+C)

        # argpartition is O(k+C) per row — faster than argsort for large C
        topk_local = np.argpartition(all_scores, -k, axis=1)[:, -k:]
        best_scores = np.take_along_axis(all_scores, topk_local, axis=1)
        best_ids = np.take_along_axis(all_ids, topk_local, axis=1)

    # Final sort within each query's top-k (sort k elements, not M)
    order = np.argsort(best_scores, axis=1)[:, ::-1]
    return (
        np.take_along_axis(best_ids, order, axis=1),
        np.take_along_axis(best_scores, order, axis=1),
    )
```

**Usage:**

```python
top_ids, top_scores = chunked_top_k(query_embs, corpus_embs, k=10, chunk_size=50_000)
# top_ids[i]    → indices of top-10 corpus docs for query i
# top_scores[i] → their cosine similarity scores
```

Tune `chunk_size` to fit `N × chunk_size × 4 bytes` comfortably in RAM.
At N=100 queries and chunk_size=50,000: 100 × 50k × 4 = **20 MB** per chunk.

### sentence-transformers path: built-in chunking

If you are using `util.semantic_search` with torch tensors, it already chunks internally:

```python
hits = util.semantic_search(
    query_embs,
    corpus_embs,
    top_k=10,
    corpus_chunk_size=50_000,   # process corpus in chunks of this size
    query_chunk_size=100,        # process this many queries at a time
)
```

`corpus_chunk_size` defaults to 500,000 and `query_chunk_size` defaults to 100. Lower
`corpus_chunk_size` when RAM is tight. The pure-numpy `chunked_top_k` above is the
alternative when corpus embeddings are precomputed `.npy` files (no torch dependency).

---

## Out-of-Core Embeddings: Memory-Mapped Arrays

When a precomputed corpus embedding matrix is too large to load into RAM, use NumPy's
memory-mapped mode. The OS pages in only the slices that are actually read:

```python
import numpy as np

# Save once (normal float32)
np.save("corpus_embs.npy", corpus_embs)

# Load as read-only memory map — no full copy into RAM
corpus_embs = np.load("corpus_embs.npy", mmap_mode="r")  # (M, D) float32, paged on demand
```

Combine with `chunked_top_k` above: each `corpus_embs[start:start+chunk_size]` read
triggers a page-in of only that slice. Works transparently with the numpy `@` operator.

**Note:** `mmap_mode="r"` prevents accidental writes. Use `mmap_mode="r+"` only when you
intend to update embeddings in-place (rare).

---

## NumPy Broadcasting Discipline

NumPy's broadcasting rule: **trailing dimensions must match or one of them must be 1**.
Broadcasting fuses the shape expansion into the operation — no intermediate array is
materialized *if* you stay with a broadcast-compatible op. But when you call `@`
(matmul), broadcasting applies to the batch dimensions only; the contraction over D
happens inside BLAS. This is the correct primitive.

**The one rule that matters for retrieval:**

> Never inject an axis with `np.newaxis` to do element-wise subtraction or multiplication
> across (queries, corpus, dim) — that materializes O(N × M × D) memory. Instead,
> reformulate to a matmul or use the squared-norm identity for L2.

```python
# BAD — creates (N, M, D) intermediate:
# diff = queries[:, np.newaxis, :] - corpus[np.newaxis, :, :]

# GOOD — (N, M) via BLAS, zero intermediate D-sized expansion:
scores = queries @ corpus.T
```

---

## Polars for Large-Corpus I/O and Preprocessing

*Polars is an optional tool. Use it only for the two retrieval wins below. For smaller
corpora, plain Python lists or pandas are fine.*

Install: `pip install polars`

### Win 1 — Lazy/streaming load of large corpus files

When a corpus CSV or Parquet file is larger than RAM, use lazy evaluation + streaming:

```python
import polars as pl

# scan_parquet / scan_csv: reads only the metadata at this point
lazy_corpus = (
    pl.scan_parquet("corpus.parquet")           # or pl.scan_csv("corpus.csv")
    .filter(pl.col("language") == "en")         # predicate pushdown: filters at read time
    .select(["doc_id", "text"])                 # projection pushdown: reads only these cols
)

# engine="streaming": processes in chunks without loading the full file into RAM
corpus_df = lazy_corpus.collect(engine="streaming")
corpus = corpus_df["text"].to_list()
```

Polars 1.x uses `.collect(engine="streaming")`. The older form `streaming=True` is
deprecated. `predicate_pushdown` and `projection_pushdown` are enabled by default in the
query optimizer — the `.filter` and `.select` calls above are hints, not in-memory ops.

### Win 2 — Vectorized BM25 tokenization at scale

For millions of documents, per-row Python callbacks in `.map_elements()` are slow (each
call crosses the Python interpreter boundary). Use Polars vectorized `.str` expressions
instead — they run in Rust across the full column without Python overhead:

```python
import polars as pl

df = pl.scan_parquet("corpus.parquet").select("text").collect(engine="streaming")

# Vectorized .str chain: runs in Rust, no Python per-row callback
tokens_col = (
    df["text"]
    .str.to_lowercase()
    .str.replace_all(r"[^a-z0-9 ]", "")  # strip punctuation
    .str.split(" ")                        # returns Series of List[str]
)
tokenized_corpus = tokens_col.to_list()  # List[List[str]] — feed directly to BM25Okapi

# versus the anti-pattern:
# df["text"].map_elements(lambda t: t.lower().split(), return_dtype=pl.List(pl.Utf8))
# ^ triggers PolarsInefficientMapWarning; Python loop defeats the point
```

**Use the same `.str` chain for queries** to ensure symmetric tokenization (Gate 1):

```python
import polars as pl

def tokenize(texts: list[str]) -> list[list[str]]:
    """Polars-vectorized tokenizer — use for BOTH corpus and queries."""
    return (
        pl.Series(texts)
        .str.to_lowercase()
        .str.replace_all(r"[^a-z0-9 ]", "")
        .str.split(" ")
        .to_list()
    )
```

---

## Boundary: This File vs `scaling.md`

| Concern | File |
|---|---|
| Memory-safe Python (matmul reformulation, corpus chunking, Polars I/O) | **This file** |
| Which index/infrastructure when exact search is too slow | `references/scaling.md` |

When the corpus grows beyond ~2M documents and chunked-matmul becomes the bottleneck
(not memory, but wall-clock time), move to FAISS/HNSW — see `references/scaling.md`.
