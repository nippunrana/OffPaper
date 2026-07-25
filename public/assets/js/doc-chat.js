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

  function sendUserMessage(text) {
    appendMessage({
      sender: 'user',
      text: text,
      time: getCurrentTimeFormatted()
    });

    showTyping();

    // Generate contextual simulated response after typing delay
    setTimeout(() => {
      hideTyping();
      const reply = generateAiResponse(text, currentDocument);
      appendMessage({
        sender: 'ai',
        html: reply,
        time: getCurrentTimeFormatted()
      });
    }, 900);
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

  /**
   * Generates AI responses based on document metadata & user prompt
   */
  function generateAiResponse(prompt, doc) {
    if (!doc) {
      return '<p>I have analyzed this document. Feel free to ask specific questions about dates, amounts, or summaries!</p>';
    }

    const query = prompt.toLowerCase();
    const ext = doc.extracted || {};
    const summary = doc.summary || '';

    // 1. Summary Queries
    if (query.includes('summar') || query.includes('key point') || query.includes('overview')) {
      let res = `<p><strong>Document Summary:</strong></p>`;
      if (summary) {
        res += `<p>“${escapeHtml(summary)}”</p>`;
      }
      res += `<ul>`;
      if (doc.created_at) res += `<li><strong>Uploaded:</strong> ${escapeHtml(doc.created_at)}</li>`;
      if (doc.status) res += `<li><strong>Processing Status:</strong> ${escapeHtml(doc.status)}</li>`;
      if (doc.categories && doc.categories.length) {
        res += `<li><strong>Detected Categories:</strong> ${doc.categories.map(c => capitalize(c)).join(', ')}</li>`;
      }
      res += `</ul>`;
      return res;
    }

    // 2. Dates & Deadlines Queries
    if (query.includes('date') || query.includes('deadline') || query.includes('due') || query.includes('when')) {
      let res = `<p><strong>Important Dates &amp; Timelines:</strong></p><ul>`;
      let foundDate = false;

      if (ext.deadline) {
        if (ext.deadline.due_date) {
          res += `<li>⏰ <strong>Due Date:</strong> ${escapeHtml(ext.deadline.due_date)} ${ext.deadline.due_time ? '(' + escapeHtml(ext.deadline.due_time) + ')' : ''}</li>`;
          foundDate = true;
        }
        if (ext.deadline.priority) {
          res += `<li>⚠️ <strong>Priority Level:</strong> ${escapeHtml(ext.deadline.priority.toUpperCase())}</li>`;
        }
      }
      if (ext.bills) {
        if (ext.bills.due_date) {
          res += `<li>💳 <strong>Bill Due Date:</strong> ${escapeHtml(ext.bills.due_date)}</li>`;
          foundDate = true;
        }
        if (ext.bills.bill_date) {
          res += `<li>📅 <strong>Invoice Date:</strong> ${escapeHtml(ext.bills.bill_date)}</li>`;
          foundDate = true;
        }
      }
      if (ext.prescription && ext.prescription.rx_date) {
        res += `<li>💊 <strong>Rx Date:</strong> ${escapeHtml(ext.prescription.rx_date)}</li>`;
        foundDate = true;
      }
      if (ext.labreport && ext.labreport.report_date) {
        res += `<li>🔬 <strong>Report Date:</strong> ${escapeHtml(ext.labreport.report_date)}</li>`;
        foundDate = true;
      }

      if (!foundDate) {
        res += `<li>No specific due dates extracted from this document, uploaded on ${escapeHtml(doc.created_at || 'record')}.</li>`;
      }
      res += `</ul>`;
      return res;
    }

    // 3. Totals & Numbers Queries
    if (query.includes('number') || query.includes('total') || query.includes('amount') || query.includes('cost') || query.includes('price') || query.includes('tax')) {
      if (ext.bills) {
        const b = ext.bills;
        let res = `<p><strong>Financial &amp; Numerical Breakdown:</strong></p><ul>`;
        if (b.vendor_name) res += `<li><strong>Vendor:</strong> ${escapeHtml(b.vendor_name)}</li>`;
        if (b.invoice_number) res += `<li><strong>Invoice #:</strong> ${escapeHtml(b.invoice_number)}</li>`;
        if (b.subtotal) res += `<li><strong>Subtotal:</strong> $${numberFormat(b.subtotal)}</li>`;
        if (b.tax) res += `<li><strong>Tax:</strong> $${numberFormat(b.tax)}</li>`;
        if (b.grand_total) res += `<li><strong>Grand Total:</strong> <span style="color: var(--clay-600); font-weight:700;">$${numberFormat(b.grand_total)}</span></li>`;
        res += `</ul>`;

        if (b.line_items && b.line_items.length) {
          res += `<p><strong>Line Items (${b.line_items.length}):</strong></p><ul>`;
          b.line_items.forEach(item => {
            res += `<li>${escapeHtml(item.description || 'Item')} — ${item.quantity ? item.quantity + 'x ' : ''}$${numberFormat(item.price || 0)}</li>`;
          });
          res += `</ul>`;
        }
        return res;
      } else if (ext.labreport && ext.labreport.test_results) {
        let res = `<p><strong>Lab Test Results (${ext.labreport.test_results.length} metrics):</strong></p><ul>`;
        ext.labreport.test_results.forEach(t => {
          res += `<li><strong>${escapeHtml(t.test_name)}:</strong> ${escapeHtml(t.result_value)} ${escapeHtml(t.unit || '')} (Ref: ${escapeHtml(t.reference_range || 'N/A')}) [${escapeHtml(t.flag || 'Normal')}]</li>`;
        });
        res += `</ul>`;
        return res;
      } else {
        return `<p>There are no major monetary totals extracted for this document type. Here is the AI summary: <em>“${escapeHtml(summary || 'No numerical totals detected')}”</em>.</p>`;
      }
    }

    // 4. Action Items & Instructions
    if (query.includes('action') || query.includes('todo') || query.includes('do') || query.includes('instruction') || query.includes('medication')) {
      let res = `<p><strong>Required Actions &amp; Instructions:</strong></p><ul>`;
      let foundAction = false;

      if (ext.deadline && ext.deadline.action_required) {
        res += `<li>⚠️ <strong>Action Required:</strong> ${escapeHtml(ext.deadline.action_required)}</li>`;
        foundAction = true;
      }
      if (ext.prescription) {
        const rx = ext.prescription;
        if (rx.doctor_name) res += `<li>👨‍⚕️ <strong>Doctor:</strong> Dr. ${escapeHtml(rx.doctor_name)}</li>`;
        if (rx.medications && rx.medications.length) {
          foundAction = true;
          rx.medications.forEach(m => {
            res += `<li>💊 <strong>${escapeHtml(m.name)}:</strong> Dosage: ${escapeHtml(m.dosage || 'As prescribed')}, Frequency: ${escapeHtml(m.frequency || 'Daily')}</li>`;
          });
        }
      }
      if (ext.plan && ext.plan.action_items) {
        foundAction = true;
        ext.plan.action_items.forEach(a => {
          const itemText = typeof a === 'string' ? a : (a.task || JSON.stringify(a));
          res += `<li>📋 ${escapeHtml(itemText)}</li>`;
        });
      }

      if (!foundAction) {
        res += `<li>No explicit action checklist items found. Review summary: “${escapeHtml(summary)}”.</li>`;
      }
      res += `</ul>`;
      return res;
    }

    // Default intelligent fallthrough
    return `<p>Based on <strong>${escapeHtml(formatDocumentTitle(doc))}</strong>:</p><p>“${escapeHtml(summary || 'Document scanned successfully and indexed by OffPaper AI.')}”</p><p>You can ask me to break down financial numbers, check due dates, or list required action steps!</p>`;
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
