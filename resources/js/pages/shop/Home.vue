<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-white">
    <Banner position="home_top" :fallback-action="goProducts" />

    <main class="mx-auto max-w-7xl space-y-6 px-4 pb-10 pt-4 sm:space-y-8 lg:px-6 lg:pt-6">
      <div v-if="alert.visible" class="fixed right-4 top-4 z-50 w-[min(92vw,420px)]">
        <BaseAlert
          :type="alert.type"
          :title="alert.title"
          :message="alert.message"
          :duration="3500"
          @close="hideAlert"
        />
      </div>
      <!-- Coupons row -->
      <section class="mb-8">
        <div class="mb-4 flex items-center gap-3">
          <span class="h-6 w-1 rounded-full bg-red-600"></span>
          <h2 class="text-sm font-black uppercase tracking-[0.18em] text-slate-900 dark:text-white">
            Khuyến mãi dành cho bạn
          </h2>
        </div>

        <div v-if="loadingCoupons" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
          <div v-for="i in 4" :key="i" class="h-[92px] rounded-xl bg-slate-200/70 dark:bg-slate-800 animate-pulse"></div>
        </div>

        <div v-else-if="coupons.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-white py-8 text-center text-sm font-medium text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
          Chưa có mã giảm giá nào.
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
          <article
            v-for="coupon in coupons"
            :key="coupon.id"
            class="group relative overflow-hidden rounded-[24px] bg-white shadow-[0_14px_30px_rgba(15,23,42,0.08)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_22px_44px_rgba(15,23,42,0.12)] dark:bg-slate-900"
          >
            <div class="absolute inset-y-0 left-0 w-3 bg-red-600"></div>
            <div class="absolute inset-y-0 left-3 w-[2px] border-l border-dashed border-red-200/80 dark:border-red-900/60"></div>

            <div class="grid min-h-[180px] gap-4 p-4 pl-6 sm:grid-cols-[1fr_auto] sm:items-stretch">
              <div class="flex flex-col justify-between gap-4">
                <div class="space-y-3">
                  <div class="flex items-center gap-2">
                    <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500">
                      Mã giảm giá
                    </span>
                  </div>

                  <h3 class="line-clamp-2 break-words text-[22px] font-extrabold leading-7 text-slate-900 dark:text-white">
                    {{ coupon.name }}
                  </h3>

                  <p class="text-[13px] font-medium leading-5 text-slate-500 dark:text-slate-400">
                    Giảm {{ coupon.value_formatted }} • Áp dụng cho đơn từ {{ moneyVND(coupon.min_order_amount || 0) }}
                  </p>
                  <div>
                  <p class="break-words text-[12px] font-black tracking-[0.22em] text-slate-900 dark:text-white">
                    Mã: {{ coupon.code }}
                  </p>
                </div>
                </div>

                <div class="flex items-center gap-2 text-[12px] font-medium text-slate-500 dark:text-slate-400">
                  <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                    ⏳
                  </span>
                  <span>HSD: <span class="font-bold text-slate-900 dark:text-white">{{ coupon.expires_at_formatted || 'Không giới hạn' }}</span></span>
                </div>
                <button
                  type="button"
                  class="mt-4 rounded-full bg-slate-950 px-4 py-2.5 text-[12px] font-black text-white transition-all duration-300 hover:bg-primary hover:shadow-lg hover:shadow-primary/25 disabled:cursor-not-allowed disabled:opacity-60"
                  :disabled="claimingCouponCode === coupon.code"
                  @click="claimCoupon(coupon.code)"
                >
                  {{ claimingCouponCode === coupon.code ? 'Đang nhận...' : 'Nhận voucher' }}
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>

      <!-- Sale block -->
      <section
        class="rounded-[18px] bg-[#e31212] p-3 shadow-[0_18px_50px_rgba(226,18,18,0.32)] md:p-4"
      >
        <div class="mb-3 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div class="flex items-center gap-2 text-white">
            <span class="text-xl">⚡</span>
            <h2 class="text-sm font-black uppercase tracking-[0.16em] md:text-base">
              Sản phẩm khuyến mãi
            </h2>
          </div>

          <div class="flex items-center gap-2 text-center">
            <div v-for="item in countdownItems" :key="item.label" class="rounded-md bg-white px-3 py-2 shadow-sm">
              <div class="text-lg font-black leading-none text-slate-900">{{ item.value }}</div>
              <div class="mt-1 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                {{ item.label }}
              </div>
            </div>
          </div>
        </div>

        <div v-if="loadingBigSale" class="grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-5">
          <div v-for="i in 5" :key="i" class="h-[270px] rounded-lg bg-white/90 animate-pulse"></div>
        </div>

        <div v-else-if="bigSaleProducts.length === 0" class="rounded-xl bg-white/95 py-10 text-center text-sm font-medium text-slate-500">
          Chưa có sản phẩm khuyến mãi.
        </div>

        <div v-else class="grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-5">
          <article
            v-for="p in bigSaleProducts"
            :key="p.id"
            class="group overflow-hidden rounded-lg bg-white shadow-sm transition-transform duration-300 hover:-translate-y-1"
            @click="goDetail(p.slug)"
          >
            <div class="relative aspect-square bg-white p-2">
              <span
                v-if="p.discountPercent"
                class="absolute left-0 top-0 z-10 rounded-br-md bg-red-600 px-2 py-1 text-[10px] font-black text-white"
              >
                -{{ p.discountPercent }}%
              </span>
              <span class="absolute right-0 top-0 z-10 rounded-bl-md bg-emerald-600 px-2 py-1 text-[10px] font-black text-white">
                NEW
              </span>

              <img
                :src="p.image"
                :alt="p.name"
                class="h-full w-full object-contain transition-transform duration-500 group-hover:scale-105"
              />
            </div>

            <div class="border-t border-slate-100 p-3">
              <p class="truncate text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">
                {{ p.brand }}
              </p>
              <h3 class="mt-1 line-clamp-2 min-h-[2.75rem] text-xs font-semibold leading-5 text-slate-900">
                {{ p.name }}
              </h3>

              <div class="mt-2 flex items-center gap-2">
                <p class="text-sm font-black text-red-600">{{ moneyVND(p.price) }}</p>
                <p v-if="p.compareAt" class="text-[11px] text-slate-400 line-through">
                  {{ moneyVND(p.compareAt) }}
                </p>
              </div>
            </div>
          </article>
        </div>

        <div class="mt-4 flex items-center justify-center gap-1.5">
          <span
            v-for="dot in 4"
            :key="dot"
            class="h-1.5 w-1.5 rounded-full bg-white/70"
            :class="dot === 2 ? 'w-4 bg-white' : ''"
          />
        </div>

        <div class="mt-4 flex justify-center">
          <button
            type="button"
            class="rounded-lg bg-white px-8 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition-transform hover:-translate-y-0.5"
            @click="goProducts"
          >
            Xem tất cả »
          </button>
        </div>
      </section>

      <!-- Featured products -->
      <section class="mb-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between mb-4">
        <div>
          <h3 class="text-xl md:text-3xl font-black tracking-tight text-slate-950 dark:text-white">
            Sản phẩm nổi bật
          </h3>
          <p class="text-slate-500 mt-1 text-sm font-medium max-w-xl">
            Những mẫu đáng chú ý được ưu tiên hiển thị
          </p>
        </div>

        <button
          type="button"
          class="self-start text-primary text-[11px] md:text-xs font-extrabold flex items-center gap-1 hover:gap-2 transition-all"
          @click="goProducts"
        >
          Xem tất cả
          <span class="material-symbols-outlined text-xs">arrow_forward</span>
        </button>
      </div>

      <div class="relative group/carousel px-0 sm:px-2">
        <!-- Left Button -->
        <button
          v-if="featured.length > 4"
          type="button"
          class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-2 z-10 w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-lg flex items-center justify-center hover:bg-slate-50 dark:hover:bg-slate-700 transition-all opacity-0 group-hover/carousel:opacity-100 disabled:opacity-0"
          :disabled="currentSlide === 0"
          @click="prevSlide"
        >
          <span class="material-symbols-outlined text-base text-slate-600 dark:text-slate-300">chevron_left</span>
        </button>

        <!-- Right Button -->
        <button
          v-if="featured.length > 4"
          type="button"
          class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-2 z-10 w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-lg flex items-center justify-center hover:bg-slate-50 dark:hover:bg-slate-700 transition-all opacity-0 group-hover/carousel:opacity-100 disabled:opacity-0"
          :disabled="currentSlide >= maxSlide"
          @click="nextSlide"
        >
          <span class="material-symbols-outlined text-base text-slate-600 dark:text-slate-300">chevron_right</span>
        </button>

        <!-- Loading State -->
        <div
          v-if="loadingFeatured"
          class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 md:gap-3"
        >
          <div v-for="i in 4" :key="i" class="bg-slate-100 rounded-lg animate-pulse h-48"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="featured.length === 0" class="w-full text-xs text-slate-500 py-8 text-center">
          Chưa có sản phẩm nổi bật.
        </div>

        <!-- Products Grid -->
        <div
          v-else
          class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 md:gap-3"
        >
          <article
            v-for="p in visibleProducts"
            :key="p.id"
            class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden hover:border-primary transition-colors cursor-pointer flex flex-col"
            @click="goDetail(p.slug)"
          >
            <div class="relative aspect-square bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <span
                v-if="p.discountPercent"
                class="absolute left-0 top-0 z-10 rounded-br-md bg-red-600 px-2 py-1 text-[10px] font-black text-white"
              >
                -{{ p.discountPercent }}%
              </span>
              <img
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                :src="p.image"
                :alt="p.name"
              />
            </div>

            <div class="p-3 flex flex-col flex-1">
              <p
                class="text-[9px] tracking-[0.15em] text-slate-500 dark:text-slate-400 uppercase font-bold truncate"
              >
                {{ p.brand }}
              </p>

              <h4
                class="mt-1 font-bold text-[13px] text-slate-900 dark:text-white line-clamp-2 leading-snug"
              >
                {{ p.name }}
              </h4>

              <div class="flex items-center gap-2 mt-auto pt-2">
                <p class="text-primary font-black text-sm">
                  {{ moneyVND(p.price) }}
                </p>

                <p
                  v-if="p.compareAt"
                  class="text-[10px] text-slate-400 line-through"
                >
                  {{ moneyVND(p.compareAt) }}
                </p>
              </div>

              <button
                class="mt-2 w-full py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-bold uppercase tracking-wider rounded-md hover:bg-primary hover:text-white dark:hover:bg-primary dark:hover:text-white transition-colors"
              >
                Xem chi tiết
              </button>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section
      class="rounded-xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white px-4 sm:px-5 md:px-8 py-7 sm:py-8 md:py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-xl"
    >
      <div>
        <div
          class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-[0.15em] mb-2"
        >
          <span class="material-symbols-outlined text-xs">shopping_bag</span>
          Bộ sưu tập mới
        </div>

        <h3 class="text-lg md:text-2xl font-black tracking-tight mb-1">
          Khám phá toàn bộ bộ sưu tập giày
        </h3>
        <p class="text-slate-300 max-w-2xl text-xs md:text-sm leading-6 font-medium">
          Xem thêm nhiều mẫu sneaker, giày chạy bộ, giày thời trang với nhiều mức giá và
          thương hiệu khác nhau.
        </p>
      </div>

      <button
        type="button"
        class="px-5 py-2.5 rounded-xl bg-white text-slate-900 text-sm font-extrabold hover:opacity-90 hover:-translate-y-0.5 transition-all"
        @click="goProducts"
      >
        Đi đến cửa hàng
      </button>
    </section>
    </main>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, computed } from "vue";
