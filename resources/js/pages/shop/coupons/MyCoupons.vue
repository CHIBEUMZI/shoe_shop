<template>
  <main class="max-w-7xl mx-auto px-4 lg:px-8 py-8 md:py-10">
    <div class="mb-10 flex items-center justify-between gap-4">
      <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
        Mã giảm giá của tôi
      </h1>
      <router-link to="/shop/coupons"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-primary text-primary font-medium hover:bg-primary/10 transition-colors">
        <span class="material-symbols-outlined text-lg">arrow_back</span>
        Quay về trang nhận mã 
      </router-link>
    </div>
    <!-- Loading State -->
    <div v-if="loading" class="py-16 text-center text-sm text-slate-500">
      Đang tải...
    </div>

    <!-- Not Authenticated -->
    <div v-else-if="!authStore.isAuthenticated" class="py-16 text-center">
      <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
        <span class="material-symbols-outlined text-3xl text-slate-400">lock</span>
      </div>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
        Vui lòng đăng nhập
      </h2>
      <p class="text-slate-500 mb-6">Đăng nhập để xem và quản lý mã giảm giá của bạn.</p>
      <router-link to="/login"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-primary/90 transition-colors">
        <span class="material-symbols-outlined text-sm">login</span>
        Đăng nhập ngay
      </router-link>
    </div>

    <!-- Empty State -->
    <div v-else-if="myCoupons.length === 0" class="py-16 text-center">
      <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
        <span class="material-symbols-outlined text-3xl text-slate-400">receipt_long</span>
      </div>
      <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
        Chưa có mã giảm giá nào
      </h2>
      <p class="text-slate-500 mb-6">Hãy nhận mã giảm giá từ cửa hàng nhé!</p>
      <router-link to="/shop/coupons"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white font-semibold hover:bg-primary/90 transition-colors">
        <span class="material-symbols-outlined text-sm">local_offer</span>
        Nhận mã giảm giá
      </router-link>
    </div>

    <!-- Coupons List -->
    <div v-else>
      <!-- Filter tabs -->
      <div class="flex flex-wrap gap-2 mb-6">
        <button v-for="tab in tabs" :key="tab.value" type="button"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
          :class="activeTab === tab.value
            ? 'bg-primary text-white'
            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'" @click="activeTab = tab.value">
          <span class="flex items-center gap-2">
            <span class="material-symbols-outlined text-base">{{ tab.icon }}</span>
            {{ tab.label }}
            <span v-if="tab.count !== undefined" class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold"
              :class="activeTab === tab.value ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-700'">
              {{ tab.count }}
            </span>
          </span>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
          <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ myCoupons.length }}</div>
          <div class="text-xs text-slate-500 flex items-center gap-1 mt-1">
            <span class="material-symbols-outlined text-sm">confirmation_number</span>
            Tổng mã
          </div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
          <div class="text-2xl font-bold text-emerald-600">{{ usableCount }}</div>
          <div class="text-xs text-slate-500 flex items-center gap-1 mt-1">
            <span class="material-symbols-outlined text-sm text-emerald-500">check_circle</span>
            Còn hiệu lực
          </div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
          <div class="text-2xl font-bold text-amber-600">{{ usedCount }}</div>
          <div class="text-xs text-slate-500 flex items-center gap-1 mt-1">
            <span class="material-symbols-outlined text-sm text-amber-500">done_all</span>
            Đã sử dụng
          </div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
          <div class="text-2xl font-bold text-red-500">{{ expiredCount }}</div>
          <div class="text-xs text-slate-500 flex items-center gap-1 mt-1">
            <span class="material-symbols-outlined text-sm text-red-500">event_busy</span>
            Hết hạn
          </div>
        </div>
      </div>

      <!-- Coupons Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div v-for="item in filteredCoupons" :key="item.id"
          class="relative rounded-xl overflow-hidden bg-white dark:bg-slate-900 border-2 shadow-sm hover:shadow-lg transition-all"
          :class="{
            'opacity-60': item.is_expired || item.is_not_started,
            'border-emerald-300 dark:border-emerald-600': item.is_usable && !item.is_used,
            'border-slate-300 dark:border-slate-600': item.is_used,
            'border-red-200 dark:border-red-800': item.is_expired && !item.is_used,
            'border-amber-200 dark:border-amber-800': item.is_not_started,
          }">
          <!-- Top gradient bar -->
          <div class="absolute top-0 left-0 right-0 h-1.5 z-10" :class="{
            'bg-gradient-to-r from-emerald-400 to-teal-500': item.is_usable && !item.is_used,
            'bg-gradient-to-r from-slate-300 to-slate-400': item.is_used,
            'bg-gradient-to-r from-red-400 to-red-500': item.is_expired && !item.is_used,
            'bg-gradient-to-r from-amber-400 to-amber-500': item.is_not_started,
          }"></div>

          <div class="p-5 pt-6">
            <!-- Discount & Code -->
            <div class="flex items-start justify-between gap-3 mb-3">
              <div class="flex-1 min-w-0">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold mb-2 border"
                  :class="{
                    'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800': item.is_usable && !item.is_used,
                    'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:border-slate-600': item.is_used,
                    'bg-red-50 text-red-600 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800': item.is_expired && !item.is_used,
                    'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800': item.is_not_started,
                  }">
                  <span class="material-symbols-outlined text-sm">sell</span>
                  {{ item.coupon.type === 'percentage' ? 'Giảm ' + item.coupon.value + '%' : 'Giảm ' +
                    formatMoney(item.coupon.value) }}
                </div>
                <h3 class="font-bold text-base text-slate-900 dark:text-white leading-tight">{{ item.coupon.name }}</h3>
              </div>
              <div class="flex flex-col items-end gap-2 shrink-0 max-w-[min(100%,12rem)]">
                <div v-if="item.is_used"
                  class="px-2.5 py-1 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold flex items-center gap-1 whitespace-nowrap">
                  <span class="material-symbols-outlined text-sm">done_all</span>
                  Đã sử dụng
                </div>
                <div v-else-if="item.is_expired"
                  class="px-2.5 py-1 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-semibold flex items-center gap-1 whitespace-nowrap">
                  <span class="material-symbols-outlined text-sm">event_busy</span>
                  Đã hết hạn
                </div>
                <div v-else-if="item.is_not_started"
                  class="px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-xs font-semibold flex items-center gap-1 whitespace-nowrap">
                  <span class="material-symbols-outlined text-sm">schedule</span>
                  Chưa đến ngày
                </div>
                <div v-else
                  class="px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-xs font-semibold flex items-center gap-1 whitespace-nowrap">
                  <span class="material-symbols-outlined text-sm">verified</span>
                  Còn hiệu lực
                </div>
                <div class="text-right w-full">
                  <div class="text-[10px] text-slate-400 uppercase tracking-wider mb-1">Mã</div>
                  <div
                    class="font-mono font-bold text-sm px-2.5 py-1.5 rounded-lg border cursor-pointer transition-colors text-right"
                    :class="{
                      'text-primary bg-primary/10 border-primary/20 hover:bg-primary/20': item.is_usable && !item.is_used,
                      'text-slate-500 bg-slate-50 border-slate-200 dark:bg-slate-700 dark:border-slate-600': item.is_used || item.is_expired || item.is_not_started,
                    }" @click="item.is_usable && !item.is_used ? copyCode(item.coupon.code) : null">
                    {{ item.coupon.code }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Description -->
            <p v-if="item.coupon.description" class="text-sm text-slate-500 dark:text-slate-400 mb-3 line-clamp-2">
              {{ item.coupon.description }}
            </p>

            <!-- Info -->
            <div class="space-y-1.5 mb-4">
              <div v-if="item.coupon.min_order_amount" class="flex items-center gap-2 text-xs text-slate-500">
                <span class="material-symbols-outlined text-sm">payments</span>
                Đơn tối thiểu {{ formatMoney(item.coupon.min_order_amount) }}
              </div>
              <div v-if="item.coupon.max_discount && item.coupon.type === 'percentage'"
                class="flex items-center gap-2 text-xs text-slate-500">
                <span class="material-symbols-outlined text-sm">trending_down</span>
                Giảm tối đa {{ formatMoney(item.coupon.max_discount) }}
              </div>
              <div class="flex items-center gap-2 text-xs" :class="getExpiryClass(item)">
                <span class="material-symbols-outlined text-sm">schedule</span>
                {{ getExpiryText(item) }}
              </div>
              <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="material-symbols-outlined text-sm">calendar_today</span>
                Nhận lúc: {{ formatDate(item.claimed_at) }}
              </div>
            </div>

            <!-- Action button -->
            <button v-if="item.is_usable && !item.is_used" type="button"
              class="w-full py-2.5 rounded-lg bg-primary text-white text-sm font-semibold hover:bg-primary/90 transition-colors"
              @click="copyCode(item.coupon.code)">
              <span class="flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-base">content_copy</span>
                Sao chép mã
              </span>
            </button>

            <div v-else
              class="w-full py-2.5 rounded-lg text-slate-400 dark:text-slate-500 text-sm font-semibold text-center bg-slate-100 dark:bg-slate-800">
              <span class="flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-sm">{{ getDisabledIcon(item) }}</span>
                {{ getDisabledReason(item) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty filtered result -->
      <div v-if="filteredCoupons.length === 0" class="py-16 text-center">
        <div
          class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
          <span class="material-symbols-outlined text-2xl text-slate-400">search_off</span>
        </div>
        <h2 class="text-base font-bold text-slate-900 dark:text-white mb-1">
          Không có mã giảm giá nào
        </h2>
        <p class="text-sm text-slate-500">Thử chọn tab khác để xem các mã giảm giá.</p>
      </div>

      <!-- Bottom CTA -->
      <div class="mt-10 text-center">
        <router-link to="/shop/coupons"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
          <span class="material-symbols-outlined text-sm">local_offer</span>
          Tìm thêm mã giảm giá mới
        </router-link>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import couponService from "../../../services/public/couponService";
import { useAlert } from "../../../composables/useAlert";
import { useAuthStore } from "../../../stores/auth";

const notify = useAlert();
const authStore = useAuthStore();

const myCoupons = ref([]);
const loading = ref(true);
const activeTab = ref("all");

const tabs = computed(() => [
  { label: "Tất cả", value: "all", icon: "confirmation_number", count: myCoupons.value.length },
  { label: "Còn hiệu lực", value: "usable", icon: "check_circle", count: usableCount.value },
  { label: "Đã sử dụng", value: "used", icon: "done_all", count: usedCount.value },
  { label: "Hết hạn", value: "expired", icon: "event_busy", count: expiredCount.value },
]);

const usableCount = computed(() => myCoupons.value.filter((c) => c.is_usable && !c.is_used).length);
const usedCount = computed(() => myCoupons.value.filter((c) => c.is_used).length);
const expiredCount = computed(() => myCoupons.value.filter((c) => c.is_expired || (!c.is_used && !c.is_usable)).length);

const filteredCoupons = computed(() => {
  switch (activeTab.value) {
    case "usable":
      return myCoupons.value.filter((c) => c.is_usable && !c.is_used);
    case "used":
      return myCoupons.value.filter((c) => c.is_used);
    case "expired":
      return myCoupons.value.filter((c) => c.is_expired || (!c.is_used && !c.is_usable));
    default:
      return myCoupons.value;
  }
});

function formatMoney(value) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

function formatDate(dateStr) {
  if (!dateStr) return "";
  return new Date(dateStr).toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function getExpiryText(item) {
  if (!item.coupon.expires_at) return "Không giới hạn";
  const expiryDate = new Date(item.coupon.expires_at);
  const now = new Date();
  const diffDays = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));

  if (diffDays < 0) return "Đã hết hạn";
  if (diffDays === 0) return "Hết hạn hôm nay";
  if (diffDays === 1) return "Hết hạn ngày mai";
  if (diffDays <= 7) return `Còn ${diffDays} ngày`;

  return `Hết hạn: ${expiryDate.toLocaleDateString("vi-VN")}`;
}

function getExpiryClass(item) {
  if (item.is_expired) return "text-red-500";
  if (item.is_not_started) return "text-amber-500";
  return "text-emerald-600 dark:text-emerald-400";
}

function getDisabledReason(item) {
  if (item.is_used) return "Đã sử dụng";
  if (item.is_expired) return "Đã hết hạn";
  if (item.is_not_started) return "Chưa đến ngày";
  return "Không khả dụng";
}

function getDisabledIcon(item) {
  if (item.is_used) return "done_all";
  if (item.is_expired) return "event_busy";
  if (item.is_not_started) return "schedule";
  return "block";
}

async function copyCode(code) {
  try {
    await navigator.clipboard.writeText(code);
    notify.success("Đã sao chép mã giảm giá!", { title: "Thành công", duration: 2000 });
  } catch {
    notify.warning("Không thể sao chép. Vui lòng copy thủ công.", { title: "Thông báo" });
  }
}

async function fetchMyCoupons() {
  if (!authStore.loaded && !authStore.user) {
    await authStore.fetchMe();
  }

  if (!authStore.isLoggedIn) {
    loading.value = false;
    return;
  }

  loading.value = true;
  try {
    const res = await couponService.getMyCoupons();
    myCoupons.value = res?.data?.data ?? [];
  } catch (e) {
    notify.error("Không tải được mã giảm giá.");
    myCoupons.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  fetchMyCoupons();
});
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
}
</style>
