<?php
require_once __DIR__ . '/../src/bootstrap.php';

$page_title = 'Get your life off paper — AI Document Copilot';
$page_nav = 'home';
$page_desc = 'Snap physical paper documents — bills, lab reports, prescriptions, napkin notes — and EarlySnap uses 2-pass AI to extract data, chat via voice, sync to Google Calendar, and finalise versioned action plans.';
$page_css = ['landing.css'];
$page_js = ['landing.js'];
$body_class = 'page-landing';

require VIEWS . '/header.php';
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "EarlySnap",
  "url": "https://earlysnap.com/",
  "applicationCategory": "ProductivityApplication",
  "operatingSystem": "Web",
  "description": "Snap physical paper documents — bills, lab reports, prescriptions, napkin notes — and EarlySnap uses 2-pass AI to extract data, chat via voice, sync to Google Calendar, and finalise versioned action plans.",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "USD"
  }
}
</script>

<!-- Hero Section -->
<section class="hero">
  <div class="hero__bg-ambient" aria-hidden="true"></div>
  <div class="container hero__grid">
    <div class="hero__copy">
      <div class="eyebrow">
        <span class="pulsing-dot" aria-hidden="true"></span>
        POWERED BY GEMINI 3.5 AI
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
          <span class="paper-card__line paper-card__line--title">City Power Co. — Electricity</span>
          <span class="paper-card__line"></span>
          <span class="paper-card__line"></span>
          <span class="paper-card__stamp">UNPAID BILL</span>
        </div>

        <div class="ai-copilot-card">
          <div class="ai-copilot-header">
            <div class="ai-copilot-tag">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"/></svg>
              AI 2-PASS PARSED
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
      <span class="eyebrow">THE PAPER CHAOS PROBLEM</span>
      <h2>The physical paper pile that steals your peace</h2>
      <p>Important dates, vital health dosages, and brilliant midnight ideas buried under household clutter.</p>
    </div>

    <div class="pain__hero-split">
      <div class="pain__photo-frame">
        <img src="<?= url('/assets/images/paper_clutter_hero.png') ?>" alt="Household paper clutter on desk" class="pain__photo" loading="lazy">
        <div class="pain__tag pain__tag--1">⚠️ Overdue Bill ($184.50)</div>
        <div class="pain__tag pain__tag--2">💊 Rx Instructions</div>
        <div class="pain__tag pain__tag--3">✍️ Napkin Idea Note</div>
      </div>

      <div class="pain__cards-column">
        <article class="pain__card">
          <div class="pain__card-header">
            <span class="pain__badge pain__badge--danger">⚡ Utility Bills</span>
            <h3>The bill under the fruit bowl</h3>
          </div>
          <p>Found four days late when the disconnect warning arrives. Late fees stack up silently every month because life gets busy.</p>
        </article>

        <article class="pain__card">
          <div class="pain__card-header">
            <span class="pain__badge pain__badge--warning">💊 Medical Prescriptions</span>
            <h3>The missing dosage slip</h3>
          </div>
          <p>Dad needs a prescription refill, but the doctor's dosage slip is misplaced right when the pharmacy calls to confirm instructions.</p>
        </article>

        <article class="pain__card">
          <div class="pain__card-header">
            <span class="pain__badge pain__badge--accent">✎ Napkin Notes</span>
            <h3>The 11pm napkin strategy</h3>
          </div>
          <p>Three breakthrough action steps written by hand on a napkin, now forgotten, illegible, and dead on the desk.</p>
        </article>
      </div>
    </div>

    <div class="pain__banner">
      <div class="pain__banner-icon">💡</div>
      <div>
        <strong>The cost of paper clutter:</strong> Late payment penalties, frantic calls to medical clinics, and forgotten plans. EarlySnap bridges paper chaos straight into structured digital action.
      </div>
    </div>
  </div>
</section>

