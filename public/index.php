<?php
require_once __DIR__ . '/../src/bootstrap.php';

$page_title = 'Get your life off paper — AI Document Copilot';
$page_nav = 'home';
$page_desc = 'Snap physical paper documents — bills, lab reports, prescriptions, napkin notes — and EarlySnap uses Gemini 3.5 2-pass AI to extract data, chat via voice, sync to Google Calendar, and finalise versioned action plans.';
$page_css = ['landing.css'];
$page_js = ['landing.js'];
$body_class = 'page-landing';

require VIEWS . '/header.php';
?>

<!-- Hero Section -->
<section class="hero">
  <div class="container hero__grid">
    <div class="hero__copy">
      <div class="eyebrow">
        <span class="pulsing-dot" aria-hidden="true"></span>
        POWERED BY GOOGLE GEMINI 3.5
      </div>
      <h1>Snap your paper chaos. EarlySnap turns it into action.</h1>
      <p class="hero__subhead">
        Bills, lab reports, prescriptions, and scribbled 11pm notes — photographed in seconds, classified by 2-pass AI, synced to Google Calendar, and ready to chat with via voice.
      </p>
      
      <div class="hero__actions">
        <div class="hero__btn-group">
          <a class="btn btn--primary btn--lg" href="<?= url('/login.php?mode=signup') ?>">Get started free</a>
          <a class="btn btn--google btn--lg" href="<?= url('/auth/google/start.php') ?>">
            <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
            Sign in with Google
          </a>
        </div>
        <div class="hero__fud">
          <span>✓ No credit card required</span>
          <span>·</span>
          <span>✓ 1-click Google Calendar sync</span>
          <span>·</span>
          <span>✓ Private storage</span>
        </div>
      </div>
    </div>

    <!-- Hero Interactive Showcase -->
    <div class="hero__visual">
      <div class="hero-showcase">
        <div class="paper-card">
          <div class="paper-card__badge">📄 Physical Paper Scan</div>
          <span class="paper-card__line paper-card__line--title"></span>
          <span class="paper-card__line"></span>
          <span class="paper-card__line"></span>
          <span class="paper-card__stamp">UNPAID BILL</span>
        </div>

        <div class="ai-copilot-card">
          <div class="ai-copilot-header">
            <div class="ai-copilot-tag">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"/></svg>
              GEMINI 2-PASS PARSED
            </div>
            <span class="badge badge--success">Ready to Sync</span>
          </div>

          <div class="ai-copilot-body">
            <h4 style="margin: 0; font-size: var(--text-base); color: white;">City Power Co. — Electricity</h4>
            <div class="ai-copilot-pill-row">
              <span class="ai-pill ai-pill--accent">$84.20</span>
              <span class="ai-pill">Due Nov 12</span>
              <span class="ai-pill">Category: Bill</span>
            </div>

            <div class="ai-copilot-chat-preview">
              <div style="font-size: var(--text-xs); color: #E3D9C4;">
                <strong style="color: white; display: block;">🎙️ Voice &amp; Chat Copilot Active</strong>
                "Remind me 2 days before due date"
              </div>
              <button class="ai-mic-btn" id="landingDemoMicBtn" title="Simulate Voice Input">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Pain Section -->
<section class="pain">
  <div class="container">
    <div class="section-header">
      <h2>The physical paper pile that steals your peace</h2>
      <p>Important dates, vital health dosages, and brilliant midnight ideas buried under household clutter.</p>
    </div>

    <div class="pain__grid">
      <article class="pain__card">
        <span class="pain__icon" aria-hidden="true">⚡</span>
        <h3>The bill under the fruit bowl</h3>
        <p>Found four days late when the disconnect warning arrives. Late fees stack up silently every month.</p>
      </article>

      <article class="pain__card">
        <span class="pain__icon" aria-hidden="true">💊</span>
        <h3>The missing dosage slip</h3>
        <p>Dad needs a prescription refill, but the doctor's instruction slip is misplaced right when the pharmacy asks.</p>
      </article>

      <article class="pain__card">
        <span class="pain__icon" aria-hidden="true">✎</span>
        <h3>The 11pm napkin strategy</h3>
        <p>Three breakthrough action steps written by hand, now illegible and dead on the notebook page.</p>
      </article>
    </div>

    <div class="pain__banner">
      <strong>The cost of paper clutter:</strong> Late payment penalties, frantic calls to medical clinics, and forgotten plans. EarlySnap bridges the gap between paper and digital action.
    </div>
  </div>
