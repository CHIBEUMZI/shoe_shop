<template>
  <main class="min-h-screen bg-slate-50 p-4 md:p-6">
    <!-- Header -->
    <section class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-2xl md:text-3xl font-black tracking-tight text-slate-900">
          Dashboard
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          Tổng quan hoạt động của website bán giày
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-[220px]">
          <BaseSelect
            v-model="selectedRange"
            :options="rangeOptions"
            size="md"
          />
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loading"
          @click="fetchDashboard"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
            <path d="M20 12A8 8 0 1 1 17.657 6.343" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M20 4V10H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Làm mới
        </button>
      </div>
    </section>

    <!-- Loading -->
    <section v-if="loading" class="space-y-6">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="n in 4"
          :key="'card-skeleton-' + n"
          class="h-32 animate-pulse rounded-3xl border border-slate-200 bg-white"
        ></div>
      </div>

      <div class="grid grid-cols-1 gap-6 2xl:grid-cols-12">
        <div class="h-96 animate-pulse rounded-3xl border border-slate-200 bg-white 2xl:col-span-7"></div>
        <div class="h-96 animate-pulse rounded-3xl border border-slate-200 bg-white 2xl:col-span-5"></div>
      </div>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="h-96 animate-pulse rounded-3xl border border-slate-200 bg-white xl:col-span-4"></div>
        <div class="h-96 animate-pulse rounded-3xl border border-slate-200 bg-white xl:col-span-5"></div>
        <div class="h-96 animate-pulse rounded-3xl border border-slate-200 bg-white xl:col-span-3"></div>
      </div>
    </section>

    <!-- Error -->
    <section
      v-else-if="error"
      class="rounded-3xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-700 shadow-sm"
    >
      <div class="font-bold">Không tải được dữ liệu dashboard</div>
      <div class="mt-1">{{ error }}</div>

      <button
        type="button"
        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700"
        @click="fetchDashboard"
      >
        Thử lại
      </button>
    </section>

    <!-- Content -->
    <template v-else>
      <!-- Stats -->
      <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="card in statsCards"
          :key="card.key"
          class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        >
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
              <h3 class="mt-3 text-2xl font-black tracking-tight text-slate-900">
                {{ card.value }}
              </h3>

              <div
                class="mt-3 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold"
                :class="
                  Number(card.trend) >= 0
                    ? 'bg-emerald-50 text-emerald-600'
                    : 'bg-rose-50 text-rose-600'
                "
              >
                <svg
                  v-if="Number(card.trend) >= 0"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-3.5 w-3.5"
                  viewBox="0 0 24 24"
                  fill="none"
                >
                  <path d="M7 14l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

                <svg
                  v-else
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-3.5 w-3.5"
                  viewBox="0 0 24 24"
                  fill="none"
                >
                  <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

                {{ Math.abs(Number(card.trend || 0)) }}% so với kỳ trước
              </div>
            </div>

            <div
              class="flex h-12 w-12 items-center justify-center rounded-2xl text-white shadow-sm"
              :class="card.iconBg"
            >
              <span v-html="card.icon"></span>
            </div>
          </div>

          <div
            class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r opacity-0 transition group-hover:opacity-100"
            :class="card.barBg"
          ></div>
        </article>
      </section>

      <!-- Row 1 -->
      <section class="mt-6 grid grid-cols-1 gap-6 2xl:grid-cols-12">
        <!-- Revenue chart -->
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm 2xl:col-span-7">
          <div class="mb-5 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-lg font-extrabold text-slate-900">Biểu đồ doanh thu</h2>
              <p class="text-sm text-slate-500">Doanh thu theo {{ revenueLabel }}</p>
            </div>

            <div class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
              Tổng: {{ formatCurrency(totalRevenueInChart) }}
            </div>
          </div>

          <template v-if="dashboard.chart.length">
            <div class="overflow-x-auto">
              <div class="flex min-w-[680px] items-end gap-4 pt-6">
                <div
                  v-for="item in dashboard.chart"
                  :key="item.label"
                  class="flex flex-1 flex-col items-center gap-3"
                >
                  <div class="flex h-72 w-full items-end">
                    <div
                      class="w-full rounded-t-2xl bg-gradient-to-t from-indigo-600 to-sky-400 transition hover:opacity-90"
                      :style="{ height: getBarHeight(item.value) }"
                      :title="`${item.label}: ${formatCurrency(item.value)}`"
                    ></div>
                  </div>

                  <div class="text-center">
                    <div class="text-xs font-bold text-slate-700">{{ item.label }}</div>
                    <div class="mt-1 text-[11px] text-slate-500">
                      {{ formatCompactCurrency(item.value) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div
            v-else
            class="flex h-80 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            Chưa có dữ liệu doanh thu
          </div>
        </article>

        <!-- Order status -->
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm 2xl:col-span-5">
          <div class="mb-5">
            <h2 class="text-lg font-extrabold text-slate-900">Trạng thái đơn hàng</h2>
            <p class="text-sm text-slate-500">Phân bố đơn hàng hiện tại</p>
          </div>

          <template v-if="dashboard.order_status.length">
            <div class="space-y-4">
              <div
                v-for="item in dashboard.order_status"
                :key="item.key"
                class="rounded-2xl border border-slate-100 bg-slate-50 p-4"
              >
                <div class="mb-2 flex items-center justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full" :class="item.dot"></span>
                    <span class="text-sm font-semibold text-slate-700">{{ item.label }}</span>
                  </div>
                  <span class="text-sm font-black text-slate-900">{{ item.count }}</span>
                </div>

                <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                  <div
                    class="h-full rounded-full transition-all"
                    :class="item.bar"
                    :style="{ width: `${getStatusPercent(item.count)}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </template>

          <div
            v-else
            class="flex h-80 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            Chưa có dữ liệu trạng thái đơn hàng
          </div>
        </article>
      </section>
      <!-- Row 2 -->
      <section class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-12">
        <!-- Top products -->
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-4">
          <div class="mb-5">
            <h2 class="text-lg font-extrabold text-slate-900">Top sản phẩm bán chạy</h2>
            <p class="text-sm text-slate-500">Sản phẩm có doanh số tốt nhất</p>
          </div>

          <template v-if="dashboard.top_products.length">
            <div class="space-y-4">
              <div
                v-for="(product, index) in dashboard.top_products"
                :key="product.id"
                class="flex items-center gap-3 rounded-2xl border border-slate-100 p-3 transition hover:bg-slate-50"
              >
                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-slate-100">
                  <img
                    v-if="product.thumbnail"
                    :src="buildImageUrl(product.thumbnail)"
                    :alt="product.name"
                    class="h-full w-full object-cover"
                  />
                  <span v-else class="text-xs font-bold text-slate-400">IMG</span>
                </div>

                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <p class="truncate text-sm font-bold text-slate-900">
                        {{ index + 1 }}. {{ product.name }}
                      </p>
                      <p class="mt-1 text-xs text-slate-500">
                        Đã bán {{ product.sold }} sản phẩm
                      </p>
                    </div>

                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-600">
                      #{{ index + 1 }}
                    </span>
                  </div>

                  <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                    <div
                      class="h-full rounded-full bg-gradient-to-r from-amber-400 to-orange-500"
                      :style="{ width: `${getTopProductPercent(product.sold)}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div
            v-else
            class="flex h-80 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            Chưa có dữ liệu sản phẩm bán chạy
          </div>
        </article>

        <!-- Recent orders -->
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-8">
          <div class="mb-5">
            <h2 class="text-lg font-extrabold text-slate-900">Đơn hàng mới</h2>
            <p class="text-sm text-slate-500">Danh sách đơn hàng gần đây</p>
          </div>

          <template v-if="dashboard.recent_orders.length">
            <div class="overflow-x-auto">
              <table class="min-w-full border-separate border-spacing-y-2">
                <thead>
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                      Mã đơn
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                      Khách hàng
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                      Tổng tiền
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                      Trạng thái
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="order in dashboard.recent_orders"
                    :key="order.id"
                    class="rounded-2xl bg-slate-50"
                  >
                    <td class="whitespace-nowrap rounded-l-2xl px-3 py-3 text-sm font-bold text-slate-900">
                      #{{ order.code }}
                    </td>
                    <td class="px-3 py-3 text-sm text-slate-700">
                      <div class="font-semibold">{{ order.customer_name }}</div>
                      <div class="text-xs text-slate-400">{{ order.created_at }}</div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-sm font-semibold text-slate-800">
                      {{ formatCurrency(order.total_amount) }}
                    </td>
                    <td class="rounded-r-2xl px-3 py-3">
                      <span
                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold"
                        :class="getOrderStatusClass(order.status)"
                      >
                        {{ order.status_label }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>

          <div
            v-else
            class="flex h-80 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            Chưa có đơn hàng gần đây
          </div>
        </article>

        <!-- New customers -->
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-12">
          <div class="mb-5">
            <h2 class="text-lg font-extrabold text-slate-900">Khách hàng mới</h2>
            <p class="text-sm text-slate-500">Người dùng đăng ký gần đây</p>
          </div>

          <template v-if="dashboard.new_customers.length">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
              <div
                v-for="customer in dashboard.new_customers"
                :key="customer.id"
                class="flex items-center gap-3 rounded-2xl border border-slate-100 p-3 transition hover:bg-slate-50"
              >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100">
                  <img
                    v-if="customer.avatar"
                    :src="buildImageUrl(customer.avatar)"
                    :alt="customer.name"
                    class="h-full w-full object-cover"
                  />
                  <span v-else class="text-xs font-bold text-slate-400">AVT</span>
                </div>

                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-bold text-slate-900">{{ customer.name }}</p>
                  <p class="truncate text-xs text-slate-500">{{ customer.email }}</p>
                </div>

                <div class="text-right text-xs text-slate-400">
                  {{ customer.created_at }}
                </div>
              </div>
            </div>
          </template>

          <div
            v-else
            class="flex h-48 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            Chưa có khách hàng mới
          </div>
        </article>
      </section>
    </template>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import dashboardAdminService from "../../../services/admin/dashboardAdminService";
import { buildImageUrl } from "../../../utils/image";
import BaseSelect from "../../../components/BaseSelect.vue";
const loading = ref(false);
const error = ref("");
const selectedRange = ref("30days");


const rangeOptions = [
  { label: "7 ngày gần đây", value: "7days" },
  { label: "30 ngày gần đây", value: "30days" },
  { label: "12 tháng gần đây", value: "12months" },
];

const defaultDashboard = () => ({
  overview: {
    revenue: 0,
    orders: 0,
    customers: 0,
    products: 0,
    revenue_growth: 0,
    orders_growth: 0,
    customers_growth: 0,
    products_growth: 0,
  },
  chart: [],
  top_products: [],
  recent_orders: [],
  order_status: [],
  new_customers: [],
});

const dashboard = ref(defaultDashboard());

const statsCards = computed(() => [
  {
    key: "revenue",
    label: "Tổng doanh thu",
    value: formatCurrency(dashboard.value.overview.revenue),
    trend: dashboard.value.overview.revenue_growth,
    iconBg: "bg-gradient-to-br from-emerald-500 to-teal-600",
    barBg: "from-emerald-400 to-teal-500",
    icon: `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
        <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    `,
  },
  {
    key: "orders",
    label: "Tổng đơn hàng",
    value: Number(dashboard.value.overview.orders || 0).toLocaleString("vi-VN"),
    trend: dashboard.value.overview.orders_growth,
    iconBg: "bg-gradient-to-br from-sky-500 to-indigo-600",
    barBg: "from-sky-400 to-indigo-500",
    icon: `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
        <path d="M3 6h19l-2 8H7L5 4H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="9" cy="19" r="1.5" fill="currentColor"/>
        <circle cx="18" cy="19" r="1.5" fill="currentColor"/>
      </svg>
    `,
  },
  {
    key: "customers",
    label: "Tổng khách hàng",
    value: Number(dashboard.value.overview.customers || 0).toLocaleString("vi-VN"),
    trend: dashboard.value.overview.customers_growth,
    iconBg: "bg-gradient-to-br from-violet-500 to-fuchsia-600",
    barBg: "from-violet-400 to-fuchsia-500",
    icon: `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
        <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11A4 4 0 1 0 9.5 3a4 4 0 0 0 0 8ZM21 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
  },
  {
    key: "products",
    label: "Tổng sản phẩm",
    value: Number(dashboard.value.overview.products || 0).toLocaleString("vi-VN"),
    trend: dashboard.value.overview.products_growth,
    iconBg: "bg-gradient-to-br from-amber-500 to-orange-600",
    barBg: "from-amber-400 to-orange-500",
    icon: `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none">
        <path d="M20 7 12 3 4 7l8 4 8-4ZM4 7v10l8 4 8-4V7M12 11v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
  },
]);

const revenueLabel = computed(() => {
  if (selectedRange.value === "7days") return "7 ngày";
  if (selectedRange.value === "30days") return "30 ngày";
  return "12 tháng";
});

const totalRevenueInChart = computed(() =>
  (dashboard.value.chart || []).reduce((sum, item) => sum + Number(item.value || 0), 0)
);

const maxChartValue = computed(() => {
  const values = (dashboard.value.chart || []).map((item) => Number(item.value || 0));
  return Math.max(...values, 1);
});

const maxTopSold = computed(() => {
  const values = (dashboard.value.top_products || []).map((item) => Number(item.sold || 0));
  return Math.max(...values, 1);
});

const totalStatusCount = computed(() =>
  (dashboard.value.order_status || []).reduce((sum, item) => sum + Number(item.count || 0), 0)
);

function formatCurrency(value) {
  return Number(value || 0).toLocaleString("vi-VN") + "đ";
}

function formatCompactCurrency(value) {
  const n = Number(value || 0);
  if (n >= 1000000) return (n / 1000000).toFixed(1).replace(".0", "") + "tr";
  if (n >= 1000) return (n / 1000).toFixed(0) + "k";
  return n.toLocaleString("vi-VN") + "đ";
}

function getBarHeight(value) {
  const height = (Number(value || 0) / maxChartValue.value) * 100;
  return `${Math.max(height, 8)}%`;
}

function getStatusPercent(count) {
  if (!totalStatusCount.value) return 0;
  return ((Number(count || 0) / totalStatusCount.value) * 100).toFixed(1);
}

function getTopProductPercent(sold) {
  return ((Number(sold || 0) / maxTopSold.value) * 100).toFixed(1);
}

function getInitials(name) {
  return String(name || "")
    .trim()
    .split(/\s+/)
    .slice(-2)
    .map((part) => part.charAt(0).toUpperCase())
    .join("");
}

function getOrderStatusClass(status) {
  const map = {
    pending: "bg-amber-50 text-amber-600",
    paid: "bg-sky-50 text-sky-600",
    processing: "bg-indigo-50 text-indigo-600",
    shipping: "bg-violet-50 text-violet-600",
    completed: "bg-emerald-50 text-emerald-600",
    cancelled: "bg-rose-50 text-rose-600",
  };

  return map[status] || "bg-slate-100 text-slate-600";
}



async function fetchDashboard() {
  try {
    loading.value = true;
    error.value = "";

    const res = await dashboardAdminService.overview({
      range: selectedRange.value,
    });

    dashboard.value = {
      ...defaultDashboard(),
      ...(res?.data?.data || {}),
      overview: {
        ...defaultDashboard().overview,
        ...(res?.data?.data?.overview || {}),
      },
      chart: Array.isArray(res?.data?.data?.chart) ? res.data.data.chart : [],
      top_products: Array.isArray(res?.data?.data?.top_products) ? res.data.data.top_products : [],
      recent_orders: Array.isArray(res?.data?.data?.recent_orders) ? res.data.data.recent_orders : [],
      order_status: Array.isArray(res?.data?.data?.order_status) ? res.data.data.order_status : [],
      new_customers: Array.isArray(res?.data?.data?.new_customers) ? res.data.data.new_customers : [],
    };
  } catch (err) {
    console.error("Lỗi tải dashboard:", err);
    error.value =
      err?.response?.data?.message ||
      err?.message ||
      "Đã có lỗi xảy ra khi tải dữ liệu.";
    dashboard.value = defaultDashboard();
  } finally {
    loading.value = false;
  }
}

watch(selectedRange, () => {
  fetchDashboard();
});

onMounted(() => {
  fetchDashboard();
});
</script>