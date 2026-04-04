<template>
  <main class="max-w-7xl mx-auto px-4 lg:px-8 py-8 md:py-10">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
        Mã giảm giá
      </h1>
      <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
        Nhận ngay các mã giảm giá hấp dẫn từ cửa hàng
      </p>
    </div>

    <!-- Stats Summary -->
    <div v-if="!loading && coupons.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
        <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ coupons.length }}</div>
        <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-1">
          <span class="material-symbols-outlined text-sm">confirmation_number</span>
          Tổng mã
        </div>
      </div>
      <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
        <div class="text-2xl font-bold text-emerald-500">{{ coupons.filter(c => c.is_claimed).length }}</div>
        <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-1">
          <span class="material-symbols-outlined text-sm text-emerald-500">check_circle</span>
          Đã nhận
        </div>
      </div>
      <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
        <div class="text-2xl font-bold text-amber-500">{{ coupons.filter(c => !c.is_claimed && !isExpired(c)).length }}</div>
        <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-1">
          <span class="material-symbols-outlined text-sm text-amber-500">new_releases</span>
          Còn lại
        </div>
      </div>
      <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
        <div class="text-2xl font-bold text-red-500">{{ coupons.filter(c => isExpired(c)).length }}</div>
        <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-1">
          <span class="material-symbols-outlined text-sm text-red-500">event_busy</span>
          Hết hạn
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="py-16 text-center text-sm text-slate-500">
      Đang tải mã giảm giá...
    </div>

    <!-- Empty State -->
    <div v-else-if="coupons.length === 0" class="py-16 text-center">
      <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
        <span class="material-symbols-outlined text-3xl text-slate-400">local_offer_off</span>
      </div>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white">Chưa có mã giảm giá nào</h2>
      <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Hãy quay lại sau để xem các ưu đãi mới nhé!</p>
    </div>

    <!-- Coupons Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="coupon in coupons"
        :key="coupon.id"
        class="relative rounded-xl overflow-hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all"
        :class="{ 'opacity-60': coupon.is_claimed }"
      >
        <!-- Top color bar -->
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary via-pink-500 to-amber-400"></div>

        <!-- Claimed badge -->
        <div
          v-if="coupon.is_claimed"
          class="absolute top-4 right-4 z-10 flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs font-semibold"
        >
          <span class="material-symbols-outlined text-sm">check_circle</span>
          Đã nhận
        </div>

        <div class="p-5 pt-6">
          <!-- Discount & Code -->
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
              <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary/10 text-primary text-xs font-semibold mb-2">
                <span class="material-symbols-outlined text-sm">sell</span>
                {{ coupon.type === 'percentage' ? 'Giảm ' + coupon.value + '%' : 'Giảm ' + formatMoney(coupon.value) }}
              </div>
              <h3 class="font-bold text-base text-slate-900 dark:text-white leading-tight">{{ coupon.name }}</h3>
            </div>
            <div class="text-right ml-3">
              <div class="text-[10px] text-slate-400 uppercase tracking-wider mb-1">Mã</div>
              <div class="font-mono font-bold text-sm text-primary bg-primary/10 px-2.5 py-1.5 rounded-lg">
                {{ coupon.code }}
              </div>
            </div>
          </div>

          <!-- Description -->
          <p v-if="coupon.description" class="text-sm text-slate-500 dark:text-slate-400 mb-3 line-clamp-2">
            {{ coupon.description }}
          </p>

          <!-- Info -->
          <div class="space-y-1.5 mb-4">
            <div v-if="coupon.min_order_amount" class="flex items-center gap-2 text-xs text-slate-500">
              <span class="material-symbols-outlined text-sm">payments</span>
              Đơn tối thiểu {{ formatMoney(coupon.min_order_amount) }}
            </div>
            <div v-if="coupon.max_discount && coupon.type === 'percentage'" class="flex items-center gap-2 text-xs text-slate-500">
              <span class="material-symbols-outlined text-sm">trending_down</span>
              Giảm tối đa {{ formatMoney(coupon.max_discount) }}
            </div>
            <div class="flex items-center gap-2 text-xs" :class="isExpired(coupon) ? 'text-red-500' : 'text-emerald-600'">
              <span class="material-symbols-outlined text-sm">schedule</span>
              {{ getExpiryText(coupon) }}
            </div>
          </div>

          <!-- Action -->
          <button
            v-if="!coupon.is_claimed"
            type="button"
            class="w-full py-2.5 rounded-lg font-semibold text-sm transition-colors"
            :class="isExpired(coupon)
              ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 cursor-not-allowed'
              : 'bg-primary text-white hover:bg-primary/90'"
            :disabled="claimingId === coupon.id || isExpired(coupon)"
            @click="claimCoupon(coupon)"
          >
            <span v-if="claimingId === coupon.id">Đang xử lý...</span>
            <span v-else-if="isExpired(coupon)">Đã hết hạn</span>
            <span v-else>Nhận mã giảm giá</span>
          </button>

          <div v-else class="py-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-sm font-semibold text-center">
            Đã nhận thành công
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom CTA -->
    <div v-if="!loading && coupons.length > 0" class="mt-10 text-center">
      <router-link
        to="/shop/my-coupons"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
      >
        <span class="material-symbols-outlined text-sm">wallet</span>
        Xem mã giảm giá của tôi
      </router-link>
    </div>
  </main>
