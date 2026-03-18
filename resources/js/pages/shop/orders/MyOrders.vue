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

          <select
            v-model="statusFilter"
            class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
          >
            <option value="">Tất cả trạng thái đơn</option>
            <option value="pending">Chờ xử lý</option>
            <option value="confirmed">Đã xác nhận</option>
            <option value="processing">Đang chuẩn bị hàng</option>
            <option value="shipping">Đang giao</option>
            <option value="completed">Hoàn thành</option>
            <option value="cancelled">Đã hủy</option>
          </select>

          <select
            v-model="paymentStatusFilter"
            class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
          >
            <option value="">Tất cả thanh toán</option>
            <option value="unpaid">Chưa thanh toán</option>
            <option value="pending">Đang chờ thanh toán</option>
            <option value="paid">Đã thanh toán</option>
            <option value="failed">Thất bại</option>
            <option value="refunded">Đã hoàn tiền</option>
          </select>
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
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-3">
                  <h3 class="font-bold text-slate-900 dark:text-slate-100">Thông tin giao hàng</h3>
                  <div class="text-sm text-slate-600 dark:text-slate-400 space-y-1.5">
                    <p><b class="text-slate-900 dark:text-slate-100">Người nhận:</b> {{ order.customer_name || "-" }}</p>
                    <p><b class="text-slate-900 dark:text-slate-100">Số điện thoại:</b> {{ order.customer_phone || "-" }}</p>
                    <p><b class="text-slate-900 dark:text-slate-100">Email:</b> {{ order.customer_email || "-" }}</p>
                    <p><b class="text-slate-900 dark:text-slate-100">Địa chỉ:</b> {{ fullAddress(order) }}</p>
                    <p><b class="text-slate-900 dark:text-slate-100">Ghi chú:</b> {{ order.note || "-" }}</p>
                  </div>
                </div>

                <div class="space-y-3">
                  <h3 class="font-bold text-slate-900 dark:text-slate-100">Tóm tắt thanh toán</h3>
                  <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                      <span>Tạm tính</span>
                      <span class="font-medium text-slate-900 dark:text-slate-100">
                        {{ moneyVND(order.subtotal || 0) }}
                      </span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                      <span>Phí vận chuyển</span>
                      <span class="font-medium text-slate-900 dark:text-slate-100">
                        {{ moneyVND(order.shipping_fee || 0) }}
                      </span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                      <span>Giảm giá</span>
                      <span class="font-medium text-slate-900 dark:text-slate-100">
                        {{ moneyVND(order.discount_total || 0) }}
                      </span>
                    </div>
                    <div class="border-t border-slate-200 dark:border-slate-700 pt-3 flex items-center justify-between">
                      <span class="font-bold text-slate-900 dark:text-slate-100">Tổng thanh toán</span>
                      <span class="text-lg font-bold text-primary">
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
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import orderService from "../../../services/public/orderService";
import { buildImageUrl } from "../../../utils/image";

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

const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

const fallbackImage = "https://via.placeholder.com/400x400?text=Shoe";

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

function formatDateTime(v) {
  if (!v) return "-";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return v;

  return new Intl.DateTimeFormat("vi-VN", {
    dateStyle: "short",
    timeStyle: "short",
  }).format(d);
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