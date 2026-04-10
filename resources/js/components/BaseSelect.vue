<template>
  <div ref="rootRef" class="w-full" :class="wrapperClass">
    <label
      v-if="label"
      :for="selectId"
      class="mb-1.5 block text-sm font-semibold text-slate-700"
    >
      {{ label }}
    </label>

    <div class="relative">
      <button
        :id="selectId"
        type="button"
        :disabled="disabled"
        :class="[
          'flex w-full items-center justify-between rounded-lg border bg-white px-4 text-left text-sm font-medium shadow-sm outline-none transition-all duration-200',
          'focus:ring-4 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400',
          error
            ? 'border-rose-300 text-rose-600 focus:border-rose-400 focus:ring-rose-100'
            : isOpen
              ? 'border-blue-500 text-slate-700 ring-4 ring-blue-100'
              : 'border-slate-200 text-slate-700 hover:border-blue-300 hover:bg-blue-50/40',
          sizeClass
        ]"
        @click="toggleDropdown"
      >
        <span
          class="block truncate pr-6"
          :class="selectedOption ? 'text-slate-700' : 'text-slate-400'"
        >
          {{ selectedOption ? selectedOption.label : placeholder || "Chọn dữ liệu" }}
        </span>

        <span
          class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400 transition duration-200"
          :class="{
            'rotate-180 text-blue-500': isOpen,
          }"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
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
          class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
        >
          <button
            v-if="clearable && modelValue !== '' && modelValue !== null && modelValue !== undefined"
            type="button"
            class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-slate-500 transition hover:bg-blue-50 hover:text-blue-600"
            @click="clearSelection"
          >
            {{ clearLabel }}
          </button>

          <button
            v-for="option in normalizedOptions"
            :key="String(option.value)"
            type="button"
            :class="[
              'flex w-full items-center rounded-lg px-3 py-2 text-left text-sm transition-all duration-150',
              isSelected(option)
                ? 'bg-blue-600 font-semibold text-white'
                : 'text-slate-700 hover:bg-blue-50 hover:text-blue-600'
            ]"
            @click="selectOption(option)"
          >
            <span class="truncate">{{ option.label }}</span>
          </button>

          <div
            v-if="!normalizedOptions.length"
            class="px-3 py-2 text-sm text-slate-400"
          >
            Không có dữ liệu
          </div>
        </div>
      </transition>
    </div>

    <p v-if="hint && !error" class="mt-1.5 text-xs text-slate-500">
      {{ hint }}
    </p>

    <p v-if="error" class="mt-1.5 text-xs font-medium text-rose-600">
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

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
});

const emit = defineEmits(["update:modelValue", "change"]);

const rootRef = ref(null);
const isOpen = ref(false);

const generatedId = `select-${Math.random().toString(36).slice(2, 9)}`;

const selectId = computed(() => {
  return props.id || generatedId;
});

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

const selectedOption = computed(() =>
  normalizedOptions.value.find((option) => option.value === props.modelValue) || null
);

const sizeClass = computed(() => {
  const map = {
    sm: "h-10 py-2",
    md: "h-11 py-2.5",
    lg: "h-12 py-3",
  };

  return map[props.size] || map.md;
});

function toggleDropdown() {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
}

function closeDropdown() {
  isOpen.value = false;
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
  if (!rootRef.value) return;
  if (!rootRef.value.contains(event.target)) {
    closeDropdown();
  }
}

function handleEscape(event) {
  if (event.key === "Escape") {
    closeDropdown();
  }
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  document.addEventListener("keydown", handleEscape);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
  document.removeEventListener("keydown", handleEscape);
});
</script>