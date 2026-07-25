# Domain: API, Network & Backend Data Flow

Use this reference when the bug crosses the network boundary or lives on the server: failing fetch/AJAX, wrong payload shape, CORS, 4xx/5xx, request times out, server returns 200 but the side effect never happened (no DB row, no email, no queued job), a query returns the wrong rows.

API/network bugs almost always involve **two sides** — the client view and the server view of the same request. Diagnose both. The client says "I sent X and got Y"; the server says "I received A and did B". The gap between X→A or B→Y is where the bug lives.

---

## 1. Sub-categories

- `client-request` — does the client actually send what you think it sends?
- `server-receipt` — does the server receive what the client sent, and does it route to the right handler?
- `handler-flow` — inside the handler, what happens? Which branches run? What does the handler decide to do?
- `db-query` — is the query (or ORM call) returning what you expect?
- `response-shape` — does the response payload match what the client parser expects?
- `auth-cors` — is the request being blocked or rejected before it hits the handler?
- `trace-correlation` — bridge client and server logs by following an existing trace/correlation ID across the network boundary

---

## 2. Probe patterns

### Reproducible curl (`client-request`)

Right-click the request in DevTools → Network tab → "Copy as cURL" (this step is inherently user-side — DevTools only runs in their browser). This gives you the exact method, URL, headers, and body the client sent — use it to diff against what you expected, and replay it to isolate client vs. server.

**Before replaying, classify the method:**
- **Safe/idempotent (GET, HEAD, or a known-idempotent endpoint)** — run it yourself per Phase 4's Agentic Execution path if you have shell access. Don't ask the user to do it.
- **Mutating (POST, PUT, PATCH, DELETE)** — replaying it re-triggers the side effect (a real order, a real charge, a real email). Only replay against a dev/staging environment, and only after explicit confirmation from the user that it is safe to do so. This is a probe-safety rule (see Probe Rule §4 in `instrumentation-protocol.md`), not optional caution.

Only ask the user to run the curl themselves if you're in a read-only environment with no shell access. Strip auth tokens before *sharing* the command in chat (not before running it — the token is needed to reproduce the request).

If the curl reproduces the bug, the bug is server-side. If the curl works but the in-app call fails, diff the headers and body — the bug is in how the client builds the request (headers, body serialization, credentials).

### Trace correlation (`trace-correlation`)

Modern distributed systems propagate a request ID across the network boundary so the same logical request can be tracked from browser → gateway → service → worker. **Check for these headers first** — if they're present, you can skip injecting your own correlation tag and pivot straight to the server logs for the exact failing invocation.

**Where to look (Network tab → Headers, both Request and Response):**

| Header | Source | Notes |
|---|---|---|
| `traceparent`, `tracestate` | W3C Trace Context | Format: `00-<trace-id>-<span-id>-<flags>`. Used by OpenTelemetry, Datadog, Honeycomb, Sentry, Jaeger. |
| `X-Request-ID`, `X-Correlation-ID`, `Request-Id` | Custom / framework default | Often added by gateways (Cloudflare, Nginx, Heroku, AWS ALB) or frameworks (Rails `ActionDispatch::RequestId`, ASP.NET `TraceIdentifier`, Express `express-request-id`). |
| `X-Amzn-Trace-Id` | AWS X-Ray | Set by ALB / API Gateway. |
| `X-Cloud-Trace-Context` | GCP Cloud Trace | Set by GCP load balancers. |
| `X-B3-TraceId`, `X-B3-SpanId` | B3 / Zipkin | Common in service meshes (Istio, Linkerd). |

**How to use:**

1. In DevTools → Network → click the failing request → Headers. Scan both request and response headers for any of the above.
2. Copy the trace/correlation value. For W3C `traceparent`, extract the **middle segment** — that's the searchable trace-id:
   ```
   traceparent: 00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01
                   └──────── trace-id (search this) ────────┘
   ```
