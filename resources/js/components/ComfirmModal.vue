<template>
  <Teleport to="body">
    <Transition name="backdrop">
      <div
        v-if="visible"
        :class="[
          'fixed inset-0 z-[9999] flex items-center justify-center',
          hideBackdrop ? '!bg-transparent' : 'bg-black/50 backdrop-blur-sm'
        ]"
        @click.self="onCancel"
      >
        <Transition name="dialog">
          <div
            v-if="visible"
            class="dialog-card"
            role="dialog"
            aria-modal="true"
          >
            <!-- TOP ACCENT BAR -->
            <div class="accent-bar" :class="accentBarClass" />

            <!-- BODY -->
            <div class="dialog-body">
              <!-- ICON -->
              <div class="icon-ring" :class="iconRingClass">
                <div class="icon-inner" :class="iconInnerClass">
                  <svg v-if="variant === 'success'" xmlns="http://www.w3.org/2000/svg" class="icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7" />
                  </svg>
                  <svg v-else-if="variant === 'warning'" xmlns="http://www.w3.org/2000/svg" class="icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v4m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.71 19h16.58a1 1 0 00.86-1.5l-7.5-13a1 1 0 00-1.72 0z" />
                  </svg>
                  <svg v-else-if="variant === 'info'" xmlns="http://www.w3.org/2000/svg" class="icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="icon-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </div>
              </div>

              <!-- TEXT -->
              <div class="dialog-text">
                <h2 class="dialog-title">{{ title }}</h2>
                <p class="dialog-message">{{ message }}</p>
              </div>
            </div>

            <!-- DIVIDER -->
            <div class="dialog-divider" />

            <!-- ACTIONS -->
            <div class="dialog-actions">
              <button class="btn-cancel" @click="onCancel">
                <svg xmlns="http://www.w3.org/2000/svg" class="btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Hủy bỏ
              </button>
              <button class="btn-confirm" :class="confirmBtnClass" @click="onConfirm">
                <svg v-if="variant === 'success'" xmlns="http://www.w3.org/2000/svg" class="btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else-if="variant === 'warning'" xmlns="http://www.w3.org/2000/svg" class="btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v4m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.71 19h16.58a1 1 0 00.86-1.5l-7.5-13a1 1 0 00-1.72 0z" />
                </svg>
                <svg v-else-if="variant === 'info'" xmlns="http://www.w3.org/2000/svg" class="btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="btn-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                {{ confirmText }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { defineProps, defineEmits, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  visible: Boolean,
  title: { type: String, default: 'Xác nhận thao tác' },
  message: { type: String, default: 'Bạn có chắc chắn muốn thực hiện hành động này không?' },
  variant: { type: String, default: 'danger' },
  hideBackdrop: { type: Boolean, default: false }
})

const emit = defineEmits(['confirm', 'cancel'])
const onConfirm = () => emit('confirm')
const onCancel = () => emit('cancel')

const accentBarClass = computed(() => `accent-${props.variant === 'danger' || !['success','warning','info'].includes(props.variant) ? 'danger' : props.variant}`)
const iconRingClass  = computed(() => `ring-${props.variant === 'danger' || !['success','warning','info'].includes(props.variant) ? 'danger' : props.variant}`)
const iconInnerClass = computed(() => `inner-${props.variant === 'danger' || !['success','warning','info'].includes(props.variant) ? 'danger' : props.variant}`)
const confirmBtnClass = computed(() => `confirm-${props.variant === 'danger' || !['success','warning','info'].includes(props.variant) ? 'danger' : props.variant}`)

const confirmText = computed(() => {
  switch (props.variant) {
    case 'success': return 'Phê duyệt'
    case 'warning': return 'Đồng ý'
    case 'info':    return 'Xác nhận'
    default:        return 'Xóa'
  }
})