import { useRouter } from "vue-router";

import Banner from "../../components/shop/Banner.vue";
import BaseAlert from "../../components/BaseAlert.vue";
import couponPublicService from "../../services/public/couponService";
import productPublicService from "../../services/public/productService";

const router = useRouter();

const API_BASE = import.meta.env.VITE_API_URL || "";

const featured = ref([]);
const bigSaleProducts = ref([]);
const coupons = ref([]);
const loadingFeatured = ref(false);
const loadingBigSale = ref(false);
const loadingCoupons = ref(false);
const claimingCouponCode = ref("");
const currentSlide = ref(0);
const visibleSlides = ref(4);
const alert = ref({
  visible: false,
  type: "info",
  title: "",
  message: "",
});
let alertTimer = null;
let countdownTimer = null;
const now = ref(Date.now());

const saleEndsAt = computed(() => {
  const configured = import.meta.env.VITE_HOME_SALE_END_AT;
  const parsed = configured ? new Date(configured).getTime() : NaN;
  if (!Number.isNaN(parsed)) return parsed;

  const twoDaysLater = new Date();
  twoDaysLater.setDate(twoDaysLater.getDate() + 2);
  twoDaysLater.setHours(0, 0, 0, 0);
  return twoDaysLater.getTime();
});

const countdownMs = computed(() => Math.max(0, saleEndsAt.value - now.value));

