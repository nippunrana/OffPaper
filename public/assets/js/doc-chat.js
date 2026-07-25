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

  let currentDocument = null;

  // Listen for open chat triggers on cards or detail modals
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-open-doc-chat]');
    if (!trigger) return;

    e.preventDefault();
    const rawData = trigger.getAttribute('data-open-doc-chat');
    if (!rawData) return;

    try {
      const doc = JSON.parse(rawData);
      openChatModal(doc);
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

  function openChatModal(doc) {
    currentDocument = doc;
    if (!chatModal) return;

    // Reset previous state & messages
    if (chatMessages) chatMessages.innerHTML = '';
    if (chatInput) {
      chatInput.value = '';
      if (sendBtn) sendBtn.disabled = true;
    }
    hideTyping();

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

    // Show modal
    chatModal.style.display = 'flex';
    requestAnimationFrame(() => {
      chatModal.classList.add('is-open');
    });
    document.body.style.overflow = 'hidden';

    // Render initial AI greeting message
    renderInitialGreeting(doc, docTitleText);

    // Focus input field
    setTimeout(() => {
      if (chatInput) chatInput.focus();
    }, 150);
  }

  function closeChatModal() {
    if (!chatModal) return;
    chatModal.classList.remove('is-open');
    setTimeout(() => {
      chatModal.style.display = 'none';
      document.body.style.overflow = '';
      currentDocument = null;
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
      const response = await fetch('/api/chat.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          document_id: currentDocument.id || 0,
          uuid: currentDocument.uuid || '',
          message: text
        })
      });

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

  function numberFormat(num) {
    return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function escapeHtml(text) {
    if (typeof text !== 'string') return text;
    return text.replace(/[&<>"']/g, (m) => {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
  }
});
