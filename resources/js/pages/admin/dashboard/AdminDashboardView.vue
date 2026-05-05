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
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:rotate-180" viewBox="0 0 24 24" fill="none">
            <path d="M20 12A8 8 0 1 1 17.657 6.343" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M20 4V10H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Làm mới
        </button>
      </div>
    </section>

    <!-- Loading -->
    <section v-if="loading" class="relative space-y-8">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="n in 4"
          :key="'card-skeleton-' + n"
          class="h-36 animate-pulse rounded-lg border border-slate-200/80 bg-white shadow-sm"
        ></div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="h-96 animate-pulse rounded-lg border border-slate-200/80 bg-white shadow-sm lg:col-span-7"></div>
        <div class="h-96 animate-pulse rounded-lg border border-slate-200/80 bg-white shadow-sm lg:col-span-5"></div>
      </div>

      <div class="h-48 animate-pulse rounded-lg border border-slate-200/80 bg-white shadow-sm"></div>
      <div class="h-80 animate-pulse rounded-lg border border-slate-200/80 bg-white shadow-sm"></div>
      <div class="h-48 animate-pulse rounded-lg border border-slate-200/80 bg-white shadow-sm"></div>
    </section>

    <!-- Error -->
    <section
      v-else-if="error"
      class="relative rounded-lg border border-rose-200 bg-gradient-to-r from-rose-50 to-pink-50 p-6 text-sm shadow-sm"
    >
      <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600" viewBox="0 0 24 24" fill="none">
            <path d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <div>
          <div class="font-semibold text-rose-800">Không tải được dữ liệu dashboard</div>
          <div class="mt-0.5 text-rose-600">{{ error }}</div>
        </div>
      </div>

      <button
        type="button"
        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-rose-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-600"
        @click="fetchDashboard"
      >
        Thử lại
      </button>
    </section>

    <!-- Content -->
    <template v-else>
      <!-- Stats -->
      <section class="relative grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="(card, index) in statsCards"
          :key="card.key"
          class="group relative overflow-hidden rounded-lg border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
          :style="{ animationDelay: `${index * 100}ms` }"
        >
          <div class="absolute inset-0 bg-gradient-to-br from-transparent via-transparent to-indigo-50/50 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

          <div class="relative flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-medium uppercase tracking-wider text-slate-500">{{ card.label }}</p>
              <h3 class="mt-2 text-2xl font-bold text-slate-900 lg:text-3xl">
                {{ card.value }}
              </h3>

              <div
                class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                :class="
                  Number(card.trend) >= 0
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-rose-100 text-rose-700'
                "
              >
                <svg
                  v-if="Number(card.trend) >= 0"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-3.5 w-3.5"
                  viewBox="0 0 24 24"
                  fill="none"
                >
                  <path d="M7 14l5-5 5 5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

                <svg
                  v-else
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-3.5 w-3.5"
                  viewBox="0 0 24 24"
                  fill="none"
                >
                  <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

                {{ Math.abs(Number(card.trend || 0)) }}% so với kỳ trước
              </div>
            </div>

            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl shadow-md"
              :class="card.iconBg"
            >
              <span v-html="card.icon"></span>
            </div>
          </div>
        </article>
      </section>

      <section class="relative mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Revenue chart -->
        <article class="relative overflow-hidden rounded-lg border border-slate-200/80 bg-white p-6 shadow-sm lg:col-span-7">
          <div class="absolute -right-16 -top-16 h-40 w-40 rounded-full bg-gradient-to-br from-indigo-100/80 to-transparent"></div>

          <div class="relative mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <h2 class="text-xl font-bold text-slate-900">Biểu đồ doanh thu</h2>
              <p class="mt-0.5 text-sm text-slate-500">Doanh thu theo {{ revenueLabel }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <div v-if="revenueTrend >= 0" class="flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                  <path d="M7 14l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                +{{ revenueTrend }}%
              </div>
              <div v-else class="flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                  <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ revenueTrend }}%
              </div>

              <div class="rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-indigo-500/30">
                {{ formatCurrency(totalRevenueInChart) }}
              </div>
            </div>
          </div>

          <template v-if="dashboard.chart.length">
            <LineChart 
              :labels="chartLabels" 
              :data="chartDataValues"
              :height="400"
            />
          </template>

          <div
            v-else
            class="flex h-80 items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
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
        <article class="rounded-lg border border-slate-200/80 bg-white p-6 shadow-sm lg:col-span-5">
          <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900">Trạng thái đơn hàng</h2>
            <p class="mt-0.5 text-sm text-slate-500">Phân bố đơn hàng hiện tại</p>
          </div>

          <template v-if="dashboard.order_status.length">
            <div class="space-y-4">
              <div
                v-for="item in dashboard.order_status"
                :key="item.key"
                class="rounded-xl border border-slate-100 bg-slate-50 p-4 transition hover:bg-slate-100"
              >
                <div class="mb-3 flex items-center justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full" :class="item.dot"></span>
                    <span class="text-sm font-medium text-slate-700">{{ item.label }}</span>
                  </div>
                  <span class="text-sm font-bold text-slate-900">{{ item.count }}</span>
                </div>

                <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="item.bar"
                    :style="{ width: `${getStatusPercent(item.count)}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </template>

          <div
            v-else
            class="flex h-80 items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            Chưa có dữ liệu trạng thái đơn hàng
          </div>
        </article>
      </section>

      <!-- Top products -->
      <section class="relative mt-6">
        <article class="overflow-hidden rounded-lg border border-slate-200/80 bg-white p-6 shadow-sm">
          <div class="mb-6">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 shadow-lg shadow-amber-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-slate-900">Top sản phẩm bán chạy</h2>
                <p class="text-sm text-slate-500">Sản phẩm có doanh số tốt nhất</p>
              </div>
            </div>
          </div>

          <template v-if="dashboard.top_products.length">
            <div class="space-y-3">
              <div
                v-for="(product, index) in dashboard.top_products"
                :key="product.id"
                class="group flex items-center gap-4 rounded-xl border border-slate-100 bg-white p-3 transition-all duration-300 hover:border-amber-200 hover:shadow-md"
              >
                <!-- Rank badge -->
                <div
                  v-if="index < 3"
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white shadow-md"
                  :class="index === 0 ? 'bg-gradient-to-br from-amber-400 to-orange-500' : index === 1 ? 'bg-gradient-to-br from-slate-300 to-slate-400' : 'bg-gradient-to-br from-amber-600 to-amber-700'"
                >
                  {{ index + 1 }}
                </div>
                <div v-else class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">
                  {{ index + 1 }}
                </div>

                <!-- Product image -->
                <div class="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100 shadow-sm">
                  <img
                    v-if="product.thumbnail"
                    :src="buildImageUrl(product.thumbnail)"
                    :alt="product.name"
                    class="h-full w-full object-cover"
                  />
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-300" viewBox="0 0 24 24" fill="none">
                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z" fill="currentColor"/>
                  </svg>
                </div>

                <!-- Product info -->
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <h3 class="text-sm font-semibold text-slate-900 line-clamp-1 group-hover:text-amber-700 transition-colors">
                      {{ product.name }}
                    </h3>
                    <span class="shrink-0 rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                      {{ product.sold }} đã bán
                    </span>
                  </div>

                  <div class="mt-2 flex flex-wrap items-center gap-3">
                    <!-- Sizes -->
                    <div v-if="product.top_sizes && product.top_sizes.length" class="flex items-center gap-1.5">
                      <span class="text-xs font-medium text-slate-400">Size:</span>
                      <div class="flex items-center gap-1">
                        <span
                          v-for="(sizeItem, sIdx) in product.top_sizes.slice(0, 5)"
                          :key="'size-' + product.id + '-' + sIdx"
                          class="rounded-md bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700"
                        >
                          {{ sizeItem.size }}
                        </span>
                        <span v-if="product.top_sizes.length > 5" class="text-xs text-slate-400">
                          +{{ product.top_sizes.length - 5 }}
                        </span>
                      </div>
                    </div>

                    <!-- Colors -->
                    <div v-if="product.top_colors && product.top_colors.length" class="flex items-center gap-1.5">
                      <span class="text-xs font-medium text-slate-400">Màu:</span>
                      <div class="flex items-center gap-1">
                        <span
                          v-for="(colorItem, cIdx) in product.top_colors.slice(0, 6)"
                          :key="'color-' + product.id + '-' + cIdx"
                          class="flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600"
                        >
                          <span class="h-3 w-3 rounded-full border border-black/20" :style="{ backgroundColor: getColorHex(colorItem.color) }"></span>
                          {{ colorItem.color }}
                        </span>
                        <span v-if="product.top_colors.length > 6" class="text-xs text-slate-400">
                          +{{ product.top_colors.length - 6 }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div
            v-else
            class="flex h-48 items-center justify-center border-2 border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            Chưa có dữ liệu sản phẩm bán chạy
          </div>
        </article>
      </section>

      <!-- Recent orders -->
      <section class="relative mt-6">
        <article class="overflow-hidden rounded-lg border border-slate-200/80 bg-white p-6 shadow-sm">
          <div class="mb-6">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-sky-400 to-cyan-500 shadow-lg shadow-sky-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
                  <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="M9 12h6M9 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-slate-900">Đơn hàng mới</h2>
                <p class="text-sm text-slate-500">Danh sách đơn hàng gần đây</p>
              </div>
            </div>
          </div>

          <template v-if="dashboard.recent_orders.length">
            <div class="overflow-x-auto rounded-xl border border-slate-200">
              <table class="min-w-full">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                      Mã đơn
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                      Khách hàng
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                      Tổng tiền
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                      Trạng thái
                    </th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                  <tr
                    v-for="order in dashboard.recent_orders"
                    :key="order.id"
                    class="transition-colors hover:bg-slate-50"
                  >
                    <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-indigo-600">
                      #{{ order.code }}
                    </td>
                    <td class="px-4 py-4">
                      <div class="font-medium text-slate-900">{{ order.customer_name }}</div>
                      <div class="text-xs text-slate-400">{{ order.created_at }}</div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-emerald-600">
                      {{ formatCurrency(order.total_amount) }}
                    </td>
                    <td class="px-4 py-4">
                      <span
                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
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
            class="flex h-48 items-center justify-center border-2 border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
          >
            Chưa có đơn hàng gần đây
          </div>
        </article>
      </section>

      <!-- New customers -->
      <section class="relative mt-6">
        <article class="overflow-hidden rounded-lg border border-slate-200/80 bg-white p-6 shadow-sm">
          <div class="mb-6">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-400 to-fuchsia-500 shadow-lg shadow-violet-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11A4 4 0 1 0 9.5 3a4 4 0 0 0 0 8ZM21 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-bold text-slate-900">Khách hàng mới</h2>
                <p class="text-sm text-slate-500">Người dùng đăng ký gần đây</p>
              </div>
            </div>
          </div>

          <template v-if="dashboard.new_customers.length">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
              <div
                v-for="customer in dashboard.new_customers"
                :key="customer.id"
                class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 transition-all duration-300 hover:border-violet-200 hover:bg-violet-50/50"
              >
                <div class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-violet-100 to-fuchsia-100 shadow-sm">
                  <img
                    v-if="customer.avatar"
                    :src="buildImageUrl(customer.avatar)"
                    :alt="customer.name"
                    class="h-full w-full object-cover"
                  />
                  <span v-else class="text-sm font-bold text-violet-600">{{ getInitials(customer.name) }}</span>
                </div>

                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-semibold text-slate-900">{{ customer.name }}</p>
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
            class="flex h-48 items-center justify-center border-2 border-dashed border-slate-200 bg-slate-50 text-sm text-slate-400"
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
import LineChart from "../../../components/admin/LineChart.vue";
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
    iconBg: "bg-gradient-to-br from-emerald-400 to-teal-500 shadow-emerald-500/30",
    barBg: "from-emerald-400 to-teal-500",
    icon: `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
        <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    `,
  },
  {
    key: "orders",
    label: "Tổng đơn hàng",
    value: Number(dashboard.value.overview.orders || 0).toLocaleString("vi-VN"),
    trend: dashboard.value.overview.orders_growth,
    iconBg: "bg-gradient-to-br from-sky-400 to-indigo-500 shadow-sky-500/30",
    barBg: "from-sky-400 to-indigo-500",
    icon: `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
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
    iconBg: "bg-gradient-to-br from-violet-400 to-fuchsia-500 shadow-violet-500/30",
    barBg: "from-violet-400 to-fuchsia-500",
    icon: `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
        <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11A4 4 0 1 0 9.5 3a4 4 0 0 0 0 8ZM21 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `,
  },
  {
    key: "products",
    label: "Tổng sản phẩm",
    value: Number(dashboard.value.overview.products || 0).toLocaleString("vi-VN"),
    trend: dashboard.value.overview.products_growth,
    iconBg: "bg-gradient-to-br from-amber-400 to-orange-500 shadow-amber-500/30",
    barBg: "from-amber-400 to-orange-500",
    icon: `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
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

// Computed properties for chart data
const chartLabels = computed(() => dashboard.value.chart.map(item => item.label));
const chartDataValues = computed(() => dashboard.value.chart.map(item => Number(item.value)));

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

  // Map tên màu -> cặp bg/text color tương ứng (light theme)
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
    pending: "bg-amber-100 text-amber-700",
    paid: "bg-sky-100 text-sky-700",
    processing: "bg-indigo-100 text-indigo-700",
    shipping: "bg-violet-100 text-violet-700",
    completed: "bg-emerald-100 text-emerald-700",
    cancelled: "bg-rose-100 text-rose-700",
  };

  return map[status] || "bg-slate-100 text-slate-700";
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