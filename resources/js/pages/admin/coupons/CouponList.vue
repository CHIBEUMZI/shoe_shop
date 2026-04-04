<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import couponAdminService from "../../../services/admin/couponAdminService";
import BaseTable, { type TableColumn } from "../../../components/BaseTable.vue";
import BaseSelect from "../../../components/BaseSelect.vue";
import ConfirmModal from "../../../components/ComfirmModal.vue";
import { useAlert } from "../../../composables/useAlert";

const router = useRouter();
const route = useRoute();
const notify = useAlert();

const showConfirm = ref(false);
const selectedItem = ref(null);
const loading = ref(false);
const error = ref("");

const items = ref<any[]>([]);
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

const confirmMessage = computed(() => {
  return `Bạn có chắc muốn xoá mã giảm giá "${selectedItem.value?.code || ''}" không?`;
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
  { key: "code", label: "Mã giảm giá", sortable: true, width: "220px" },
  { key: "value", label: "Giá trị", width: "140px", align: "center" as const },
  { key: "usage", label: "Đã dùng", width: "120px", align: "center" as const },
  { key: "condition", label: "Điều kiện", width: "180px" },
  { key: "time", label: "Thời gian", width: "180px" },
  { key: "status", label: "Trạng thái", width: "140px", sortable: true, align: "center" as const },
]);

const statusOptions = [
  { label: "Tất cả", value: "" },
  { label: "Hoạt động", value: "true" },
  { label: "Vô hiệu hóa", value: "false" },
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

function formatMoney(value: number) {
  if (!value) return "--";
  return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(value);
}

function formatDate(value: string) {
  if (!value) return "--";
  try {
    return new Date(value).toLocaleDateString("vi-VN");
  } catch {
    return "--";
  }
}

function statusText(item: any) {
  if (!item.is_active) return "Vô hiệu hoá";
  if (item.status === "Đã hết hạn" || item.status === "Đã dùng hết") return item.status;
  if (item.status === "Chưa bắt đầu") return item.status;
  return "Hoạt động";
}

function statusClass(item: any) {
  if (!item.is_active) return "border-slate-200 bg-slate-100 text-slate-700";
  if (item.status === "Đã hết hạn" || item.status === "Đã dùng hết") return "border-rose-200 bg-rose-50 text-rose-700";
  if (item.status === "Chưa bắt đầu") return "border-amber-200 bg-amber-50 text-amber-700";
  return "border-emerald-200 bg-emerald-50 text-emerald-700";
}

async function fetchList() {
  loading.value = true;
  error.value = "";

  try {
    const res = await couponAdminService.getCoupons({
      search: q.value || undefined,
      is_active: status.value === "" ? undefined : status.value === "true",
      page: page.value,
      per_page: perPage.value,
      ...(sortBy.value ? { sort_by: sortBy.value, sort_dir: sortDir.value } : {}),
    });

    const root = res?.data || {};
    items.value = root?.data || [];
    meta.value = {
      current_page: Number(root?.meta?.current_page || 1),
      last_page: Number(root?.meta?.last_page || 1),
      per_page: Number(root?.meta?.per_page || perPage.value),
      total: Number(root?.meta?.total || 0),
    };
  } catch (e: any) {
    console.error(e);
    error.value = e?.response?.data?.message || "Không tải được danh sách mã giảm giá";
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
  router.push("/admin/coupons/create");
}

function goEdit(id: number) {
  router.push(`/admin/coupons/${id}/edit`);
}

function goDetail(id: number) {
  router.push(`/admin/coupons/${id}`);
}

function onDelete(item: any) {
  if (item.used_count > 0) {
    notify.error("Không thể xoá mã giảm giá đã được sử dụng.", {
      title: "Không thể xoá",
      duration: 2500,
    });
    return;
  }
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
    await couponAdminService.deleteCoupon(id);

    if (items.value.length <= 1 && page.value > 1) {
      page.value -= 1;
    }

    pushQuery();
    await fetchList();
    notify.success("Xoá mã giảm giá thành công.", {
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
        <h2 class="m-0 text-2xl font-extrabold">Danh sách mã giảm giá</h2>
        <div class="mt-1 text-sm text-slate-500">Quản lý mã giảm giá thêm mới, chỉnh sửa, xóa</div>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          class="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          @click="goCreate"
        >
          + Tạo mã giảm giá
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
      emptyText="Không có mã giảm giá"
      searchPlaceholder="Tìm theo mã/tên..."
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

      <template #cell-code="{ item }">
        <div>
          <div class="flex items-center gap-2">
            <span class="inline-flex rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700">
              {{ item.code }}
            </span>
          </div>
          <div class="mt-1 font-semibold text-slate-900">{{ item.name }}</div>
        </div>
      </template>

      <template #cell-value="{ item }">
        <div>
          <span
            class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold"
            :class="item.type === 'percentage' ? 'bg-purple-100 text-purple-700' : 'bg-cyan-100 text-cyan-700'"
          >
            {{ item.value_formatted }}
          </span>
          <div v-if="item.type === 'percentage' && item.max_discount" class="mt-1 text-xs text-slate-500">
            Tối đa: {{ formatMoney(item.max_discount) }}
          </div>
        </div>
      </template>

      <template #cell-usage="{ item }">
        <div class="text-sm">
          <span class="font-semibold text-slate-900">{{ item.used_count }}</span>
          <span class="text-slate-500">/{{ item.usage_limit || '∞' }}</span>
          <div v-if="item.per_user_limit > 1" class="text-xs text-slate-500">
            {{ item.per_user_limit }}/user
          </div>
        </div>
      </template>

      <template #cell-condition="{ item }">
        <div class="space-y-1 text-xs text-slate-500">
          <div v-if="item.min_order_amount">
            Đơn tối thiểu: {{ formatMoney(item.min_order_amount) }}
          </div>
          <div>{{ item.applicable_to_label || '-' }}</div>
        </div>
      </template>

      <template #cell-time="{ item }">
        <div class="space-y-1 text-xs text-slate-500">
          <div v-if="item.starts_at">
            <span class="text-slate-400">Từ:</span> {{ formatDate(item.starts_at) }}
          </div>
          <div v-if="item.expires_at">
            <span class="text-slate-400">Đến:</span> {{ formatDate(item.expires_at) }}
          </div>
          <div v-if="!item.starts_at && !item.expires_at" class="text-slate-400">
            Không giới hạn
          </div>
        </div>
      </template>

      <template #cell-status="{ item }">
        <span
          class="inline-flex items-center rounded-full border px-2 py-1 text-xs font-bold"
          :class="statusClass(item)"
        >
          {{ statusText(item) }}
        </span>
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
