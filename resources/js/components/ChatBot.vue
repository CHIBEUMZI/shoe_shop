<template>
  <div class="chatbot-wrapper">
    <button class="chat-toggle" @click="toggleOpen" :aria-expanded="open">
      <span class="chat-toggle-dot" aria-hidden="true"></span>
      Chat tư vấn giày
    </button>

    <div v-if="open" class="chat-window">
      <div class="chat-header">
        <div class="chat-title">
          <div class="chat-title-name">Trợ lý giày</div>
          <div class="chat-title-sub">Tư vấn nhanh theo nhu cầu của bạn</div>
        </div>
        <button type="button" class="chat-close" @click="open = false">×</button>
      </div>

      <div class="chat-messages" ref="messagesEl">
        <div v-for="(m, i) in messages" :key="i" :class="['chat-msg', m.from]">
          <template v-if="m.from === 'bot' && m.custom?.type === 'products'">
            <div class="chat-products">
              <div v-if="m.custom?.title" class="chat-products-title">
                {{ m.custom.title }}
              </div>

              <div class="chat-products-grid">
                <a
                  v-for="(it, ii) in m.custom.items || []"
                  :key="ii"
                  class="chat-product-card"
                  :href="it.url"
                  title="Xem chi tiết"
                >
                  <img
                    class="chat-product-img"
                    :src="buildImageUrl(it.thumbnail)"
                    :alt="it.name"
                    loading="lazy"
                  />
                  <div class="chat-product-meta">
                    <div class="chat-product-name">{{ it.name }}</div>
                    <div class="chat-product-price">
                      {{ it.price_text || formatPrice(it.price) }}
                    </div>
                  </div>
                </a>
              </div>
            </div>
          </template>

          <template v-else-if="m.from === 'bot' && m.custom?.type === 'chips'">
            <div class="chat-chips">
              <div v-if="m.custom?.title" class="chat-products-title">
                {{ m.custom.title }}
              </div>
              <div class="chat-chips-wrap">
                <a
                  v-for="(it, ii) in m.custom.items || []"
                  :key="ii"
                  class="chat-chip"
                  :href="it.href"
                  title="Xem danh sách"
                >
                  {{ it.label }}
                </a>
              </div>
            </div>
          </template>

          <template v-else-if="m.from === 'bot'">
            <template v-for="(p, pi) in linkify(m.text)" :key="pi">
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
            </template>
          </template>
          <template v-else>
            {{ m.text }}
          </template>
        </div>
      </div>

      <form class="chat-input" @submit.prevent="send">
        <input v-model="input" placeholder="Hỏi về giày, size, tầm giá..." :disabled="sending" />
        <button type="submit" :disabled="sending || !input.trim()">Gửi</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { nextTick, ref } from "vue";
import api from "../api";
import { buildImageUrl } from "../utils/image";

const open = ref(false);
const input = ref("");
const sending = ref(false);
const messages = ref([
  {
    from: "bot",
    text: "Chào bạn, minh là trợ lý ảo của BMC Shoes, mình có thể giúp gì cho bạn hôm nay?",
  },
]);

const messagesEl = ref(null);

function toggleOpen() {
  open.value = !open.value;
  scrollToBottom();
}

function scrollToBottom() {
  nextTick(() => {
    if (!messagesEl.value) return;
    messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
  });
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

  messages.value.push({ from: "user", text });
  input.value = "";
  scrollToBottom();

  try {
    sending.value = true;
    const res = await api.post("/api/v1/chatbot", { message: text });
    const data = res?.data ?? [];

    if (Array.isArray(data)) {
      data.forEach((msg) => {
        if (msg?.custom?.type === "products" || msg?.custom?.type === "chips") {
          messages.value.push({ from: "bot", custom: msg.custom });
        } else if (msg?.text) {
          messages.value.push({ from: "bot", text: msg.text });
        }
      });
    } else if (data?.text) {
      messages.value.push({ from: "bot", text: data.text });
    } else {
      messages.value.push({
        from: "bot",
        text: "Mình đã nhận được tin nhắn, bạn mô tả rõ hơn giúp mình nhé.",
      });
    }
  } catch (e) {
    messages.value.push({
      from: "bot",
      text: "Xin lỗi, hệ thống đang bận hoặc chatbot chưa sẵn sàng. Bạn thử lại sau nhé.",
    });
  } finally {
    sending.value = false;
    scrollToBottom();
  }
}
</script>

