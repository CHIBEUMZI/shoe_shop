<template>
  <div class="chatbot-wrapper">
    <!-- Toggle Button -->
    <button class="chat-toggle" @click="toggleOpen" :class="{ 'is-open': open }" :aria-expanded="open">
      <span class="chat-toggle-icon">
        <svg v-if="!open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </span>
      <span class="chat-toggle-text">Chat tư vấn giày</span>
      <span class="chat-toggle-badge" v-if="unreadCount > 0 && !open">{{ unreadCount }}</span>
      <span class="chat-toggle-pulse" v-if="!open"></span>
    </button>

    <!-- Chat Window -->
    <Transition name="chat-slide">
      <div v-if="open" class="chat-window" @click="unreadCount = 0">
        <!-- Header -->
        <div class="chat-header">
          <div class="chat-header-left">
            <div class="chat-avatar">
              <span class="chat-avatar-fallback">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
              </span>
              <span class="chat-status-dot"></span>
            </div>
            <div class="chat-title">
              <div class="chat-title-name">Trợ lý giày</div>
              <div class="chat-title-sub">
                <span class="chat-status-text">Online</span>
                <span class="chat-divider">•</span>
                <span>Trả lời trong vài giây</span>
              </div>
            </div>
          </div>
          <div class="chat-header-actions">
            <button type="button" class="chat-action-btn" @click="clearChat" title="Xóa lịch sử">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </button>
            <button type="button" class="chat-close" @click="open = false" title="Đóng">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>

        <!-- Messages -->
        <div class="chat-messages" ref="messagesEl">
          <!-- Welcome Message -->
          <div class="chat-welcome" v-if="messages.length <= 1">
            <div class="chat-welcome-icon">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <div class="chat-welcome-title">Chào bạn! 👋</div>
            <div class="chat-welcome-sub">Mình là trợ lý ảo của BMC Shoes</div>
            <div class="chat-welcome-hint">Bạn có thể hỏi về:</div>
          </div>

          <!-- Quick Suggestions -->
          <div class="chat-suggestions" v-if="messages.length <= 1">
            <button
              v-for="(suggestion, idx) in suggestions"
              :key="idx"
              class="chat-suggestion"
              @click="sendSuggestion(suggestion)"
            >
              <span class="chat-suggestion-icon">{{ suggestion.icon }}</span>
              <span>{{ suggestion.text }}</span>
            </button>
          </div>

          <!-- Message List -->
          <TransitionGroup name="msg-fade" tag="div" class="chat-messages-list">
            <div v-for="(m, i) in messages" :key="i" :class="['chat-msg', m.from]">
              <div v-if="m.from === 'bot'" class="chat-msg-avatar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
              </div>
              <div class="chat-msg-content">
                <div class="chat-msg-bubble">
                  <!-- Products Grid -->
                  <template v-if="m.from === 'bot' && m.custom?.type === 'products'">
                    <div class="chat-products">
                      <div v-if="m.custom?.title" class="chat-products-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                          <line x1="3" y1="6" x2="21" y2="6"></line>
                          <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        {{ m.custom.title }}
                      </div>
                      <div class="chat-products-grid" :class="`cols-${Math.min((m.custom.items || []).length, 2)}`">
                        <a
                          v-for="(it, ii) in m.custom.items || []"
                          :key="ii"
                          class="chat-product-card"
                          :href="it.url"
                          title="Xem chi tiết"
                        >
                          <div class="chat-product-img-wrap">
                            <img
                              class="chat-product-img"
                              :src="buildImageUrl(it.thumbnail)"
                              :alt="it.name"
                              loading="lazy"
                            />
                            <span v-if="it.discount" class="chat-product-badge">-{{ it.discount }}%</span>
                          </div>
                          <div class="chat-product-meta">
                            <div class="chat-product-name">{{ it.name }}</div>
                            <div class="chat-product-rating" v-if="it.rating">
                              <span class="chat-stars">★★★★★</span>
                              <span class="chat-review-count">({{ it.rating }})</span>
                            </div>
                            <div class="chat-product-price-row">
                              <span class="chat-product-price">{{ it.price_text || formatPrice(it.price) }}</span>
                              <span v-if="it.original_price" class="chat-product-original">{{ formatPrice(it.original_price) }}</span>
                            </div>
                          </div>
                        </a>
                      </div>
                    </div>
                  </template>

                  <!-- Chips/Categories -->
                  <template v-else-if="m.from === 'bot' && m.custom?.type === 'chips'">
                    <div class="chat-chips">
                      <div v-if="m.custom?.title" class="chat-products-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <rect x="3" y="3" width="7" height="7"></rect>
                          <rect x="14" y="3" width="7" height="7"></rect>
                          <rect x="14" y="14" width="7" height="7"></rect>
                          <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        {{ m.custom.title }}
                      </div>
                      <div class="chat-chips-wrap">
                        <a
                          v-for="(it, ii) in m.custom.items || []"
                          :key="ii"
                          class="chat-chip"
                          :href="it.href"
                          target="_blank"
                          rel="noopener noreferrer"
                        >
                          {{ it.label }}
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                          </svg>
                        </a>
                      </div>
                    </div>
                  </template>

                  <!-- Text with linkify & typing animation -->
                  <template v-if="m.from === 'bot' && m.typing !== undefined">
                    <span v-for="(p, pi) in linkify(m.displayedText)" :key="pi">
                      <a
                        v-if="p.type === 'link'"
                        class="chat-link"
                        :href="p.href"
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        {{ p.text }}
                      </a>
                      <span v-else>{{ p.text }}<span v-if="m.typing && pi === linkify(m.displayedText).length - 1" class="chat-cursor">|</span></span>
                    </span>
                  </template>
                  <template v-else-if="m.from === 'bot'">
                    <span v-for="(p, pi) in linkify(m.text)" :key="pi">
                      <a
                        v-if="p.type === 'link'"
                        class="chat-link"
                        :href="p.href"
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        {{ p.text }}
                      </a>
                      <span v-else>{{ p.text }}</span>
                    </span>
                  </template>
                  <template v-else>
                    {{ m.text }}
                  </template>
                </div>
                <div class="chat-msg-time">{{ formatTime(m.timestamp) }}</div>
              </div>
            </div>
          </TransitionGroup>

          <!-- Typing Indicator -->
          <div v-if="isTyping" class="chat-msg bot chat-typing">
            <div class="chat-msg-avatar">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
            </div>
            <div class="chat-msg-content">
              <div class="chat-msg-bubble chat-typing-bubble">
                <span class="chat-typing-dot"></span>
                <span class="chat-typing-dot"></span>
                <span class="chat-typing-dot"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Input -->
        <form class="chat-input" @submit.prevent="send">
          <div class="chat-input-wrap">
            <input
              v-model="input"
              placeholder="Hỏi về giày, size, tầm giá..."
              :disabled="sending"
              @keydown.enter.exact.prevent="send"
            />
            <button type="submit" class="chat-send-btn" :disabled="sending || !input.trim()">
              <svg v-if="!sending" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
              <span v-else class="chat-spinner"></span>
            </button>
          </div>
        </form>

        <!-- Footer -->
        <div class="chat-footer">
          <span>Powered by </span>
          <span class="chat-footer-brand">BMC Shoes</span>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { nextTick, ref, reactive, watch } from "vue";