const handleKey = (e) => { if (e.key === 'Escape') onCancel() }
onMounted(() => window.addEventListener('keydown', handleKey))
onBeforeUnmount(() => window.removeEventListener('keydown', handleKey))
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.dialog-card {
  font-family: 'Plus Jakarta Sans', sans-serif;
  position: relative;
  width: 420px;
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow:
    0 0 0 1px rgba(0,0,0,0.06),
    0 4px 6px -1px rgba(0,0,0,0.07),
    0 24px 48px -8px rgba(0,0,0,0.18);
}

/* Accent bar */
.accent-bar { height: 4px; width: 100%; }
.accent-success { background: linear-gradient(90deg, #16a34a, #4ade80); }
.accent-warning  { background: linear-gradient(90deg, #d97706, #fbbf24); }
.accent-info     { background: linear-gradient(90deg, #2563eb, #60a5fa); }
.accent-danger   { background: linear-gradient(90deg, #dc2626, #f87171); }

/* Body */
.dialog-body {
  display: flex;
  align-items: flex-start;
  gap: 18px;
  padding: 28px 28px 24px;
}

/* Icon ring */
.icon-ring {
  flex-shrink: 0;
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.ring-success { background: #f0fdf4; border: 1.5px solid #bbf7d0; }
.ring-warning  { background: #fffbeb; border: 1.5px solid #fde68a; }
.ring-info     { background: #eff6ff; border: 1.5px solid #bfdbfe; }
.ring-danger   { background: #fff1f2; border: 1.5px solid #fecdd3; }

.icon-inner {
  width: 36px; height: 36px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
}
.inner-success { background: #16a34a; }
.inner-warning  { background: #d97706; }
.inner-info     { background: #2563eb; }
.inner-danger   { background: #dc2626; }

.icon-svg { width: 18px; height: 18px; color: #fff; }

/* Text */
.dialog-text { flex: 1; padding-top: 2px; }
.dialog-title {
  font-size: 15px; font-weight: 700;
  color: #0f172a; letter-spacing: -0.2px;
  margin: 0 0 6px; line-height: 1.3;
}
.dialog-message {
  font-size: 13.5px; color: #64748b;
  line-height: 1.6; margin: 0; font-weight: 400;
}

/* Divider */
.dialog-divider { height: 1px; background: #f1f5f9; margin: 0 28px; }

/* Actions */
.dialog-actions {
  display: flex; justify-content: flex-end;
  gap: 10px; padding: 18px 28px 22px;
}

.btn-cancel {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px; border-radius: 8px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 13.5px; font-weight: 600;
  color: #475569; background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  cursor: pointer; transition: all 0.15s ease;
}
.btn-cancel:hover { background: #f1f5f9; border-color: #cbd5e1; color: #334155; }

.btn-confirm {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 20px; border-radius: 8px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 13.5px; font-weight: 600;
  color: #fff; border: none;
  cursor: pointer; transition: all 0.15s ease;
}
.btn-confirm:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.btn-confirm:active { transform: translateY(0); }

.confirm-success { background: linear-gradient(135deg, #16a34a, #15803d); }
.confirm-warning  { background: linear-gradient(135deg, #d97706, #b45309); }
.confirm-info     { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
.confirm-danger   { background: linear-gradient(135deg, #dc2626, #b91c1c); }

.btn-icon { width: 15px; height: 15px; flex-shrink: 0; }

/* Transitions */
.backdrop-enter-active, .backdrop-leave-active { transition: opacity 0.2s ease; }
.backdrop-enter-from, .backdrop-leave-to { opacity: 0; }

.dialog-enter-active { transition: all 0.22s cubic-bezier(0.34, 1.56, 0.64, 1); }
.dialog-leave-active { transition: all 0.15s ease-in; }
.dialog-enter-from { opacity: 0; transform: scale(0.88) translateY(12px); }
.dialog-leave-to   { opacity: 0; transform: scale(0.94) translateY(6px); }
</style>