<style scoped>
.chatbot-wrapper {
  position: fixed;
  right: 20px;
  bottom: 20px;
  z-index: 9999;
}

.chat-toggle {
  background: linear-gradient(135deg, #111827, #0f172a);
  color: #fff;
  border: none;
  border-radius: 999px;
  padding: 10px 14px;
  cursor: pointer;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.18);
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.chat-toggle-dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: #22c55e;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.18);
}

.chat-window {
  position: absolute;
  right: 0;
  bottom: 50px;
  width: 340px;
  max-height: 520px;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.22);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(17, 24, 39, 0.08);
}

.chat-header {
  padding: 10px 12px;
  background: linear-gradient(135deg, #111827, #0f172a);
  color: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chat-title {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.chat-title-name {
  font-weight: 700;
  font-size: 14px;
}

.chat-title-sub {
  font-size: 12px;
  opacity: 0.85;
}

.chat-close {
  background: transparent;
  border: none;
  color: #fff;
  font-size: 20px;
  line-height: 1;
  cursor: pointer;
}

.chat-messages {
  flex: 1;
  padding: 12px;
  overflow-y: auto;
  background: #f9fafb;
}

.chat-msg {
  max-width: 82%;
  padding: 9px 11px;
  border-radius: 12px;
  margin-bottom: 8px;
  font-size: 14px;
  line-height: 1.4;
  white-space: pre-wrap;
  word-break: break-word;
}

.chat-msg.user {
  background: #dbeafe;
  margin-left: auto;
  color: #111827;
}

.chat-msg.bot {
  background: #fff;
  margin-right: auto;
  border: 1px solid rgba(17, 24, 39, 0.08);
  color: #111827;
}

.chat-link {
  color: #2563eb;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.chat-link:hover {
  color: #1d4ed8;
}

.chat-products {
  width: 100%;
}

.chat-products-title {
  font-weight: 700;
  font-size: 13px;
  margin-bottom: 8px;
  color: #111827;
}

.chat-products-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
}

.chat-product-card {
  display: grid;
  grid-template-columns: 56px 1fr;
  gap: 10px;
  padding: 8px;
  border-radius: 12px;
  border: 1px solid rgba(17, 24, 39, 0.08);
  background: #fff;
  text-decoration: none;
  color: inherit;
  transition: transform 0.08s ease, border-color 0.08s ease, box-shadow 0.08s ease;
}

.chat-product-card:hover {
  transform: translateY(-1px);
  border-color: rgba(37, 99, 235, 0.35);
  box-shadow: 0 10px 18px rgba(15, 23, 42, 0.06);
}

.chat-product-img {
  width: 56px;
  height: 56px;
  border-radius: 10px;
  object-fit: cover;
  background: #f3f4f6;
}

.chat-product-meta {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.chat-product-name {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  overflow: hidden;
  display: -webkit-box;
  line-clamp: 2;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.chat-product-price {
  font-size: 12px;
  font-weight: 800;
  color: #2563eb;
}

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
  padding: 8px 10px;
  border-radius: 999px;
  border: 1px solid rgba(17, 24, 39, 0.12);
  background: #f8fafc;
  color: #0f172a;
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
  transition: background 0.08s ease, border-color 0.08s ease, transform 0.08s ease;
}

.chat-chip:hover {
  background: #eff6ff;
  border-color: rgba(37, 99, 235, 0.35);
  transform: translateY(-1px);
}

.chat-input {
  display: flex;
  border-top: 1px solid rgba(17, 24, 39, 0.08);
  background: #fff;
}

.chat-input input {
  flex: 1;
  border: none;
  padding: 10px 12px;
  outline: none;
}

.chat-input button {
  border: none;
  background: #2563eb;
  color: #fff;
  padding: 0 14px;
  cursor: pointer;
}

.chat-input button:disabled {
  background: #93c5fd;
  cursor: not-allowed;
}
</style>