const countdownItems = computed(() => {
  const totalSeconds = Math.floor(countdownMs.value / 1000);
  const days = Math.floor(totalSeconds / 86400);
  const hours = Math.floor((totalSeconds % 86400) / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = totalSeconds % 60;

  return [
    { value: String(days).padStart(2, "0"), label: "Ngày" },
    { value: String(hours).padStart(2, "0"), label: "Giờ" },
    { value: String(minutes).padStart(2, "0"), label: "Phút" },
    { value: String(seconds).padStart(2, "0"), label: "Giây" },
  ];
});

const maxSlide = computed(() => {
  return Math.max(0, featured.value.length - visibleSlides.value);
});

const visibleProducts = computed(() => {
  return featured.value.slice(currentSlide.value, currentSlide.value + visibleSlides.value);
});

function prevSlide() {
  if (currentSlide.value > 0) {
    currentSlide.value--;
  }
}

function nextSlide() {
  if (currentSlide.value < maxSlide.value) {
    currentSlide.value++;
  }
}

function buildImageUrl(pathOrUrl) {
  if (!pathOrUrl) return "";
  if (String(pathOrUrl).startsWith("http")) return pathOrUrl;
  if (String(pathOrUrl).startsWith("/")) return `${API_BASE}${pathOrUrl}`;
  return `${API_BASE}/storage/${pathOrUrl}`;
}

function firstVariantImageUrl(p) {
  const v0 = Array.isArray(p.variants) ? p.variants[0] : null;
  const imgs = v0 && Array.isArray(v0.images) ? v0.images : [];
  return imgs.length ? imgs[0].url : "";
}

function getThumbnailUrl(p) {
  if (p.thumbnail) return buildImageUrl(p.thumbnail);
  return buildImageUrl(firstVariantImageUrl(p));
}

function getPrice(p) {
  const sale =
    p.base_sale_price !== null && p.base_sale_price !== undefined
      ? Number(p.base_sale_price)
      : null;
  const base =
    p.base_price !== null && p.base_price !== undefined ? Number(p.base_price) : 0;

  return sale !== null ? sale : base;
}

function getCompareAt(p) {
  if (p.base_sale_price === null || p.base_sale_price === undefined) return null;
  return Number(p.base_price ?? 0);
}

function getDiscountPercent(basePrice, salePrice) {
  const base = Number(basePrice || 0);
  const sale = Number(salePrice || 0);
  if (!base || !sale || sale >= base) return 0;
  return Math.round(((base - sale) / base) * 100);
}

function mapProduct(p) {
  const price = getPrice(p);
  const compareAt = getCompareAt(p);
  const discountPercent = compareAt && compareAt > price ? getDiscountPercent(compareAt, price) : 0;

  return {
    id: p.id,
    name: p.name,
    slug: p.slug,
    brand: p.brand?.name ?? "Brand",
    image: getThumbnailUrl(p),
    price,
    compareAt: compareAt && compareAt > price ? compareAt : null,
    discountPercent,
  };
}

function moneyVND(v) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(Number(v || 0));
}