import api from "../api";
import { buildImageUrl } from "../utils/image";

const open = ref(false);
const input = ref("");
const sending = ref(false);
const messagesEl = ref(null);
const typingTimeouts = ref([]);
const unreadCount = ref(0);
const isTyping = ref(false);

const suggestions = [
  { icon: "👟", text: "Giày đá bóng" },
  { icon: "👠", text: "Giày cao gót nữ" },
  { icon: "💰", text: "Giày dưới 500k" },
  { icon: "🏷️", text: "Khuyến mãi hôm nay" },
];

const messages = ref([
  {
    from: "bot",
    text: "Chào bạn, mình là trợ lý ảo của BMC Shoes, mình có thể giúp gì cho bạn hôm nay?",
    timestamp: new Date(),
  },
]);

function clearAllTypingTimeouts() {
  typingTimeouts.value.forEach(clearTimeout);
  typingTimeouts.value = [];
}

function toggleOpen() {
  open.value = !open.value;
  if (open.value) {
    unreadCount.value = 0;
    scrollToBottom();
  }
}

function scrollToBottom() {
  nextTick(() => {
    if (!messagesEl.value) return;
    messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
  });
}

function formatTime(date) {
  if (!date) return "";
  const d = new Date(date);
  return d.toLocaleTimeString("vi-VN", { hour: "2-digit", minute: "2-digit" });
}

