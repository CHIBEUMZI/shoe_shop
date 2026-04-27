<template>
  <div ref="rootRef" class="w-full" :class="wrapperClass">
    <label v-if="label" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300">
      {{ label }}
    </label>

    <div class="relative">
      <button
        :id="selectId"
        type="button"
        :disabled="disabled"
        :class="[
          'flex w-full items-center justify-between rounded-lg border bg-white px-4 text-left text-sm font-medium shadow-sm outline-none transition-all duration-200 dark:bg-slate-900 dark:text-slate-100',
          'focus:ring-4 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400',
          error
            ? 'border-red-300 text-red-600 focus:border-red-400 focus:ring-red-100 dark:border-red-500 dark:text-red-400'
            : isOpen
              ? 'border-primary text-slate-700 ring-4 ring-primary/20 dark:text-slate-100'
              : 'border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-100 hover:border-primary hover:bg-slate-50 dark:hover:bg-slate-800',
          sizeClass
        ]"
        @click="toggleDropdown"
      >
        <span
          class="block truncate pr-6"
          :class="selectedOption ? 'text-slate-700 dark:text-slate-100' : 'text-slate-400 dark:text-slate-500'"
        >
          {{ selectedOption ? selectedOption.label : placeholder || 'Chọn dữ liệu' }}
        </span>

        <span
          class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400 transition duration-200"
          :class="{ 'rotate-180 text-primary': isOpen }"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
            <path
              fill-rule="evenodd"
              d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z"
              clip-rule="evenodd"
            />
          </svg>
        </span>
      </button>

      <transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="translate-y-1 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-1 opacity-0"
      >
        <div
          v-if="isOpen && !disabled"
          class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800"
        >
          <div v-if="searchable" class="p-2 border-b border-slate-100 dark:border-slate-700">
            <div class="relative">
              <svg
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"
                />
              </svg>
              <input
                ref="searchInputRef"
                v-model="searchQuery"
                type="text"
                class="w-full rounded-md border border-slate-200 bg-slate-50 pl-9 pr-3 py-2 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500"
                placeholder="Tìm kiếm..."
                @keydown.esc="closeDropdown"
              />
              <button
                v-if="searchQuery"
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                @click.stop="searchQuery = ''"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div class="max-h-60 overflow-y-auto p-1">
            <button
              v-if="clearable && modelValue !== '' && modelValue !== null && modelValue !== undefined"
              type="button"
              class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-slate-500 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700"
              @click="clearSelection"
            >
              {{ clearLabel }}
            </button>

            <button
              v-for="option in filteredOptions"
              :key="String(option.value)"
              type="button"
              :class="[
                'flex w-full items-center rounded-lg px-3 py-2 text-left text-sm transition-all duration-150',
                isSelected(option)
                  ? 'bg-primary font-semibold text-white'
                  : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'
              ]"
              @click="selectOption(option)"
            >
              <span class="truncate">{{ option.label }}</span>
            </button>

            <div
              v-if="!filteredOptions.length"
              class="px-3 py-3 text-center text-sm text-slate-400 dark:text-slate-500"
            >
              Không tìm thấy kết quả
            </div>
          </div>
        </div>
      </transition>
    </div>

    <p v-if="hint && !error" class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ hint }}</p>

    <p v-if="error" class="mt-1.5 text-sm text-red-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean, null],
    default: "",
  },
  options: {
    type: Array,
    default: () => [],
  },
  label: {
    type: String,
    default: "",
  },
  placeholder: {
    type: String,
    default: "Chọn dữ liệu",
  },
  hint: {
    type: String,
    default: "",
  },
  error: {
    type: String,
    default: "",
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  id: {
    type: String,
    default: "",
  },
  size: {
    type: String,
    default: "md",
  },
  wrapperClass: {
    type: String,
    default: "",
  },
  clearable: {
    type: Boolean,
    default: false,
  },
  clearLabel: {
    type: String,
    default: "Bỏ chọn",
  },
  searchable: {
    type: Boolean,
    default: false,
  },
  searchPlaceholder: {
    type: String,
    default: "Tìm kiếm...",
  },
});

const emit = defineEmits(["update:modelValue", "change", "search"]);

const rootRef = ref(null);
const searchInputRef = ref(null);
const isOpen = ref(false);
const searchQuery = ref("");

const generatedId = `select-${Math.random().toString(36).slice(2, 9)}`;
const selectId = computed(() => props.id || generatedId);

const normalizedOptions = computed(() =>
  props.options.map((item) => {
    if (typeof item === "object" && item !== null) {
      return {
        label: item.label ?? String(item.value ?? ""),
        value: item.value ?? "",
      };
    }
    return { label: String(item), value: item };
  })
);

const filteredOptions = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return normalizedOptions.value;
  return normalizedOptions.value.filter((opt) => opt.label.toLowerCase().includes(q));
});

const selectedOption = computed(() =>
  normalizedOptions.value.find((option) => option.value === props.modelValue) || null
);

const sizeClass = computed(() => {
  const map = {
    sm: "h-10 py-2",
    md: "h-11 py-3",
    lg: "h-12 py-3",
  };
  return map[props.size] || map.md;
});

function toggleDropdown() {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
  if (isOpen.value && props.searchable) {
    nextTick(() => {
      searchInputRef.value?.focus();
    });
  }
}

function closeDropdown() {
  isOpen.value = false;
  searchQuery.value = "";
}

function selectOption(option) {
  emit("update:modelValue", option.value);
  emit("change", option.value);
  closeDropdown();
}

function clearSelection() {
  emit("update:modelValue", "");
  emit("change", "");
  closeDropdown();
}

function isSelected(option) {
  return option.value === props.modelValue;
}

function handleClickOutside(event) {
  if (!rootRef.value?.contains(event.target)) {
    closeDropdown();
  }
}

function handleEscape(event) {
  if (event.key === "Escape") closeDropdown();
}

watch(
  () => props.modelValue,
  () => {
    searchQuery.value = "";
  }
);

watch(searchQuery, (val) => {
  emit("search", val);
});

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  document.addEventListener("keydown", handleEscape);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
  document.removeEventListener("keydown", handleEscape);
});
</script>