async function fetchCoupons() {
  loadingCoupons.value = true;

  try {
    const res = await couponPublicService.getAvailableCoupons();
    coupons.value = res?.data?.data ?? [];
  } catch {
    coupons.value = [];
  } finally {
    loadingCoupons.value = false;
  }
}

async function fetchFeatured() {
  loadingFeatured.value = true;
  try {
    const res = await productPublicService.list({
      per_page: 12,
      page: 1,
      featured: 1,
      sort: "latest",
    });

    featured.value = (res?.data?.data ?? []).map(mapProduct);
  } catch {
    featured.value = [];
  } finally {
    loadingFeatured.value = false;
  }
}

async function fetchBigSaleProducts() {
  loadingBigSale.value = true;
  try {
    const res = await productPublicService.list({
      per_page: 12,
      page: 1,
      sale: 1,
      sort: "latest",
    });

    const items = (res?.data?.data ?? []).map(mapProduct);

    bigSaleProducts.value = items
      .filter((p) => p.compareAt && p.discountPercent >= 15)
      .sort((a, b) => b.discountPercent - a.discountPercent)
      .slice(0, 5);
  } catch {
    bigSaleProducts.value = [];
  } finally {
    loadingBigSale.value = false;
  }
}

function clearAlertTimer() {
  if (alertTimer) {
    clearTimeout(alertTimer);
    alertTimer = null;
  }
}

