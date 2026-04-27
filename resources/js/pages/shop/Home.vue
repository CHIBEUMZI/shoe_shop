<template>
    <!-- Hero Banner - Full Width (flush with header) -->
    <Banner position="home_top" :fallback-action="goProducts" />

    <main class="max-w-7xl mx-auto px-4 lg:px-6 pb-6 md:pb-8">
      <!-- Intro / Hero content -->
    <section
      class="mb-10 lg:mb-14 grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-6 lg:gap-8 items-center"
    >
      <div>
        <div
          class="inline-flex items-center gap-2 rounded-full bg-primary/10 text-primary px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[0.22em] mb-4"
        >
          <span class="material-symbols-outlined text-xs">local_mall</span>
          BMC Shoes
        </div>

        <h1
          class="max-w-3xl text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black leading-[1.1] tracking-[-0.02em] text-slate-950 dark:text-white mb-4"
        >
          Chọn đôi giày
          <span class="text-primary">đúng gu</span>
          <br />
          cho phong cách của bạn
        </h1>

        <p
          class="text-slate-600 dark:text-slate-300 text-sm md:text-[15px] leading-6 max-w-2xl font-medium"
        >
          Từ sneaker trẻ trung, giày chạy bộ năng động đến các mẫu thời trang hiện đại,
          cửa hàng mang đến nhiều lựa chọn phù hợp cho nhu cầu hằng ngày, học tập, đi
          chơi và vận động.
        </p>

        <div class="mt-6 flex flex-wrap gap-3">
          <button
            type="button"
            class="px-5 py-2.5 rounded-xl bg-slate-950 text-white text-sm font-extrabold shadow-lg shadow-slate-900/15 hover:-translate-y-0.5 hover:bg-primary transition-all"
            @click="goProducts"
          >
            Xem tất cả sản phẩm
          </button>

          <button
            type="button"
            class="px-5 py-2.5 rounded-xl text-sm font-extrabold text-white bg-gradient-to-r from-red-500 via-orange-500 to-yellow-500 shadow-lg shadow-red-500/40 hover:shadow-xl hover:shadow-red-500/60 hover:-translate-y-1 hover:scale-105 transition-all duration-300"
            @click="goBigSale"
          >
            🔥 Sale lớn từ 25%
          </button>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
          <div
            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300"
          >
            <span class="material-symbols-outlined text-primary text-base">bolt</span>
            Mẫu mới
          </div>
          <div
            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300"
          >
            <span class="material-symbols-outlined text-primary text-base">sell</span>
            Ưu đãi
          </div>
          <div
            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300"
          >
            <span class="material-symbols-outlined text-primary text-base">favorite</span>
            Đa phong cách
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div
          class="rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 text-white p-4 shadow-xl"
        >
          <div class="text-2xl md:text-3xl font-black tracking-tight mb-1">100+</div>
          <p class="text-slate-300 text-xs font-medium leading-5">
            Mẫu giày đa dạng
          </p>
        </div>

        <div
          class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-4 shadow-sm"
        >
          <div class="text-2xl md:text-3xl font-black tracking-tight mb-1 text-primary">
            25%+
          </div>
          <p class="text-slate-500 text-xs font-medium leading-5">
            Deal sale mỗi ngày
          </p>
        </div>

        <div
          class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-4 shadow-sm"
        >
          <div class="text-2xl md:text-3xl font-black tracking-tight mb-1">Trend</div>
          <p class="text-slate-500 text-xs font-medium leading-5">
            Thiết kế hiện đại
          </p>
        </div>

        <div
          class="rounded-2xl bg-gradient-to-br from-primary/15 to-pink-100 dark:from-primary/20 dark:to-slate-800 border border-primary/10 p-4 shadow-sm"
        >
          <div class="text-2xl md:text-3xl font-black tracking-tight mb-1 text-slate-900 dark:text-white">
            Hot
          </div>
          <p class="text-slate-600 dark:text-slate-300 text-xs font-medium leading-5">
            Sản phẩm nổi bật
          </p>
        </div>
      </div>
    </section>

    <!-- Benefits -->
    <section class="mb-10">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 flex items-start gap-3 shadow-sm"
        >
          <div
            class="size-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0"
          >
            <span class="material-symbols-outlined text-sm">workspace_premium</span>
          </div>
          <div>
            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white mb-0.5">
              Sản phẩm chọn lọc
            </h3>
            <p class="text-xs text-slate-500 leading-5 font-medium">
              Gợi ý những mẫu giày đẹp, dễ phối đồ và hợp xu hướng.
            </p>
          </div>
        </div>

        <div
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 flex items-start gap-3 shadow-sm"
        >
          <div
            class="size-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0"
          >
            <span class="material-symbols-outlined text-sm">local_fire_department</span>
          </div>
          <div>
            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white mb-0.5">
              Deal sale hấp dẫn
            </h3>
            <p class="text-xs text-slate-500 leading-5 font-medium">
              Tập trung hiển thị các sản phẩm giảm giá mạnh.
            </p>
          </div>
        </div>

        <div
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 flex items-start gap-3 shadow-sm"
        >
          <div
            class="size-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0"
          >
            <span class="material-symbols-outlined text-sm">rocket_launch</span>
          </div>
          <div>
            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white mb-0.5">
              Phong cách trẻ trung
            </h3>
            <p class="text-xs text-slate-500 leading-5 font-medium">
              Giao diện sáng, hiện đại, rõ ràng và dễ nhìn.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Big sale products -->
    <section class="mb-14">
      <div
        class="relative rounded-xl overflow-hidden p-[1px] bg-gradient-to-r from-rose-500 via-pink-500 to-orange-400 shadow-xl"
      >
        <!-- Glow effect -->
        <div class="absolute -top-12 -left-12 w-48 h-48 bg-rose-500/20 blur-[80px]"></div>
        <div class="absolute -bottom-12 -right-12 w-48 h-48 bg-orange-400/20 blur-[80px]"></div>

        <div class="relative bg-white dark:bg-slate-950 rounded-[9px] px-5 md:px-8 py-6 md:py-8">

          <!-- Header -->
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
              <div class="flex items-center gap-2 mb-2">
                <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider bg-rose-500 text-white rounded-full">
                  🔥 Flash Sale
                </span>

                <span class="text-[10px] font-bold text-rose-600 bg-rose-100 px-2.5 py-1 rounded-full">
                  Sắp kết thúc
                </span>
              </div>

              <h2 class="text-xl md:text-3xl font-black text-slate-950 dark:text-white leading-tight">
                Giảm giá lên đến <span class="text-rose-500">50%</span>
              </h2>

              <p class="text-slate-500 mt-1 text-xs md:text-sm font-medium">
                Những đôi giày hot đang sale mạnh – số lượng có hạn!
              </p>
            </div>

            <button
              class="px-5 py-2.5 rounded-xl bg-rose-500 text-white text-sm font-extrabold shadow-lg hover:scale-105 hover:shadow-xl transition-all"
              @click="goBigSale"
            >
              Mua ngay
            </button>
          </div>

          <!-- Products -->
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div v-if="loadingBigSale" class="col-span-full text-slate-500 text-sm">
              Đang tải...
            </div>

            <article
              v-for="p in bigSaleProducts"
              :key="p.id"
              class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden hover:border-primary transition-colors cursor-pointer flex flex-col"
              @click="goDetail(p.slug)"
            >
              <div class="relative aspect-square bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <span
                  v-if="p.discountPercent"
                  class="absolute top-0 left-0 bg-red-600 text-white text-[10px] font-black px-3 py-1.5 uppercase z-10 tracking-wider rounded-bl-md"
                >
                  -{{ p.discountPercent }}%
                </span>
                <img
                  class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                  :src="p.image"
                  :alt="p.name"
                />
              </div>

              <div class="p-4 flex flex-col flex-1">
                <p
                  class="text-[10px] tracking-[0.2em] text-slate-500 dark:text-slate-400 uppercase font-bold truncate"
                >
                  {{ p.brand }}
                </p>

                <h4
                  class="mt-2 font-bold text-sm text-slate-900 dark:text-white line-clamp-2 leading-snug"
                >
                  {{ p.name }}
                </h4>

                <div class="flex items-center gap-3 mt-auto pt-3">
                  <p class="text-primary font-black text-base">
                    {{ moneyVND(p.price) }}
                  </p>

                  <p
                    v-if="p.compareAt"
                    class="text-xs text-slate-400 line-through"
                  >
                    {{ moneyVND(p.compareAt) }}
                  </p>
                </div>

                <button
                  class="mt-3 w-full py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-bold uppercase tracking-wider rounded-md hover:bg-rose-500 hover:text-white transition-colors"
                >
                  Xem ngay
                </button>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured products (Carousel) -->
    <section class="mb-8">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-lg md:text-xl font-black tracking-tight text-slate-950 dark:text-white">
            Sản phẩm nổi bật
          </h3>
          <p class="text-slate-500 mt-0.5 text-[11px] md:text-xs font-medium">
            Những mẫu đáng chú ý được ưu tiên hiển thị
          </p>
        </div>

        <button
          type="button"
          class="text-primary text-[11px] md:text-xs font-extrabold flex items-center gap-1 hover:gap-2 transition-all"
          @click="goProducts"
        >
          Xem tất cả
          <span class="material-symbols-outlined text-xs">arrow_forward</span>
        </button>
      </div>

      <div class="relative group/carousel px-2">
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
          class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
        >
          <div v-for="i in 4" :key="i" class="bg-slate-100 rounded-lg animate-pulse h-64"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="featured.length === 0" class="w-full text-xs text-slate-500 py-8 text-center">
          Chưa có sản phẩm nổi bật.
        </div>

        <!-- Products Grid -->
        <TransitionGroup
          v-else
          name="product-slide"
          tag="div"
          class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
        >
          <article
            v-for="p in visibleProducts"
            :key="p.id"
            class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden hover:border-primary transition-colors cursor-pointer flex flex-col"
            @click="goDetail(p.slug)"
          >
            <div class="relative aspect-square bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <span
                v-if="p.badge && p.discountPercent"
                class="absolute top-0 left-0 bg-red-600 text-white text-[10px] font-black px-3 py-1.5 uppercase z-10 tracking-wider rounded-bl-md"
              >
                -{{ p.discountPercent }}%
              </span>
              <img
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                :src="p.image"
                :alt="p.name"
              />
            </div>

            <div class="p-4 flex flex-col flex-1">
              <p
                class="text-[10px] tracking-[0.2em] text-slate-500 dark:text-slate-400 uppercase font-bold truncate"
              >
                {{ p.brand }}
              </p>

              <h4
                class="mt-2 font-bold text-sm text-slate-900 dark:text-white line-clamp-2 leading-snug"
              >
                {{ p.name }}
              </h4>

              <div class="flex items-center gap-3 mt-auto pt-3">
                <p class="text-primary font-black text-base">
                  {{ moneyVND(p.price) }}
                </p>

                <p
                  v-if="p.compareAt"
                  class="text-xs text-slate-400 line-through"
                >
                  {{ moneyVND(p.compareAt) }}
                </p>
              </div>

              <button
                class="mt-3 w-full py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-bold uppercase tracking-wider rounded-md hover:bg-primary hover:text-white dark:hover:bg-primary dark:hover:text-white transition-colors"
              >
                Xem chi tiết
              </button>
            </div>
          </article>
        </TransitionGroup>
      </div>
    </section>

    <!-- CTA -->
    <section
      class="rounded-xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white px-5 md:px-8 py-8 md:py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-xl"
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
</template>