</section>

<!-- How It Works Section -->
<section class="how">
  <div class="container">
    <div class="section-header">
      <h2>How EarlySnap Works</h2>
      <p>3 simple steps powered by Google Gemini 3.5</p>
    </div>

    <ol class="how__steps">
      <li class="how__step">
        <span class="how__number tabular-nums">1</span>
        <h3>Snap &amp; Upload</h3>
        <p>Photograph any document with your phone camera or upload a file. No specialized scanner or app install needed.</p>
      </li>

      <li class="how__step">
        <span class="how__number tabular-nums">2</span>
        <h3>Gemini 2-Pass Extraction</h3>
        <p>Pass 1 classifies category &amp; generates a strict 10–20 word summary. Pass 2 extracts precise JSON fields (vendors, lab values, dosages).</p>
      </li>

      <li class="how__step">
        <span class="how__number tabular-nums">3</span>
        <h3>Act, Chat &amp; Sync</h3>
        <p>Sync due dates directly to Google Calendar, converse via voice STT, or finalise scribbles into structured, versioned action plans.</p>
      </li>
    </ol>
  </div>
</section>

<!-- Feature Deep-Dive Showcases -->
<section class="features-showcase">
  <div class="container">
    
    <!-- Feature 1: Google Calendar Sync -->
    <div class="feature-block">
      <div class="feature-block__copy">
        <span class="eyebrow">1-CLICK CALENDAR INTEGRATION</span>
        <h3>Never pay a late fee again. Direct Google Calendar Sync.</h3>
        <p>When EarlySnap detects a bill or deadline document, it parses the exact due date and time. With a single tap, sync it straight into your official Google Calendar with automatic reminders.</p>
        <div style="font-family: var(--font-mono); font-size: var(--text-xs); color: var(--color-accent-text);">
          ✓ Handles expired deadline protection &amp; calendar link tracking
        </div>
      </div>

      <div class="feature-block__visual">
        <div class="calendar-demo-card">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <span class="badge badge--danger">Due Soon</span>
              <h4 style="margin: 0.25rem 0 0 0; font-size: var(--text-base);">City Power Co. Electricity Bill</h4>
            </div>
            <div style="font-family: var(--font-mono); font-weight: 700; font-size: var(--text-lg);">$84.20</div>
          </div>
          <p style="font-size: var(--text-xs); color: var(--color-text-secondary); margin: 0;">Due Date: November 12, 2026</p>

          <button id="landingDemoCalendarBtn" class="btn btn--primary btn--sm" style="width: 100%; justify-content: center;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Sync to Google Calendar
          </button>
          
          <div id="landingDemoCalendarStatus"></div>
        </div>
      </div>
    </div>

    <!-- Feature 2: Voice-Powered Document AI Chat -->
    <div class="feature-block feature-block--reverse">
      <div class="feature-block__copy">
        <span class="eyebrow">GEMINI FLASH LITE STT &amp; CHAT</span>
        <h3>Talk to your documents. Ask questions with your voice.</h3>
        <p>Every scanned document gets its own AI copilot. Tap the microphone to ask questions out loud ("When is my next refill?", "Did this lab test show normal glucose?") and get verbatim transcriptions with instant AI answers.</p>
      </div>

      <div class="feature-block__visual">
        <div class="demo-chat-box">
          <div class="demo-chat-prompts">
            <span style="font-size: var(--text-xs); color: var(--color-text-muted); width: 100%; display: block; margin-bottom: 0.25rem;">Try clicking a sample voice prompt:</span>
            <button class="demo-prompt-btn is-active" data-demo-prompt="prescription">💊 Prescription Dosage</button>
            <button class="demo-prompt-btn" data-demo-prompt="bill">⚡ Power Bill Due Date</button>
            <button class="demo-prompt-btn" data-demo-prompt="plan">✎ Napkin Note Summary</button>
          </div>

          <div class="demo-chat-messages" id="landingDemoChatMessages">
            <div class="landing-chat-msg landing-chat-msg--user">
              <div class="landing-chat-bubble">What dosage did Dr. Patel prescribe for Amoxicillin?</div>
            </div>
            <div class="landing-chat-msg landing-chat-msg--ai">
              <div class="landing-chat-badge">✨ Gemini 3.5 AI</div>
              <div class="landing-chat-bubble">Dr. Patel prescribed Amoxicillin 500mg — take 1 capsule 3 times daily with food for 10 days. Refill available before Nov 30.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Feature 3: Plan Finalisation Engine -->
    <div class="feature-block">
      <div class="feature-block__copy">
        <span class="eyebrow">PLAN SYNTHESIS &amp; SNAPSHOTS</span>
        <h3>Turn scribbles into versioned action plans.</h3>
        <p>For plans and notes, chat with AI to refine your ideas, then hit <strong>"Finalise the Plan"</strong>. EarlySnap synthesises your entire conversation into a clean, versioned snapshot with Goals, Action Checklists, Timelines, and Risks.</p>
      </div>

      <div class="feature-block__visual">
        <div class="demo-plan-box">
          <div class="demo-plan-tabs">
            <button class="demo-plan-tab" data-plan-ver="1">v1.0 Raw Note</button>
            <button class="demo-plan-tab" data-plan-ver="2">v2.0 Refined Chat</button>
            <button class="demo-plan-tab is-active" data-plan-ver="3">v3.0 Finalised Plan</button>
          </div>

          <div id="landingDemoPlanContent">
            <div class="plan-demo-card plan-demo-card--v3">
              <div class="plan-demo-header">
                <span class="badge badge--success">v3.0 Finalised Action Plan</span>
                <span class="text-muted font-mono">Snapshot saved</span>
              </div>
              <h4 style="margin: 0 0 0.25rem 0;">🎯 Goal</h4>
              <p style="margin: 0 0 0.75rem 0;">Execute weekend home repairs and resolve vendor invoice dispute.</p>
              <h4 style="margin: 0 0 0.25rem 0;">✅ Action Items</h4>
              <ul class="plan-demo-checklist">
                <li><input type="checkbox" checked disabled> <strong>Call Plumber:</strong> Main line inspection (Urgent)</li>
                <li><input type="checkbox" checked disabled> <strong>Book NYC Flights:</strong> Compare Delta vs United</li>
                <li><input type="checkbox" disabled> <strong>Submit Invoice #8849:</strong> Request 10% credit</li>
              </ul>
              <div class="plan-demo-recap">
                💡 <em>Next step: Track flight price alerts before midnight.</em>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Feature 4: 5 Specialized Categories -->
    <div class="feature-block feature-block--reverse">
      <div class="feature-block__copy">
        <span class="eyebrow">STRUCTURED EXTRACTION SCHEMAS</span>
        <h3>5 Specialized AI Schemas. Zero manual entry.</h3>
        <p>EarlySnap automatically detects your document type and applies customized extraction schemas designed specifically for bills, prescriptions, lab reports, plans, and deadlines.</p>
      </div>

      <div class="feature-block__visual">
        <div class="category-pills-row">
          <button class="cat-inspect-btn is-active" data-cat-inspect="bills">⚡ Bills</button>
          <button class="cat-inspect-btn" data-cat-inspect="prescription">💊 Prescriptions</button>
          <button class="cat-inspect-btn" data-cat-inspect="labreport">🔬 Lab Reports</button>
          <button class="cat-inspect-btn" data-cat-inspect="plan">✎ Plans &amp; Notes</button>
          <button class="cat-inspect-btn" data-cat-inspect="deadline">📅 Deadlines</button>
        </div>

        <div id="landingCategoryVisual">
          <div class="inspector-card">
            <div class="inspector-card__tag">BILL EXTRACTOR</div>
            <h3 style="margin: 0; font-size: var(--text-lg);">Electricity — City Power Co.</h3>
            <div class="inspector-card__grid">
              <div><span class="label">Amount:</span> <strong class="val tabular-nums">$84.20</strong></div>
              <div><span class="label">Due Date:</span> <strong class="val tabular-nums">Nov 12, 2026</strong></div>
              <div><span class="label">Invoice #:</span> <strong class="val tabular-nums">INV-993821</strong></div>
              <div><span class="label">Status:</span> <span class="badge badge--due-soon">Unpaid</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- Trust & Security Section -->
