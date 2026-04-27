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
            @change="fetchDashboard"
          />
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
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
          class="h-32 animate-pulse rounded-lg border border-slate-200 bg-white"
        ></div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="h-96 animate-pulse rounded-lg border border-slate-200 bg-white lg:col-span-7"></div>
        <div class="h-96 animate-pulse rounded-lg border border-slate-200 bg-white lg:col-span-5"></div>
      </div>

      <div class="h-48 animate-pulse rounded-lg border border-slate-200 bg-white"></div>
      <div class="h-80 animate-pulse rounded-lg border border-slate-200 bg-white"></div>
      <div class="h-48 animate-pulse rounded-lg border border-slate-200 bg-white"></div>
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

      <section class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Revenue chart -->
        <article class="relative overflow-hidden rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:col-span-7">
          <!-- Background decoration -->
          <div class="absolute right-0 top-0 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-50 to-transparent opacity-60"></div>

          <div class="mb-5 flex items-start justify-between gap-3">
            <div>
              <h2 class="text-lg font-extrabold text-slate-900">Biểu đồ doanh thu</h2>
              <p class="text-sm text-slate-500">Doanh thu theo {{ revenueLabel }}</p>
            </div>

            <div class="flex items-center gap-3">
              <div v-if="revenueTrend >= 0" class="flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                  <path d="M7 14l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                +{{ revenueTrend }}%
              </div>
              <div v-else class="flex items-center gap-1 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                  <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ revenueTrend }}%
              </div>

              <div class="rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-bold text-white">
                {{ formatCurrency(totalRevenueInChart) }}
              </div>
            </div>
          </div>

          <template v-if="dashboard.chart.length">
            <div class="overflow-x-auto">
              <!-- Y-axis labels -->
              <div class="relative flex min-w-[680px] gap-4 pl-12">
                <!-- Grid lines -->
                <div class="absolute inset-x-0 bottom-12 left-12 right-0 top-0 flex flex-col justify-between pointer-events-none">
                  <div v-for="i in 5" :key="i" class="border-b border-dashed border-slate-100"></div>
                </div>

                <!-- Y-axis -->
                <div class="absolute left-0 top-12 bottom-12 flex flex-col justify-between text-[10px] text-slate-400 font-medium">
                  <span>{{ formatCompactCurrency(maxChartValue) }}</span>
                  <span>{{ formatCompactCurrency(maxChartValue * 0.75) }}</span>
                  <span>{{ formatCompactCurrency(maxChartValue * 0.5) }}</span>
                  <span>{{ formatCompactCurrency(maxChartValue * 0.25) }}</span>
                  <span>0</span>
                </div>

                <!-- Bars container -->
                <div class="flex flex-1 items-end gap-4 pt-6">
                  <div
                    v-for="item in dashboard.chart"
                    :key="item.label"
                    class="group relative flex flex-1 flex-col items-center gap-3"
                  >
                    <!-- Tooltip -->
                    <div class="absolute -top-2 left-1/2 -translate-x-1/2 translate-y-full opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none z-10">
                      <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-center shadow-lg">
                        <div class="text-xs font-bold text-slate-900">{{ formatCurrency(item.value) }}</div>
                        <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 h-3 w-3 rotate-45 border-l border-t border-slate-200 bg-white"></div>
                      </div>
                    </div>

                    <!-- Bar -->
                    <div class="flex h-72 w-full items-end">
                      <div
                        class="group-hover:opacity-80 relative w-full rounded-t-2xl bg-gradient-to-t transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg cursor-pointer"
                        :style="{ height: getBarHeight(item.value) }"
                        :class="getBarGradient(item.value)"
                      >
                        <!-- Shine effect -->
                        <div class="absolute inset-0 rounded-t-2xl bg-gradient-to-t from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                      </div>
                    </div>

                    <!-- Label -->
                    <div class="text-center">
                      <div class="text-xs font-bold text-slate-700">{{ item.label }}</div>
                      <div class="mt-1 text-[11px] text-slate-500">
                        {{ formatCompactCurrency(item.value) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Legend -->
            <div class="mt-6 flex items-center justify-center gap-6 border-t border-slate-100 pt-4">
              <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-gradient-to-r from-indigo-600 to-sky-400"></div>
                <span class="text-xs font-medium text-slate-500">Doanh thu</span>
              </div>
            </div>
          </template>

          <div
            v-else
            class="flex h-80 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            <div class="text-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none">
                <path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7 12l4-4 4 4 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <p class="mt-2">Chưa có dữ liệu doanh thu</p>
            </div>
          </div>
        </article>

        <!-- Order status -->
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:col-span-5">
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
      <!-- Row 2: Top products (1 hàng) -->
      <section class="mt-6 grid grid-cols-1 gap-6">
        <!-- Top products -->
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-5">
            <h2 class="text-lg font-extrabold text-slate-900">Top sản phẩm bán chạy</h2>
            <p class="text-sm text-slate-500">Sản phẩm có doanh số tốt nhất</p>
          </div>

          <template v-if="dashboard.top_products.length">
            <div class="space-y-3">
              <div
                v-for="(product, index) in dashboard.top_products"
                :key="product.id"
                class="flex items-start gap-4 border border-slate-100 p-4 transition hover:bg-slate-50"
              >
                <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden bg-slate-100">
                  <img
                    v-if="product.thumbnail"
                    :src="buildImageUrl(product.thumbnail)"
                    :alt="product.name"
                    class="h-full w-full object-cover"
                  />
                  <span v-else class="text-sm font-bold text-slate-400">IMG</span>
                </div>

                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                      <p class="text-sm font-bold text-slate-900">
                        {{ index + 1 }}. {{ product.name }}
                      </p>
                      <p class="mt-1 text-xs text-slate-500">
                        Đã bán {{ product.sold }} sản phẩm
                      </p>
                    </div>
                    <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-600">
                      #{{ index + 1 }}
                    </span>
                  </div>

                  <!-- Top sizes và màu -->
                  <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span
                      v-for="(sizeItem, sIdx) in product.top_sizes"
                      :key="'size-' + product.id + '-' + sIdx"
                      class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-600"
                    >
                      {{ sizeItem.size }}
                    </span>
                    <span
                      v-for="(colorItem, cIdx) in product.top_colors"
                      :key="'color-' + product.id + '-' + cIdx"
                      class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                      :class="getColorClass(colorItem.color)"
                    >
                      <span class="h-2.5 w-2.5 rounded-full border border-black/10" :style="{ backgroundColor: getColorHex(colorItem.color) }"></span>
                      {{ colorItem.color }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div
            v-else
            class="flex h-48 items-center justify-center border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            Chưa có dữ liệu sản phẩm bán chạy
          </div>
        </article>
      </section>

      <!-- Row 3: Đơn hàng mới -->
      <section class="mt-6">
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-5">
            <h2 class="text-lg font-extrabold text-slate-900">Đơn hàng mới</h2>
            <p class="text-sm text-slate-500">Danh sách đơn hàng gần đây</p>
          </div>

          <template v-if="dashboard.recent_orders.length">
            <div class="overflow-x-auto">
              <table class="min-w-full">
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
                    class="bg-slate-50"
                  >
                    <td class="whitespace-nowrap px-3 py-3 text-sm font-bold text-slate-900">
                      #{{ order.code }}
                    </td>
                    <td class="px-3 py-3 text-sm text-slate-700">
                      <div class="font-semibold">{{ order.customer_name }}</div>
                      <div class="text-xs text-slate-400">{{ order.created_at }}</div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-3 text-sm font-semibold text-slate-800">
                      {{ formatCurrency(order.total_amount) }}
                    </td>
                    <td class="px-3 py-3">
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
            class="flex h-48 items-center justify-center border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            Chưa có đơn hàng gần đây
          </div>
        </article>
      </section>

      <!-- Row 4: Khách hàng mới -->
      <section class="mt-6">
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-5">
            <h2 class="text-lg font-extrabold text-slate-900">Khách hàng mới</h2>
            <p class="text-sm text-slate-500">Người dùng đăng ký gần đây</p>
          </div>

          <template v-if="dashboard.new_customers.length">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
              <div
                v-for="customer in dashboard.new_customers"
                :key="customer.id"
                class="flex items-center gap-3 border border-slate-100 p-3 transition hover:bg-slate-50"
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
            class="flex h-48 items-center justify-center border border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            Chưa có khách hàng mới
          </div>
        </article>
      </section>
    </template>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
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

const revenueTrend = computed(() => {
  return dashboard.value.overview.revenue_growth || 0;
});

const maxChartValue = computed(() => {
  const values = (dashboard.value.chart || []).map((item) => Number(item.value || 0));
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

function getBarGradient(value) {
  const ratio = Number(value || 0) / maxChartValue.value;
  if (ratio >= 0.75) return "from-indigo-600 to-violet-500";
  if (ratio >= 0.5) return "from-indigo-500 to-sky-500";
  if (ratio >= 0.25) return "from-sky-500 to-cyan-400";
  return "from-cyan-400 to-teal-400";
}

function getStatusPercent(count) {
  if (!totalStatusCount.value) return 0;
  return ((Number(count || 0) / totalStatusCount.value) * 100).toFixed(1);
}

// Bảng ánh xạ tên màu -> mã hex
const colorMap = {
  'đen': '#1f2937', 'đỏ': '#ef4444', 'xanh lá': '#22c55e', 'xanh dương': '#3b82f6',
  'vàng': '#eab308', 'trắng': '#f9fafb', 'nâu': '#92400e', 'xám': '#6b7280',
  'tím': '#a855f7', 'cam': '#f97316', 'hồng': '#ec4899', 'be': '#d6c5b3',
  'bạc': '#9ca3af', 'vàng gold': '#d4af37', 'xanh navy': '#1e3a5f',
  'black': '#1f2937', 'red': '#ef4444', 'green': '#22c55e', 'blue': '#3b82f6',
  'yellow': '#eab308', 'white': '#f9fafb', 'brown': '#92400e', 'gray': '#6b7280',
  'grey': '#6b7280', 'purple': '#a855f7', 'orange': '#f97316', 'pink': '#ec4899',
  'navy': '#1e3a5f', 'khaki': '#c3b091', 'olive': '#808000', 'teal': '#14b8a6',
  'maroon': '#800000', 'wine': '#722f37', 'cream': '#fffdd0', 'tan': '#d2b48c',
  'camel': '#c19a6b', 'burgundy': '#800020', 'mustard': '#ffdb58', 'coral': '#ff7f50',
  'lavender': '#e6e6fa', 'mint': '#98ff98', 'peach': '#ffcba4', 'sky': '#87ceeb',
  'lime': '#32cd32', 'gold': '#ffd700', 'silver': '#c0c0c0', 'bronze': '#cd7f32',
  'champagne': '#f7e7ce', 'emerald': '#50c878', 'ruby': '#e0115f', 'sapphire': '#0f52ba',
};

/**
 * Lấy mã hex từ tên màu
 * @param {string} colorName Tên màu
 * @returns {string} Mã hex hoặc màu xám mặc định
 */
function getColorHex(colorName) {
  if (!colorName) return '#9ca3af';
  const normalized = colorName.toLowerCase().trim();
  return colorMap[normalized] || '#9ca3af';
}

/**
 * Lấy class CSS cho badge màu dựa trên tên màu
 * @param {string} colorName Tên màu
 * @returns {object} Object chứa các class CSS Tailwind
 */
function getColorClass(colorName) {
  if (!colorName) return 'bg-slate-100 text-slate-600';
  const normalized = colorName.toLowerCase().trim();

  // Map tên màu -> cặp bg/text color tương ứng
  const colorClasses = {
    'đen': 'bg-slate-800 text-white', 'black': 'bg-slate-800 text-white',
    'đỏ': 'bg-red-100 text-red-700', 'red': 'bg-red-100 text-red-700',
    'xanh lá': 'bg-green-100 text-green-700', 'green': 'bg-green-100 text-green-700',
    'xanh dương': 'bg-blue-100 text-blue-700', 'blue': 'bg-blue-100 text-blue-700',
    'vàng': 'bg-yellow-100 text-yellow-700', 'yellow': 'bg-yellow-100 text-yellow-700',
    'trắng': 'bg-white text-slate-700 border border-slate-200', 'white': 'bg-white text-slate-700 border border-slate-200',
    'nâu': 'bg-amber-100 text-amber-800', 'brown': 'bg-amber-100 text-amber-800',
    'xám': 'bg-gray-100 text-gray-700', 'gray': 'bg-gray-100 text-gray-700', 'grey': 'bg-gray-100 text-gray-700',
    'tím': 'bg-purple-100 text-purple-700', 'purple': 'bg-purple-100 text-purple-700',
    'cam': 'bg-orange-100 text-orange-700', 'orange': 'bg-orange-100 text-orange-700',
    'hồng': 'bg-pink-100 text-pink-700', 'pink': 'bg-pink-100 text-pink-700',
    'xanh navy': 'bg-slate-700 text-white', 'navy': 'bg-slate-700 text-white',
    'be': 'bg-orange-50 text-orange-800',
    'bạc': 'bg-slate-100 text-slate-600', 'silver': 'bg-slate-100 text-slate-600',
    'vàng gold': 'bg-yellow-100 text-yellow-800', 'gold': 'bg-yellow-100 text-yellow-800',
  };

  return colorClasses[normalized] || 'bg-slate-100 text-slate-700';
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

onMounted(() => {
  fetchDashboard();
});
</script>