<script setup>
import { onMounted, ref, computed } from "vue";
import { useRouter } from "vue-router";

import Banner from "../../components/shop/Banner.vue";
import categoryService from "../../services/public/categoryService";
import productPublicService from "../../services/public/productService";

const router = useRouter();

const API_BASE = import.meta.env.VITE_API_URL || "";

const categories = ref([]);
const featured = ref([]);
const bigSaleProducts = ref([]);

const loadingCategories = ref(false);
const loadingFeatured = ref(false);
const loadingBigSale = ref(false);

const currentSlide = ref(0);
const visibleSlides = ref(4);

const maxSlide = computed(() => {
  // Max slides = total products - 4 visible products
  return Math.max(0, featured.value.length - visibleSlides.value);
});

const visibleProducts = computed(() => {
  // Show visibleSlides products, move 1 at a time
  return featured.value.slice(currentSlide.value, currentSlide.value + visibleSlides.value);
});

function updateVisibleSlides() {
  if (window.innerWidth < 640) {
    visibleSlides.value = 2;
  } else if (window.innerWidth < 1024) {
    visibleSlides.value = 3;
  } else {
    visibleSlides.value = 4;
  }
  // Reset slide when changing visible count
  currentSlide.value = 0;
}

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
  const discountPercent =
    compareAt && compareAt > price ? getDiscountPercent(compareAt, price) : 0;

  return {
    id: p.id,
    name: p.name,
    slug: p.slug,
    brand: p.brand?.name ?? "Brand",
    image: getThumbnailUrl(p),
    price,
    compareAt: compareAt && compareAt > price ? compareAt : null,
    discountPercent,
    badge: compareAt && compareAt > price ? "Sale" : null,
  };
}

