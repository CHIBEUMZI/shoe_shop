<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import brandAdminService from "../../../services/admin/brandAdminService";
import BaseTable, { type TableColumn } from "../../../components/BaseTable.vue";
import { buildImageUrl } from "../../../utils/image";
import BaseSelect from "../../../components/BaseSelect.vue";
import { useAlert } from "../../../composables/useAlert";

const router = useRouter();
const route = useRoute();
const notify = useAlert();

const loading = ref(false);
const error = ref("");

const items = ref<any[]>([]);
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
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
const sortDir = ref<"asc" | "desc">(route.query.sort_dir === "desc" ? "desc" : "asc");

const fromToText = computed(() => {
  const total = Number(meta.value.total || 0);
  if (!total) return "0 kết quả";
  const cur = Number(meta.value.current_page || 1);
  const pp = Number(meta.value.per_page || perPage.value);
  const from = (cur - 1) * pp + 1;
  const to = Math.min(cur * pp, total);
  return `Hiển thị ${from}-${to} / ${total}`;
});

const columns = computed<TableColumn[]>(() => [
  { key: "logo", label: "Logo", width: "84px" },
  { key: "name", label: "Thương hiệu", sortable: true },
  { key: "status", label: "Trạng thái", width: "160px", sortable: true, align: "center" },
]);

const statusOptions = [
  { label: "Tất cả", value: "" },
  { label: "Hoạt động", value: "1" },
  { label: "Ngừng", value: "0" },
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

function statusText(s: any) {
  return Number(s) === 1 ? "Hoạt động" : "Ngừng";
}

async function fetchList() {
  loading.value = true;
  error.value = "";

  try {
    const { data } = await brandAdminService.list({
      search: q.value || undefined,
      status: status.value === "" ? undefined : Number(status.value),
      per_page: perPage.value,
      page: page.value,
      ...(sortBy.value ? { sort_by: sortBy.value, sort_dir: sortDir.value } : {}),
    });

    items.value = data.data || [];
    meta.value = data.meta || meta.value;
  } catch (e: any) {
    console.error(e);
    error.value = e?.response?.data?.message || "Không tải được danh sách thương hiệu";
  } finally {
    loading.value = false;
  }
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
  router.push("/admin/brands/create");
}

function goEdit(id: number) {
  router.push(`/admin/brands/${id}/edit`);
}

function goDetail(id: number) {
  router.push(`/admin/brands/${id}`);
}

async function onDelete(item: any) {
  const ok = window.confirm(`Bạn chắc chắn muốn xoá thương hiệu "${item?.name}"?`);
  if (!ok) return;

  loading.value = true;
  error.value = "";

  try {
    await brandAdminService.destroy(item.id);

    if (items.value.length <= 1 && page.value > 1) page.value -= 1;
    pushQuery();
    await fetchList();
    notify.success("Xoá thương hiệu thành công.", {
      title: "Xoá thành công",
      duration: 2500,
    });
  } catch (e: any) {
    notify.error(e?.response?.data?.message || "Xoá thất bại.", {
      title: "Xoá thất bại",
      duration: 2500,
    });
  } finally {
    loading.value = false;
  }
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
  if (e.key === "delete") return onDelete(e.item);
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

    fetchList();
  },
  { immediate: true }
);
</script>

<template>
  <div class="mx-auto max-w-[1200px] p-6">
    <div class="mb-4 flex items-end justify-between gap-4">
      <div>
        <div class="text-xs text-slate-500">Quản trị • Thương hiệu</div>
        <h2 class="m-0 text-2xl font-extrabold">Danh sách thương hiệu</h2>
        <div class="mt-1 text-xs text-slate-500">{{ fromToText }}</div>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          class="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          @click="goCreate"
        >
          + Tạo thương hiệu
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
      emptyText="Không có thương hiệu"
      searchPlaceholder="Tìm theo tên/slug..."
      :searchDebounceMs="300"
      :showPerPage="true"
      :perPageOptions="[10, 20, 50]"
      :actions="true"
      :rowActions="rowActions"
      @update:search="onSearch"
      @update:perPage="onPerPage"
      @sort="onSort"
      @page-change="onPageChange"
      @action="onAction"
    >
    <template #filters>
      <div class="flex flex-wrap items-center gap-2">
        <BaseSelect
          v-model="status"
          :options="statusOptions"
          size="sm"
          placeholder="Trạng thái"
          wrapperClass="!w-[170px] shrink-0"
          @change="onStatusChange"
        />

        <button
          class="h-10 shrink-0 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold hover:bg-slate-50 disabled:opacity-60"
          @click="resetFilters"
          :disabled="loading"
        >
          Làm mới
        </button>
      </div>
    </template>

      <template #cell-logo="{ item }">
        <div
          class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
        >
          <img
            v-if="item.logo"
            :src="buildImageUrl(item.logo)"
            class="h-full w-full object-cover"
            alt="logo"
          />
          <span v-else class="text-xs text-slate-500">No</span>
        </div>
      </template>

      <template #cell-name="{ item }">
        <div>
          <div class="font-extrabold text-slate-900">{{ item.name }}</div>
          <div class="mt-1 text-xs text-slate-500">Slug: {{ item.slug }}</div>
        </div>
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
          {{ statusText(value) }}
        </span>
      </template>
    </BaseTable>
  </div>
</template>