<!-- How It Works Section -->
<section class="how">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">3-STEP WORKFLOW</span>
      <h2>How EarlySnap Works</h2>
      <p>Simple 3-step magic powered by 2-pass AI</p>
    </div>

    <p class="how__purpose">
      <strong>What EarlySnap does:</strong> EarlySnap is a document-to-action app. You photograph a paper document, our AI reads and classifies it, and — with your permission — EarlySnap adds any due dates it finds directly to your Google Calendar using the Google Calendar API. We request Google Calendar access solely to create and manage those reminder events on your behalf; we never sell or share your data with third parties.
    </p>

    <div class="how__grid">
      <div class="how__card">
        <div class="how__step-badge">1</div>
        <div class="how__image-wrap">
          <img src="<?= url('/assets/images/paper_clutter_hero.png') ?>" alt="Step 1: Snap photo" class="how__img" loading="lazy">
          <div class="how__img-overlay">📸 Snap &amp; Upload</div>
        </div>
        <div class="how__content">
          <h3>1. Photograph Any Paper</h3>
          <p>Snap a picture with your phone camera or upload a file. Bills, prescription slips, lab results, napkin notes, parking citations — no app install required.</p>
        </div>
      </div>

      <div class="how__card">
        <div class="how__step-badge">2</div>
        <div class="how__image-wrap">
          <img src="<?= url('/assets/images/doc_bill.png') ?>" alt="Step 2: AI 2-pass scan" class="how__img" loading="lazy">
          <div class="how__img-overlay">✨ AI 2-Pass Extraction</div>
        </div>
        <div class="how__content">
          <h3>2. AI Parses &amp; Summarises</h3>
          <p>Pass 1 classifies the category &amp; generates a 10–20 word summary. Pass 2 extracts precise JSON fields: vendors, dosages, lab values, and due dates.</p>
        </div>
      </div>

      <div class="how__card">
        <div class="how__step-badge">3</div>
        <div class="how__image-wrap">
          <img src="<?= url('/assets/images/google_calendar_sync.png') ?>" alt="Step 3: Act &amp; Sync" class="how__img" loading="lazy">
          <div class="how__img-overlay">📅 Voice Chat &amp; Sync</div>
        </div>
        <div class="how__content">
          <h3>3. Sync Calendar &amp; Voice Chat</h3>
          <p>Sync due dates straight to your Google Calendar with automatic alerts, speak to your documents via voice STT, or finalise scribbles into action plans.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Feature Deep-Dive Showcases -->
