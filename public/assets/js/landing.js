/**
 * Landing Page Interactive Features & Micro-Interactions
 * EarlySnap — Powered by AI
 */

document.addEventListener('DOMContentLoaded', () => {
  initVoiceChatDemo();
  initPlanSnapshotDemo();
  initCalendarSyncDemo();
  initCategoryInspectorDemo();
});

/* --- Interactive Demo 1: Voice Chat Simulation --- */
function initVoiceChatDemo() {
  const promptBtns = document.querySelectorAll('[data-demo-prompt]');
  const chatMessages = document.getElementById('landingDemoChatMessages');
  const micBtn = document.getElementById('landingDemoMicBtn');

  if (!chatMessages || !promptBtns.length) return;

  const responses = {
    'prescription': {
      user: "What dosage did Dr. Patel prescribe for Amoxicillin?",
      ai: "Dr. Patel prescribed Amoxicillin 500mg — take 1 capsule 3 times daily with food for 10 days. Refill available before Nov 30."
    },
    'bill': {
      user: "When is the City Power bill due and how much?",
      ai: "City Power Co. bill is $84.20, due on November 12. 1-click Google Calendar reminder is ready to sync!"
    },
    'plan': {
      user: "Summarize the action items from last night's note.",
      ai: "3 action items extracted: 1. Call plumber regarding kitchen sink. 2. Book flights for conference. 3. Request refund from vendor."
    }
  };

  promptBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      promptBtns.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      const key = btn.dataset.demoPrompt;
      const data = responses[key];
      if (!data) return;

      // Animate mic pulsing
      if (micBtn) {
        micBtn.classList.add('is-recording');
        setTimeout(() => micBtn.classList.remove('is-recording'), 1200);
      }

      // Render user turn then AI response turn
      chatMessages.innerHTML = `
        <div class="landing-chat-msg landing-chat-msg--user">
          <div class="landing-chat-bubble">${data.user}</div>
        </div>
        <div class="landing-chat-msg landing-chat-msg--ai landing-chat-msg--typing">
          <div class="landing-chat-bubble">
            <span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>
          </div>
        </div>
      `;

      setTimeout(() => {
        chatMessages.innerHTML = `
          <div class="landing-chat-msg landing-chat-msg--user">
            <div class="landing-chat-bubble">${data.user}</div>
          </div>
          <div class="landing-chat-msg landing-chat-msg--ai">
            <div class="landing-chat-badge">✨ AI</div>
            <div class="landing-chat-bubble">${data.ai}</div>
          </div>
        `;
      }, 700);
    });
  });
}

/* --- Interactive Demo 2: Plan Snapshot Version Switcher --- */
function initPlanSnapshotDemo() {
  const versionBtns = document.querySelectorAll('[data-plan-ver]');
  const planContent = document.getElementById('landingDemoPlanContent');

  if (!planContent || !versionBtns.length) return;

  const versions = {
    '1': `
      <div class="plan-demo-card plan-demo-card--v1">
        <div class="plan-demo-header">
          <span class="badge badge--warning">v1.0 Scribbled Note</span>
          <span class="text-muted">Scanned 11:42 PM</span>
        </div>
        <p class="plan-demo-raw">— call plumber abt leak<br>— book flights before price hike<br>— ask supplier for 10% refund???</p>
      </div>
    `,
    '2': `
      <div class="plan-demo-card plan-demo-card--v2">
        <div class="plan-demo-header">
          <span class="badge badge--info">v2.0 Refined with AI</span>
          <span class="text-muted">Chat session active</span>
        </div>
        <h4>AI Clarifications:</h4>
        <ul class="plan-demo-list">
          <li><strong>Plumber:</strong> Identified main line leak. Scheduled morning call.</li>
          <li><strong>Flights:</strong> Targeted NYC depart Nov 14, return Nov 18.</li>
          <li><strong>Refund:</strong> Invoice #8849 attached for refund request.</li>
        </ul>
      </div>
    `,
    '3': `
      <div class="plan-demo-card plan-demo-card--v3">
        <div class="plan-demo-header">
          <span class="badge badge--success">v3.0 Finalised Action Plan</span>
          <span class="text-muted font-mono">Snapshot saved</span>
        </div>
        <h4>🎯 Goal</h4>
        <p>Execute weekend home repairs and resolve vendor invoice dispute.</p>
        <h4>✅ Action Items</h4>
        <ul class="plan-demo-checklist">
          <li><input type="checkbox" checked disabled> <strong>Call Plumber:</strong> Main line inspection (Urgent)</li>
          <li><input type="checkbox" checked disabled> <strong>Book NYC Flights:</strong> Compare Delta vs United</li>
          <li><input type="checkbox" disabled> <strong>Submit Invoice #8849:</strong> Request 10% credit</li>
        </ul>
        <div class="plan-demo-recap">
          💡 <em>Next step: Track flight price alerts before midnight.</em>
        </div>
      </div>
    `
  };

  versionBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      versionBtns.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      const ver = btn.dataset.planVer;
      if (versions[ver]) {
        planContent.style.opacity = '0.4';
        setTimeout(() => {
          planContent.innerHTML = versions[ver];
          planContent.style.opacity = '1';
        }, 150);
      }
    });
  });
}