function hideAlert() {
  clearAlertTimer();
  alert.value.visible = false;
}

function showAlert({ type = "info", title = "", message = "" }, duration = 3500) {
  clearAlertTimer();

  alert.value = {
    visible: true,
    type,
    title,
    message,
  };

  if (duration > 0) {
    alertTimer = setTimeout(() => {
      alert.value.visible = false;
      alertTimer = null;
    }, duration);
  }
}

async function claimCoupon(code) {
  if (!code || claimingCouponCode.value) return;

  claimingCouponCode.value = code;

  try {
    await couponPublicService.claimCoupon(code);
    showAlert({
      type: "success",
      title: "Thành công",
      message: "Đã nhận voucher thành công!",
    });
  } catch (error) {
    const status = error?.response?.status;
    const message =
      error?.response?.data?.message ||
      error?.response?.data?.error ||
      "Không thể nhận voucher. Vui lòng thử lại sau.";

    if (status === 401) {
      showAlert({
        type: "warning",
        title: "Cần đăng nhập",
        message: "Bạn cần đăng nhập để nhận voucher.",
      });
      return;
    }

    showAlert({
      type: "error",
      title: "Không thể nhận voucher",
      message,
    });
  } finally {
    claimingCouponCode.value = "";
  }
}

function goProducts() {
  router.push("/shop/products");
}

function goDetail(slug) {
  router.push(`/shop/products/${slug}`);
}

onMounted(() => {
  fetchCoupons();
  fetchFeatured();
  fetchBigSaleProducts();

  now.value = Date.now();
  countdownTimer = setInterval(() => {
    now.value = Date.now();
  }, 1000);
});

onBeforeUnmount(() => {
  clearAlertTimer();
  if (countdownTimer) {
    clearInterval(countdownTimer);
    countdownTimer = null;
  }
});
</script>
