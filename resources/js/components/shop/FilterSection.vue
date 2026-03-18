<template>
  <div class="rounded-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
    <button
      type="button"
      class="w-full flex items-center justify-between px-3 py-3 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
      @click="$emit('update:open', !open)"
    >
      <span class="font-extrabold text-sm">{{ title }}</span>
      <span class="material-symbols-outlined text-[18px] text-slate-500">
        {{ open ? "expand_less" : "expand_more" }}
      </span>
    </button>

    <div v-show="open" class="px-3 pb-3">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: "" },
  open: { type: Boolean, default: true },
});
defineEmits(["update:open"]);
</script>