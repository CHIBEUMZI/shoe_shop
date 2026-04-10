<template>
  <div class="w-full">
    <!-- Toolbar -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
        <!-- Search -->
        <div v-if="searchable" class="w-full sm:w-80">
          <label class="sr-only">Search</label>
          <div class="relative">
            <input
              :value="internalSearch"
              @input="onSearchInput"
              type="text"
              class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm outline-none ring-0 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
              :placeholder="searchPlaceholder"
            />
            <button
              v-if="internalSearch"
              type="button"
              class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-2 py-1 text-xs text-slate-500 hover:bg-slate-50"
              @click="clearSearch"
              title="Clear"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Filters slot -->
        <div class="flex flex-wrap items-center gap-2">
          <slot name="filters" />
        </div>
      </div>

      <div class="flex items-center justify-between gap-3 sm:justify-end">
        <!-- Bulk actions -->
        <div v-if="selectable && selectedCount > 0" class="flex items-center gap-2">
          <span class="text-sm text-slate-600">
            Selected: <span class="font-medium text-slate-800">{{ selectedCount }}</span>
          </span>
          <slot name="bulk-actions" :selected-ids="selectedIds" :selected-items="selectedItems" />
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
            @click="clearSelection"
          >
            Clear
          </button>
        </div>

        <!-- Per page -->
        <div v-if="showPerPage" class="flex items-center gap-2">
          <span class="text-sm text-slate-600">Hàng</span>

          <BaseSelect
            :modelValue="String(internalPerPage)"
            :options="perPageSelectOptions"
            size="sm"
            wrapperClass="!w-[90px] shrink-0"
            @update:modelValue="onPerPageChange"
          />
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="sticky top-0 z-10 bg-slate-50">
            <tr class="text-slate-700">
              <!-- Selection -->
              <th v-if="selectable" class="w-12 px-4 py-3">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300"
                  :checked="allOnPageSelected"
                  :indeterminate="someOnPageSelected"
                  @change="toggleSelectAllOnPage(($event.target as HTMLInputElement).checked)"
                />
              </th>

              <th
                v-for="col in columnsResolved"
                :key="col.key"
                class="whitespace-nowrap px-4 py-3 font-semibold"
                :style="col.width ? { width: col.width } : undefined"
                :class="[
                  col.align === 'center' ? 'text-center' : '',
                  col.align === 'right' ? 'text-right' : '',
                  col.sortable ? 'cursor-pointer select-none hover:text-slate-900' : ''
                ]"
                @click="col.sortable ? onSort(col.key) : undefined"
              >
                <div class="flex items-center gap-2" :class="col.alignClass">
                  <span>{{ col.label }}</span>
                  <span v-if="col.sortable" class="text-xs text-slate-500">
                    <template v-if="sortBy === col.key">
                      {{ sortDir === "asc" ? "▲" : "▼" }}
                    </template>
                    <template v-else>↕</template>
                  </span>
                </div>
              </th>

              <!-- Actions -->
              <th v-if="hasActions" class="whitespace-nowrap px-4 py-3 text-right font-semibold">
                Actions
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <!-- Loading -->
            <tr v-if="loading">
              <td :colspan="colspan" class="px-4 py-10">
                <div class="flex items-center justify-center gap-3 text-slate-600">
                  <span class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-transparent"></span>
                  <span class="text-sm">Loading...</span>
                </div>
              </td>
            </tr>

            <!-- Empty -->
            <tr v-else-if="items.length === 0">
              <td :colspan="colspan" class="px-4 py-12 text-center text-slate-600">
                <div class="flex flex-col items-center gap-2">
                  <div class="text-sm">{{ emptyText }}</div>
                  <slot name="empty" />
                </div>
              </td>
            </tr>

            <!-- Rows -->
            <tr
              v-else
              v-for="(item, idx) in items"
              :key="getRowKey(item, idx)"
              class="hover:bg-slate-50/70"
            >
              <!-- Selection -->
              <td v-if="selectable" class="px-4 py-3">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300"
                  :checked="isSelected(item)"
                  @change="toggleRow(item)"
                />
              </td>

              <!-- Cells -->
              <td
                v-for="col in columnsResolved"
                :key="col.key"
                class="px-4 py-3 align-middle"
                :class="[
                  col.align === 'center' ? 'text-center' : '',
                  col.align === 'right' ? 'text-right' : ''
                ]"
              >
                <slot
                  :name="`cell-${col.key}`"
                  :item="item"
                  :value="(item as any)[col.key]"
                  :column="col"
                >
                  <span class="text-slate-800">
                    {{ formatCell((item as any)[col.key], col, item) }}
                  </span>
                </slot>
              </td>

              <!-- Actions -->
              <td v-if="hasActions" class="px-4 py-3 text-right">
                <div v-if="rowActionsResolved.length" class="flex justify-end gap-1.5">
                  <button
                    v-for="a in rowActionsResolved"
                    :key="a.key"
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border text-sm font-semibold transition"
                    :title="a.title || a.key"
                    :class="
                      a.class
                        ? a.class
                        : a.danger
                          ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100'
                          : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                    "
                    v-show="a.show ? a.show(item) : true"
                    @click="() => fireAction(a, item)"
                  >
                    <span class="material-symbols-outlined text-[20px] leading-none">
                      {{ a.icon }}
                    </span>
                  </button>
                </div>

                <slot v-else name="actions" :item="item" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer: pagination -->
      <div
        v-if="pagination"
        class="flex flex-col gap-3 border-t border-slate-100 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="text-sm text-slate-600">
          <template v-if="pagination.total > 0">
            Hiển thị
            <span class="font-medium text-slate-800">{{ from }}</span>
            –
            <span class="font-medium text-slate-800">{{ to }}</span>
            của
            <span class="font-medium text-slate-800">{{ pagination.total }}</span> 
            Bản ghi
          </template>
        </div>

        <div class="flex items-center justify-end gap-2">
          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="pagination.current_page <= 1 || loading"
            @click="goToPage(pagination.current_page - 1)"
          >
            Trước
          </button>

          <div class="flex items-center gap-1">
            <button
              v-for="p in pageButtons"
              :key="p.key"
              type="button"
              class="min-w-10 rounded-xl border px-3 py-2 text-sm"
              :class="
                p.type === 'page'
                  ? p.page === pagination.current_page
                    ? 'border-slate-900 bg-slate-900 text-white'
                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                  : 'cursor-default border-transparent bg-white text-slate-400'
              "
              :disabled="loading || p.type !== 'page'"
              @click="p.type === 'page' ? goToPage(p.page) : undefined"
            >
              {{ p.label }}
            </button>
          </div>

          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="pagination.current_page >= pagination.last_page || loading"
            @click="goToPage(pagination.current_page + 1)"
          >
            Sau
          </button>
        </div>
      </div>
    </div>

    <div class="mt-3">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, useSlots } from "vue";
