<template>
  <main class="min-h-screen py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
      <div v-if="loading" class="text-sm text-slate-500">Đang tải thông tin đơn hàng...</div>
      <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>

      <div
        v-else
        class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-lg p-8"
      >
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-600">
          <span class="material-symbols-outlined text-4xl">check_circle</span>
        </div>

        <div class="text-center">
          <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">
            Đặt hàng thành công
          </h1>
          <p class="mt-2 text-slate-500 dark:text-slate-400">
            Cảm ơn bạn đã mua hàng tại BMC Shoes.
          </p>
        </div>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="rounded-2xl bg-slate-50 dark:bg-slate-900 p-4">
            <div class="text-sm text-slate-500">Mã đơn hàng</div>
            <div class="mt-1 font-bold text-slate-900 dark:text-slate-100">
              {{ order.code }}
            </div>
          </div>

          <div class="rounded-2xl bg-slate-50 dark:bg-slate-900 p-4">
            <div class="text-sm text-slate-500">Tổng thanh toán</div>
            <div class="mt-1 font-bold text-primary">
              {{ moneyVND(order.grand_total || 0) }}
            </div>
          </div>

          <div class="rounded-2xl bg-slate-50 dark:bg-slate-900 p-4">
            <div class="text-sm text-slate-500">Phương thức thanh toán</div>
            <div class="mt-1 font-bold text-slate-900 dark:text-slate-100">
              {{ paymentMethodText(order.payment_method) }}
            </div>
          </div>

          <div class="rounded-2xl bg-slate-50 dark:bg-slate-900 p-4">
            <div class="text-sm text-slate-500">Trạng thái thanh toán</div>
            <div class="mt-1 font-bold text-slate-900 dark:text-slate-100">
              {{ paymentStatusText(order.payment_status) }}
            </div>
          </div>
        </div>

        <div class="mt-8 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
          <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-3">
            Thông tin nhận hàng
          </h2>

          <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
            <p><b class="text-slate-900 dark:text-slate-100">Người nhận:</b> {{ order.customer_name }}</p>
            <p><b class="text-slate-900 dark:text-slate-100">Số điện thoại:</b> {{ order.customer_phone }}</p>
            <p>
              <b class="text-slate-900 dark:text-slate-100">Địa chỉ:</b>
              {{ fullAddress }}
            </p>
            <p v-if="order.note">
              <b class="text-slate-900 dark:text-slate-100">Ghi chú:</b> {{ order.note }}
            </p>
          </div>
        </div>

        <div class="mt-8 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
          <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">
            Sản phẩm đã đặt
          </h2>

          <div class="space-y-4">
            <div
              v-for="item in order.items || []"
              :key="item.id"
              class="flex items-start justify-between gap-4 border-b border-slate-100 dark:border-slate-700 pb-4"
            >
              <div class="min-w-0">
                <div class="font-semibold text-slate-900 dark:text-slate-100">
                  {{ item.product_name }}
                </div>
                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                  <span v-if="item.size">Size: {{ item.size }}</span>
                  <span v-if="item.size && item.color"> | </span>
                  <span v-if="item.color">Màu: {{ item.color }}</span>
                </div>
                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                  Số lượng: {{ item.quantity }}
                </div>
              </div>

              <div class="font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                {{ moneyVND(item.line_total) }}
              </div>
            </div>
          </div>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row gap-3">
          <button
            type="button"
            class="flex-1 rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-primary/90 transition"
            @click="goShop"
          >
            Tiếp tục mua sắm
          </button>

          <button
            type="button"
            class="flex-1 rounded-xl border border-slate-200 dark:border-slate-700 px-5 py-3 font-bold text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
            @click="goOrders"
          >
            Xem đơn hàng
          </button>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import orderService from "../../../services/public/orderService";

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const error = ref("");
const order = ref({});

const orderId = computed(() => route.params.id);

const fullAddress = computed(() => {
  const parts = [
    order.value.address_line,
    order.value.ward,
    order.value.district,
    order.value.province,
  ].filter(Boolean);

  return parts.join(", ");
});

onMounted(async () => {
  loading.value = true;
  error.value = "";

  try {
    const res = await orderService.getMyOrderDetail(orderId.value);
    order.value = res?.data?.data || {};
  } catch (e) {
    error.value =
      e?.response?.data?.message || "Không tải được thông tin đơn hàng.";
  } finally {
    loading.value = false;
  }
});

function moneyVND(v) {
  const n = Number(v || 0);
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(n);
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

function paymentStatusText(v) {
  switch (v) {
    case "unpaid":
      return "Chưa thanh toán";
    case "pending":
      return "Đang chờ thanh toán";
    case "paid":
      return "Đã thanh toán";
    case "failed":
      return "Thanh toán thất bại";
    case "refunded":
      return "Đã hoàn tiền";
    default:
      return v || "-";
  }
}

function goShop() {
  router.push("/shop/products");
}

function goOrders() {
  router.push("/shop/orders");
}
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: "FILL" 1, "wght" 400, "GRAD" 0, "opsz" 24;
}
</style>