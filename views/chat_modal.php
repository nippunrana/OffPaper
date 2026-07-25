<?php
/**
 * Document AI Chat — Full-Screen Split Layout Modal
 * Left: conversation pane | Right: Finalised Plan panel (plan_assist mode)
 */
?>
<div id="docChatModal" class="doc-chat-modal" role="dialog" aria-modal="true" aria-labelledby="chatModalTitle" style="display: none;">
  <div class="doc-chat-modal__dialog">

    <!-- ─── HEADER ─────────────────────────────────────────────────── -->
    <header class="doc-chat-modal__header">
      <div class="doc-chat-modal__title-group">
        <div class="doc-chat-modal__avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/>
          </svg>
        </div>
        <div class="doc-chat-modal__title-block">
          <h2 id="chatModalTitle" class="doc-chat-modal__title">Document AI Assistant</h2>
          <div class="doc-chat-modal__sub">
            <span id="chatDocTitle" class="doc-chat-modal__doc-name">Document</span>
            <div id="chatDocBadges" class="doc-chat-modal__badges"></div>
          </div>
        </div>
      </div>

      <div class="doc-chat-modal__header-actions">
        <!-- Plan snapshot mini-pill (plan_assist only) — 1-line, click opens right panel -->
        <button type="button" id="docChatPlanPill" class="plan-pill" style="display:none;" aria-label="View Finalised Plan" title="View your latest saved plan">
          <span class="plan-pill__dot"></span>
          <span class="plan-pill__label">Plan saved</span>
          <span id="planPillDate" class="plan-pill__date"></span>
          <svg class="plan-pill__chevron" viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3l5 5-5 5"/></svg>
        </button>

        <button type="button" id="docChatNewChatBtn" class="doc-chat-modal__new-chat-btn" aria-label="Start new chat" style="display: none;" title="Clear chat history">
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
          New Chat
        </button>
        <button type="button" class="doc-chat-modal__close" data-close-doc-chat aria-label="Close AI Chat">&times;</button>
      </div>
    </header>

    <!-- ─── SPLIT BODY ─────────────────────────────────────────────── -->
    <div class="doc-chat-modal__body doc-chat-split" id="docChatSplitBody">

      <!-- LEFT: Conversation Pane -->
      <section class="chat-pane" aria-label="AI conversation">
        <!-- Quick Prompt chips -->
        <div class="doc-chat-prompts-wrapper">
          <span class="doc-chat-prompts__label">Suggested Prompts:</span>
          <div id="chatQuickPrompts" class="doc-chat-prompts">
            <button type="button" class="chat-chip" data-prompt="Summarize key points of this document">
              <span>✨</span> Summarize key points
            </button>
            <button type="button" class="chat-chip" data-prompt="What are the important dates or deadlines?">
              <span>⏰</span> Important dates
            </button>
            <button type="button" class="chat-chip" data-prompt="What actions do I need to take?">
              <span>✅</span> Action items
            </button>
          </div>
        </div>

        <!-- Messages log -->
        <div id="docChatMessages" class="doc-chat-messages" role="log" aria-live="polite"></div>

        <!-- Typing indicator -->
        <div id="docChatTyping" class="doc-chat-typing" style="display: none;">
          <div class="typing-bubble">
            <span class="typing-dot"></span>
            <span class="typing-dot"></span>
            <span class="typing-dot"></span>
          </div>
          <span class="typing-text">AI is reading document details...</span>
        </div>

        <!-- Input footer -->
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
              <button type="button" id="docChatFinalisePlanBtn" class="doc-chat-finalise-btn" aria-label="Finalise Plan" style="display: none;" title="Finalise the Plan — synthesise chat &amp; lock in the plan">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                <span>Finalise</span>
              </button>
              <button type="button" id="docChatMicBtn" class="doc-chat-mic-btn" aria-label="Voice input (Speech to text)" title="Speak your prompt (transcribed by Gemini Flash Lite)">
                <svg class="doc-chat-mic-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                  <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                  <line x1="12" y1="19" x2="12" y2="23"/>
                  <line x1="8" y1="23" x2="16" y2="23"/>
                </svg>
                <span class="doc-chat-mic-pulse"></span>
              </button>
              <button type="submit" id="docChatSendBtn" class="doc-chat-send-btn" aria-label="Send message" disabled>
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="22" y1="2" x2="11" y2="13"></line>
                  <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
              </button>
            </div>
          </form>
        </footer>
      </section>

      <!-- RIGHT: Plan Panel (plan_assist mode only, hidden by default) -->
      <aside id="docChatPlanPanel" class="plan-panel" aria-label="Finalised Plan" style="display:none;">
        <!-- Plan panel header -->
        <div class="plan-panel__header">
          <div class="plan-panel__header-left">
            <div class="plan-panel__icon-wrap" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
            </div>
            <span class="plan-panel__title">Finalised Plan</span>
          </div>
          <div class="plan-panel__header-right">
            <span id="planPanelDate" class="plan-panel__date-badge"></span>
            <button type="button" id="docChatPlanPanelClose" class="plan-panel__close" aria-label="Close plan panel" title="Close plan panel">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 19l-7-7 7-7"/></svg>
            </button>
          </div>
        </div>

        <!-- Snapshot version tabs (up to 3) -->
        <div id="planVersionTabs" class="plan-version-tabs" style="display:none;">
          <span class="plan-version-tabs__label">Versions:</span>
          <div class="plan-version-tabs__list" id="planVersionTabList"></div>
        </div>

        <!-- Plan content -->
        <div class="plan-panel__body" id="planPanelBody">
          <div class="plan-panel__empty">
            <div class="plan-panel__empty-icon" aria-hidden="true">🎯</div>
            <p class="plan-panel__empty-text">No plan finalised yet.</p>
            <p class="plan-panel__empty-hint">Chat with the AI, then click <strong>Finalise</strong> or say <em>"amazing, lets update the plan"</em> to lock in your plan here.</p>
          </div>
        </div>
      </aside>

    </div><!-- /.doc-chat-split -->
  </div><!-- /.doc-chat-modal__dialog -->
</div><!-- /#docChatModal -->