import BaseSelect from "./BaseSelect.vue";

type Align = "left" | "center" | "right";

export type TableColumn = {
  key: string;
  label: string;
  sortable?: boolean;
  width?: string;
  align?: Align;
  formatter?: (value: any, item: any) => string;
};

export type PaginationMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

type SortDir = "asc" | "desc";

export type RowAction = {
  key: string;
  icon: string;
  title?: string;
  danger?: boolean;
  class?: string;
  show?: (item: any) => boolean;
};

const props = withDefaults(
  defineProps<{
    columns: TableColumn[];
    items: any[];
    loading?: boolean;
    emptyText?: string;

    sortBy?: string;
    sortDir?: SortDir;

    pagination?: PaginationMeta | null;

    searchable?: boolean;
    search?: string;
    searchPlaceholder?: string;
    searchDebounceMs?: number;

    showPerPage?: boolean;
    perPage?: number;
    perPageOptions?: number[];

    selectable?: boolean;
    rowKey?: string | ((item: any, index: number) => string | number);

    actions?: boolean;
    rowActions?: RowAction[];
  }>(),
  {
    loading: false,
    emptyText: "No data found",
    sortBy: "",
    sortDir: "asc",
    pagination: null,

    searchable: true,
    search: "",
    searchPlaceholder: "Search...",
    searchDebounceMs: 350,

    showPerPage: true,
    perPage: 10,
    perPageOptions: () => [10, 20, 50, 100],

    selectable: false,
    rowKey: "id",

    actions: true,
    rowActions: () => [],
  }
);

const emit = defineEmits<{
  (e: "update:search", value: string): void;
  (e: "update:perPage", value: number): void;
  (e: "sort", payload: { sortBy: string; sortDir: SortDir }): void;
  (e: "page-change", page: number): void;
  (e: "selection-change", payload: { ids: Array<string | number>; items: any[] }): void;
  (e: "action", payload: { key: string; item: any }): void;
}>();

const slots = useSlots();

const rowActionsResolved = computed<RowAction[]>(() => props.rowActions || []);
const hasActions = computed(
  () => props.actions && (rowActionsResolved.value.length > 0 || !!slots.actions)
);

const columnsResolved = computed(() =>
  props.columns.map((c) => ({
    ...c,
    align: (c.align ?? "left") as Align,
    alignClass:
      (c.align ?? "left") === "center"
        ? "justify-center"
        : (c.align ?? "left") === "right"
          ? "justify-end"
          : "justify-start",
  }))
);

const colspan = computed(() => {
  let n = columnsResolved.value.length;
  if (props.selectable) n += 1;
  if (hasActions.value) n += 1;
  return n;
});

const internalSearch = ref(props.search ?? "");

watch(
  () => props.search,
  (v) => {
    if (v !== undefined && v !== internalSearch.value) {
      internalSearch.value = v;
    }
  }
);

let searchTimer: number | null = null;

function onSearchInput(e: Event) {
  const val = (e.target as HTMLInputElement).value;
  internalSearch.value = val;

  if (searchTimer) window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(() => {
    emit("update:search", internalSearch.value.trim());
  }, props.searchDebounceMs);
}