function moneyVND(v) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(Number(v || 0));
}

async function fetchCategories() {
  loadingCategories.value = true;
  try {
    const res = await categoryService.list({ with_children: 0 });
    categories.value = res?.data?.data ?? [];
  } catch (e) {
    categories.value = [];
  } finally {
    loadingCategories.value = false;
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
  } catch (e) {
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
      .filter((p) => p.compareAt && p.discountPercent >= 25)
      .sort((a, b) => b.discountPercent - a.discountPercent)
      .slice(0, 4);
  } catch (e) {
    bigSaleProducts.value = [];
  } finally {
    loadingBigSale.value = false;
  }
}

function goProducts() {
  router.push("/shop/products");
}

function goSale() {
  router.push({
    path: "/shop/products",
    query: { sale: 1, page: 1 },
  });
}

function goBigSale() {
  router.push({
    path: "/shop/products",
    query: {
      sale: 1,
      min_discount: 25,
      page: 1,
    },
  });
}

function goDetail(slug) {
  router.push(`/shop/products/${slug}`);
}

onMounted(() => {
  fetchCategories();
  fetchFeatured();
  fetchBigSaleProducts();

  updateVisibleSlides();
  window.addEventListener("resize", updateVisibleSlides);
});
</script>

<style scoped>
/* Product slide animation */
.product-slide-enter-active,
.product-slide-leave-active {
  transition: all 0.5s ease;
}

.product-slide-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.product-slide-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

.product-slide-move {
  transition: transform 0.5s ease;
}
</style>