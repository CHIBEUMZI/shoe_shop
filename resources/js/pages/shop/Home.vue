<template>
  <main class="max-w-7xl mx-auto px-4 lg:px-8 py-8 md:py-10">
    <!-- Hero Banner -->
    <Banner position="home_top" :fallback-action="goProducts" />

    <!-- Intro / Hero content -->
    <section
      class="mt-10 mb-16 grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-8 items-center"
    >
      <div>
        <div
          class="inline-flex items-center gap-2 rounded-full bg-primary/10 text-primary px-4 py-2 text-[11px] font-extrabold uppercase tracking-[0.22em] mb-5"
        >
          <span class="material-symbols-outlined text-sm">local_mall</span>
          BMC Shoes
        </div>

        <h1
          class="max-w-3xl text-4xl md:text-5xl lg:text-[64px] font-black leading-[0.98] tracking-[-0.03em] text-slate-950 dark:text-white mb-5"
        >
          Chọn đôi giày
          <span class="text-primary">đúng gu</span>
          <br />
          cho phong cách của bạn
        </h1>

        <p
          class="text-slate-600 dark:text-slate-300 text-[15px] md:text-[17px] leading-7 md:leading-8 max-w-2xl font-medium"
        >
          Từ sneaker trẻ trung, giày chạy bộ năng động đến các mẫu thời trang hiện đại,
          cửa hàng mang đến nhiều lựa chọn phù hợp cho nhu cầu hằng ngày, học tập, đi
          chơi và vận động.
        </p>

        <div class="mt-8 flex flex-wrap gap-3">
          <button
            type="button"
            class="px-6 py-3 rounded-2xl bg-slate-950 text-white text-sm md:text-[15px] font-extrabold shadow-lg shadow-slate-900/15 hover:-translate-y-0.5 hover:bg-primary transition-all"
            @click="goProducts"
          >
            Xem tất cả sản phẩm
          </button>

          <button
            type="button"
            class="px-6 py-3 rounded-2xl text-sm md:text-[15px] font-extrabold text-white bg-gradient-to-r from-red-500 via-orange-500 to-yellow-500 shadow-lg shadow-red-500/40 hover:shadow-xl hover:shadow-red-500/60 hover:-translate-y-1 hover:scale-105 transition-all duration-300"
            @click="goBigSale"
          >
            🔥 Sale lớn từ 25%
          </button>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
          <div
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-2 text-[13px] font-semibold text-slate-600 dark:text-slate-300"
          >
            <span class="material-symbols-outlined text-primary text-[18px]">bolt</span>
            Mẫu mới cập nhật thường xuyên
          </div>
          <div
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-2 text-[13px] font-semibold text-slate-600 dark:text-slate-300"
          >
            <span class="material-symbols-outlined text-primary text-[18px]">sell</span>
            Nhiều ưu đãi hấp dẫn
          </div>
          <div
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-2 text-[13px] font-semibold text-slate-600 dark:text-slate-300"
          >
            <span class="material-symbols-outlined text-primary text-[18px]">favorite</span>
            Phù hợp nhiều phong cách
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div
          class="rounded-3xl bg-gradient-to-br from-slate-900 to-slate-800 text-white p-6 shadow-xl"
        >
          <div class="text-3xl md:text-4xl font-black tracking-tight mb-2">100+</div>
          <p class="text-slate-300 text-sm font-medium leading-6">
            Mẫu giày đa dạng phong cách
          </p>
        </div>

        <div
          class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-6 shadow-sm"
        >
          <div class="text-3xl md:text-4xl font-black tracking-tight mb-2 text-primary">
            25%+
          </div>
          <p class="text-slate-500 text-sm font-medium leading-6">
            Nhiều deal sale nổi bật mỗi ngày
          </p>
        </div>

        <div
          class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-6 shadow-sm"
        >
          <div class="text-3xl md:text-4xl font-black tracking-tight mb-2">Trend</div>
          <p class="text-slate-500 text-sm font-medium leading-6">
            Thiết kế trẻ trung, hiện đại
          </p>
        </div>

        <div
          class="rounded-3xl bg-gradient-to-br from-primary/15 to-pink-100 dark:from-primary/20 dark:to-slate-800 border border-primary/10 p-6 shadow-sm"
        >
          <div class="text-3xl md:text-4xl font-black tracking-tight mb-2 text-slate-900 dark:text-white">
            Hot
          </div>
          <p class="text-slate-600 dark:text-slate-300 text-sm font-medium leading-6">
            Nhiều sản phẩm nổi bật được yêu thích
          </p>
        </div>
      </div>
    </section>

    <!-- Benefits -->
    <section class="mb-16">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div
          class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 flex items-start gap-4 shadow-sm"
        >
          <div
            class="size-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0"
          >
            <span class="material-symbols-outlined">workspace_premium</span>
          </div>
          <div>
            <h3 class="font-extrabold text-[17px] text-slate-900 dark:text-white mb-1">
              Sản phẩm chọn lọc
            </h3>
            <p class="text-sm text-slate-500 leading-6 font-medium">
              Gợi ý những mẫu giày đẹp, dễ phối đồ và hợp xu hướng hiện nay.
            </p>
          </div>
        </div>

        <div
          class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 flex items-start gap-4 shadow-sm"
        >
          <div
            class="size-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0"
          >
            <span class="material-symbols-outlined">local_fire_department</span>
          </div>
          <div>
            <h3 class="font-extrabold text-[17px] text-slate-900 dark:text-white mb-1">
              Deal sale hấp dẫn
            </h3>
            <p class="text-sm text-slate-500 leading-6 font-medium">
              Tập trung hiển thị các sản phẩm giảm giá mạnh để người dùng dễ lựa chọn.
            </p>
          </div>
        </div>

        <div
          class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 flex items-start gap-4 shadow-sm"
        >
          <div
            class="size-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0"
          >
            <span class="material-symbols-outlined">rocket_launch</span>
          </div>
          <div>
            <h3 class="font-extrabold text-[17px] text-slate-900 dark:text-white mb-1">
              Phong cách trẻ trung
            </h3>
            <p class="text-sm text-slate-500 leading-6 font-medium">
              Giao diện sáng, hiện đại, rõ ràng và dễ nhìn hơn cho trang chủ.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Big sale products -->
    <section class="mb-16">
      <div
        class="rounded-[28px] overflow-hidden border border-rose-100 dark:border-rose-900/30 bg-gradient-to-r from-rose-500 via-pink-500 to-orange-400 p-[1px]"
      >
        <div
          class="bg-white dark:bg-slate-950 rounded-[27px] px-5 md:px-8 py-6 md:py-8"
        >
          <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
              <div
                class="inline-flex items-center gap-2 rounded-full bg-rose-100 text-rose-600 px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-[0.2em] mb-3"
              >
                <span class="material-symbols-outlined text-sm">sell</span>
                Big sale & Deals
              </div>
              <h2 class="text-2xl md:text-4xl font-black tracking-tight text-slate-950 dark:text-white">
                Sản phẩm giảm giá cực khủng
              </h2>
              <p class="text-slate-500 mt-2 text-sm md:text-[15px] leading-7 font-medium max-w-2xl">
                Những mẫu đang có mức giảm tốt để khách hàng có thể lựa chọn những đôi
                giày tốt nhất.
              </p>
            </div>

            <button
              type="button"
              class="self-start md:self-auto px-5 py-3 rounded-2xl bg-slate-950 text-white text-sm font-extrabold hover:bg-primary transition"
              @click="goBigSale"
            >
              Xem tất cả deal hot
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-if="loadingBigSale" class="col-span-full text-sm text-slate-500">
              Đang tải sản phẩm sale lớn...
            </div>

            <div v-else-if="bigSaleProducts.length === 0" class="col-span-full">
              <div
                class="rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-slate-500 font-medium"
              >
                Hiện chưa có sản phẩm giảm giá từ 25%.
              </div>
            </div>

            <article
              v-for="p in bigSaleProducts"
              :key="`sale-${p.id}`"
              class="group bg-white dark:bg-slate-900 rounded-3xl overflow-hidden border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all cursor-pointer"
              @click="goDetail(p.slug)"
            >
              <div class="relative aspect-square bg-slate-50 dark:bg-slate-800 overflow-hidden">
                <span
                  class="absolute top-3 left-3 z-10 rounded-full bg-rose-500 text-white px-3 py-1 text-[11px] font-black shadow"
                >
                  -{{ p.discountPercent }}%
                </span>

                <span
                  class="absolute top-3 right-3 z-10 rounded-full bg-white/90 text-slate-900 px-3 py-1 text-[11px] font-extrabold backdrop-blur"
                >
                  Deal hot
                </span>

                <img
                  class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                  :src="p.image"
                  :alt="p.name"
                />
              </div>

              <div class="p-4">
                <div class="flex items-center justify-between gap-3 mb-2">
                  <p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">
                    {{ p.brand }}
                  </p>
                  <span
                    class="text-[11px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-500/10 px-2.5 py-1 rounded-full"
                  >
                    Tiết kiệm {{ moneyVND((p.compareAt || 0) - p.price) }}
                  </span>
                </div>

                <h3
                  class="font-extrabold text-[16px] text-slate-900 dark:text-white leading-6 min-h-[48px] line-clamp-2"
                >
                  {{ p.name }}
                </h3>

                <div class="flex items-end gap-2 mt-3 mb-4">
                  <span class="text-xl md:text-2xl font-black tracking-tight text-slate-950 dark:text-white">
                    {{ moneyVND(p.price) }}
                  </span>
                  <span class="text-sm text-slate-400 line-through font-medium">
                    {{ moneyVND(p.compareAt) }}
                  </span>
                </div>

                <button
                  type="button"
                  class="w-full rounded-2xl bg-slate-950 text-white py-3 text-sm font-extrabold hover:bg-primary transition"
                  @click.stop="goDetail(p.slug)"
                >
                  Xem chi tiết
                </button>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured products -->
    <section class="mb-16">
      <div class="flex items-center justify-between mb-8">
        <div>
          <h3 class="text-2xl md:text-4xl font-black tracking-tight text-slate-950 dark:text-white">
            Sản phẩm nổi bật
          </h3>
          <p class="text-slate-500 mt-1 text-sm md:text-[15px] font-medium">
            Những mẫu đáng chú ý được ưu tiên hiển thị
          </p>
        </div>

        <button
          type="button"
          class="text-primary text-sm md:text-[15px] font-extrabold flex items-center gap-1 hover:gap-2 transition-all"
          @click="goProducts"
        >
          Xem tất cả
          <span class="material-symbols-outlined text-sm">arrow_forward</span>
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div v-if="loadingFeatured" class="col-span-full text-sm text-slate-500">
          Đang tải sản phẩm nổi bật...
        </div>

        <div v-else-if="featured.length === 0" class="col-span-full text-sm text-slate-500">
          Chưa có sản phẩm nổi bật.
        </div>

        <article
          v-for="p in featured"
          :key="p.id"
          class="group bg-white dark:bg-slate-900 rounded-3xl overflow-hidden shadow-sm hover:shadow-2xl transition-all border border-slate-100 dark:border-slate-800 cursor-pointer hover:-translate-y-1"
          @click="goDetail(p.slug)"
        >
          <div class="relative aspect-square bg-slate-50 dark:bg-slate-800 overflow-hidden">
            <span
              v-if="p.badge && p.discountPercent"
              class="absolute top-3 left-3 bg-red-500 text-white text-[10px] font-black px-2.5 py-1 rounded-full uppercase z-10"
            >
              {{ p.badge }} -{{ p.discountPercent }}%
            </span>
            <img
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
              :src="p.image"
              :alt="p.name"
            />
          </div>

          <div class="p-4">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-extrabold uppercase tracking-[0.14em] mb-1">
              {{ p.brand }}
            </p>

            <h4
              class="font-extrabold text-[16px] text-slate-900 dark:text-white mb-2 leading-6 min-h-[48px] line-clamp-2"
            >
              {{ p.name }}
            </h4>

            <div class="flex items-center gap-2 mb-4">
              <span class="text-lg md:text-xl font-black tracking-tight text-slate-950 dark:text-white">
                {{ moneyVND(p.price) }}
              </span>
              <span v-if="p.compareAt" class="text-sm text-slate-400 line-through font-medium">
                {{ moneyVND(p.compareAt) }}
              </span>
            </div>
          </div>
        </article>
      </div>
    </section>

    <!-- CTA -->
    <section
      class="rounded-[32px] bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white px-6 md:px-10 py-10 md:py-12 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 shadow-2xl"
    >
      <div>
        <div
          class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-[0.2em] mb-3"
        >
          <span class="material-symbols-outlined text-sm">shopping_bag</span>
          Bộ sưu tập mới
        </div>

        <h3 class="text-2xl md:text-4xl font-black tracking-tight mb-2">
          Khám phá toàn bộ bộ sưu tập giày
        </h3>
        <p class="text-slate-300 max-w-2xl text-sm md:text-[15px] leading-7 font-medium">
          Xem thêm nhiều mẫu sneaker, giày chạy bộ, giày thời trang với nhiều mức giá và
          thương hiệu khác nhau để chọn đôi phù hợp nhất với bạn.
        </p>
      </div>

      <button
        type="button"
        class="px-6 py-3 rounded-2xl bg-white text-slate-900 text-sm md:text-[15px] font-extrabold hover:opacity-90 hover:-translate-y-0.5 transition-all"
        @click="goProducts"
      >
        Đi đến cửa hàng
      </button>
    </section>
  </main>
</template>

<script setup>
import { onMounted, ref } from "vue";
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
      per_page: 4,
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

function goCategory(categoryId) {
  router.push({
    path: "/shop/products",
    query: {
      category_id: categoryId,
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
});
</script>