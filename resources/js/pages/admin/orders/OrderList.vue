<template>
  <main class="space-y-6">
    <section>
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-2xl font-black tracking-tight text-slate-900">Quản lý đơn hàng</h1>
          <p class="mt-1 text-sm text-slate-500">
            Theo dõi, xác nhận và cập nhật trạng thái đơn hàng.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 disabled:opacity-60"
            :disabled="loading"
            @click="exportExcel"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Xuất Excel
          </button>
          <button
            class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-rose-700 disabled:opacity-60"
            :disabled="loading"
            @click="exportPdf"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Xuất PDF
          </button>
        </div>
      </div>
    </section>

    <section>
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-sm text-slate-500">Tổng đơn</div>
          <div class="mt-2 text-2xl font-black text-slate-900">{{ meta.total }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-sm text-slate-500">Chờ xử lý</div>
          <div class="mt-2 text-2xl font-black text-amber-600">{{ countByStatus("pending") }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-sm text-slate-500">Đã xác nhận</div>
          <div class="mt-2 text-2xl font-black text-blue-600">{{ countByStatus("confirmed") }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-sm text-slate-500">Đang giao</div>
          <div class="mt-2 text-2xl font-black text-sky-600">{{ countByStatus("shipping") }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-sm text-slate-500">Hoàn thành</div>
          <div class="mt-2 text-2xl font-black text-green-600">{{ countByStatus("completed") }}</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-sm text-slate-500">Đã hủy</div>
          <div class="mt-2 text-2xl font-black text-red-600">{{ countByStatus("cancelled") }}</div>
        </div>
      </div>
    </section>

    <section>
      <BaseTable
        :columns="columns"
        :items="items"
        :loading="loading"
        empty-text="Không có đơn hàng nào"
        :searchable="true"
        :search="filters.search"
        search-placeholder="Tìm mã đơn, khách hàng, số điện thoại..."
        :sort-by="filters.sortBy"
        :sort-dir="filters.sortDir"
        :pagination="meta"
        :per-page="filters.per_page"
        :show-per-page="true"
        :actions="true"
        :row-actions="rowActions"
        @update:search="onSearchChange"
        @update:perPage="onPerPageChange"
        @sort="onSort"
        @page-change="onPageChange"
        @action="onRowAction"
      >
        <template #filters>
          <div class="flex flex-wrap items-center gap-2">
            <BaseSelect
              v-model="filters.status"
              :options="statusOptions"
              size="sm"
              placeholder="Tất cả trạng thái đơn"
              wrapperClass="!w-[200px] shrink-0"
              @change="onOrderStatusChange"
            />

            <BaseSelect
              v-model="filters.payment_status"
              :options="paymentStatusOptions"
              size="sm"
              placeholder="Tất cả thanh toán"
              wrapperClass="!w-[200px] shrink-0"
              @change="onPaymentStatusChange"
            />

            <button
              type="button"
              class="h-10 shrink-0 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
              @click="resetFilters"
            >
              Làm mới
            </button>
          </div>
        </template>

        <template #cell-code="{ item }">
          <div>
            <div class="font-bold text-slate-900">{{ item.code }}</div>
          </div>
        </template>

        <template #cell-customer="{ item }">
          <div>
            <div class="font-semibold text-slate-900">{{ item.customer_name }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ item.customer_phone }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ item.customer_email || "-" }}</div>
          </div>
        </template>

        <template #cell-payment="{ item }">
          <div>
            <div class="font-semibold text-slate-900">
              {{ paymentMethodText(item.payment_method) }}
            </div>
            <span
              class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-bold"
              :class="paymentStatusClass(item.payment_status)"
            >
              {{ paymentStatusText(item.payment_status) }}
            </span>
          </div>
        </template>

        <template #cell-status="{ item }">
          <div class="flex flex-col gap-1.5">
            <span
              class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold"
              :class="orderStatusClass(item.status)"
            >
              {{ orderStatusText(item.status) }}
            </span>
            <span
              v-if="item.cancellation_requested_at"
              class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-600"
            >
              <span class="text-[10px]">⚠️</span>
              Chờ hủy
            </span>
          </div>
        </template>

        <template #cell-grand_total="{ value }">
          <span class="font-bold text-slate-900">{{ moneyVND(value) }}</span>
        </template>

        <template #cell-created_at="{ value }">
          <span class="text-slate-500">{{ formatDateTime(value) }}</span>
        </template>
      </BaseTable>

      <div v-if="error" class="mt-4 text-sm text-red-600">
        {{ error }}
      </div>
    </section>
  </main>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import BaseTable from "../../../components/BaseTable.vue";
import BaseSelect from "../../../components/BaseSelect.vue";
import orderAdminService from "../../../services/admin/orderAdminService";
import orderExportService from "../../../services/admin/orderExportService";
import { useAlert } from "../../../composables/useAlert";
import { formatDateTime } from "../../../utils/date";

const router = useRouter();
const route = useRoute();
const notify = useAlert();

const loading = ref(false);
const error = ref("");
const actionLoadingId = ref(null);

const items = ref([]);

const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

const filters = reactive({
  search: String(route.query.search || ""),
  status: String(route.query.status || ""),
  payment_status: String(route.query.payment_status || ""),
  page: Number(route.query.page || 1),
  per_page: Number(route.query.per_page || 10),
  sortBy: String(route.query.sortBy || "created_at"),
  sortDir: String(route.query.sortDir || "desc"),
});

const statusOptions = [
  { label: "Tất cả trạng thái đơn", value: "" },
  { label: "Chờ xử lý", value: "pending" },
  { label: "Đã xác nhận", value: "confirmed" },
  { label: "Đang chuẩn bị", value: "processing" },
  { label: "Đang giao", value: "shipping" },
  { label: "Hoàn thành", value: "completed" },
  { label: "Đã hủy", value: "cancelled" },
];

const paymentStatusOptions = [
  { label: "Tất cả thanh toán", value: "" },
  { label: "Chưa thanh toán", value: "unpaid" },
  { label: "Chờ thanh toán", value: "pending" },
  { label: "Đã thanh toán", value: "paid" },
  { label: "Thất bại", value: "failed" },
  { label: "Hoàn tiền", value: "refunded" },
];

const columns = [
  { key: "code", label: "Mã đơn", sortable: true, width: "180px" },
  { key: "customer", label: "Khách hàng", width: "240px" },
  { key: "payment", label: "Thanh toán", width: "180px" },
  { key: "status", label: "Trạng thái đơn", sortable: true, width: "160px" },
  { key: "grand_total", label: "Tổng tiền", sortable: true, align: "right", width: "150px" },
  { key: "created_at", label: "Ngày tạo", sortable: true, width: "160px" },
];

const rowActions = [
  {
    key: "view",
    icon: "visibility",
    title: "Chi tiết",
    class: "border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100"
  },
];

onMounted(() => {
  fetchOrders();
});

async function fetchOrders() {
  loading.value = true;
  error.value = "";

  try {
    const res = await orderAdminService.list({
      search: filters.search || undefined,
      status: filters.status || undefined,
      payment_status: filters.payment_status || undefined,
      page: filters.page,
      per_page: filters.per_page,
      sort_by: filters.sortBy || undefined,
      sort_dir: filters.sortDir || undefined,
    });

    const payload = res?.data || {};
    items.value = payload.data || [];

    meta.value = {
      current_page: payload.meta?.current_page || 1,
      last_page: payload.meta?.last_page || 1,
      per_page: payload.meta?.per_page || filters.per_page,
      total: payload.meta?.total || 0,
    };
  } catch (e) {
    error.value = e?.response?.data?.message || "Không tải được danh sách đơn hàng.";
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  filters.page = 1;
  fetchOrders();
}

function resetFilters() {
  filters.search = "";
  filters.status = "";
  filters.payment_status = "";
  filters.page = 1;
  filters.per_page = 10;
  filters.sortBy = "created_at";
  filters.sortDir = "desc";
  fetchOrders();
}

function onOrderStatusChange(value) {
  filters.status = value == null ? "" : String(value);
  filters.page = 1;
  fetchOrders();
}

function onPaymentStatusChange(value) {
  filters.payment_status = value == null ? "" : String(value);
  filters.page = 1;
  fetchOrders();
}

function onSearchChange(value) {
  filters.search = value;
  filters.page = 1;
  fetchOrders();
}

function onPerPageChange(value) {
  filters.per_page = Number(value || 10);
  filters.page = 1;
  fetchOrders();
}

function onSort({ sortBy, sortDir }) {
  filters.sortBy = sortBy;
  filters.sortDir = sortDir;
  filters.page = 1;
  fetchOrders();
}

function onPageChange(page) {
  filters.page = page;
  fetchOrders();
}

async function onRowAction({ key, item }) {
  if (key === "view") {
    goDetail(item.id);
    return;
  }
}

function goDetail(id) {
  router.push(`/admin/orders/${id}`);
}

async function exportExcel() {
  loading.value = true;
  try {
    const response = await orderExportService.exportExcel({
      search: filters.search || undefined,
      status: filters.status || undefined,
      payment_status: filters.payment_status || undefined,
    });
    const blob = new Blob([response.data], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `danh-sach-don-hang-${new Date().toISOString().split("T")[0]}.xlsx`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    notify.success("Xuất Excel thành công!", { title: "Xuất file", duration: 2500 });
  } catch (e) {
    notify.error("Xuất Excel thất bại.", { title: "Lỗi", duration: 2500 });
  } finally {
    loading.value = false;
  }
}

async function exportPdf() {
  loading.value = true;
  try {
    const response = await orderExportService.exportPdf({
      search: filters.search || undefined,
      status: filters.status || undefined,
      payment_status: filters.payment_status || undefined,
    });
    const blob = new Blob([response.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `danh-sach-don-hang-${new Date().toISOString().split("T")[0]}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    notify.success("Xuất PDF thành công!", { title: "Xuất file", duration: 2500 });
  } catch (e) {
    notify.error("Xuất PDF thất bại.", { title: "Lỗi", duration: 2500 });
  } finally {
    loading.value = false;
  }
}

function countByStatus(status) {
  return items.value.filter((x) => x.status === status).length;
}

function moneyVND(v) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(Number(v || 0));
}

function paymentMethodText(v) {
  switch (v) {
    case "cod":
      return "COD";
    case "vnpay":
      return "VNPay";
    case "momo":
      return "MoMo";
    default:
      return v || "-";
  }
}

function paymentStatusText(v) {
  switch (v) {
    case "unpaid":
      return "Chưa thanh toán";
    case "pending":
      return "Chờ thanh toán";
    case "paid":
      return "Đã thanh toán";
    case "failed":
      return "Thất bại";
    case "refunded":
      return "Hoàn tiền";
    default:
      return v || "-";
  }
}

function orderStatusText(v) {
  switch (v) {
    case "pending":
      return "Chờ xử lý";
    case "confirmed":
      return "Đã xác nhận";
    case "processing":
      return "Đang chuẩn bị";
    case "shipping":
      return "Đang giao";
    case "completed":
      return "Hoàn thành";
    case "cancelled":
      return "Đã hủy";
    default:
      return v || "-";
  }
}

function paymentStatusClass(v) {
  switch (v) {
    case "unpaid":
      return "bg-slate-100 text-slate-700";
    case "pending":
      return "bg-amber-100 text-amber-700";
    case "paid":
      return "bg-green-100 text-green-700";
    case "failed":
      return "bg-red-100 text-red-700";
    case "refunded":
      return "bg-purple-100 text-purple-700";
    default:
      return "bg-slate-100 text-slate-700";
  }
}

function orderStatusClass(v) {
  switch (v) {
    case "pending":
      return "bg-amber-100 text-amber-700";
    case "confirmed":
      return "bg-blue-100 text-blue-700";
    case "processing":
      return "bg-indigo-100 text-indigo-700";
    case "shipping":
      return "bg-sky-100 text-sky-700";
    case "completed":
      return "bg-green-100 text-green-700";
    case "cancelled":
      return "bg-red-100 text-red-700";
    default:
      return "bg-slate-100 text-slate-700";
  }
}
</script>