3. Search wherever your server logs land — exact match on the ID:
   Use your native search tools, log viewers, or aggregators:
   - **Log files:** Run a recursive search for the exact trace ID string within your server log files/directories.
   - **Log aggregators / dashboards:** Filter logs by querying the trace/correlation ID field (e.g., matching the trace ID variable, trace attribute, or a plain-text filter).
4. The matching lines pin the exact handler invocation, with surrounding context (timing, errors, downstream DB queries, queued jobs, third-party calls) — without injecting a single probe.

**If no trace ID is present** (system isn't propagating context), in order of preference:

1. **Enable the framework's built-in instrumentation.** Most backend frameworks ship dormant request-ID tagging (a config flag or a first-party middleware package) — check current docs for the framework in use. Configure once, get correlation forever.
2. **Inject a one-off debug trace header** for this investigation only. Tag both client and server sides per `[DEBUG-<id>]` protocol; remove both in Phase 7.

> **Caution — this probe is not side-effect-free for cross-origin requests.** Adding a custom header turns a "simple" cross-origin request into one that requires a CORS preflight (OPTIONS). If the server's CORS config doesn't already allow-list `X-Debug-Trace`, the probe *creates* a CORS failure that wasn't there before, masking the bug you're chasing. It can also break requests signed with HMAC/SigV4, since the signature was computed without this header. Use this only for same-origin requests, or first confirm the server's `Access-Control-Allow-Headers` will accept the new header.

**Client side:** intercept the client's outbound-request mechanism (a fetch/HTTP-client wrapper, an interceptor hook) so every request gets one extra header — a per-session random value generated once, reused for every request in that session. Merge it into any existing headers rather than replacing them, so an already-in-flight header set from the request isn't dropped. Log the generated value once, tagged, so you can search for it in server logs.

**Server side:** at the earliest point every request passes through (the framework's middleware/interceptor layer, not each individual handler), read the debug-trace header and log it alongside the method/path — one line, tagged. Registering it once at the middleware layer means you don't have to touch every handler.

**Worker / queue boundaries** (background job queues, message brokers, pub-sub systems) are where trace context usually gets dropped — the producer enqueues a job without attaching the trace, so the consumer's logs are orphaned. If the bug spans a job, verify the producer writes the trace into the job payload and the consumer reads it back into its logging context. This handoff is a common defect site, not just an observability gap.

### Client-side request logger (no source edits)

A console-only probe: wrap the browser's request mechanism (fetch or equivalent) so every outbound request and its response get logged — method, URL, request body, response status, timing, and response body (truncated). Two constraints matter more than the exact code:

- **Duplicate the response before reading its body.** Response bodies are typically single-read streams; if you consume the original to log it, the caller that's supposed to receive the response gets nothing. Clone/duplicate it first, read the clone.
- **Don't block the original call on your logging.** Read the cloned body asynchronously so the real response returns to its caller without added latency.

This snippet is console-only and discarded on page refresh, so no cleanup is needed and it's exempt from the "one id per hypothesis round" rule (protocol §2) — a fresh id per request is fine, it just makes the log easier to read.

### Server-handler instrumentation (`server-receipt`, `handler-flow`)

Inject tagged logs at: handler entry, every branch decision (especially early returns/short-circuits), every external call (DB, third-party API, queue), and handler exit. At entry, log the inbound payload's *keys/shape*, not the raw body (see Probe Rule §4 — don't log secrets), plus the resolved user/session identity if one exists. At exit, log the shape of what's about to be returned.

Use the runtime's server-side logging channel rather than an unguaranteed stdout print, so the log reliably records the message instead of being silently dropped when stdout is discarded (e.g. by web server gateways, container runners, or process managers). Most server runtimes distinguish a "write to stdout" call from a "write to the configured log" call (a logger object, error log stream, etc.) — prefer the latter unless you've confirmed stdout is captured as the primary server log.

### DB-query logging (`db-query`)

Every ORM/database layer ships a built-in hook or flag for query logging — enable it rather than instrumenting each call site by hand; it captures both the bound SQL and the timing in one place. Two shapes exist, and mixing them up produces empty or stale-looking output:

- **Live/streaming hooks** (a callback or listener fired on each query as it runs) — the common form for backend-framework ORMs. If you paste the registration code inside a namespaced/scoped class, the framework's facade or service import may need to be fully qualified — an unqualified reference can resolve to the wrong namespace and fail with a "class/name not found" style error.
- **Read-after-execution buffers** (a list/array the runtime fills in as queries execute, gated behind a debug flag) — common in CMS and dev-mode ORM configs. Read the buffer *after* the suspect code has run (end of request, a shutdown/teardown hook) — reading it at handler entry returns nothing, because nothing has executed yet.

Either way: enabling the hook is a config change, not a source edit — record the original flag value in the ledger (protocol §3) so Phase 7 can revert it or, for a persistent debug-log flag, ask the user whether to keep it enabled (protocol §5).

For slow queries, prepend `EXPLAIN ANALYZE` (Postgres/MySQL) to the suspect query to get the execution plan.

### Response shape diff (`response-shape`)

When the API "works" but the client crashes parsing the response: fetch the endpoint, parse the body, and diff its actual top-level keys against the keys the client code expects — log what's missing, what's extra, and the full actual payload.

Common causes: serializer changed, field renamed, nested key flattened, null where an array was expected.

### Auth / CORS / preflight (`auth-cors`)

Check the **Network tab** in DevTools for the *preflight* (OPTIONS) request, not just the actual request. CORS failures show up as the OPTIONS being missing or returning the wrong `Access-Control-Allow-*` headers. The actual request never fires when the preflight fails — DevTools shows it as "CORS error" in the console.

For auth failures, check whether `Authorization` / `Cookie` headers are actually being sent (Network → Request Headers). Common causes: `credentials: 'omit'` in fetch options, cross-origin cookie blocked, token expired.

---

## 3. Signals to look for in the returned data

- **Client sent X, server received A ≠ X** → bug is in client request building (serializer, headers, URL params).
- **Server received correct payload, returns 200, but side effect missing** → handler short-circuited silently. Add a probe at every `return` and every conditional. Look for empty catch blocks swallowing errors.
- **Handler logs reach a `return` early** → the gate above it is wrong. Don't add a workaround in the gate; fix what makes the condition true.
- **DB query log shows wrong WHERE/JOIN** → the bug is in the query builder, not the data.
- **DB query log is missing entirely** → the code path never reached the DB call. Trace upstream.
- **Status 200 but response body is `null` / `{}`** → handler returned before populating the response. The probe at the handler exit will show what was about to be returned.
- **CORS preflight 4xx** → server-side CORS config, not client code.
- **`Authorization` header missing** → fetch credentials mode or token-injection middleware.
- **Trace ID found in server logs** → you have the exact handler invocation; read the surrounding lines for branch decisions, downstream calls, and the final response.
- **Trace ID present on the request but absent from server logs** → the request never reached the logging code path. Either an upstream layer (gateway, proxy, auth middleware) rejected it, or the trace isn't being propagated into the logger's context — check middleware order.
- **Trace propagates browser → API but disappears at a worker/job boundary** → the producer isn't attaching trace context to the job payload. The bug may *be* the missing propagation, not a downstream defect.

---

## 4. Fix discipline

- If the bug is server-side, fix the handler. Don't paper over it with client-side retries or fallback data.
- If the bug is in the request the client builds, fix the request builder. Don't make the server tolerate the malformed input.
- After fixing, write a regression test (HTTP-level if integration tests exist, or unit-level for the request builder / handler logic).
- Run Phase 7 cleanup. Server-side `[DEBUG-<id>]` logs in particular have a habit of getting deployed accidentally — verifying that searching the codebase for "[DEBUG-" returns zero matches using your native search tool is non-negotiable.