</template>

<script setup>
import { onMounted, ref } from "vue";
import couponService from "../../../services/public/couponService";
import { useAlert } from "../../../composables/useAlert";
import { useAuthStore } from "../../../stores/auth";

const notify = useAlert();
const authStore = useAuthStore();

const coupons = ref([]);
const loading = ref(true);
const claimingId = ref(null);

onMounted(async () => {
  if (!authStore.loaded && !authStore.user) {
    await authStore.fetchMe();
  }
  fetchCoupons();
});

function formatMoney(value) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

function isExpired(coupon) {
  if (!coupon.expires_at) return false;
  return new Date(coupon.expires_at) < new Date();
}

function getExpiryText(coupon) {
  if (!coupon.expires_at) return "Không giới hạn";
  const expiryDate = new Date(coupon.expires_at);
  const now = new Date();
  const diffDays = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));

  if (diffDays < 0) return "Đã hết hạn";
  if (diffDays === 0) return "Hết hạn hôm nay";
  if (diffDays === 1) return "Hết hạn ngày mai";
  if (diffDays <= 7) return `Còn ${diffDays} ngày`;

  return `Hết hạn: ${expiryDate.toLocaleDateString("vi-VN")}`;
}

async function fetchCoupons() {
  loading.value = true;
  try {
    const res = await couponService.getAvailableCoupons();
    coupons.value = res?.data?.data ?? [];
  } catch (e) {
    notify.error("Không tải được mã giảm giá.");
    coupons.value = [];
  } finally {
    loading.value = false;
  }
}

async function claimCoupon(coupon) {
  if (!authStore.loaded && !authStore.user) {
    try {
      await authStore.fetchMe();
    } catch (e) {
      notify.warning("Vui lòng đăng nhập để nhận mã giảm giá.", { title: "Thông báo" });
      return;
    }
  }

  if (!authStore.isLoggedIn) {
    notify.warning("Vui lòng đăng nhập để nhận mã giảm giá.", { title: "Thông báo" });
    return;
  }

  claimingId.value = coupon.id;
  try {
    await couponService.claimCoupon(coupon.code);
    coupon.is_claimed = true;
    notify.success("Nhận mã giảm giá thành công!", { title: "Thành công", duration: 2000 });
  } catch (e) {
    const msg = e?.response?.data?.message || "Không thể nhận mã giảm giá.";
    notify.error(msg, { title: "Lỗi" });
  } finally {
    claimingId.value = null;
  }
}
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
}
</style>
