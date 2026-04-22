<template>
  <main class="min-h-screen py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
            Đơn hàng của tôi
          </h1>
          <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Theo dõi trạng thái và xem lại các đơn hàng bạn đã đặt.
          </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <div class="relative">
            <span
              class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]"
            >
              search
            </span>
            <input
              v-model="keyword"
              type="text"
              placeholder="Tìm theo mã đơn, tên người nhận..."
              class="w-full sm:w-80 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 pl-10 pr-4 py-3 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
            />
          </div>

          <BaseSelect
            v-model="statusFilter"
            :options="statusOptions"
            placeholder="Tất cả trạng thái đơn"
            wrapper-class="w-full sm:w-auto"
          />

          <BaseSelect
            v-model="paymentStatusFilter"
            :options="paymentStatusOptions"
            placeholder="Tất cả thanh toán"
            wrapper-class="w-full sm:w-auto"
          />
        </div>
      </div>

      <!-- Loading / Error -->
      <div v-if="loading" class="text-sm text-slate-500">Đang tải danh sách đơn hàng...</div>
      <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>

      <template v-else>
        <!-- Empty -->
        <div
          v-if="filteredOrders.length === 0"
          class="rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-10 text-center"
        >
          <div
            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700"
          >
            <span class="material-symbols-outlined text-3xl text-slate-500">receipt_long</span>
          </div>
          <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">
            Chưa có đơn hàng nào
          </h2>
          <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Hãy chọn sản phẩm yêu thích và tiến hành đặt hàng.
          </p>
          <button
            type="button"
            class="mt-6 rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-primary/90 transition"
            @click="goShop"
          >
            Mua sắm ngay
          </button>
        </div>

        <!-- Orders -->
        <div v-else class="space-y-5">
          <div
            v-for="order in filteredOrders"
            :key="order.id"
            class="overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm"
          >
            <!-- top -->
            <div
              class="border-b border-slate-100 dark:border-slate-700 px-6 py-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
              <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-3">
                  <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                    {{ order.code || `#${order.id}` }}
                  </h2>

                  <span
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                    :class="orderStatusClass(order.status)"
                  >
                    {{ orderStatusText(order.status) }}
                  </span>

                  <span
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                    :class="paymentStatusClass(order.payment_status)"
                  >
                    {{ paymentStatusText(order.payment_status) }}
                  </span>
                </div>

                <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
                  <div>Ngày đặt: {{ formatDateTime(order.created_at) }}</div>
                  <div>Người nhận: {{ order.customer_name || "-" }}</div>
                  <div>SĐT: {{ order.customer_phone || "-" }}</div>
                </div>
              </div>

              <div class="text-left lg:text-right">
                <div class="text-sm text-slate-500 dark:text-slate-400">Tổng thanh toán</div>
                <div class="text-2xl font-bold text-primary">
                  {{ moneyVND(order.grand_total || 0) }}
                </div>
              </div>
            </div>

            <!-- body -->
            <div class="px-6 py-5">
              <div class="space-y-4">
                <div
                  v-for="item in order.items || []"
                  :key="item.id"
                  class="flex flex-col sm:flex-row sm:items-start gap-4 border-b border-slate-100 dark:border-slate-700 pb-4 last:border-b-0 last:pb-0"
                >
                  <div
                    class="h-20 w-20 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-900 flex-shrink-0"
                  >
                    <img
                      :src="buildImageUrl(item.thumbnail) || fallbackImage"
                      :alt="item.product_name || 'Sản phẩm'"
                      class="h-full w-full object-cover"
                    />
                  </div>

                  <div class="min-w-0 flex-1">
                    <div class="font-semibold text-slate-900 dark:text-slate-100">
                      {{ item.product_name }}
                    </div>

                    <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                      <span v-if="item.size">Size: {{ item.size }}</span>
                      <span v-if="item.size && item.color"> | </span>
                      <span v-if="item.color">Màu: {{ item.color }}</span>
                    </div>

                    <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                      Đơn giá: {{ moneyVND(item.unit_price) }} · Số lượng: {{ item.quantity }}
                    </div>
                  </div>

                  <div class="text-sm font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                    {{ moneyVND(item.line_total) }}
                  </div>
                </div>
              </div>
            </div>

            <!-- bottom -->
            <div
              class="border-t border-slate-100 dark:border-slate-700 px-6 py-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
              <div class="text-sm text-slate-500 dark:text-slate-400">
                Thanh toán:
                <span class="font-semibold text-slate-900 dark:text-slate-100">
                  {{ paymentMethodText(order.payment_method) }}
                </span>
              </div>

              <div class="flex flex-wrap gap-3">
                <button
                  type="button"
                  class="rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                  @click="toggleExpand(order.id)"
                >
                  {{ expandedIds[order.id] ? "Ẩn chi tiết" : "Xem chi tiết" }}
                </button>

                <button
                  v-if="canCancel(order)"
                  type="button"
                  class="rounded-xl border border-red-200 dark:border-red-800 px-4 py-2.5 text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                  @click="openCancelModal(order)"
                >
                  Yêu cầu hủy
                </button>

                <span
                  v-else-if="order.cancellation_requested_at"
                  class="inline-flex items-center gap-1.5 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-2.5 text-sm font-semibold text-amber-700 dark:text-amber-400"
                >
                  <span class="material-symbols-outlined text-base">hourglass_empty</span>
                  Chờ xử lý hủy
                </span>

                <button
                  v-if="canRepay(order)"
                  type="button"
                  class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary/90 transition disabled:opacity-50"
                  :disabled="repayLoadingId === order.id"
                  @click="payNow(order)"
                >
                  {{ repayLoadingId === order.id ? "Đang xử lý..." : "Thanh toán ngay" }}
                </button>
              </div>
            </div>

            <!-- expanded -->
            <div
              v-if="expandedIds[order.id]"
              class="border-t border-slate-100 dark:border-slate-700 px-6 py-5 bg-slate-50/70 dark:bg-slate-900/30"
            >
              <!-- Order Progress -->
              <div class="mb-6">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wide mb-4">
                  Tiến trình đơn hàng
                </h3>
                <div class="relative">
                  <div class="absolute top-4 left-0 right-0 h-1 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                  <div
                    class="absolute top-4 left-0 h-1 bg-primary rounded-full transition-all duration-500"
                    :style="{ width: getProgressWidth(order.status) }"
                  ></div>
                  <div class="relative flex justify-between">
                    <div
                      v-for="(step, index) in getOrderSteps()"
                      :key="step.key"
                      class="flex flex-col items-center"
                    >
                      <div
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm transition-all duration-300"
                        :class="getStepClass(order.status, step.key)"
                      >
                        <span class="material-symbols-outlined text-base">{{ step.icon }}</span>
                      </div>
                      <span class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-400 text-center max-w-[70px]">
                        {{ step.label }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-xl bg-white dark:bg-slate-800 p-5 border border-slate-200 dark:border-slate-700">
                  <h3 class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-lg text-primary">local_shipping</span>
                    Thông tin giao hàng
                  </h3>
                  <div class="space-y-3">
                    <div class="flex items-start gap-3">
                      <span class="material-symbols-outlined text-lg text-slate-400 dark:text-slate-500 mt-0.5">person</span>
                      <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Người nhận</div>
                        <div class="font-medium text-slate-900 dark:text-slate-100">{{ order.customer_name || "-" }}</div>
                      </div>
                    </div>
                    <div class="flex items-start gap-3">
                      <span class="material-symbols-outlined text-lg text-slate-400 dark:text-slate-500 mt-0.5">phone</span>
                      <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Số điện thoại</div>
                        <div class="font-medium text-slate-900 dark:text-slate-100">{{ order.customer_phone || "-" }}</div>
                      </div>
                    </div>
                    <div class="flex items-start gap-3">
                      <span class="material-symbols-outlined text-lg text-slate-400 dark:text-slate-500 mt-0.5">mail</span>
                      <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Email</div>
                        <div class="font-medium text-slate-900 dark:text-slate-100">{{ order.customer_email || "-" }}</div>
                      </div>
                    </div>
                    <div class="flex items-start gap-3">
                      <span class="material-symbols-outlined text-lg text-slate-400 dark:text-slate-500 mt-0.5">home</span>
                      <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Địa chỉ giao hàng</div>
                        <div class="font-medium text-slate-900 dark:text-slate-100">{{ fullAddress(order) }}</div>
                      </div>
                    </div>
                    <div v-if="order.note" class="flex items-start gap-3">
                      <span class="material-symbols-outlined text-lg text-slate-400 dark:text-slate-500 mt-0.5">note</span>
                      <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Ghi chú</div>
                        <div class="font-medium text-slate-900 dark:text-slate-100">{{ order.note }}</div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="rounded-xl bg-white dark:bg-slate-800 p-5 border border-slate-200 dark:border-slate-700">
                  <h3 class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2 mb-4">
                    <span class="material-symbols-outlined text-lg text-primary">payments</span>
                    Tóm tắt thanh toán
                  </h3>
                  <div class="space-y-3">
                    <div class="flex items-center justify-between">
                      <span class="text-slate-600 dark:text-slate-400">Tạm tính</span>
                      <span class="font-medium text-slate-900 dark:text-slate-100">
                        {{ moneyVND(order.subtotal || 0) }}
                      </span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-slate-600 dark:text-slate-400">Phí vận chuyển</span>
                      <span class="font-medium text-slate-900 dark:text-slate-100">
                        {{ moneyVND(order.shipping_fee || 0) }}
                      </span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-slate-600 dark:text-slate-400">Giảm giá</span>
                      <span class="font-medium text-green-600 dark:text-green-400">
                        -{{ moneyVND(order.discount_total || 0) }}
                      </span>
                    </div>
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                      <span class="font-bold text-slate-900 dark:text-slate-100">Tổng thanh toán</span>
                      <span class="text-xl font-bold text-primary">
                        {{ moneyVND(order.grand_total || 0) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-if="actionErrorId === order.id && actionError"
                class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600"
              >
                {{ actionError }}
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div
          v-if="meta.last_page > 1"
          class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4"
        >
          <div class="text-sm text-slate-500 dark:text-slate-400">
            Trang {{ meta.current_page }} / {{ meta.last_page }}
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              class="rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800 transition disabled:opacity-50"
              :disabled="meta.current_page <= 1 || loadingPage"
              @click="changePage(meta.current_page - 1)"
            >
              Trước
            </button>

            <button
              type="button"
              class="rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800 transition disabled:opacity-50"
              :disabled="meta.current_page >= meta.last_page || loadingPage"
              @click="changePage(meta.current_page + 1)"
            >
              Sau
            </button>
          </div>
        </div>
      </template>
    </div>
  </main>

  <!-- Cancel Order Modal -->
  <Teleport to="body">
    <div
      v-if="showCancelModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      @click.self="closeCancelModal"
    >
      <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 shadow-xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700">
          <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">
            Yêu cầu hủy đơn hàng
          </h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Vui lòng cho chúng tôi biết lý do bạn muốn hủy đơn hàng {{ cancelOrder?.code || '' }}
          </p>
        </div>

        <div class="px-6 py-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Lý do hủy đơn hàng <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="cancelReason"
              rows="4"
              placeholder="Nhập lý do bạn muốn hủy đơn hàng..."
              class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 resize-none"
            ></textarea>
            <p v-if="cancelReasonError" class="mt-1.5 text-sm text-red-600">
              {{ cancelReasonError }}
            </p>
          </div>

          <div
            v-if="cancelError"
            class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-600 dark:text-red-400"
          >
            {{ cancelError }}
          </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
          <button
            type="button"
            class="rounded-xl border border-slate-200 dark:border-slate-700 px-5 py-2.5 text-sm font-semibold text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
            @click="closeCancelModal"
          >
            Đóng
          </button>
          <button
            type="button"
            class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700 transition disabled:opacity-50"
            :disabled="cancelLoading"
            @click="submitCancelRequest"
          >
            {{ cancelLoading ? 'Đang gửi...' : 'Gửi yêu cầu hủy' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import BaseSelect from "../../../components/BaseSelect.vue";
import orderService from "../../../services/public/orderService";
import { buildImageUrl } from "../../../utils/image";
import { formatDateTime } from "../../../utils/date";

const router = useRouter();

const loading = ref(true);
const loadingPage = ref(false);
const error = ref("");

const orders = ref([]);
const keyword = ref("");
const statusFilter = ref("");
const paymentStatusFilter = ref("");

const expandedIds = ref({});
const repayLoadingId = ref(null);
const actionErrorId = ref(null);
const actionError = ref("");

// Cancel order modal
const showCancelModal = ref(false);
const cancelOrder = ref(null);
const cancelReason = ref("");
const cancelReasonError = ref("");
const cancelLoading = ref(false);
const cancelError = ref("");

const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

const fallbackImage = "https://via.placeholder.com/400x400?text=Shoe";

const statusOptions = [
  { value: "", label: "Tất cả trạng thái đơn" },
  { value: "pending", label: "Chờ xử lý" },
  { value: "confirmed", label: "Đã xác nhận" },
  { value: "processing", label: "Đang chuẩn bị hàng" },
  { value: "shipping", label: "Đang giao" },
  { value: "completed", label: "Hoàn thành" },
  { value: "cancelled", label: "Đã hủy" },
];

const paymentStatusOptions = [
  { value: "", label: "Tất cả thanh toán" },
  { value: "unpaid", label: "Chưa thanh toán" },
  { value: "pending", label: "Đang chờ thanh toán" },
  { value: "paid", label: "Đã thanh toán" },
  { value: "failed", label: "Thất bại" },
  { value: "refunded", label: "Đã hoàn tiền" },
];

onMounted(async () => {
  await fetchOrders(1, true);
});

async function fetchOrders(page = 1, firstLoad = false) {
  if (firstLoad) {
    loading.value = true;
  } else {
    loadingPage.value = true;
  }

  error.value = "";

  try {
    const res = await orderService.getMyOrders({
      page,
      per_page: 10,
    });

    const payload = res?.data;
    const data = payload?.data || [];

    orders.value = data;

    meta.value = {
      current_page: payload?.meta?.current_page || 1,
      last_page: payload?.meta?.last_page || 1,
      per_page: payload?.meta?.per_page || 10,
      total: payload?.meta?.total || data.length,
    };
  } catch (e) {
    error.value =
      e?.response?.data?.message || "Không tải được danh sách đơn hàng.";
  } finally {
    loading.value = false;
    loadingPage.value = false;
  }
}

const filteredOrders = computed(() => {
  let result = [...orders.value];

  if (statusFilter.value) {
    result = result.filter((o) => String(o.status || "") === statusFilter.value);
  }

  if (paymentStatusFilter.value) {
    result = result.filter(
      (o) => String(o.payment_status || "") === paymentStatusFilter.value
    );
  }

  if (keyword.value.trim()) {
    const q = keyword.value.trim().toLowerCase();
    result = result.filter((o) => {
      const code = String(o.code || "").toLowerCase();
      const name = String(o.customer_name || "").toLowerCase();
      const phone = String(o.customer_phone || "").toLowerCase();
      return code.includes(q) || name.includes(q) || phone.includes(q);
    });
  }

  return result;
});

watch([statusFilter, paymentStatusFilter, keyword], () => {
  expandedIds.value = {};
});

function toggleExpand(orderId) {
  expandedIds.value = {
    ...expandedIds.value,
    [orderId]: !expandedIds.value[orderId],
  };
}

function moneyVND(v) {
  const n = Number(v || 0);
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(n);
}

function fullAddress(order) {
  return [
    order.address_line,
    order.ward,
    order.district,
    order.province,
  ]
    .filter(Boolean)
    .join(", ") || "-";
}

function orderStatusText(v) {
  switch (v) {
    case "pending":
      return "Chờ xử lý";
    case "confirmed":
      return "Đã xác nhận";
    case "processing":
      return "Đang chuẩn bị hàng";
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

function paymentStatusText(v) {
  switch (v) {
    case "unpaid":
      return "Chưa thanh toán";
    case "pending":
      return "Đang chờ thanh toán";
    case "paid":
      return "Đã thanh toán";
    case "failed":
      return "Thất bại";
    case "refunded":
      return "Đã hoàn tiền";
    default:
      return v || "-";
  }
}

function paymentMethodText(v) {
  switch (v) {
    case "cod":
      return "Thanh toán khi nhận hàng";
    case "vnpay":
      return "VNPay";
    case "momo":
      return "MoMo";
    default:
      return v || "-";
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

function canRepay(order) {
  return (
    ["vnpay", "momo"].includes(String(order.payment_method || "")) &&
    ["pending", "unpaid", "failed"].includes(String(order.payment_status || ""))
  );
}

// Kiểm tra đơn hàng có thể yêu cầu hủy hay không
function canCancel(order) {
  const status = String(order.status || "");
  const cancellableStatuses = ["pending", "confirmed", "processing"];
  return (
    cancellableStatuses.includes(status) &&
    !order.cancellation_requested_at &&
    status !== "cancelled" &&
    status !== "shipping" &&
    status !== "completed"
  );
}

// Mở modal hủy đơn
function openCancelModal(order) {
  cancelOrder.value = order;
  cancelReason.value = "";
  cancelReasonError.value = "";
  cancelError.value = "";
  showCancelModal.value = true;
}

// Đóng modal hủy đơn
function closeCancelModal() {
  showCancelModal.value = false;
  cancelOrder.value = null;
  cancelReason.value = "";
  cancelReasonError.value = "";
  cancelError.value = "";
}

// Gửi yêu cầu hủy đơn
async function submitCancelRequest() {
  cancelReasonError.value = "";
  cancelError.value = "";

  if (!cancelReason.value.trim()) {
    cancelReasonError.value = "Vui lòng nhập lý do hủy đơn hàng.";
    return;
  }

  if (cancelReason.value.trim().length > 500) {
    cancelReasonError.value = "Lý do hủy không được vượt quá 500 ký tự.";
    return;
  }

  cancelLoading.value = true;

  try {
    await orderService.requestCancellation(cancelOrder.value.id, cancelReason.value.trim());

    // Cập nhật lại thông tin đơn hàng trong danh sách
    const idx = orders.value.findIndex((o) => o.id === cancelOrder.value.id);
    if (idx !== -1) {
      orders.value[idx] = {
        ...orders.value[idx],
        cancellation_requested_at: new Date().toISOString(),
        cancellation_reason: cancelReason.value.trim(),
      };
    }

    closeCancelModal();

    actionErrorId.value = cancelOrder.value.id;
    actionError.value = "Yêu cầu hủy đơn hàng đã được gửi thành công.";
    setTimeout(() => {
      if (actionErrorId.value === cancelOrder.value.id) {
        actionError.value = "";
      }
    }, 5000);
  } catch (e) {
    cancelError.value =
      e?.response?.data?.message || "Không thể gửi yêu cầu hủy đơn hàng.";
  } finally {
    cancelLoading.value = false;
  }
}

// Các bước tiến trình đơn hàng
function getOrderSteps() {
  return [
    { key: "pending", label: "Chờ xử lý", icon: "hourglass_empty" },
    { key: "confirmed", label: "Xác nhận", icon: "check_circle" },
    { key: "processing", label: "Chuẩn bị", icon: "inventory_2" },
    { key: "shipping", label: "Giao hàng", icon: "local_shipping" },
    { key: "completed", label: "Hoàn thành", icon: "task_alt" },
  ];
}

// CSS class cho từng bước
function getStepClass(currentStatus, stepKey) {
  const statusOrder = ["pending", "confirmed", "processing", "shipping", "completed"];
  const currentIndex = statusOrder.indexOf(currentStatus);
  const stepIndex = statusOrder.indexOf(stepKey);

  if (stepKey === "cancelled") {
    return currentStatus === "cancelled"
      ? "bg-red-500 text-white"
      : "bg-slate-200 dark:bg-slate-700 text-slate-400";
  }

  if (stepIndex <= currentIndex) {
    return "bg-primary text-white";
  }
  return "bg-slate-200 dark:bg-slate-700 text-slate-400";
}

// Độ rộng thanh tiến trình
function getProgressWidth(status) {
  const statusOrder = ["pending", "confirmed", "processing", "shipping", "completed"];
  const index = statusOrder.indexOf(status);

  if (status === "cancelled") return "0%";
  if (index === -1) return "0%";

  return `${(index / (statusOrder.length - 1)) * 100}%`;
}

async function payNow(order) {
  actionError.value = "";
  actionErrorId.value = order.id;
  repayLoadingId.value = order.id;

  try {
    const res = await orderService.createPayment(order.id);
    const paymentData = res?.data?.data || {};
    const paymentUrl = paymentData.payment_url;

    if (!paymentUrl) {
      throw new Error("Không tạo được liên kết thanh toán.");
    }

    window.location.href = paymentUrl;
  } catch (e) {
    actionError.value =
      e?.response?.data?.message ||
      e?.message ||
      "Không thể tạo liên kết thanh toán.";
  } finally {
    repayLoadingId.value = null;
  }
}

async function changePage(page) {
  if (page < 1 || page > meta.value.last_page) return;
  await fetchOrders(page, false);
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function goShop() {
  router.push("/shop/products");
}
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
}
</style>