<section class="trust">
  <div class="container">
    <div class="section-header">
      <h2>Built for privacy &amp; reliability</h2>
      <p>Your documents stay under your control with security best practices.</p>
    </div>

    <div class="trust__grid">
      <div class="trust__card">
        <span class="trust__icon">🔒</span>
        <h3>Private Storage</h3>
        <p>Uploaded documents are stored securely with session-level authorization check on every file request.</p>
      </div>

      <div class="trust__card">
        <span class="trust__icon">⚡</span>
        <h3>Gemini REST Engine</h3>
        <p>Direct REST communication with Google Gemini 3.5 models. No third-party ad networks ever see your files.</p>
      </div>

      <div class="trust__card">
        <span class="trust__icon">📅</span>
        <h3>Official Google Calendar Sync</h3>
        <p>Uses OAuth 2.0 authorization code flow with automatic offline refresh tokens for calendar updates.</p>
      </div>

      <div class="trust__card">
        <span class="trust__icon">🗑️</span>
        <h3>1-Click Erasure</h3>
        <p>Delete any document instantly. Removes the disk file, knowledgebase chat history, and linked Google Calendar event.</p>
      </div>
    </div>
  </div>
</section>

<!-- FAQ Section -->
<section class="faq">
  <div class="container">
    <h2>Frequently Asked Questions</h2>
    <div class="faq__list">
      <details class="faq__item">
        <summary>What types of paper can EarlySnap process?</summary>
        <p>EarlySnap works on utility bills, medical prescriptions, laboratory blood reports, scribbled notepad plans, invoices, parking tickets, event flyers, and general handwritten notes.</p>
      </details>

      <details class="faq__item">
        <summary>How does the Google Calendar integration work?</summary>
        <p>When you log in with Google (or link your account), EarlySnap requests permission to add events to your Google Calendar. When a bill or deadline is detected, tap "Add to Google Calendar" to add the event with due date and reminder alert.</p>
      </details>

      <details class="faq__item">
        <summary>How accurate is the Voice Chat and AI Extraction?</summary>
        <p>Powered by Google Gemini 3.5 Flash Lite, extraction leverages strict JSON schema validation. You can also ask questions via microphone (STT) or text, and review/edit any parsed data anytime.</p>
      </details>

      <details class="faq__item">
        <summary>Do I need a credit card to get started?</summary>
        <p>No! You can sign up with email/password or 1-click Google OAuth login in about 30 seconds.</p>
      </details>
    </div>
  </div>
</section>

<!-- Final CTA Section -->
<section class="final-cta">
  <div class="container">
    <div class="final-cta__inner">
      <h2>Ready to get your life off paper?</h2>
      <p>Transform bills, prescriptions, and scribbled notes into organized digital action in under 30 seconds.</p>
      
      <div class="final-cta__actions">
        <a class="btn btn--primary btn--lg" href="<?= url('/login.php?mode=signup') ?>">Get started free</a>
        <a class="btn btn--google btn--lg" href="<?= url('/auth/google/start.php') ?>">
          <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
          Sign in with Google
        </a>
      </div>
    </div>
  </div>
</section>

<?php require VIEWS . '/footer.php'; ?>
