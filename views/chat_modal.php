<?php
/**
 * Document AI Chat Pop-over Modal Component
 */
?>
<div id="docChatModal" class="doc-chat-modal" role="dialog" aria-modal="true" aria-labelledby="chatModalTitle" style="display: none;">
  <div class="doc-chat-modal__dialog">
    <!-- Header -->
    <header class="doc-chat-modal__header">
      <div class="doc-chat-modal__title-group">
        <div class="doc-chat-modal__avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2a10 10 0 1 0 10 10H12V2z"/>
            <path d="M12 12 2.1 10.5"/>
            <path d="M12 12V22"/>
            <path d="M20 12a8 8 0 1 0-16 0"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </div>
        <div>
          <h2 id="chatModalTitle" class="doc-chat-modal__title">Document AI Assistant</h2>
          <div class="doc-chat-modal__sub">
            <span id="chatDocTitle" class="doc-chat-modal__doc-name">Document</span>
            <div id="chatDocBadges" class="doc-chat-modal__badges"></div>
          </div>
        </div>
      </div>
      <div class="doc-chat-modal__header-actions">
        <button type="button" id="docChatNewChatBtn" class="doc-chat-modal__new-chat-btn" aria-label="Start new chat" style="display: none;" title="Start a new chat (clears history)">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
          New Chat
        </button>
        <button type="button" class="doc-chat-modal__close" data-close-doc-chat aria-label="Close AI Chat">&times;</button>
      </div>
    </header>

    <!-- Body / Chat Log -->
    <div class="doc-chat-modal__body">
      <!-- Quick Prompt Suggestions -->
      <div class="doc-chat-prompts-wrapper">
        <span class="doc-chat-prompts__label">Suggested Prompts:</span>
        <div id="chatQuickPrompts" class="doc-chat-prompts">
          <button type="button" class="chat-chip" data-prompt="Summarize key points of this document">
            <span>✨</span> Summarize key points
          </button>
          <button type="button" class="chat-chip" data-prompt="What are the important dates or deadlines?">
            <span>⏰</span> Important dates
          </button>
          <button type="button" class="chat-chip" data-prompt="List all key numbers and financial totals">
            <span>📊</span> Key totals &amp; numbers
          </button>
          <button type="button" class="chat-chip" data-prompt="What actions do I need to take?">
            <span>✅</span> Action items
          </button>
        </div>
      </div>

      <!-- Plan Snapshot Banner (shown when a saved plan exists) -->
      <div id="docChatPlanBanner" class="doc-chat-plan-banner" style="display: none;" aria-live="polite"></div>

      <!-- Messages Stream -->
      <div id="docChatMessages" class="doc-chat-messages" role="log" aria-live="polite">
        <!-- Messages rendered dynamically via JS -->
      </div>

      <!-- Typing Indicator -->
      <div id="docChatTyping" class="doc-chat-typing" style="display: none;">
        <div class="typing-bubble">
          <span class="typing-dot"></span>
          <span class="typing-dot"></span>
          <span class="typing-dot"></span>
        </div>
        <span class="typing-text">AI is reading document details...</span>
      </div>
    </div>

    <!-- Footer / Input Form -->
    <footer class="doc-chat-modal__footer">
      <form id="docChatForm" class="doc-chat-form" onsubmit="return false;">
        <div class="doc-chat-input-wrapper">
          <input 
            type="text" 
            id="docChatInput" 
            class="doc-chat-input" 
            placeholder="Ask anything about this document..." 
            autocomplete="off"
            aria-label="Ask anything about this document"
          >
          <button type="button" id="docChatFinalisePlanBtn" class="doc-chat-finalise-btn" aria-label="Finalise Plan" style="display: none;" title="Finalise the Plan — study chat & lock in the plan">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            <span>Finalise</span>
          </button>
          <button type="submit" id="docChatSendBtn" class="doc-chat-send-btn" aria-label="Send message" disabled>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="22" y1="2" x2="11" y2="13"></line>
              <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
          </button>
        </div>
      </form>
    </footer>
  </div>
</div>
