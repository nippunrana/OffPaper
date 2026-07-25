/**
 * Landing Page Interactive Features & Micro-Interactions
 * OffPaper — Powered by Google Gemini AI
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
            <div class="landing-chat-badge">✨ Gemini 3.5 AI</div>
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
          <span class="badge badge--info">v2.0 Refined with Gemini</span>
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

  if (!inspectorVisual || !categoryBtns.length) return;

  const catData = {
    'bills': `
      <div class="inspector-card">
        <div class="inspector-card__tag">BILL EXTRACTOR</div>
        <h3>Electricity — City Power Co.</h3>
        <div class="inspector-card__grid">
          <div><span class="label">Amount:</span> <strong class="val tabular-nums">$84.20</strong></div>
          <div><span class="label">Due Date:</span> <strong class="val tabular-nums">Nov 12, 2026</strong></div>
          <div><span class="label">Invoice #:</span> <strong class="val tabular-nums">INV-993821</strong></div>
          <div><span class="label">Status:</span> <span class="badge badge--due-soon">Unpaid</span></div>
        </div>
      </div>
    `,
    'prescription': `
      <div class="inspector-card">
        <div class="inspector-card__tag">PRESCRIPTION EXTRACTOR</div>
        <h3>Amoxicillin 500mg</h3>
        <div class="inspector-card__grid">
          <div><span class="label">Doctor:</span> <strong class="val">Dr. Aris Patel</strong></div>
          <div><span class="label">Clinic:</span> <strong class="val">Apex Health Care</strong></div>
          <div><span class="label">Dosage:</span> <strong class="val">1 cap 3x/day w/ food</strong></div>
          <div><span class="label">Refills:</span> <strong class="val">2 remaining</strong></div>
        </div>
      </div>
    `,
    'labreport': `
      <div class="inspector-card">
        <div class="inspector-card__tag">LAB REPORT EXTRACTOR</div>
        <h3>Comprehensive Blood Panel</h3>
        <div class="inspector-card__grid">
          <div><span class="label">Lab:</span> <strong class="val">Quest Diagnostics</strong></div>
          <div><span class="label">Vitamin D3:</span> <strong class="val text-success">42 ng/mL (Normal)</strong></div>
          <div><span class="label">Fasting Glucose:</span> <strong class="val text-warning">104 mg/dL (Slightly High)</strong></div>
          <div><span class="label">Action:</span> <strong class="val">Follow up in 6 months</strong></div>
        </div>
      </div>
    `,
    'plan': `
      <div class="inspector-card">
        <div class="inspector-card__tag">PLAN & NOTES EXTRACTOR</div>
        <h3>Q4 House Maintenance Plan</h3>
        <div class="inspector-card__grid">
          <div><span class="label">Goal:</span> <strong class="val">Winterize home & plumbing</strong></div>
          <div><span class="label">Checklist:</span> <strong class="val">3 items extracted</strong></div>
          <div><span class="label">Mode:</span> <span class="badge badge--accent">Plan Assist Active</span></div>
          <div><span class="label">Snapshots:</span> <strong class="val">v1.0 Available</strong></div>
        </div>
      </div>
    `,
    'deadline': `
      <div class="inspector-card">
        <div class="inspector-card__tag">DEADLINE EXTRACTOR</div>
        <h3>Property Tax Installment #2</h3>
        <div class="inspector-card__grid">
          <div><span class="label">Issuer:</span> <strong class="val">County Collector</strong></div>
          <div><span class="label">Due Date:</span> <strong class="val tabular-nums">Dec 01, 2026</strong></div>
          <div><span class="label">Priority:</span> <span class="badge badge--danger">High Priority</span></div>
          <div><span class="label">Calendar Sync:</span> <strong class="val text-accent">Ready to sync</strong></div>
        </div>
      </div>
    `
  };

  categoryBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      categoryBtns.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      const cat = btn.dataset.catInspect;
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
