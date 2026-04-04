<template>
  <div ref="rootRef" class="w-full" :class="wrapperClass">
    <label v-if="label" :for="selectId" class="mb-1.5 block text-sm font-semibold text-slate-700">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <div class="relative">
      <button
        :id="selectId"
        type="button"
        :disabled="disabled"
        :class="[
          'flex min-h-[44px] w-full flex-wrap items-center gap-2 rounded-xl border bg-white px-4 py-2 text-left text-sm font-medium shadow-sm outline-none transition-all duration-200',
          'focus:ring-4 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400',
          error
            ? 'border-rose-300 text-rose-600 focus:border-rose-400 focus:ring-rose-100'
            : isOpen
              ? 'border-blue-500 text-slate-700 ring-4 ring-blue-100'
              : 'border-slate-200 text-slate-700 hover:border-blue-300 hover:bg-blue-50/40',
        ]"
        @click="toggleDropdown"
      >
        <template v-if="selectedItems.length > 0">
          <span
            v-for="item in displayItems"
            :key="item.value"
            class="inline-flex items-center gap-1 rounded-lg bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700"
          >
            {{ item.label }}
            <button
              type="button"
              class="ml-0.5 rounded-full hover:bg-blue-200"
              @click.stop="removeItem(item.value)"
            >
              <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </span>
          <span v-if="selectedItems.length > maxDisplay" class="text-xs text-slate-500">
            +{{ selectedItems.length - maxDisplay }} khác
          </span>
        </template>
        <span v-else class="text-slate-400">{{ placeholder || "Chọn dữ liệu" }}</span>

        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400 transition duration-200 ml-auto" :class="{ 'rotate-180 text-blue-500': isOpen }">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.512a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06Z" clip-rule="evenodd" />
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
          class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-lg"
          :style="{ maxHeight: maxHeight + 'px' }"
        >
          <div v-if="searchable" class="p-1">
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-blue-500"
              :placeholder="'Tìm kiếm...'"
              @click.stop
            />
          </div>

          <div v-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-slate-400">
            Không có dữ liệu
          </div>

          <button
            v-for="option in filteredOptions"
            :key="String(option.value)"
            type="button"
            :class="[
              'flex w-full items-center rounded-lg px-3 py-2 text-left text-sm transition-all duration-150',
              isSelected(option)
                ? 'bg-blue-600 font-semibold text-white'
                : 'text-slate-700 hover:bg-blue-50 hover:text-blue-600'
            ]"
            @click="toggleOption(option)"
          >
            <span class="mr-2 flex h-4 w-4 items-center justify-center rounded border" :class="isSelected(option) ? 'border-blue-600 bg-blue-600' : 'border-slate-300'">
              <svg v-if="isSelected(option)" class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </span>
            <span class="truncate">{{ option.label }}</span>
          </button>
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
import { computed, onBeforeUnmount, onMounted, ref, watch, nextTick } from "vue";

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
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
  wrapperClass: {
    type: String,
    default: "",
  },
  searchable: {
    type: Boolean,
    default: true,
  },
  maxDisplay: {
    type: Number,
    default: 3,
  },
  maxHeight: {
    type: Number,
    default: 250,
  },
  required: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue", "change"]);

const rootRef = ref(null);
const searchInput = ref(null);
const isOpen = ref(false);
const searchQuery = ref("");

const generatedId = `multiselect-${Math.random().toString(36).slice(2, 9)}`;

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
  if (!searchQuery.value) return normalizedOptions.value;
  const query = searchQuery.value.toLowerCase();
  return normalizedOptions.value.filter((opt) =>
    opt.label.toLowerCase().includes(query)
  );
});

const selectedItems = computed(() => {
  return normalizedOptions.value.filter((opt) =>
    (props.modelValue || []).includes(opt.value)
  );
});

const displayItems = computed(() => {
  return selectedItems.value.slice(0, props.maxDisplay);
});

function toggleDropdown() {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
  if (isOpen.value && props.searchable) {
    nextTick(() => searchInput.value?.focus());
  }
}

function closeDropdown() {
  isOpen.value = false;
  searchQuery.value = "";
}

function isSelected(option) {
  return (props.modelValue || []).includes(option.value);
}

function toggleOption(option) {
  const current = [...(props.modelValue || [])];
  const index = current.indexOf(option.value);

  if (index > -1) {
    current.splice(index, 1);
  } else {
    current.push(option.value);
  }

  emit("update:modelValue", current);
  emit("change", current);
}

function removeItem(value) {
  const current = [...(props.modelValue || [])];
  const index = current.indexOf(value);
  if (index > -1) {
    current.splice(index, 1);
    emit("update:modelValue", current);
    emit("change", current);
  }
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

watch(isOpen, (val) => {
  if (!val) searchQuery.value = "";
});
</script>
