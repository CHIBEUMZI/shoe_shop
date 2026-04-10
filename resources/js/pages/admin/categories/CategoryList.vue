<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import categoryAdminService from "../../../services/admin/categoryAdminService";
import BaseTable, { type TableColumn } from "../../../components/BaseTable.vue";
import BaseSelect from "../../../components/BaseSelect.vue";
import ConfirmModal from "../../../components/ComfirmModal.vue";
import { useAlert } from "../../../composables/useAlert";

const router = useRouter();
const route = useRoute();
const notify = useAlert();

const loading = ref(false);
const error = ref("");

const items = ref<any[]>([]);

const showConfirm = ref(false);
const selectedItem = ref<any>(null);

const confirmMessage = computed(() => {
  return `Bạn có chắc muốn xoá danh mục "${selectedItem.value?.name || ''}" không?`;
});

const q = ref(String(route.query.search || ""));
const status = ref(
  route.query.status === undefined || route.query.status === ""
    ? ""
    : String(route.query.status)
);
const perPage = ref(Number(route.query.per_page || 10));
const page = ref(Number(route.query.page || 1));

const sortBy = ref(String(route.query.sort_by || ""));
const sortDir = ref<"asc" | "desc">(
  route.query.sort_dir === "desc" ? "desc" : "asc"
);

const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

const columns = computed<TableColumn[]>(() => [
  { key: "name", label: "Tên danh mục", sortable: true },
  { key: "slug", label: "Slug", sortable: true },
  { key: "parent", label: "Danh mục cha" },
  { key: "sort_order", label: "Thứ tự", width: "110px", sortable: true, align: "center" },
  { key: "status", label: "Trạng thái", width: "150px", sortable: true, align: "center" },
  { key: "children_count", label: "Danh mục con", width: "140px", align: "center" },
  { key: "products_count", label: "Sản phẩm", width: "120px", align: "center" },
]);

const statusOptions = [
  { label: "Tất cả trạng thái", value: "" },
  { label: "Đang hoạt động", value: "1" },
  { label: "Tạm ngưng", value: "0" },
];

function pushQuery() {
  router.replace({
    path: route.path,
    query: {
      ...(q.value ? { search: q.value } : {}),
      ...(status.value === "" ? {} : { status: status.value }),
      ...(perPage.value ? { per_page: String(perPage.value) } : {}),
      ...(page.value ? { page: String(page.value) } : {}),
      ...(sortBy.value ? { sort_by: sortBy.value } : {}),
      ...(sortBy.value ? { sort_dir: sortDir.value } : {}),
    },
  });
}

async function fetchData() {
  loading.value = true;
  error.value = "";

  try {
    const { data } = await categoryAdminService.list({
      page: page.value,
      per_page: perPage.value,
      search: q.value || undefined,
      status: status.value === "" ? undefined : Number(status.value),
      ...(sortBy.value ? { sort_by: sortBy.value, sort_dir: sortDir.value } : {}),
    });

    items.value = data.data || [];

    const m = data.meta || {};
    meta.value = {
      current_page: Number(m.current_page || page.value || 1),
      last_page: Number(m.last_page || 1),
      per_page: Number(m.per_page || perPage.value || 10),
      total: Number(m.total || 0),
    };
  } catch (e: any) {
    console.error(e);
    error.value = e?.response?.data?.message || "Không tải được danh mục";
  } finally {
    loading.value = false;
  }
}

function removeItem(item: any) {
  selectedItem.value = item;
  showConfirm.value = true;
}

async function handleConfirmDelete() {
  if (!selectedItem.value) return;

  const id = selectedItem.value.id;
  showConfirm.value = false;

  loading.value = true;
  error.value = "";

  try {
    await categoryAdminService.remove(id);

    if (items.value.length <= 1 && page.value > 1) {
      page.value -= 1;
    }

    pushQuery();
    await fetchData();
    notify.success("Xoá danh mục thành công.", {
      title: "Xoá thành công",
      duration: 2500,
    });
    selectedItem.value = null;
  } catch (e: any) {
    notify.error(e?.response?.data?.message || "Xoá thất bại.", {
      title: "Xoá thất bại",
      duration: 2500,
    });
  } finally {
    loading.value = false;
  }
}

function onSearch(v: string) {
  q.value = v;
  page.value = 1;
  pushQuery();
}

function onPerPage(v: number) {
  perPage.value = v;
  page.value = 1;
  pushQuery();
}

function onPageChange(p: number) {
  page.value = p;
  pushQuery();
}

function onSort(payload: { sortBy: string; sortDir: "asc" | "desc" }) {
  sortBy.value = payload.sortBy;
  sortDir.value = payload.sortDir;
  page.value = 1;
  pushQuery();
}

function onStatusChange(value: string | number | boolean | null) {
  status.value = value === null || value === undefined ? "" : String(value);
  page.value = 1;
  pushQuery();
}

function resetFilters() {
  q.value = "";
  status.value = "";
  perPage.value = 10;
  page.value = 1;
  sortBy.value = "";
  sortDir.value = "asc";
  pushQuery();
}

function goCreate() {
  router.push("/admin/categories/create");
}