/* --- Interactive Demo 3: Google Calendar Sync Simulation --- */
function initCalendarSyncDemo() {
  const syncBtn = document.getElementById('landingDemoCalendarBtn');
  const syncStatus = document.getElementById('landingDemoCalendarStatus');

  if (!syncBtn) return;

  syncBtn.addEventListener('click', () => {
    syncBtn.disabled = true;
    syncBtn.innerHTML = `
      <svg class="spin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="10"/></svg>
      Syncing with Google Calendar...
    `;

    setTimeout(() => {
      syncBtn.className = 'btn btn--success btn--sm';
      syncBtn.innerHTML = `
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        Synced to Google Calendar
      `;
      if (syncStatus) {
        syncStatus.innerHTML = `
          <div class="calendar-toast">
            <span class="calendar-toast__icon">📅</span>
            <div>
              <strong>Event created on Google Calendar</strong>
              <div class="text-xs text-muted">City Power Co. Due Date · Nov 12, 2026 at 9:00 AM</div>
            </div>
          </div>
        `;
      }
    }, 900);
  });
}

/* --- Interactive Demo 4: Category Inspector --- */
function initCategoryInspectorDemo() {
  const categoryBtns = document.querySelectorAll('[data-cat-inspect]');
  const inspectorVisual = document.getElementById('landingCategoryVisual');
  const inspectorImg = document.getElementById('landingCategoryImg');

  if (!inspectorVisual || !categoryBtns.length) return;

  const catImages = {
    'bills': 'doc_bill.png',
    'prescription': 'doc_prescription.png',
    'labreport': 'doc_labreport.png',
    'plan': 'doc_plan.png',
    'deadline': 'doc_deadline.png'
  };

  const catData = {
    'bills': `
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
    `,
    'prescription': `
      <div class="inspector-card">
        <div class="inspector-card__header">
          <span class="inspector-card__tag">💊 PRESCRIPTION EXTRACTOR</span>
          <span class="badge badge--success">Active Rx</span>
        </div>
        <h3 style="margin: 0.5rem 0; font-size: var(--text-lg); color: var(--color-text-primary);">Amoxicillin 500mg Capsules</h3>
        <p style="font-size: var(--text-xs); color: var(--color-text-secondary); margin-bottom: 1rem;">Doctor dosage and refill timelines parsed from physical prescription slip.</p>
        <div class="inspector-card__grid">
          <div class="inspector-field">
            <span class="label">Prescribing Doctor:</span>
            <strong class="val">Dr. Arun Patel, M.D.</strong>
          </div>
          <div class="inspector-field">
            <span class="label">Dosage Instructions:</span>
            <strong class="val">1 capsule 3x/day with food</strong>
          </div>
          <div class="inspector-field">
            <span class="label">Total Quantity:</span>
            <strong class="val tabular-nums">21 Capsules (7 Days)</strong>
          </div>
          <div class="inspector-field">
            <span class="label">Refills Available:</span>
            <strong class="val">Refillable before Nov 30</strong>
          </div>
        </div>
        <div class="inspector-card__footer">
          <span class="text-xs text-muted">🎙️ Ask Voice Copilot for dosage reminders anytime</span>
        </div>
      </div>
    `,
    'labreport': `
      <div class="inspector-card">
        <div class="inspector-card__header">
          <span class="inspector-card__tag">🔬 LAB REPORT EXTRACTOR</span>
          <span class="badge badge--info">Clinical Panel</span>
        </div>
        <h3 style="margin: 0.5rem 0; font-size: var(--text-lg); color: var(--color-text-primary);">Comprehensive Lipid &amp; Blood Panel</h3>
        <p style="font-size: var(--text-xs); color: var(--color-text-secondary); margin-bottom: 1rem;">Lab values matched against standard reference ranges with status flags.</p>
        <div class="inspector-card__grid">
          <div class="inspector-field">
            <span class="label">Laboratory Name:</span>
            <strong class="val">City Medical Labs</strong>
          </div>
          <div class="inspector-field">
            <span class="label">Fasting Glucose:</span>
            <strong class="val text-success">92 mg/dL (70-99 Normal)</strong>
          </div>
          <div class="inspector-field">
            <span class="label">Total Cholesterol:</span>
            <strong class="val text-warning">208 mg/dL (High Flag)</strong>
          </div>
          <div class="inspector-field">
            <span class="label">HDL Cholesterol:</span>
            <strong class="val text-success">58 mg/dL (&gt;40 Desirable)</strong>
          </div>
        </div>
        <div class="inspector-card__footer">
          <span class="text-xs text-muted">📊 AI explains complex medical terms in plain English</span>
        </div>
      </div>
    `,
    'plan': `
      <div class="inspector-card">
        <div class="inspector-card__header">
          <span class="inspector-card__tag">✎ PLAN &amp; NOTES EXTRACTOR</span>
          <span class="badge badge--accent">Plan Assist Active</span>
        </div>
        <h3 style="margin: 0.5rem 0; font-size: var(--text-lg); color: var(--color-text-primary);">Weekend Home Maintenance Plan</h3>
        <p style="font-size: var(--text-xs); color: var(--color-text-secondary); margin-bottom: 1rem;">Handwritten napkin scribble converted into structured checklist snapshots.</p>
        <div class="inspector-card__grid">
          <div class="inspector-field">
            <span class="label">Primary Goal:</span>
            <strong class="val">Resolve plumbing leak &amp; repairs</strong>
          </div>
          <div class="inspector-field">
            <span class="label">Action Items Extracted:</span>
            <strong class="val">3 checklist tasks created</strong>
          </div>
          <div class="inspector-field">
            <span class="label">Urgent Contact:</span>
            <strong class="val">A&amp;R Plumbing (555-0199)</strong>
          </div>
          <div class="inspector-field">
            <span class="label">Snapshot Versions:</span>
            <strong class="val">v1.0, v2.0, v3.0 saved</strong>
          </div>
        </div>
        <div class="inspector-card__footer">
          <span class="text-xs text-muted">🎯 Finalise plan snapshots anytime during chat</span>
        </div>
      </div>
    `,
    'deadline': `
      <div class="inspector-card">
        <div class="inspector-card__header">
          <span class="inspector-card__tag">📅 DEADLINE EXTRACTOR</span>
          <span class="badge badge--danger">High Priority</span>
        </div>
        <h3 style="margin: 0.5rem 0; font-size: var(--text-lg); color: var(--color-text-primary);">Municipal Parking Citation Notice</h3>
        <p style="font-size: var(--text-xs); color: var(--color-text-secondary); margin-bottom: 1rem;">Official deadline dates and citation numbers parsed instantly.</p>
        <div class="inspector-card__grid">
          <div class="inspector-field">
            <span class="label">Issuing Authority:</span>
            <strong class="val">City of Oakhaven</strong>
          </div>
          <div class="inspector-field">
            <span class="label">Action Due Date:</span>
            <strong class="val tabular-nums text-danger" style="font-weight: 700;">Nov 12, 2026</strong>
          </div>
          <div class="inspector-field">
            <span class="label">Citation Number:</span>
            <strong class="val tabular-nums">0487532</strong>
          </div>
          <div class="inspector-field">
            <span class="label">Penalty Amount:</span>
            <strong class="val tabular-nums">$65.00 ($95 after due date)</strong>
          </div>
        </div>
        <div class="inspector-card__footer">
          <span class="text-xs text-muted">⏰ Prevents late fee penalties with early Google Calendar alerts</span>
        </div>
      </div>
    `
  };

  categoryBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      categoryBtns.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      const cat = btn.dataset.catInspect;
      
      // Update image
      if (inspectorImg && catImages[cat]) {
        inspectorImg.style.opacity = '0.3';
        setTimeout(() => {
          inspectorImg.src = window.EARLYSNAP_BASE_PATH ? window.EARLYSNAP_BASE_PATH + '/assets/images/' + catImages[cat] : 'assets/images/' + catImages[cat];
          inspectorImg.style.opacity = '1';
        }, 150);
      }

      // Update data card
      if (catData[cat]) {
        inspectorVisual.style.opacity = '0.3';
        setTimeout(() => {
          inspectorVisual.innerHTML = catData[cat];
          inspectorVisual.style.opacity = '1';
        }, 150);
      }
    });
  });
}