function clearChat() {
  messages.value = [
    {
      from: "bot",
      text: "Chào bạn, mình là trợ lý ảo của BMC Shoes, mình có thể giúp gì cho bạn hôm nay?",
      timestamp: new Date(),
    },
  ];
}

async function sendSuggestion(suggestion) {
  input.value = suggestion.text;
  await send();
}

function linkify(text) {
  const raw = String(text ?? "");
  const urlRe = /(https?:\/\/[^\s]+)/g;
  const out = [];
  let last = 0;
  let m;

  while ((m = urlRe.exec(raw)) !== null) {
    if (m.index > last) out.push({ type: "text", text: raw.slice(last, m.index) });

    const href = m[0];
    out.push({ type: "link", href, text: href });
    last = m.index + href.length;
  }

  if (last < raw.length) out.push({ type: "text", text: raw.slice(last) });
  return out.length ? out : [{ type: "text", text: raw }];
}

function formatPrice(price) {
  if (price == null || price === "") return "";
  const n = Number(price);
  if (Number.isNaN(n)) return String(price);
  return new Intl.NumberFormat("vi-VN").format(n) + "đ";
}

async function send() {
  const text = input.value.trim();
  if (!text) return;

  messages.value.push({ from: "user", text, timestamp: new Date() });
  input.value = "";
  scrollToBottom();

  try {
    sending.value = true;
    isTyping.value = true;
    const res = await api.post("/api/v1/chatbot", { message: text });
    const data = res?.data ?? [];

    clearAllTypingTimeouts();
    isTyping.value = false;

    if (Array.isArray(data)) {
      data.forEach((msg) => {
        if (msg?.custom?.type === "products" || msg?.custom?.type === "chips") {
          messages.value.push({ from: "bot", custom: msg.custom, timestamp: new Date() });
        } else if (msg?.text) {
          const msgObj = { from: "bot", text: msg.text, displayedText: "", typing: true, timestamp: new Date() };
          messages.value.push(msgObj);
          animateText(msgObj);
        }
      });
    } else if (data?.text) {
      const msgObj = { from: "bot", text: data.text, displayedText: "", typing: true, timestamp: new Date() };
      messages.value.push(msgObj);
      animateText(msgObj);
    } else {
      const msgObj = {
        from: "bot",
        text: "Mình đã nhận được tin nhắn, bạn mô tả rõ hơn giúp mình nhé.",
        displayedText: "",
        typing: true,
        timestamp: new Date(),
      };
      messages.value.push(msgObj);
      animateText(msgObj);
    }
  } catch (e) {
    isTyping.value = false;
    const msgObj = {
      from: "bot",
      text: "Xin lỗi, hệ thống đang bận hoặc chatbot chưa sẵn sàng. Bạn thử lại sau nhé.",
      displayedText: "",
      typing: true,
      timestamp: new Date(),
    };
    messages.value.push(msgObj);
    animateText(msgObj);
  } finally {
    sending.value = false;
  }
}

function animateText(msgObj) {
  const msgReactive = reactive(msgObj);
  msgReactive.typing = true;

  let index = 0;
  const chars = msgReactive.text.split("");

  function addChar() {
    if (index < chars.length) {
      msgReactive.displayedText += chars[index];
      index++;
      scrollToBottom();
      const timeout = setTimeout(addChar, 18 + Math.random() * 12);
      typingTimeouts.value.push(timeout);
    } else {
      msgReactive.typing = false;
    }
  }

  const timeout = setTimeout(addChar, 200);
  typingTimeouts.value.push(timeout);
}
</script>