function goEdit(id: number) {
  router.push(`/admin/categories/${id}/edit`);
}

function goDetail(id: number) {
  router.push(`/admin/categories/${id}`);
}

const rowActions = computed(() => [
  {
    key: "view",
    icon: "visibility",
    title: "Xem",
    class: "border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100",
  },
  {
    key: "edit",
    icon: "edit",
    title: "Sửa",
    class: "border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100",
  },
  {
    key: "delete",
    icon: "delete",
    title: "Xoá",
    class: "border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100",
  },
]);

function onAction(e: { key: string; item: any }) {
  if (e.key === "view") return goDetail(e.item.id);
  if (e.key === "edit") return goEdit(e.item.id);
  if (e.key === "delete") return removeItem(e.item);
}

watch(
  () => route.query,
  () => {
    q.value = String(route.query.search || "");
    status.value =
      route.query.status === undefined || route.query.status === ""
        ? ""
        : String(route.query.status);
    perPage.value = Number(route.query.per_page || 10);
    page.value = Number(route.query.page || 1);
    sortBy.value = String(route.query.sort_by || "");
    sortDir.value = route.query.sort_dir === "desc" ? "desc" : "asc";

    fetchData();
  },
  { immediate: true }
);

function statusLabel(s: any) {
  return Number(s) === 1 ? "Đang hoạt động" : "Tạm ngưng";
}
</script>

<template>
  <div class="mx-auto max-w-[1200px] p-6">
    <div class="mb-4 flex items-end justify-between gap-4">
      <div>
        <div class="text-2xl font-extrabold">Danh mục</div>
        <div class="mt-1 text-sm text-slate-500">
          Quản lý danh mục (cha/con, thứ tự, trạng thái)
        </div>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          @click="goCreate"
        >
          + Tạo danh mục
        </button>
      </div>
    </div>

    <div
      v-if="error"
      class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"
    >
      <div class="font-extrabold">Có lỗi</div>
      <div class="mt-1 text-sm">{{ error }}</div>
    </div>

    <BaseTable
      :columns="columns"
      :items="items"
      :loading="loading"
      :pagination="meta"
      :search="q"
      :perPage="perPage"
      :sortBy="sortBy"
      :sortDir="sortDir"
      emptyText="Chưa có danh mục"
      searchPlaceholder="Tìm theo tên / slug..."
      :searchDebounceMs="250"
      :showPerPage="true"
      :perPageOptions="[10, 20, 50]"
      :actions="true"
      :rowActions="rowActions"
      @action="onAction"
      @update:search="onSearch"
      @update:perPage="onPerPage"
      @sort="onSort"
      @page-change="onPageChange"
    >
      <template #filters>
        <div class="flex flex-wrap items-center gap-2">
          <BaseSelect
            v-model="status"
            :options="statusOptions"
            size="sm"
            placeholder="Tất cả trạng thái"
            wrapperClass="!w-[190px] shrink-0"
            @change="onStatusChange"
          />

          <button
            class="h-10 shrink-0 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold hover:bg-slate-50 disabled:opacity-60"
            :disabled="loading"
            @click="resetFilters"
          >
            Làm mới
          </button>
        </div>
      </template>

      <template #cell-id="{ value }">
        <span class="font-semibold text-slate-500">#{{ value }}</span>
      </template>

      <template #cell-name="{ item }">
        <div>
          <div class="font-extrabold text-slate-900">{{ item.name }}</div>
          <div class="mt-1 text-xs text-slate-500">sort: {{ item.sort_order }}</div>
        </div>
      </template>

      <template #cell-parent="{ item }">
        <template v-if="item.parent?.name">
          <span class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700">
            <span>{{ item.parent.name }}</span>
          </span>
        </template>
        <span v-else class="text-slate-500">-</span>
      </template>

      <template #cell-sort_order="{ value }">
        <span class="inline-flex min-w-10 justify-center rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-bold text-slate-700">
          {{ value ?? 0 }}
        </span>
      </template>

      <template #cell-status="{ value }">
        <span
          class="inline-flex items-center rounded-full border px-2 py-1 text-xs font-bold"
          :class="
            Number(value) === 1
              ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
              : 'border-slate-200 bg-slate-100 text-slate-700'
          "
        >
          {{ statusLabel(value) }}
        </span>
      </template>

      <template #cell-children_count="{ value }">
        <span class="inline-flex min-w-10 justify-center rounded-full border border-slate-200 bg-white px-2 py-1 text-xs font-bold text-slate-700">
          {{ value ?? 0 }}
        </span>
      </template>

      <template #cell-products_count="{ value }">
        <span class="inline-flex min-w-10 justify-center rounded-full border border-slate-200 bg-white px-2 py-1 text-xs font-bold text-slate-700">
          {{ value ?? 0 }}
        </span>
      </template>

      <template #empty>
        <div class="mt-2 text-xs text-slate-500">
          Hãy tạo danh mục đầu tiên để bắt đầu.
        </div>
      </template>
    </BaseTable>
  </div>
  <ConfirmModal
    :visible="showConfirm"
    title="Xác nhận xoá"
    :message="confirmMessage"
    @confirm="handleConfirmDelete"
    @cancel="showConfirm = false"
  />
</template>