function clearSearch() {
  internalSearch.value = "";
  emit("update:search", "");
}

const perPageOptionsResolved = computed(() => props.perPageOptions ?? [10, 20, 50, 100]);

const perPageSelectOptions = computed(() =>
  perPageOptionsResolved.value.map((n) => ({
    label: String(n),
    value: String(n),
  }))
);

const internalPerPage = ref<number>(props.perPage ?? 10);

watch(
  () => props.perPage,
  (v) => {
    if (typeof v === "number" && v !== internalPerPage.value) {
      internalPerPage.value = v;
    }
  }
);

function onPerPageChange(value: string | number | boolean | null) {
  const v = Number(value || 10);
  internalPerPage.value = v;
  emit("update:perPage", v);
}

const sortBy = computed(() => props.sortBy || "");
const sortDir = computed<SortDir>(() => props.sortDir || "asc");

function onSort(key: string) {
  const nextDir: SortDir =
    sortBy.value === key ? (sortDir.value === "asc" ? "desc" : "asc") : "asc";

  emit("sort", { sortBy: key, sortDir: nextDir });
}

const from = computed(() => {
  if (!props.pagination) return 0;
  const { current_page, per_page, total } = props.pagination;
  if (total <= 0) return 0;
  return (current_page - 1) * per_page + 1;
});

const to = computed(() => {
  if (!props.pagination) return 0;
  const { current_page, per_page, total } = props.pagination;
  if (total <= 0) return 0;
  return Math.min(current_page * per_page, total);
});

function goToPage(page: number) {
  if (!props.pagination) return;
  const p = Math.max(1, Math.min(page, props.pagination.last_page));
  if (p === props.pagination.current_page) return;
  emit("page-change", p);
}

type PageBtn =
  | { key: string; type: "page"; page: number; label: string }
  | { key: string; type: "dots"; label: string };

const pageButtons = computed<PageBtn[]>(() => {
  const meta = props.pagination;
  if (!meta) return [];

  const cur = meta.current_page;
  const last = meta.last_page;
  const btns: PageBtn[] = [];

  const addPage = (p: number) =>
    btns.push({ key: `p-${p}`, type: "page", page: p, label: String(p) });

  const addDots = (k: string) =>
    btns.push({ key: `d-${k}`, type: "dots", label: "…" });

  if (last <= 7) {
    for (let p = 1; p <= last; p++) addPage(p);
    return btns;
  }

  addPage(1);

  const left = Math.max(2, cur - 1);
  const right = Math.min(last - 1, cur + 1);

  if (left > 2) addDots("l");
  for (let p = left; p <= right; p++) addPage(p);
  if (right < last - 1) addDots("r");

  addPage(last);
  return btns;
});

function getRowKey(item: any, index: number): string | number {
  if (typeof props.rowKey === "function") return props.rowKey(item, index);
  const k = props.rowKey || "id";
  return item?.[k] ?? index;
}

function formatCell(value: any, col: TableColumn, item: any): string {
  if (col.formatter) return col.formatter(value, item);
  if (value === null || value === undefined) return "";
  if (typeof value === "object") return JSON.stringify(value);
  return String(value);
}

const selectedMap = ref(new Map<string | number, any>());

const selectedIds = computed(() => Array.from(selectedMap.value.keys()));
const selectedItems = computed(() => Array.from(selectedMap.value.values()));
const selectedCount = computed(() => selectedMap.value.size);

function isSelected(item: any) {
  const id = getRowKey(item, -1);
  return selectedMap.value.has(id);
}

function toggleRow(item: any) {
  const id = getRowKey(item, -1);

  if (selectedMap.value.has(id)) selectedMap.value.delete(id);
  else selectedMap.value.set(id, item);

  emit("selection-change", {
    ids: selectedIds.value,
    items: selectedItems.value,
  });
}

function clearSelection() {
  selectedMap.value.clear();
  emit("selection-change", { ids: [], items: [] });
}

const allOnPageSelected = computed(() => {
  if (!props.selectable || props.items.length === 0) return false;
  return props.items.every((it, idx) => selectedMap.value.has(getRowKey(it, idx)));
});

const someOnPageSelected = computed(() => {
  if (!props.selectable || props.items.length === 0) return false;

  const count = props.items.reduce(
    (acc, it, idx) => acc + (selectedMap.value.has(getRowKey(it, idx)) ? 1 : 0),
    0
  );

  return count > 0 && count < props.items.length;
});

function toggleSelectAllOnPage(checked: boolean) {
  if (!props.selectable) return;

  if (checked) {
    props.items.forEach((it, idx) => selectedMap.value.set(getRowKey(it, idx), it));
  } else {
    props.items.forEach((it, idx) => selectedMap.value.delete(getRowKey(it, idx)));
  }

  emit("selection-change", {
    ids: selectedIds.value,
    items: selectedItems.value,
  });
}

function fireAction(a: RowAction, item: any) {
  emit("action", { key: a.key, item });
}
</script>