<section class="features-showcase">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">DEEP-DIVE CAPABILITIES</span>
      <h2>Built for real life productivity</h2>
      <p>Explore the core engines that turn static paper into intelligent copilot actions.</p>
    </div>
    
    <!-- Feature 1: Google Calendar Sync -->
    <div class="feature-block">
      <div class="feature-block__copy">
        <span class="eyebrow">1-CLICK CALENDAR INTEGRATION</span>
        <h3>Never pay a late fee again. Direct Google Calendar Sync.</h3>
        <p>When EarlySnap detects a bill or deadline document, it parses the exact due date and time. With a single tap, sync it straight into your official Google Calendar with automatic reminders.</p>
        
        <ul class="feature-checklist">
          <li>✓ Automatic timezone &amp; date normalization</li>
          <li>✓ Past-deadline protection ensures expired dates aren't added</li>
          <li>✓ direct OAuth 2.0 integration with zero third-party brokers</li>
        </ul>
      </div>

      <div class="feature-block__visual">
        <div class="feature-photo-card">
          <img src="<?= url('/assets/images/google_calendar_sync.png') ?>" alt="Google Calendar integration preview" class="feature-photo" loading="lazy">
          <div class="calendar-demo-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <span class="badge badge--danger">Due Soon</span>
                <h4 style="margin: 0.25rem 0 0 0; font-size: var(--text-base); color: var(--color-text-primary);">City Power Co. Electricity Bill</h4>
              </div>
              <div style="font-family: var(--font-mono); font-weight: 700; font-size: var(--text-lg); color: var(--color-accent-text);">$84.20</div>
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
    </div>

    <!-- Feature 2: Voice-Powered Document AI Chat -->
    <div class="feature-block feature-block--reverse">
      <div class="feature-block__copy">
        <span class="eyebrow">AI-POWERED STT &amp; CHAT</span>
        <h3>Talk to your documents. Ask questions with your voice.</h3>
        <p>Every scanned document gets its own AI copilot. Tap the microphone to ask questions out loud ("When is my next refill?", "Did this lab test show normal glucose?") and get verbatim transcriptions with instant AI answers.</p>
        
        <ul class="feature-checklist">
          <li>✓ Powered by Gemini 3.5 Speech-to-Text transcription</li>
          <li>✓ Complete document context + original high-res image analysis</li>
          <li>✓ Clear chat history with New Chat reset option</li>
        </ul>
      </div>

      <div class="feature-block__visual">
        <div class="feature-photo-card">
          <img src="<?= url('/assets/images/voice_copilot_preview.png') ?>" alt="Voice AI Copilot preview" class="feature-photo" loading="lazy">
          
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
                <div class="landing-chat-badge">✨ AI</div>
                <div class="landing-chat-bubble">Dr. Patel prescribed Amoxicillin 500mg — take 1 capsule 3 times daily with food for 10 days. Refill available before Nov 30.</div>
              </div>
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
        
        <ul class="feature-checklist">
          <li>✓ Split-panel split view for document &amp; markdown plan</li>
          <li>✓ Stores up to 3 versioned snapshots (v1.0 → v3.0)</li>
          <li>✓ Auto-generates personalized greeting for your next visit</li>
        </ul>
      </div>

      <div class="feature-block__visual">
        <div class="feature-photo-card">
          <img src="<?= url('/assets/images/doc_plan.png') ?>" alt="Napkin Plan document preview" class="feature-photo" loading="lazy">
          
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
                <h4 style="margin: 0 0 0.25rem 0; color: var(--color-text-primary);">🎯 Goal</h4>
                <p style="margin: 0 0 0.75rem 0; color: var(--color-text-secondary);">Execute weekend home repairs and resolve vendor invoice dispute.</p>
                <h4 style="margin: 0 0 0.25rem 0; color: var(--color-text-primary);">✅ Action Items</h4>
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
    </div>

    <!-- Feature 4: 5 Specialized Categories -->
    <div class="feature-block feature-block--reverse">
      <div class="feature-block__copy">
        <span class="eyebrow">STRUCTURED EXTRACTION SCHEMAS</span>
        <h3>5 Specialized AI Schemas. Zero manual entry.</h3>
        <p>EarlySnap automatically detects your document type and applies customized extraction schemas designed specifically for bills, prescriptions, lab reports, plans, and deadlines.</p>
        
        <p style="font-size: var(--text-xs); color: var(--color-text-muted);">Click through the document categories below to inspect how real scanned paper gets translated into structured digital JSON fields:</p>
      </div>

      <div class="feature-block__visual">
        <div class="category-inspector-wrap">
          <div class="category-pills-row">
            <button class="cat-inspect-btn is-active" data-cat-inspect="bills">⚡ Bills</button>
            <button class="cat-inspect-btn" data-cat-inspect="prescription">💊 Prescriptions</button>
            <button class="cat-inspect-btn" data-cat-inspect="labreport">🔬 Lab Reports</button>
            <button class="cat-inspect-btn" data-cat-inspect="plan">✎ Plans &amp; Notes</button>
            <button class="cat-inspect-btn" data-cat-inspect="deadline">📅 Deadlines</button>
          </div>

          <div class="category-split-preview">
            <div class="category-img-pane">
              <img id="landingCategoryImg" src="<?= url('/assets/images/doc_bill.png') ?>" alt="Category Document Scan" class="category-thumb" loading="lazy">
              <div class="category-img-badge">📷 Physical Paper Scan</div>
            </div>

            <div id="landingCategoryVisual" class="category-data-pane">
              <div class="inspector-card">
                <div class="inspector-card__header">
                  <span class="inspector-card__tag">⚡ BILL EXTRACTOR</span>
                  <span class="badge badge--due-soon">Unpaid</span>
                </div>
                <h3 style="margin: 0.5rem 0; font-size: var(--text-lg); color: var(--color-text-primary);">Electricity — City Power Co.</h3>
                <p style="font-size: var(--text-xs); color: var(--color-text-secondary); margin-bottom: 1rem;">2-pass AI extracted payment amounts and due dates automatically.</p>
                <div class="inspector-card__grid">
                  <div class="inspector-field">
                    <span class="label">Vendor Name:</span>
                    <strong class="val">City Power &amp; Light</strong>
                  </div>
                  <div class="inspector-field">
                    <span class="label">Amount Due:</span>
                    <strong class="val tabular-nums text-accent" style="font-size: var(--text-base); font-weight: 700;">$84.20</strong>
                  </div>
                  <div class="inspector-field">
                    <span class="label">Due Date:</span>
                    <strong class="val tabular-nums">Nov 12, 2026</strong>
                  </div>
                  <div class="inspector-field">
                    <span class="label">Account / Invoice #:</span>
                    <strong class="val tabular-nums">INV-993821</strong>
                  </div>
                </div>
                <div class="inspector-card__footer">
                  <span class="text-xs text-muted">✓ 1-click Google Calendar reminder ready</span>
                </div>
              </div>
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
      <span class="eyebrow">PRIVACY &amp; SECURITY FIRST</span>
      <h2>Built for privacy &amp; reliability</h2>
      <p>Your documents stay strictly under your control with enterprise security practices.</p>
    </div>

    <div class="trust__grid">
      <div class="trust__card">
        <div class="trust__icon-wrap">🔒</div>
        <h3>Session-Protected Storage</h3>
        <p>Uploaded documents are stored in secure private storage. Every single request enforces strict session-level ownership authorization before serving content.</p>
      </div>

      <div class="trust__card">
        <div class="trust__icon-wrap">⚡</div>
        <h3>Direct Gemini AI Pipeline</h3>
        <p>Direct REST communications with Google Gemini models. No advertising networks or third-party data brokers ever see or index your files.</p>
      </div>

      <div class="trust__card">
        <div class="trust__icon-wrap">📅</div>
        <h3>Official Google OAuth 2.0</h3>
        <p>Uses official Google Calendar REST APIs with authorization code flow. Refresh tokens are stored safely with automatic access token rotation.</p>
      </div>

      <div class="trust__card">
        <div class="trust__icon-wrap">🗑️</div>
        <h3>1-Click Complete Erasure</h3>
        <p>Delete any document instantly with one tap. Removes the physical file from disk, wipes chat knowledgebase history, and deletes linked Google Calendar events.</p>
      </div>
    </div>
  </div>