<style scoped>
/* === Variables === */
.chatbot-wrapper {
  --chat-primary: #6366f1;
  --chat-primary-dark: #4f46e5;
  --chat-primary-light: #818cf8;
  --chat-success: #22c55e;
  --chat-danger: #ef4444;
  --chat-bg: #f8fafc;
  --chat-white: #ffffff;
  --chat-text: #1e293b;
  --chat-text-muted: #64748b;
  --chat-border: rgba(0, 0, 0, 0.08);
  --chat-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  --chat-radius: 20px;
  --chat-radius-sm: 12px;
}

/* === Wrapper === */
.chatbot-wrapper {
  position: fixed;
  right: 20px;
  bottom: 20px;
  z-index: 9999;
}

/* === Toggle Button === */
.chat-toggle {
  background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
  color: var(--chat-white);
  border: none;
  border-radius: 999px;
  padding: 12px 20px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 8px 32px rgba(99, 102, 241, 0.4);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.chat-toggle::before {
  content: "";
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.chat-toggle:hover::before {
  left: 100%;
}

.chat-toggle:hover {
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 12px 40px rgba(99, 102, 241, 0.5);
}

.chat-toggle.is-open {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.chat-toggle-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-toggle-text {
  font-weight: 600;
  font-size: 14px;
  letter-spacing: 0.02em;
}

.chat-toggle-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: var(--chat-danger);
  color: white;
  font-size: 11px;
  font-weight: 700;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: badge-pop 0.3s ease;
}

@keyframes badge-pop {
  0% { transform: scale(0); }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

.chat-toggle-pulse {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 100%;
  height: 100%;
  border-radius: 999px;
  background: var(--chat-primary);
  transform: translate(-50%, -50%);
  animation: pulse-ring 2s infinite;
  z-index: -1;
}

@keyframes pulse-ring {
  0% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 0.5;
  }
  100% {
    transform: translate(-50%, -50%) scale(1.5);
    opacity: 0;
  }
}

/* === Chat Window === */
.chat-window {
  position: absolute;
  right: 0;
  bottom: 60px;
  width: 380px;
  max-height: 580px;
  background: var(--chat-white);
  border-radius: var(--chat-radius);
  box-shadow: var(--chat-shadow);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid var(--chat-border);
}

/* === Slide Animation === */
.chat-slide-enter-active {
  animation: slide-up 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.chat-slide-leave-active {
  animation: slide-down 0.3s cubic-bezier(0.4, 0, 1, 1);
}

@keyframes slide-up {
  0% {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes slide-down {
  0% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  100% {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
}

/* === Header === */
.chat-header {
  padding: 16px 16px;
  background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
  color: var(--chat-white);
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  overflow: hidden;
}

.chat-header::before {
  content: "";
  position: absolute;
  top: -50%;
  right: -20%;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
  border-radius: 50%;
}

.chat-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
  position: relative;
  z-index: 1;
}

.chat-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  backdrop-filter: blur(10px);
}

.chat-avatar img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.chat-avatar-fallback {
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-status-dot {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 12px;
  height: 12px;
  background: var(--chat-success);
  border: 2px solid white;
  border-radius: 50%;
  animation: status-pulse 2s infinite;
}

@keyframes status-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
  50% { box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
}

.chat-title {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.chat-title-name {
  font-weight: 700;
  font-size: 15px;
  letter-spacing: 0.01em;
}

.chat-title-sub {
  font-size: 12px;
  opacity: 0.85;
  display: flex;
  align-items: center;
  gap: 6px;
}

.chat-status-text {
  color: #86efac;
  font-weight: 600;
}

.chat-divider {
  opacity: 0.5;
}

.chat-header-actions {
  display: flex;
  gap: 8px;
  position: relative;
  z-index: 1;
}

.chat-action-btn,
.chat-close {
  background: rgba(255, 255, 255, 0.15);
  border: none;
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  backdrop-filter: blur(10px);
}

.chat-action-btn:hover,
.chat-close:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(1.05);
}

/* === Messages Area === */
.chat-messages {
  flex: 1;
  overflow-y: auto;
  background: var(--chat-bg);
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.chat-messages::-webkit-scrollbar {
  width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
  background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.15);
  border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.25);
}

/* === Welcome Section === */
.chat-welcome {
  text-align: center;
  padding: 24px 16px;
  margin-bottom: 8px;
}

.chat-welcome-icon {
  width: 72px;
  height: 72px;
  margin: 0 auto 16px;
  background: linear-gradient(135deg, var(--chat-primary-light) 0%, var(--chat-primary) 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
}

.chat-welcome-title {
  font-size: 20px;
  font-weight: 700;
  color: var(--chat-text);
  margin-bottom: 4px;
}

.chat-welcome-sub {
  font-size: 14px;
  color: var(--chat-text-muted);
  margin-bottom: 16px;
}

.chat-welcome-hint {
  font-size: 13px;
  color: var(--chat-text-muted);
  margin-bottom: 12px;
}

/* === Suggestions === */
.chat-suggestions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
  padding: 0 8px;
}

.chat-suggestion {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: var(--chat-white);
  border: 1px solid var(--chat-border);
  border-radius: 999px;
  font-size: 13px;
  font-weight: 500;
  color: var(--chat-text);
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.chat-suggestion:hover {
  background: linear-gradient(135deg, var(--chat-primary-light) 0%, var(--chat-primary) 100%);
  color: white;
  border-color: transparent;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.chat-suggestion-icon {
  font-size: 14px;
}

/* === Message List === */
.chat-messages-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* === Message === */
.chat-msg {
  display: flex;
  gap: 8px;
  max-width: 85%;
}

.chat-msg.user {
  margin-left: auto;
  flex-direction: row-reverse;
}

.chat-msg-avatar {
  width: 28px;
  height: 28px;
  min-width: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

.chat-msg.user .chat-msg-avatar {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
}

.chat-msg-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.chat-msg-bubble {
  padding: 10px 14px;
  border-radius: var(--chat-radius-sm);
  font-size: 14px;
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
}

.chat-msg.bot .chat-msg-bubble {
  background: var(--chat-white);
  color: var(--chat-text);
  border: 1px solid var(--chat-border);
  border-top-left-radius: 4px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.chat-msg.user .chat-msg-bubble {
  background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
  color: white;
  border-top-right-radius: 4px;
}

.chat-msg-time {
  font-size: 11px;
  color: var(--chat-text-muted);
  padding: 0 4px;
}

.chat-msg.user .chat-msg-time {
  text-align: right;
}

/* === Message Fade Animation === */
.msg-fade-enter-active {
  animation: msg-in 0.3s ease;
}

@keyframes msg-in {
  0% {
    opacity: 0;
    transform: translateY(10px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

/* === Typing Indicator === */
.chat-typing {
  animation: typing-in 0.3s ease;
}

@keyframes typing-in {
  0% { opacity: 0; transform: translateY(5px); }
  100% { opacity: 1; transform: translateY(0); }
}

.chat-typing-bubble {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 12px 16px;
}

.chat-typing-dot {
  width: 8px;
  height: 8px;
  background: var(--chat-text-muted);
  border-radius: 50%;
  animation: typing-bounce 1.4s infinite ease-in-out;
}

.chat-typing-dot:nth-child(1) { animation-delay: 0s; }
.chat-typing-dot:nth-child(2) { animation-delay: 0.2s; }
.chat-typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing-bounce {
  0%, 60%, 100% {
    transform: translateY(0);
    opacity: 0.4;
  }
  30% {
    transform: translateY(-4px);
    opacity: 1;
  }
}

/* === Link === */
.chat-link {
  color: var(--chat-primary);
  text-decoration: underline;
  text-underline-offset: 2px;
  transition: color 0.2s;
}

.chat-link:hover {
  color: var(--chat-primary-dark);
}

/* === Cursor === */
.chat-cursor {
  display: inline-block;
  animation: blink 0.7s infinite;
  color: var(--chat-primary);
  font-weight: 700;
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0; }
}

/* === Products === */
.chat-products {
  width: 100%;
}

.chat-products-title {
  font-weight: 700;
  font-size: 13px;
  margin-bottom: 10px;
  color: var(--chat-text);
  display: flex;
  align-items: center;
  gap: 6px;
}

.chat-products-grid {
  display: grid;
  gap: 10px;
}

.chat-products-grid.cols-1 {
  grid-template-columns: 1fr;
}

.chat-products-grid.cols-2 {
  grid-template-columns: repeat(2, 1fr);
}

.chat-product-card {
  display: flex;
  flex-direction: column;
  padding: 8px;
  border-radius: var(--chat-radius-sm);
  border: 1px solid var(--chat-border);
  background: var(--chat-bg);
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
  overflow: hidden;
}

.chat-product-card:hover {
  transform: translateY(-2px);
  border-color: var(--chat-primary);
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
}

.chat-product-img-wrap {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  background: #f1f5f9;
}

.chat-product-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.chat-product-card:hover .chat-product-img {
  transform: scale(1.05);
}

.chat-product-badge {
  position: absolute;
  top: 6px;
  left: 6px;
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 6px;
  border-radius: 4px;
}

.chat-product-meta {
  padding: 8px 4px 4px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.chat-product-name {
  font-size: 12px;
  font-weight: 600;
  color: var(--chat-text);
  overflow: hidden;
  display: -webkit-box;
  line-clamp: 2;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  line-height: 1.3;
}

.chat-product-rating {
  display: flex;
  align-items: center;
  gap: 4px;
}

.chat-stars {
  color: #fbbf24;
  font-size: 10px;
  letter-spacing: -1px;
}

.chat-review-count {
  font-size: 10px;
  color: var(--chat-text-muted);
}

.chat-product-price-row {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.chat-product-price {
  font-size: 13px;
  font-weight: 800;
  color: var(--chat-primary);
}

.chat-product-original {
  font-size: 11px;
  color: var(--chat-text-muted);
  text-decoration: line-through;
}

/* === Chips === */
.chat-chips {
  width: 100%;
}

.chat-chips-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.chat-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 999px;
  border: 1px solid var(--chat-border);
  background: var(--chat-white);
  color: var(--chat-text);
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
}

.chat-chip:hover {
  background: linear-gradient(135deg, var(--chat-primary-light) 0%, var(--chat-primary) 100%);
  color: white;
  border-color: transparent;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
}

/* === Input === */
.chat-input {
  padding: 12px 16px;
  background: var(--chat-white);
  border-top: 1px solid var(--chat-border);
}

.chat-input-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--chat-bg);
  border-radius: 999px;
  padding: 4px;
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

.chat-input-wrap:focus-within {
  border-color: var(--chat-primary);
  background: var(--chat-white);
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.chat-input input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 8px 12px;
  outline: none;
  font-size: 14px;
  color: var(--chat-text);
}

.chat-input input::placeholder {
  color: var(--chat-text-muted);
}

.chat-send-btn {
  width: 36px;
  height: 36px;
  border: none;
  background: linear-gradient(135deg, var(--chat-primary) 0%, var(--chat-primary-dark) 100%);
  color: white;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.chat-send-btn:hover:not(:disabled) {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.chat-send-btn:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
}

.chat-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* === Footer === */
.chat-footer {
  padding: 8px 16px;
  background: var(--chat-bg);
  text-align: center;
  font-size: 11px;
  color: var(--chat-text-muted);
  border-top: 1px solid var(--chat-border);
}

.chat-footer-brand {
  font-weight: 700;
  color: var(--chat-primary);
}

/* === Responsive === */
@media (max-width: 480px) {
  .chatbot-wrapper {
    right: 12px;
    bottom: 12px;
  }

  .chat-window {
    width: calc(100vw - 24px);
    max-width: 380px;
    right: -8px;
    max-height: calc(100vh - 100px);
  }

  .chat-toggle {
    padding: 10px 16px;
  }

  .chat-suggestions {
    justify-content: center;
  }

  .chat-products-grid.cols-2 {
    grid-template-columns: 1fr;
  }
}
</style>

