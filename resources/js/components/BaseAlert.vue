<template>
  <div
    :class="wrapperClass"
    class="pointer-events-auto relative overflow-hidden rounded-2xl border p-4 shadow-xl backdrop-blur-sm"
    role="alert"
  >
    <div class="flex items-start gap-3">
      <div
        :class="iconWrapClass"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
      >
        <span class="material-symbols-outlined text-[20px]">
          {{ icon }}
        </span>
      </div>

      <div class="min-w-0 flex-1">
        <p v-if="title" class="text-sm font-bold leading-5">
          {{ title }}
        </p>
        <p class="text-sm leading-6" :class="title ? 'mt-1' : ''">
          {{ message }}
        </p>
      </div>

      <button
        v-if="closable"
        type="button"
        class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-black/5 hover:text-slate-900"
        @click="$emit('close')"
      >
        <span class="material-symbols-outlined text-[18px]">close</span>
      </button>
    </div>

    <div
      v-if="duration > 0"
      class="absolute bottom-0 left-0 h-1 w-full bg-black/5"
    >
      <div
        :class="progressClass"
        class="h-full origin-left animate-progress"
        :style="{ animationDuration: `${duration}ms` }"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  type: {
    type: String,
    default: "info",
  },
  title: {
    type: String,
    default: "",
  },
  message: {
    type: String,
    default: "",
  },
  duration: {
    type: Number,
    default: 3000,
  },
  closable: {
    type: Boolean,
    default: true,
  },
});

defineEmits(["close"]);

const icon = computed(() => {
  switch (props.type) {
    case "success":
      return "check_circle";
    case "error":
      return "cancel";
    case "warning":
      return "warning";
    default:
      return "info";
  }
});

const wrapperClass = computed(() => {
  switch (props.type) {
    case "success":
      return "border-emerald-200 bg-white text-slate-700";
    case "error":
      return "border-rose-200 bg-white text-slate-700";
    case "warning":
      return "border-amber-200 bg-white text-slate-700";
    default:
      return "border-sky-200 bg-white text-slate-700";
  }
});

const iconWrapClass = computed(() => {
  switch (props.type) {
    case "success":
      return "bg-emerald-100 text-emerald-700";
    case "error":
      return "bg-rose-100 text-rose-700";
    case "warning":
      return "bg-amber-100 text-amber-700";
    default:
      return "bg-sky-100 text-sky-700";
  }
});

const progressClass = computed(() => {
  switch (props.type) {
    case "success":
      return "bg-emerald-500";
    case "error":
      return "bg-rose-500";
    case "warning":
      return "bg-amber-500";
    default:
      return "bg-sky-500";
  }
});
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: "FILL" 1, "wght" 500, "GRAD" 0, "opsz" 24;
}

.animate-progress {
  animation-name: shrink-bar;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}

@keyframes shrink-bar {
  from {
    transform: scaleX(1);
  }
  to {
    transform: scaleX(0);
  }
}
</style>