</section>

<!-- FAQ Section -->
<section class="faq">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">FREQUENTLY ASKED QUESTIONS</span>
      <h2>Got questions? We have answers.</h2>
    </div>

    <div class="faq__list">
      <details class="faq__item" open>
        <summary>What types of paper documents can EarlySnap process?</summary>
        <div class="faq__answer">
          <p>EarlySnap handles utility bills, medical prescriptions, laboratory blood reports, scribbled notepad plans, invoices, parking tickets, event flyers, and general handwritten notes.</p>
        </div>
      </details>

      <details class="faq__item">
        <summary>How does the Google Calendar integration work?</summary>
        <div class="faq__answer">
          <p>When you log in with Google (or link your account), EarlySnap requests permission to add events to your Google Calendar. When a bill or deadline is detected, tap "Add to Google Calendar" to add the event with due date and reminder alert.</p>
        </div>
      </details>

      <details class="faq__item">
        <summary>How accurate is the Voice Chat and AI Extraction?</summary>
        <div class="faq__answer">
          <p>Powered by Gemini 3.5, extraction leverages strict JSON schema validation. You can ask questions via microphone (STT) or text, and review or edit any parsed data anytime.</p>
        </div>
      </details>

      <details class="faq__item">
        <summary>Do I need a credit card to get started?</summary>
        <div class="faq__answer">
          <p>No! EarlySnap is free to use. You can sign up with email/password or 1-click Google OAuth login in under 30 seconds.</p>
        </div>
      </details>
    </div>
  </div>
</section>

<!-- Final CTA Section -->
<section class="final-cta">
  <div class="container">
    <div class="final-cta__inner">
      <div class="eyebrow eyebrow--light">
        <span class="pulsing-dot" aria-hidden="true"></span>
        GET STARTED IN 30 SECONDS
      </div>
      <h2>Ready to get your life off paper?</h2>
      <p>Transform bills, prescriptions, and scribbled notes into organized digital action in under 30 seconds.</p>
      
      <div class="final-cta__actions">
        <a class="btn btn--primary btn--lg" href="<?= url('/login.php?mode=signup') ?>">Get started free</a>
        <a class="btn btn--google btn--lg" href="<?= url('/auth/google/start.php') ?>">
          <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
          Sign in with Google
        </a>
      </div>

      <div class="final-cta__reassurance">
        <span>✓ Free forever tier</span>
        <span>·</span>
        <span>✓ Instant setup</span>
        <span>·</span>
        <span>✓ Delete data anytime</span>
      </div>
    </div>
  </div>
</section>

<?php require VIEWS . '/footer.php'; ?>
