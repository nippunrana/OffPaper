OffPaper — get your life off paper.

Think about how much important stuff still comes to us on paper: electricity
bills, insurance papers, school notices, doctor's prescriptions, lab reports,
even plans we scribble in a notebook. Today all of that either sits in a drawer
or gets photographed and lost in the gallery — and then we forget the due date,
lose the prescription, or never look at that plan again.

OffPaper is a simple web app where you just take a photo of any paper, and the
app is smart enough to understand WHAT that paper is and turn it into something
useful:

- A bill? It reads the amount and due date, puts it on your deadline timeline,
  and reminds you before it's late. You can add it to your calendar in one tap.

- A prescription or lab report? It becomes a clean digital health record you
  can actually find later, instead of a crumpled slip.

- A handwritten plan or notes? It converts your handwriting into editable text,
  so you can keep working on the plan — change it, extend it, build on it —
  instead of it dying on the page.

One-line version: you snap a photo of any paper, and OffPaper turns it into the
digital thing it should have been — a reminder, a record, or a document you can
keep editing.

The demo moment: hand someone a paper bill, snap it, and within seconds the due
date and amount appear on the timeline. That's an hour of "life admin" turned
into five seconds.

---

## Technical Approach (initial)

- **Stack:** PHP backend, vanilla HTML5/CSS/JS frontend — no build tools, no
  bundlers, no JS framework.
- **Photo capture:** `<input type="file" accept="image/*" capture="environment">`
  as the default (native camera/file picker, no build step, works on mobile and
  desktop). `getUserMedia` is a fallback option only if a live in-page camera
  preview is specifically wanted later.
- **Document understanding (OCR + classification + extraction):** Google Gemini
  API (Flash / Flash-Lite models only), called via plain PHP `curl` — image and
  prompt in, structured JSON out. No other AI providers used in this project.
- See `ai-context.md` in the project root for the full running context.
