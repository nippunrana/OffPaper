# OffPaper — Project Context

## What this is
OffPaper is a web app: the user photographs a paper document (bill, prescription/lab
report, or handwritten notes) and the app identifies what it is and turns it into
something useful — a deadline reminder, a digital health record, or editable text.
See `01-temp/plan.md` for the full pitch.

## Status
Early planning stage — no code written yet.

## Tech Stack (decided)
- **Backend:** PHP, no framework, no build step.
- **Frontend:** Vanilla HTML5, CSS, JavaScript. No bundlers, no build tools, no JS
  framework (no React/Vue/webpack/npm build pipeline).
- **Camera capture:** Default to `<input type="file" accept="image/*"
  capture="environment">` — opens the native camera/file picker with no JS required
  and works consistently across mobile and desktop. Only move to `getUserMedia` +
  `<video>`/`<canvas>` if a live in-page preview becomes a specific requirement (it
  adds stream-management complexity and requires HTTPS in production).
- **AI / OCR:** Google Gemini API (Flash / Flash-Lite models) for ALL AI tasks —
  document classification (bill vs. prescription vs. note), OCR and handwriting
  extraction, and structured data extraction (amount, due date, etc.). Call the
  Gemini REST API directly via PHP `curl` (image + prompt in, JSON out) — no SDK.
  No other AI providers are to be used in this project.
  - Exact Gemini model IDs still need confirming against Google's current docs
    before being hardcoded anywhere.

## Constraints
- No build tooling anywhere in the stack.
- If `getUserMedia` is ever used, camera access requires HTTPS in production
  (localhost is exempt for dev).
