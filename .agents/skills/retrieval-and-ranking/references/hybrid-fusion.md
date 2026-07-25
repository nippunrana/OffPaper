# Hybrid Search — Reciprocal Rank Fusion

*Combine BM25 (lexical) + semantic search (dense) rankings into one fused result list.*

---

## Why Hybrid Search?

BM25 and semantic search fail in complementary ways:

- **BM25 wins** when the query uses exact terms from the document: product codes, names,
  technical identifiers, abbreviations.
- **Semantic search wins** when meaning matters more than keywords: paraphrases, synonyms,
  cross-lingual queries, conceptual similarity.

Hybrid fusion captures both signals without choosing between them.

---

## Reciprocal Rank Fusion (RRF)

RRF merges arbitrary ranked lists by converting each rank to a score and summing:

```
RRF_score(doc) = Σ_r  1 / (k + rank_r(doc))
```

- `k = 60` is the standard constant (dampens the advantage of top-ranked docs).
- Documents not present in a ranking are skipped (not penalized as rank ∞).
- Higher RRF score = better fused position.

### Implementation

```python
from collections import defaultdict

def reciprocal_rank_fusion(*rankings: list[int], k: int = 60) -> list[int]:
    """
    Combine multiple doc-ID rankings via Reciprocal Rank Fusion.

    Args:
        *rankings: Each is a list of doc IDs ordered best-first.
        k: Smoothing constant (default 60 per Cormack et al. 2009).

    Returns:
        Sorted list of doc IDs, highest RRF score first.
    """
    scores: dict[int, float] = defaultdict(float)
    for ranking in rankings:
        for rank, doc_id in enumerate(ranking, start=1):
            scores[doc_id] += 1.0 / (k + rank)
    return sorted(scores, key=scores.__getitem__, reverse=True)
```

---

## Full End-to-End Hybrid Search Example

```python
import numpy as np
from collections import defaultdict
from rank_bm25 import BM25Okapi
from sentence_transformers import SentenceTransformer, util


# ── Corpus ──────────────────────────────────────────────────────────────────
corpus = [
    "Python GIL prevents true parallelism for CPU-bound code.",
    "NumPy achieves speed by calling into BLAS C extensions.",
    "asyncio enables concurrent IO using cooperative multitasking.",
    "multiprocessing bypasses the GIL by using separate processes.",
    "Threading is effective for IO-bound Python tasks.",
]
doc_ids = list(range(len(corpus)))


# ── BM25 retrieval ───────────────────────────────────────────────────────────
def tokenize(text: str) -> list[str]:
    return text.lower().split()

tokenized_corpus = [tokenize(doc) for doc in corpus]
bm25 = BM25Okapi(tokenized_corpus, k1=1.5, b=0.75)


# ── Semantic retrieval ───────────────────────────────────────────────────────
model = SentenceTransformer("all-MiniLM-L6-v2")
corpus_embs = model.encode(corpus, convert_to_tensor=True, normalize_embeddings=True)


# ── Hybrid search ─────────────────────────────────────────────────────────
def hybrid_search(query: str, top_k: int = 5) -> list[tuple[int, float]]:
    """Return list of (doc_id, rrf_score) sorted best-first."""
    # BM25 ranking (sort by score descending, return doc IDs)
    bm25_scores = bm25.get_scores(tokenize(query))
    bm25_ranking = [int(i) for i in np.argsort(bm25_scores)[::-1][:top_k * 2]]

    # Semantic ranking
    query_emb = model.encode(query, convert_to_tensor=True, normalize_embeddings=True)
    sem_hits = util.semantic_search(query_emb, corpus_embs, top_k=top_k * 2)[0]
    sem_ranking = [hit["corpus_id"] for hit in sem_hits]

    # Fuse
    fused_ids = reciprocal_rank_fusion(bm25_ranking, sem_ranking)[:top_k]
    # Reconstruct scores for transparency
    rrf_scores: dict[int, float] = defaultdict(float)
    for ranking in [bm25_ranking, sem_ranking]:
        for rank, doc_id in enumerate(ranking, start=1):
            rrf_scores[doc_id] += 1.0 / (60 + rank)

    return [(doc_id, rrf_scores[doc_id]) for doc_id in fused_ids]


# ── Usage ────────────────────────────────────────────────────────────────────
results = hybrid_search("parallel execution in Python")
for doc_id, score in results:
    print(f"[{score:.4f}] {corpus[doc_id]}")
```

---

## Tuning the k Parameter

- **k=60** (default): standard from Cormack et al. 2009. Works well as a starting point.
- **Increasing k**: reduces the weight advantage of the #1 position in each ranking.
  Use when you want later-ranked documents to contribute more.
- **Decreasing k (toward 1)**: gives very high weight to top-ranked documents. Use when
  precision at rank 1 matters most.

If you have labeled test data, tune k using NDCG@10 — see `references/evaluation.md`.

---

## When NOT to Bother with Hybrid

Hybrid adds a second model and a fusion step. Skip it when:
- The corpus is small (<1,000 docs): BM25 or semantic alone is usually fine.
- Queries are always exact-match (e.g., code identifiers): BM25 alone is better.
- Queries are always paraphrastic (no keyword overlap expected): semantic alone is better.
- Latency is the hard constraint and one retriever is already at the limit.
