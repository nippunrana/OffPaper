# Retrieval Evaluation Reference

*Use `scripts/metrics.py` (pure numpy, zero sklearn dependency) for all local evaluation.*

---

## Metric Selection

Choose the right metric *before* writing evaluation code. Picking the wrong metric gives
correct-looking numbers that measure the wrong thing.

| Relevance labels | Recommended | Also consider | Rationale |
|---|---|---|---|
| **Graded** (0/1/2/3…) | NDCG@k | Recall@k | Weights higher grades; log discount favors early precision |
| **Binary** (0/1) | MAP and P@k | Recall@k | MAP is position-sensitive; P@k is a blunt precision check |
| **Single relevant doc per query** | MRR | P@1 | First-hit rank is the only thing that matters |
| **Need to know nothing was missed** | Recall@k | — | Complements any precision metric |

**Common mistake:** using NDCG for binary labels and only reporting one number. Binary
NDCG and MAP are interchangeable for relative ranking, but MAP is more interpretable (it
has a direct "fraction found" intuition). Report both when in doubt.

---

## Using `scripts/metrics.py`

```python
from scripts.metrics import (
    ndcg_at_k,
    mean_average_precision,
    average_precision,
    precision_at_k,
    recall_at_k,
    mean_reciprocal_rank,
    reciprocal_rank,
)

# `relevances`: ground-truth labels in the order the retriever returned them.
# Position 0 = rank 1 (first result), position 1 = rank 2, etc.
relevances = [3, 0, 2, 1, 0, 3]  # graded: doc at rank 1 has relevance 3

# NDCG@5 (exponential gains — default, matches sklearn.metrics.ndcg_score)
ndcg5 = ndcg_at_k(relevances, k=5)

# NDCG@5 with linear gains (simpler; identical to exponential for binary 0/1 labels)
ndcg5_lin = ndcg_at_k(relevances, k=5, gains="linear")

# MAP for a set of queries
all_relevances = [
    [1, 0, 1, 0, 1],  # query 1: relevant at ranks 1, 3, 5
    [0, 1, 0, 0, 1],  # query 2: relevant at ranks 2, 5
]
map_score = mean_average_precision(all_relevances)

# P@5 and Recall@5
p5 = precision_at_k(relevances, k=5)
r5 = recall_at_k(relevances, k=5, num_relevant=4)  # 4 relevant docs exist in total

# MRR
mrr = mean_reciprocal_rank(all_relevances)
```

---

## NDCG — Conventions and Convention Forks

NDCG has two common gain formulas. Both use the same discount `1/log2(rank+1)` (1-indexed).

| Variant | Gain formula | When to use | Matches |
|---|---|---|---|
| `gains="exponential"` (default) | `2^rel - 1` | Graded relevance; academic benchmarks | `sklearn.metrics.ndcg_score` |
| `gains="linear"` | `rel` | Binary (0/1) labels; simpler interpretation | — |

For binary labels, both give the same ranking (since `2^1-1 = 1`, `2^0-1 = 0`), but
different absolute values. Be consistent within a project and document your choice.

**Do not mix conventions when comparing systems.** NDCG computed with exponential gains is
not numerically comparable to NDCG computed with linear gains.

---

## MAP vs sklearn.average_precision_score — They Are Different

`sklearn.metrics.average_precision_score` computes **precision-recall curve area** using
trapezoidal interpolation. This is the standard metric for binary classification.

`scripts/metrics.average_precision` computes **ranking AP**: the mean of P@k at ranks
where a relevant document appears, divided by the number of relevant documents. This is the
standard metric for information retrieval.

**Do not substitute one for the other.** For retrieval tasks, use `scripts/metrics.py`. The
values will differ on any ranking where relevant and irrelevant documents are interleaved.

---

## Worked Example: Evaluating a Retriever

```python
from scripts.metrics import ndcg_at_k, mean_average_precision, precision_at_k, recall_at_k

# Ground truth: for each query, which doc IDs are relevant (and at what grade)
# relevance_map[query_id][doc_id] = relevance grade
relevance_map = {
    "q1": {0: 3, 2: 2, 4: 1},
    "q2": {1: 2, 3: 3},
}

# Retriever results: for each query, list of doc IDs in retrieval order
retriever_results = {
    "q1": [0, 1, 2, 3, 4],   # doc 0 returned first, etc.
    "q2": [3, 0, 1, 2, 4],
}

def build_relevance_list(query_id: str, ranked_ids: list[int]) -> list[int]:
    grades = relevance_map[query_id]
    return [grades.get(doc_id, 0) for doc_id in ranked_ids]

all_rels = []
for qid, ranked in retriever_results.items():
    rels = build_relevance_list(qid, ranked)
    all_rels.append(rels)
    ndcg = ndcg_at_k(rels, k=5)
    p5   = precision_at_k(rels, k=5)
    n_rel = sum(1 for g in relevance_map[qid].values() if g > 0)
    r5   = recall_at_k(rels, k=5, num_relevant=n_rel)
    print(f"{qid}: NDCG@5={ndcg:.3f}  P@5={p5:.3f}  Recall@5={r5:.3f}")

print(f"MAP: {mean_average_precision(all_rels):.3f}")
```

---

## What k to Use for @k Metrics

| Setting | Typical k | Rationale |
|---|---|---|
| Search engine / web | @10 | "First page" of results |
| Chatbot RAG context | @5 or @3 | Only top chunks fed to LLM |
| Recommendation | @10 or @20 | User browses a list |
| Binary single-relevant | @1 or MRR | Only first hit matters |

Report multiple k values when behavior at different cutoffs matters to stakeholders.

---

## Metric Definitions (Reference)

**P@k** — fraction of top-k results that are relevant. Denominator = k (always).

**Recall@k** — fraction of all relevant documents found in top-k. Denominator = total relevant in corpus.

**AP** — average of P@rank at each rank position where a relevant document occurs, divided by number of relevant docs.

**MAP** — mean of AP across queries.

**DCG@k** — sum of gain/discount at each rank up to k. Discount = 1/log2(rank+1), 1-indexed.

**NDCG@k** — DCG@k / IDCG@k, where IDCG is DCG of the ideal (perfectly ranked) list. Normalized to [0, 1].

**MRR** — mean of 1/(rank of first relevant result) across queries. Returns 0 when no relevant result is found.
