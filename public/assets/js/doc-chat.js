/**
 * OffPaper Document AI Chat Controller
 * Manages frontend UI interactions for chatting with AI about specific paper documents.
 */
document.addEventListener('DOMContentLoaded', () => {
  const chatModal = document.getElementById('docChatModal');
  const closeBtns = document.querySelectorAll('[data-close-doc-chat]');

  const chatDocTitle = document.getElementById('chatDocTitle');
  const chatDocBadges = document.getElementById('chatDocBadges');
  const chatMessages = document.getElementById('docChatMessages');
  const chatTyping = document.getElementById('docChatTyping');
  const chatForm = document.getElementById('docChatForm');
  const chatInput = document.getElementById('docChatInput');
  const sendBtn = document.getElementById('docChatSendBtn');
  const quickPromptsContainer = document.getElementById('chatQuickPrompts');
  const finalisePlanBtn = document.getElementById('docChatFinalisePlanBtn');
  const newChatBtn = document.getElementById('docChatNewChatBtn');

  // Plan panel elements
  const planPill          = document.getElementById('docChatPlanPill');
  const planPillDate      = document.getElementById('planPillDate');
  const splitBody         = document.getElementById('docChatSplitBody');
  const planPanel         = document.getElementById('docChatPlanPanel');
  const planPanelBody     = document.getElementById('planPanelBody');
  const planPanelDate     = document.getElementById('planPanelDate');
  const planVersionTabs   = document.getElementById('planVersionTabs');
  const planVersionTabList = document.getElementById('planVersionTabList');
  const planPanelCloseBtn = document.getElementById('docChatPlanPanelClose');

  let currentDocument = null;
  let chatMode = null; // null for normal chat, 'plan_assist' for Improve Plan
  let newMessageSentInSession = false;
  let allPlanSnapshots = []; // cached snapshots for version tabs

  // Listen for open chat triggers on cards or detail modals
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-open-doc-chat]');
    if (!trigger) return;

    e.preventDefault();
    const rawData = trigger.getAttribute('data-open-doc-chat');
    if (!rawData) return;

    try {
      const doc = JSON.parse(rawData);
      const categories = doc.categories || [doc.doc_type || 'plan'];
      const explicitMode = trigger.getAttribute('data-chat-mode');
      const mode = explicitMode || (categories.includes('plan') ? 'plan_assist' : null);
      openChatModal(doc, mode);
    } catch (err) {
      console.error('Error parsing document data for chat:', err);
    }
  });

  // Close modal event handlers
  closeBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      closeChatModal();
    });
  });

  if (chatModal) {
    chatModal.addEventListener('click', (e) => {
      if (e.target === chatModal) {
        closeChatModal();
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && chatModal && chatModal.classList.contains('is-open')) {
      closeChatModal();
    }
  });

  // Enable/disable send button based on input content
  if (chatInput && sendBtn) {
    chatInput.addEventListener('input', () => {
      sendBtn.disabled = !chatInput.value.trim();
    });
  }

  // Handle form submit / message send
  if (chatForm) {
    chatForm.addEventListener('submit', (e) => {
      e.preventDefault();
      handleSendMessage();
    });
  }

  // Handle quick prompt chip clicks
  if (quickPromptsContainer) {
    quickPromptsContainer.addEventListener('click', (e) => {
      const chip = e.target.closest('.chat-chip');
      if (!chip) return;

      const promptText = chip.getAttribute('data-prompt');
      if (promptText) {
        sendUserMessage(promptText);
      }
    });
  }

  function openChatModal(doc, mode = null) {
    currentDocument = doc;
    chatMode = mode === 'plan_assist' ? 'plan_assist' : null;
    newMessageSentInSession = false;

    if (!chatModal) return;

    // Reset previous state & messages
    if (chatMessages) chatMessages.innerHTML = '';
    if (chatInput) {
      chatInput.value = '';
      if (sendBtn) sendBtn.disabled = true;
    }
    hideTyping();

    // Update modal header title based on mode
    const modalTitleEl = document.getElementById('chatModalTitle');
    if (modalTitleEl) {
      modalTitleEl.textContent = chatMode === 'plan_assist' ? 'Plan AI Assistant' : 'Document AI Assistant';
    }

    // Populate header info
    const docTitleText = formatDocumentTitle(doc);
    if (chatDocTitle) {
      chatDocTitle.textContent = docTitleText;
      chatDocTitle.title = docTitleText;
    }

    if (chatDocBadges) {
      chatDocBadges.innerHTML = '';
      const categories = doc.categories || [doc.doc_type || 'plan'];
      categories.forEach(cat => {
        const badge = document.createElement('span');
        const badgeConfig = getCategoryBadgeConfig(cat);
        badge.className = `doc-card__badge ${badgeConfig.class}`;
        badge.innerHTML = `<span class="doc-card__badge-icon">${badgeConfig.icon}</span>${escapeHtml(badgeConfig.label)}`;
        chatDocBadges.appendChild(badge);
      });
    }

    // Render suggested prompts, then load everything in one shot for plan_assist
    if (chatMode === 'plan_assist') {
      renderPlanAssistPrompts();
      // greeting + history + snapshot handled together in loadPlanAssistSession
    } else {
      renderQuickPrompts(doc);
      renderInitialGreeting(doc, docTitleText);
      loadExistingChatHistory(doc);
    }

    // Show New Chat button only when there's saved history to clear
    if (newChatBtn) newChatBtn.style.display = 'none';

    // Show/hide the Finalise Plan button based on mode
    if (finalisePlanBtn) {
      finalisePlanBtn.style.display = chatMode === 'plan_assist' ? 'inline-flex' : 'none';
    }

    // Reset plan panel to closed state on every open
    closePlanPanel();
    allPlanSnapshots = [];
    if (planPill) planPill.style.display = 'none';

    // For plan_assist: one async call handles greeting, history, and snapshot
    if (chatMode === 'plan_assist') {
      loadPlanAssistSession(doc, docTitleText);
    }

    // Show modal
    chatModal.style.display = 'flex';
    requestAnimationFrame(() => {
      chatModal.classList.add('is-open');
    });
    document.body.style.overflow = 'hidden';

    // Focus input field
    setTimeout(() => {
      if (chatInput) chatInput.focus();
    }, 150);
  }

  // New Chat button handler
  if (newChatBtn) {
    newChatBtn.addEventListener('click', async () => {
      if (!currentDocument) return;
      await clearChatHistory();
    });
  }

  function closeChatModal() {
    if (!chatModal) return;
    chatModal.classList.remove('is-open');

    // Fire chat_finalize.php if closing plan_assist session with new messages
    if (chatMode === 'plan_assist' && newMessageSentInSession && currentDocument) {
      const finalizeUrl = window.OFFPAPER_CHAT_FINALIZE_URL || 'api/chat_finalize.php';
      fetch(finalizeUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          document_id: currentDocument.id || 0,
          uuid: currentDocument.uuid || ''
        })
      }).catch(err => console.error('Chat finalize request error:', err));
    }

    setTimeout(() => {
      chatModal.style.display = 'none';
      document.body.style.overflow = '';
      currentDocument = null;
      chatMode = null;
      newMessageSentInSession = false;
      const modalTitleEl = document.getElementById('chatModalTitle');
      if (modalTitleEl) modalTitleEl.textContent = 'Document AI Assistant';
    }, 250);
  }

  function handleSendMessage() {
    if (!chatInput) return;
    const text = chatInput.value.trim();
    if (!text) return;

    chatInput.value = '';
    if (sendBtn) sendBtn.disabled = true;
    sendUserMessage(text);
  }

  async function sendUserMessage(text) {
    // --- Magic phrase: intercept & trigger plan finalisation ---
    const magicPhrase = 'amazing, lets update the plan';
    if (chatMode === 'plan_assist' && text.trim().toLowerCase() === magicPhrase) {
      triggerFinalisePlan();
      return;
    }

    newMessageSentInSession = true;
    if (newChatBtn) newChatBtn.style.display = 'inline-flex';

    appendMessage({
      sender: 'user',
      text: text,
      time: getCurrentTimeFormatted()
    });

    showTyping();

    if (!currentDocument || (!currentDocument.id && !currentDocument.uuid)) {
      hideTyping();
      appendMessage({
        sender: 'ai',
        html: '<p>⚠️ Error: No active document selected for chat.</p>',
        time: getCurrentTimeFormatted()
      });
      return;
    }

    try {
      const chatApiUrl = window.OFFPAPER_CHAT_URL || 'api/chat.php';
      const response = await fetch(chatApiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          document_id: currentDocument.id || 0,
          uuid: currentDocument.uuid || '',
          message: text,
          mode: chatMode || ''
        })
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Chat API HTTP Error:', response.status, errorText);
        throw new Error(`Server error (${response.status})`);
      }

      const data = await response.json();
      hideTyping();

      if (data.ok && data.reply) {
        const formattedHtml = formatMarkdownResponse(data.reply);
        appendMessage({
          sender: 'ai',
          html: formattedHtml,
          time: getCurrentTimeFormatted()
        });
      } else {
        const errorMsg = data.error || 'Failed to generate response from Gemini AI.';
        appendMessage({
          sender: 'ai',
          html: `<p>⚠️ ${escapeHtml(errorMsg)}</p>`,
          time: getCurrentTimeFormatted()
        });
      }
    } catch (err) {
      console.error('Document AI Chat error:', err);
      hideTyping();
      appendMessage({
        sender: 'ai',
        html: '<p>⚠️ Connection error. Please try again.</p>',
        time: getCurrentTimeFormatted()
      });
    }
  }

  async function clearChatHistory() {
    if (!currentDocument) return;
    try {
      const chatApiUrl = window.OFFPAPER_CHAT_URL || 'api/chat.php';
      const res = await fetch(chatApiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          document_id: currentDocument.id || 0,
          uuid: currentDocument.uuid || '',
          mode: 'clear_history'
        })
      });
      const data = await res.json();
      if (data.ok) {
        newMessageSentInSession = false;
        if (chatMessages) chatMessages.innerHTML = '';
        if (newChatBtn) newChatBtn.style.display = 'none';
        const docTitleText = formatDocumentTitle(currentDocument);
        if (chatMode === 'plan_assist') {
          renderPlanAssistGreeting(currentDocument, docTitleText);
        } else {
          renderInitialGreeting(currentDocument, docTitleText);
        }
      }
    } catch (err) {
      console.error('Error clearing chat history:', err);
    }
  }

  // ----------------------------------------------------------------
  // Plan Panel helpers
  // ----------------------------------------------------------------
  function openPlanPanel() {
    if (!splitBody || !planPanel) return;
    splitBody.classList.add('plan-panel--open');
    planPanel.style.display = 'flex';
  }

  function closePlanPanel() {
    if (!splitBody || !planPanel) return;
    splitBody.classList.remove('plan-panel--open');
    planPanel.style.display = 'none';
  }

  // Plan pill click — opens panel
  if (planPill) {
    planPill.addEventListener('click', () => openPlanPanel());
  }

  // Plan panel close button
  if (planPanelCloseBtn) {
    planPanelCloseBtn.addEventListener('click', () => closePlanPanel());
  }

  // ----------------------------------------------------------------
  // Finalise Plan — synthesise chat → save snapshot → clear & display
  // ----------------------------------------------------------------
  async function triggerFinalisePlan() {
    if (!currentDocument) return;

    if (chatMessages) chatMessages.innerHTML = '';
    showTyping();
    if (chatTyping) {
      const typingText = chatTyping.querySelector('.typing-text');
      if (typingText) typingText.textContent = '✨ Studying the conversation & finalising your plan...';
    }

    try {
      const url = window.OFFPAPER_CHAT_FINALISE_URL || 'api/chat_finalise_plan.php';
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          document_id: currentDocument.id || 0,
          uuid: currentDocument.uuid || ''
        })
      });

      const data = await res.json();
      hideTyping();

      if (chatTyping) {
        const typingText = chatTyping.querySelector('.typing-text');
        if (typingText) typingText.textContent = 'AI is reading document details...';
      }

      if (!data.ok) {
        appendMessage({
          sender: 'ai',
          html: `<p>⚠️ ${escapeHtml(data.error || 'Could not finalise the plan. Please try again.')}</p>`,
          time: getCurrentTimeFormatted()
        });
        return;
      }

      newMessageSentInSession = false;
      if (newChatBtn) newChatBtn.style.display = 'none';

      // Prepend to cached snapshots (newest first)
      allPlanSnapshots.unshift({ plan_text: data.plan_text, finalised_at: data.finalised_at });
      allPlanSnapshots = allPlanSnapshots.slice(0, 3);

      // Show plan in panel and refresh pill
      showPlanInPanel(data.plan_text, data.finalised_at, 0);
      updatePlanPill(data.finalised_at);
      renderVersionTabs(allPlanSnapshots, 0);
      openPlanPanel();

      // Brief confirmation in chat
      renderFinaliseConfirmation(data.finalised_at);

    } catch (err) {
      console.error('Finalise Plan error:', err);
      hideTyping();
      if (chatTyping) {
        const typingText = chatTyping.querySelector('.typing-text');
        if (typingText) typingText.textContent = 'AI is reading document details...';
      }
      appendMessage({
        sender: 'ai',
        html: '<p>⚠️ Connection error while finalising. Please try again.</p>',
        time: getCurrentTimeFormatted()
      });
    }
  }

  // Wire the Finalise Plan button
  if (finalisePlanBtn) {
    finalisePlanBtn.addEventListener('click', () => triggerFinalisePlan());
  }

  // ----------------------------------------------------------------
  // Plan snapshot: load, display, helpers
  // ----------------------------------------------------------------

  // Load snapshots from API and populate pill + panel
  async function loadPlanSnapshot(doc) {
    // This function is kept for non-plan-assist snapshot refreshes.
    // plan_assist mode uses loadPlanAssistSession instead.
    try {
      const chatApiUrl = window.OFFPAPER_CHAT_URL || 'api/chat.php';
      const docId = doc.id || 0;
      const uuid  = doc.uuid || '';
      const fetchUrl = `${chatApiUrl}?document_id=${docId}&uuid=${encodeURIComponent(uuid)}`;

      const res = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;

      const data = await res.json();
      if (!data.ok) return;

      const snapshots = data.plan_snapshots || null;
      if (!Array.isArray(snapshots) || snapshots.length === 0) return;

      allPlanSnapshots = snapshots;
      const latest = snapshots[0];

      updatePlanPill(latest.finalised_at);
      showPlanInPanel(latest.plan_text, latest.finalised_at, 0);
      renderVersionTabs(snapshots, 0);

    } catch (err) {
      console.error('Error loading plan snapshot:', err);
    }
  }

  // One-shot loader for plan_assist mode:
  // fetches history + snapshots in one request, renders greeting then history in correct order
  async function loadPlanAssistSession(doc, docTitleText) {
    try {
      const chatApiUrl = window.OFFPAPER_CHAT_URL || 'api/chat.php';
      const docId = doc.id || 0;
      const uuid  = doc.uuid || '';
      const fetchUrl = `${chatApiUrl}?document_id=${docId}&uuid=${encodeURIComponent(uuid)}`;

      const res = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });

      // On fetch failure fall back to generic greeting only
      if (!res.ok) {
        renderPlanAssistGreeting(doc, docTitleText);
        return;
      }

      const data = await res.json();
      if (!data.ok) {
        renderPlanAssistGreeting(doc, docTitleText);
        return;
      }

      const snapshots   = Array.isArray(data.plan_snapshots) ? data.plan_snapshots : [];
      const chatHistory = Array.isArray(data.chat_history)   ? data.chat_history   : [];
      const latest      = snapshots[0] || null;

      // ── Greeting (must be first message) ─────────────────────────────
      if (latest && latest.greeting) {
        // Contextual greeting generated at finalisation time
        const greetHtml = `
          <p>${escapeHtml(latest.greeting)}</p>
          <p style="font-size:0.82rem; color:var(--color-text-muted); margin-top:0.4rem;">
            Your latest plan is saved — tap <strong>Plan saved</strong> above to view it, or keep chatting to refine further.
          </p>`;
        appendMessage({ sender: 'ai', html: greetHtml, time: getCurrentTimeFormatted() });
      } else {
        // No snapshot yet — use the generic plan_assist greeting
        renderPlanAssistGreeting(doc, docTitleText);
      }

      // ── Existing chat history ─────────────────────────────────────────
      if (chatHistory.length > 0) {
        chatHistory.forEach(turn => {
          const sender = turn.role === 'user' ? 'user' : 'ai';
          const text   = turn.text || '';
          const time   = turn.ts
            ? new Date(turn.ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            : getCurrentTimeFormatted();
          if (sender === 'ai') {
            appendMessage({ sender: 'ai', html: formatMarkdownResponse(text), time });
          } else {
            appendMessage({ sender: 'user', text, time });
          }
        });
        if (newChatBtn) newChatBtn.style.display = 'inline-flex';
      }

      // ── Plan snapshot panel + pill ────────────────────────────────────
      if (snapshots.length > 0) {
        allPlanSnapshots = snapshots;
        updatePlanPill(latest.finalised_at);
        showPlanInPanel(latest.plan_text, latest.finalised_at, 0);
        renderVersionTabs(snapshots, 0);
        openPlanPanel(); // auto-open: user can close with the ← button
      }

    } catch (err) {
      console.error('Error loading plan assist session:', err);
      renderPlanAssistGreeting(doc, docTitleText);
    }
  }

  // Update the 1-line pill in the header
  function updatePlanPill(finalisedAt) {
    if (!planPill) return;
    if (planPillDate) {
      const dateStr = finalisedAt ? new Date(finalisedAt).toLocaleDateString([], {
        month: 'short', day: 'numeric'
      }) : '';
      planPillDate.textContent = dateStr ? `· ${dateStr}` : '';
    }
    planPill.style.display = 'inline-flex';
  }

  // Render plan content into the right panel
  function showPlanInPanel(planText, finalisedAt, activeIdx) {
    if (!planPanelBody) return;

    // Update date badge
    if (planPanelDate) {
      const dateStr = finalisedAt ? new Date(finalisedAt).toLocaleString([], {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
      }) : '';
      planPanelDate.textContent = dateStr ? `Saved ${dateStr}` : '';
    }

    const contentHtml = formatPlanMarkdown(planText);
    planPanelBody.innerHTML = `<div class="plan-panel__content" aria-live="polite">${contentHtml}</div>`;
    planPanelBody.scrollTop = 0;
  }

  // Render version tabs (up to 3)
  function renderVersionTabs(snapshots, activeIdx) {
    if (!planVersionTabs || !planVersionTabList) return;
    if (snapshots.length <= 1) {
      planVersionTabs.style.display = 'none';
      return;
    }
    planVersionTabs.style.display = 'flex';
    planVersionTabList.innerHTML = '';
    snapshots.forEach((snap, i) => {
      const dateStr = snap.finalised_at ? new Date(snap.finalised_at).toLocaleDateString([], {
        month: 'short', day: 'numeric'
      }) : `v${i + 1}`;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'plan-version-tab' + (i === activeIdx ? ' is-active' : '');
      btn.textContent = i === 0 ? `Latest · ${dateStr}` : dateStr;
      btn.setAttribute('aria-label', `Plan version ${i + 1}`);
      btn.addEventListener('click', () => {
        showPlanInPanel(snap.plan_text, snap.finalised_at, i);
        renderVersionTabs(snapshots, i);
      });
      planVersionTabList.appendChild(btn);
    });
  }

  // Brief confirmation message in chat after finalising
  function renderFinaliseConfirmation(finalisedAt) {
    if (!chatMessages) return;
    chatMessages.innerHTML = '';
    const dateStr = finalisedAt ? new Date(finalisedAt).toLocaleString([], {
      month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    }) : '';
    const html = `
      <div class="plan-snapshot-card">
        <div class="plan-snapshot-card__header">
          <span class="plan-snapshot-card__icon">🎯</span>
          <span class="plan-snapshot-card__title">Plan Finalised</span>
          ${dateStr ? `<span class="plan-snapshot-badge">Saved ${escapeHtml(dateStr)}</span>` : ''}
        </div>
        <div class="plan-snapshot-card__body">
          <p>Your plan has been synthesised and saved. <strong>View it in the panel →</strong></p>
          <p style="font-size:0.82rem; color:var(--color-text-muted); margin-top:0.4rem;">Continue chatting here to refine further, then finalise again to update.</p>
        </div>
      </div>
    `;
    appendMessage({ sender: 'ai', html, time: dateStr || getCurrentTimeFormatted() });
  }

  async function loadExistingChatHistory(doc) {
    try {
      const chatApiUrl = window.OFFPAPER_CHAT_URL || 'api/chat.php';
      const docId = doc.id || 0;
      const uuid = doc.uuid || '';
      const fetchUrl = `${chatApiUrl}?document_id=${docId}&uuid=${encodeURIComponent(uuid)}`;

      const response = await fetch(fetchUrl, {
        headers: { 'Accept': 'application/json' }
      });
      if (!response.ok) return;

      const data = await response.json();
      if (data.ok && Array.isArray(data.chat_history) && data.chat_history.length > 0) {
        if (chatMessages) chatMessages.innerHTML = '';
        data.chat_history.forEach(turn => {
          const sender = (turn.role === 'user') ? 'user' : 'ai';
          const text = turn.text || '';
          const time = turn.ts ? new Date(turn.ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : getCurrentTimeFormatted();
          if (sender === 'ai') {
            appendMessage({ sender: 'ai', html: formatMarkdownResponse(text), time: time });
          } else {
            appendMessage({ sender: 'user', text: text, time: time });
          }
        });
        // Show 'New Chat' button since history exists
        if (newChatBtn) newChatBtn.style.display = 'inline-flex';
      }
    } catch (err) {
      console.error('Error loading existing chat history:', err);
    }
  }

  function formatMarkdownResponse(rawText) {
    if (!rawText) return '';
    let escaped = escapeHtml(rawText);

    // Format **bold**
    escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // Format *italic*
    escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');

    const lines = escaped.split('\n');
    let inList = false;
    let listType = 'ul';
    let html = '';

    lines.forEach(line => {
      const trimmed = line.trim();
      if (trimmed.startsWith('* ') || trimmed.startsWith('- ') || trimmed.startsWith('• ')) {
        if (!inList || listType !== 'ul') {
          if (inList) html += listType === 'ul' ? '</ul>' : '</ol>';
          html += '<ul>';
          inList = true;
          listType = 'ul';
        }
        const itemContent = trimmed.substring(2).trim();
        html += `<li>${itemContent}</li>`;
      } else if (/^\d+\.\s/.test(trimmed)) {
        if (!inList || listType !== 'ol') {
          if (inList) html += listType === 'ul' ? '</ul>' : '</ol>';
          html += '<ol>';
          inList = true;
          listType = 'ol';
        }
        const itemContent = trimmed.replace(/^\d+\.\s/, '').trim();
        html += `<li>${itemContent}</li>`;
      } else {
        if (inList) {
          html += listType === 'ul' ? '</ul>' : '</ol>';
          inList = false;
        }
        if (trimmed.length > 0) {
          html += `<p>${trimmed}</p>`;
        }
      }
    });

    if (inList) {
      html += listType === 'ul' ? '</ul>' : '</ol>';
    }

    return html;
  }

  // Plan-panel markdown formatter — handles ## / ### headings from AI synthesis output
  function formatPlanMarkdown(rawText) {
    if (!rawText) return '';
    let escaped = escapeHtml(rawText);

    // Bold & italic
    escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');

    const lines = escaped.split('\n');
    let inList = false;
    let listType = 'ul';
    let html = '';

    lines.forEach(line => {
      const trimmed = line.trim();

      // h2 headings: ## Emoji Title
      if (/^##\s/.test(trimmed)) {
        if (inList) { html += listType === 'ul' ? '</ul>' : '</ol>'; inList = false; }
        html += `<h2>${trimmed.replace(/^##\s/, '')}</h2>`;

      // h3 headings: ### Title
      } else if (/^###\s/.test(trimmed)) {
        if (inList) { html += listType === 'ul' ? '</ul>' : '</ol>'; inList = false; }
        html += `<h3>${trimmed.replace(/^###\s/, '')}</h3>`;

      // Bullet lists
      } else if (trimmed.startsWith('* ') || trimmed.startsWith('- ') || trimmed.startsWith('• ')) {
        if (!inList || listType !== 'ul') {
          if (inList) html += listType === 'ul' ? '</ul>' : '</ol>';
          html += '<ul>'; inList = true; listType = 'ul';
        }
        html += `<li>${trimmed.substring(2).trim()}</li>`;

      // Ordered lists
      } else if (/^\d+\.\s/.test(trimmed)) {
        if (!inList || listType !== 'ol') {
          if (inList) html += listType === 'ul' ? '</ul>' : '</ol>';
          html += '<ol>'; inList = true; listType = 'ol';
        }
        html += `<li>${trimmed.replace(/^\d+\.\s/, '').trim()}</li>`;

      // Paragraphs
      } else {
        if (inList) { html += listType === 'ul' ? '</ul>' : '</ol>'; inList = false; }
        if (trimmed.length > 0) html += `<p>${trimmed}</p>`;
      }
    });

    if (inList) html += listType === 'ul' ? '</ul>' : '</ol>';
    return html;
  }

  function renderInitialGreeting(doc, title) {
    const summary = doc.summary ? `“${escapeHtml(doc.summary)}”` : '';
    const cats = (doc.categories || []).map(c => capitalize(c)).join(', ');

    let greetingHtml = `<p>Hello! I'm your OffPaper AI assistant for <strong>${escapeHtml(title)}</strong>.</p>`;
    if (summary) {
      greetingHtml += `<p class="doc-card__summary" style="margin-top: 0.5rem; margin-bottom: 0.5rem;">${summary}</p>`;
    }
    greetingHtml += `<p style="font-size: 0.85rem; color: var(--color-text-secondary);">Ask me anything about this document's categories (<em>${escapeHtml(cats)}</em>), line items, due dates, or extracted data!</p>`;

    appendMessage({
      sender: 'ai',
      html: greetingHtml,
      time: getCurrentTimeFormatted()
    });
  }

  function renderPlanAssistGreeting(doc, title) {
    const summary = doc.summary ? `“${escapeHtml(doc.summary)}”` : '';
    let greetingHtml = `<p>Hello! I'm your Plan AI Assistant for <strong>${escapeHtml(title)}</strong>.</p>`;
    if (summary) {
      greetingHtml += `<p class="doc-card__summary" style="margin-top: 0.5rem; margin-bottom: 0.5rem;">${summary}</p>`;
    }
    greetingHtml += `<p style="font-size: 0.85rem; color: var(--color-text-secondary);">I can help you analyze, prioritize, identify risks, and add missing action items to strengthen this plan.</p>`;

    appendMessage({
      sender: 'ai',
      html: greetingHtml,
      time: getCurrentTimeFormatted()
    });
  }

  function renderPlanAssistPrompts() {
    if (!quickPromptsContainer) return;
    quickPromptsContainer.innerHTML = '';

    const planPrompts = [
      "What's missing from this plan?",
      "Any risks I should plan for?",
      "Help me prioritize these steps"
    ];

    planPrompts.forEach((promptText) => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'chat-chip';
      chip.setAttribute('data-prompt', promptText);
      chip.innerHTML = `<span>📋</span> ${escapeHtml(promptText)}`;
      quickPromptsContainer.appendChild(chip);
    });
  }

  function appendMessage({ sender, text, html, time }) {
    if (!chatMessages) return;

    const msgEl = document.createElement('div');
    msgEl.className = `chat-msg chat-msg--${sender}`;

    const avatarEl = document.createElement('div');
    avatarEl.className = 'chat-msg__avatar';
    avatarEl.setAttribute('aria-hidden', 'true');
    avatarEl.innerHTML = sender === 'ai' ? '🤖' : '👤';

    const contentEl = document.createElement('div');
    contentEl.className = 'chat-msg__content';

    const bubbleEl = document.createElement('div');
    bubbleEl.className = 'chat-msg__bubble';
    if (html) {
      bubbleEl.innerHTML = html;
    } else {
      bubbleEl.textContent = text;
    }

    const timeEl = document.createElement('time');
    timeEl.className = 'chat-msg__time';
    timeEl.textContent = time;

    contentEl.appendChild(bubbleEl);
    contentEl.appendChild(timeEl);

    msgEl.appendChild(avatarEl);
    msgEl.appendChild(contentEl);

    chatMessages.appendChild(msgEl);
    scrollToBottom();
  }

  function showTyping() {
    if (chatTyping) {
      chatTyping.style.display = 'flex';
      scrollToBottom();
    }
  }

  function hideTyping() {
    if (chatTyping) chatTyping.style.display = 'none';
  }

  function scrollToBottom() {
    if (chatMessages) {
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
  }

  // Helper functions
  function formatDocumentTitle(doc) {
    const ext = doc.extracted || {};
    if (ext.bills && ext.bills.vendor_name) return ext.bills.vendor_name + ' Bill';
    if (ext.deadline && ext.deadline.title) return ext.deadline.title;
    if (ext.prescription && ext.prescription.medications && ext.prescription.medications[0]) return ext.prescription.medications[0].name + ' Rx';
    if (ext.labreport && ext.labreport.lab_name) return ext.labreport.lab_name + ' Lab Report';
    if (ext.plan && ext.plan.plan_title) return ext.plan.plan_title;
    return doc.filename || doc.original_filename || 'Scanned Document';
  }

  function getCategoryBadgeConfig(cat) {
    return matchCategory(cat);
  }

  function matchCategory(cat) {
    switch (cat) {
      case 'bills': return { icon: '⚡', label: 'Bill', class: 'doc-type--bills' };
      case 'deadline': return { icon: '⏰', label: 'Deadline', class: 'doc-type--deadline' };
      case 'prescription': return { icon: '💊', label: 'Rx', class: 'doc-type--prescription' };
      case 'labreport': return { icon: '🔬', label: 'Lab Report', class: 'doc-type--labreport' };
      case 'plan': return { icon: '📋', label: 'Plan', class: 'doc-type--plan' };
      default: return { icon: '📄', label: 'Doc', class: 'doc-type--plan' };
    }
  }

  function getCurrentTimeFormatted() {
    const now = new Date();
    return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function renderQuickPrompts(doc) {
    if (!quickPromptsContainer) return;
    quickPromptsContainer.innerHTML = '';

    let questions = doc.suggested_questions || [];
    if (typeof questions === 'string') {
      try { questions = JSON.parse(questions); } catch (e) { questions = []; }
    }

    if (!Array.isArray(questions) || questions.length === 0) {
      const cats = doc.categories || [doc.doc_type || 'plan'];
      questions = getDefaultCategoryQuestions(cats);
    }

    // Ensure maximum 3 questions
    questions = questions.slice(0, 3);

    questions.forEach((qText) => {
      if (!qText || typeof qText !== 'string') return;
      const trimmedQ = qText.trim();
      if (!trimmedQ) return;

      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'chat-chip';
      chip.setAttribute('data-prompt', trimmedQ);

      const icon = getPromptIcon(trimmedQ);
      chip.innerHTML = `<span>${icon}</span> ${escapeHtml(trimmedQ)}`;
      quickPromptsContainer.appendChild(chip);
    });
  }

  function getPromptIcon(promptText) {
    const textLower = (promptText || '').toLowerCase();
    if (textLower.includes('total') || textLower.includes('tax') || textLower.includes('price') || textLower.includes('bill') || textLower.includes('charge') || textLower.includes('vendor')) {
      return '⚡';
    }
    if (textLower.includes('due') || textLower.includes('date') || textLower.includes('deadline') || textLower.includes('time') || textLower.includes('when') || textLower.includes('priority')) {
      return '⏰';
    }
    if (textLower.includes('medication') || textLower.includes('rx') || textLower.includes('dosage') || textLower.includes('doctor') || textLower.includes('prescription') || textLower.includes('medicine')) {
      return '💊';
    }
    if (textLower.includes('lab') || textLower.includes('test') || textLower.includes('flag') || textLower.includes('range') || textLower.includes('result') || textLower.includes('abnormal')) {
      return '🔬';
    }
    if (textLower.includes('step') || textLower.includes('action') || textLower.includes('plan') || textLower.includes('task') || textLower.includes('assigned') || textLower.includes('note')) {
      return '📋';
    }
    return '✨';
  }

  function getDefaultCategoryQuestions(categories) {
    const defaultQs = [];
    (categories || []).forEach(cat => {
      switch (cat) {
        case 'bills':
          defaultQs.push('What is the grand total and payment due date?');
          break;
        case 'deadline':
          defaultQs.push('What is the deadline date and required action?');
          break;
        case 'prescription':
          defaultQs.push('What are the prescribed medications and dosages?');
          break;
        case 'labreport':
          defaultQs.push('Are any lab test results flagged as high or low?');
          break;
        case 'plan':
        default:
          defaultQs.push('What are the key action steps and notes?');
          break;
      }
    });

    if (defaultQs.length === 1) {
      const cat = categories[0] || 'plan';
      if (cat === 'bills') {
        defaultQs.push('Can you list all itemized charges and prices?');
        defaultQs.push('What is the tax amount and vendor name?');
      } else if (cat === 'deadline') {
        defaultQs.push('Who is the issuing organization?');
        defaultQs.push('What priority is assigned to this deadline?');
      } else if (cat === 'prescription') {
        defaultQs.push('How often should each medicine be taken?');
        defaultQs.push('Who prescribed this and are there special instructions?');
      } else if (cat === 'labreport') {
        defaultQs.push('What are the specific numerical test values and ranges?');
        defaultQs.push('Which test results were normal?');
      } else {
        defaultQs.push('Who is assigned to each task in the plan?');
        defaultQs.push('What is the target completion date?');
      }
    }

    return Array.from(new Set(defaultQs)).slice(0, 3);
  }

  function escapeHtml(text) {
    if (typeof text !== 'string') return text;
    return text.replace(/[&<>"']/g, (m) => {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
  }
});
