<template>
  <main class="max-w-6xl mx-auto w-full px-4 sm:px-5 lg:px-6 py-5 pb-24 lg:pb-8">
    <!-- Loading / Error -->
    <div
      v-if="loading"
      class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm"
    >
      Đang tải sản phẩm...
    </div>

    <div
      v-else-if="error"
      class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-600 shadow-sm"
    >
      {{ error }}
    </div>

    <!-- Content -->
    <template v-else-if="product">
      <!-- Breadcrumbs -->
      <nav class="mb-5 flex flex-wrap items-center gap-2 text-[13px]">
        <a
          class="text-slate-500 transition hover:text-primary"
          href="#"
          @click.prevent="goShop"
        >
          Home
        </a>
        <span class="text-slate-300">/</span>

        <a
          class="text-slate-500 transition hover:text-primary"
          href="#"
          @click.prevent="goShop"
        >
          Shop
        </a>

        <template v-if="firstCategory">
          <span class="text-slate-300">/</span>
          <a
            class="text-slate-500 transition hover:text-primary"
            href="#"
            @click.prevent="goCategory(firstCategory)"
          >
            {{ firstCategory.name }}
          </a>
        </template>

        <template v-if="product.brand?.name">
          <span class="text-slate-300">/</span>
          <a
            class="text-slate-500 transition hover:text-primary"
            href="#"
            @click.prevent="goBrand(product.brand)"
          >
            {{ product.brand.name }}
          </a>
        </template>

        <span class="text-slate-300">/</span>
        <span class="font-semibold text-slate-900">{{ product.name }}</span>
      </nav>

      <!-- Main layout -->
      <section class="grid grid-cols-1 gap-6 lg:grid-cols-[1.02fr_0.98fr] lg:gap-7">
        <!-- LEFT -->
        <div class="space-y-3.5">
          <!-- Main Image -->
          <div
            class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-100 via-white to-slate-100 shadow-sm"
          >
            <div
              class="absolute left-3 top-3 z-10 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-700 shadow-sm backdrop-blur"
            >
              <span class="material-symbols-outlined text-[14px] text-primary">verified</span>
              New Arrival
            </div>

            <img
              :src="buildImageUrl(activeImage) || buildImageUrl(images[0]) || fallbackImage"
              :alt="product.name"
              class="aspect-square h-full w-full object-cover transition duration-500 group-hover:scale-[1.02]"
            />
          </div>

          <!-- Thumbnails -->
          <div class="grid grid-cols-4 gap-2.5 sm:gap-3">
            <button
              v-for="img in images.slice(0, 4)"
              :key="img"
              type="button"
              class="group overflow-hidden rounded-xl border bg-white transition-all duration-200"
              :class="
                img === activeImage
                  ? 'border-primary ring-2 ring-primary/20'
                  : 'border-slate-200 hover:-translate-y-0.5 hover:border-primary/70'
              "
              @click="activeImage = img"
              title="Xem ảnh"
            >
              <img
                class="aspect-square h-full w-full object-cover transition duration-300 group-hover:scale-105"
                :src="buildImageUrl(img)"
                alt="thumb"
              />
            </button>
          </div>

          <!-- Small highlight cards -->
          <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
              <div class="mb-1.5 flex items-center gap-2 text-slate-900">
                <span class="material-symbols-outlined text-[18px] text-primary">local_shipping</span>
                <span class="text-sm font-semibold">Giao hàng nhanh</span>
              </div>
              <p class="text-[13px] leading-5 text-slate-500">
                Miễn phí vận chuyển cho đơn hàng đủ điều kiện.
              </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
              <div class="mb-1.5 flex items-center gap-2 text-slate-900">
                <span class="material-symbols-outlined text-[18px] text-primary">cached</span>
                <span class="text-sm font-semibold">Đổi trả dễ dàng</span>
              </div>
              <p class="text-[13px] leading-5 text-slate-500">
                Hỗ trợ đổi size nếu sản phẩm còn nguyên trạng.
              </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
              <div class="mb-1.5 flex items-center gap-2 text-slate-900">
                <span class="material-symbols-outlined text-[18px] text-primary">workspace_premium</span>
                <span class="text-sm font-semibold">Chính hãng</span>
              </div>
              <p class="text-[13px] leading-5 text-slate-500">
                Cam kết chất lượng và bảo hành theo chính sách.
              </p>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="lg:sticky lg:top-20 h-fit">
          <div
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:p-6"
          >
            <!-- Top info -->
            <div class="mb-5">
              <div class="flex flex-wrap items-center gap-2.5">
                <span
                  class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-primary"
                >
                  {{ product.brand?.name || "Brand" }}
                </span>

                <span
                  v-if="hasSale && discountPercent > 0"
                  class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-bold text-red-600"
                >
                  -{{ discountPercent }}%
                </span>
              </div>

              <h1
                class="mt-3 text-2xl font-black leading-tight tracking-tight text-slate-900 sm:text-3xl"
              >
                {{ product.name }}
              </h1>

              <div class="mt-3 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5">
                  <div class="flex items-center text-amber-400">
                    <span class="material-symbols-outlined filled-icon text-[18px]">star</span>
                    <span class="material-symbols-outlined filled-icon text-[18px]">star</span>
                    <span class="material-symbols-outlined filled-icon text-[18px]">star</span>
                    <span class="material-symbols-outlined filled-icon text-[18px]">star</span>
                    <span class="material-symbols-outlined text-[18px]">star_half</span>
                  </div>
                  <span class="ml-1 text-sm font-bold text-slate-900">4.5</span>
                </div>

                <span class="text-xs text-slate-400">|</span>
                <span class="text-[13px] text-slate-500">120 đánh giá</span>
                <span class="text-xs text-slate-400">|</span>
                <span class="text-[13px] text-slate-500">SKU: {{ product.sku || "-" }}</span>
              </div>
            </div>

            <!-- Price block -->
            <div
              class="mb-6 rounded-xl bg-slate-50 p-3.5 ring-1 ring-slate-200 sm:p-4"
            >
              <div class="flex flex-wrap items-end gap-2.5">
                <span class="text-2xl font-extrabold tracking-tight text-primary sm:text-3xl">
                  {{ moneyVND(displayPrice) }}
                </span>

                <span
                  v-if="hasSale"
                  class="pb-0.5 text-base text-slate-400 line-through"
                >
                  {{ moneyVND(Number(product.base_price || 0)) }}
                </span>
              </div>

              <p class="mt-1.5 text-[13px] text-slate-500">
                Giá đã bao gồm ưu đãi hiện tại.
              </p>
            </div>

            <!-- Description short -->
            <div class="mb-6">
              <p class="line-clamp-3 text-[13px] leading-6 text-slate-500">
                {{ plainTextDescription }}
              </p>
            </div>

            <!-- Options -->
            <div class="space-y-6">
              <!-- Size -->
              <div>
                <div class="mb-2.5 flex items-center justify-between gap-3">
                  <label class="text-[13px] font-bold uppercase tracking-wide text-slate-900">
                    Chọn size
                  </label>
                  <a
                    class="text-[13px] font-semibold text-primary hover:underline"
                    href="#"
                    @click.prevent
                  >
                    Size guide
                  </a>
                </div>

                <div class="grid grid-cols-4 gap-2.5 sm:grid-cols-6">
                  <button
                    v-for="s in sizes"
                    :key="s"
                    type="button"
                    class="h-10 rounded-xl border text-sm font-semibold transition-all"
                    :class="
                      s === selectedSize
                        ? 'border-primary bg-primary text-white shadow-md shadow-primary/20'
                        : 'border-slate-200 bg-white text-slate-700 hover:border-primary hover:text-primary'
                    "
                    @click="selectSize(s)"
                  >
                    {{ s }}
                  </button>
                </div>
              </div>

              <!-- Color -->
              <div>
                <label class="mb-2.5 block text-[13px] font-bold uppercase tracking-wide text-slate-900">
                  Chọn màu
                </label>

                <div class="flex flex-wrap gap-2.5">
                  <button
                    v-for="c in colors"
                    :key="c"
                    type="button"
                    class="h-9 w-9 rounded-full border-2 transition-all"
                    :style="{ backgroundColor: colorToCss(c) }"
                    :class="
                      c === selectedColor
                        ? 'scale-110 border-slate-900 ring-4 ring-primary/20'
                        : 'border-white shadow-sm hover:scale-105'
                    "
                    @click="selectColor(c)"
                    :title="c"
                  />
                </div>

                <p v-if="selectedColor" class="mt-2.5 text-[13px] text-slate-500">
                  Màu đã chọn:
                  <span class="font-semibold text-slate-900">{{ selectedColor }}</span>
                </p>
              </div>

              <!-- Quantity -->
              <div>
                <label class="mb-2.5 block text-[13px] font-bold uppercase tracking-wide text-slate-900">
                  Số lượng
                </label>

                <div class="flex flex-wrap items-center gap-3.5">
                  <div
                    class="inline-flex items-center overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                  >
                    <button
                      type="button"
                      class="grid h-10 w-10 place-items-center text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                      :disabled="qty <= 1"
                      @click="decQty"
                      aria-label="Decrease quantity"
                    >
                      <span class="material-symbols-outlined text-[18px]">remove</span>
                    </button>

                    <input
                      class="h-10 w-14 border-x border-slate-200 bg-transparent text-center text-sm font-bold text-slate-900 outline-none"
                      type="number"
                      inputmode="numeric"
                      min="1"
                      :max="maxQty"
                      v-model="qtyInput"
                      @blur="commitQty"
                    />

                    <button
                      type="button"
                      class="grid h-10 w-10 place-items-center text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                      :disabled="qty >= maxQty"
                      @click="incQty"
                      aria-label="Increase quantity"
                    >
                      <span class="material-symbols-outlined text-[18px]">add</span>
                    </button>
                  </div>

                  <div class="text-[13px]">
                    <template v-if="maxQty <= 0">
                      <span class="font-semibold text-red-600">Hết hàng</span>
                    </template>
                    <template v-else>
                      <span class="text-slate-500">Còn </span>
                      <span class="font-bold text-slate-900">{{ maxQty }}</span>
                      <span class="text-slate-500"> sản phẩm</span>
                    </template>
                  </div>
                </div>

                <p
                  v-if="variants.length && !selectedVariant"
                  class="mt-2.5 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700"
                >
                  Hãy chọn size và màu phù hợp trước khi thêm vào giỏ hàng.
                </p>
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 grid grid-cols-1 gap-2.5 sm:grid-cols-[1fr_auto]">
              <button
                class="flex h-12 items-center justify-center gap-2 rounded-xl bg-primary font-extrabold text-white shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5 hover:shadow-xl hover:shadow-primary/25 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:translate-y-0"
                type="button"
                :disabled="!canAddToCart || maxQty <= 0"
                @click="addToCart"
              >
                <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                Thêm vào giỏ hàng
              </button>

              <button
                class="flex h-12 items-center justify-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 font-bold text-slate-900 transition hover:border-primary hover:text-primary"
                type="button"
                @click="addToWishlist"
              >
                <span class="material-symbols-outlined text-[20px]">favorite</span>
                Yêu thích
              </button>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
              <div class="flex items-center gap-2.5 rounded-xl bg-slate-50 px-3.5 py-3">
                <span class="material-symbols-outlined text-[20px] text-green-600">local_shipping</span>
                <div>
                  <div class="text-sm font-semibold text-slate-900">Free Delivery</div>
                  <div class="text-[12px] text-slate-500">Áp dụng theo khu vực</div>
                </div>
              </div>

              <div class="flex items-center gap-2.5 rounded-xl bg-slate-50 px-3.5 py-3">
                <span class="material-symbols-outlined text-[20px] text-green-600">verified_user</span>
                <div>
                  <div class="text-sm font-semibold text-slate-900">Warranty</div>
                  <div class="text-[12px] text-slate-500">Chính sách bảo hành rõ ràng</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Tabs -->
      <section class="mt-10 mb-14">
        <div class="mb-5 flex gap-2.5 overflow-x-auto">
          <button
            class="rounded-full px-4 py-2 text-[13px] font-bold transition"
            :class="
              tab === 'desc'
                ? 'bg-primary text-white shadow'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            "
            type="button"
            @click="tab = 'desc'"
          >
            Mô tả
          </button>

          <button
            class="rounded-full px-4 py-2 text-[13px] font-bold transition"
            :class="
              tab === 'spec'
                ? 'bg-primary text-white shadow'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            "
            type="button"
            @click="tab = 'spec'"
          >
            Thông số
          </button>

          <button
            class="rounded-full px-4 py-2 text-[13px] font-bold transition"
            :class="
              tab === 'review'
                ? 'bg-primary text-white shadow'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            "
            type="button"
            @click="tab = 'review'"
          >
            Đánh giá
          </button>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
          <div class="max-w-4xl space-y-4 text-slate-600 leading-7">
            <template v-if="tab === 'desc'">
              <div class="prose max-w-none prose-sm" v-html="safeHtml(product.description)"></div>
            </template>

            <template v-else-if="tab === 'spec'">
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 p-3.5">
                  <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">SKU</div>
                  <div class="mt-1.5 text-sm font-semibold text-slate-900">{{ product.sku || "-" }}</div>
                </div>

                <div class="rounded-xl bg-slate-50 p-3.5">
                  <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Brand</div>
                  <div class="mt-1.5 text-sm font-semibold text-slate-900">{{ product.brand?.name || "-" }}</div>
                </div>

                <div class="rounded-xl bg-slate-50 p-3.5">
                  <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Category</div>
                  <div class="mt-1.5 text-sm font-semibold text-slate-900">{{ categoriesText }}</div>
                </div>

                <div class="rounded-xl bg-slate-50 p-3.5">
                  <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Variants</div>
                  <div class="mt-1.5 text-sm font-semibold text-slate-900">{{ variants.length }}</div>
                </div>
              </div>
            </template>

            <template v-else>
              <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                Hiện chưa có API reviews. Sau này bạn có thể thêm phần đánh giá khách hàng ở đây.
              </div>
            </template>
          </div>
        </div>
      </section>

      <!-- Related Products -->
      <section class="mb-16">
        <div class="mb-6 flex items-end justify-between gap-4">
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-primary">
              More to explore
            </p>
            <h3 class="mt-1 text-xl font-black text-slate-900">
              You May Also Like
            </h3>
          </div>

          <a
            class="text-sm font-bold text-primary hover:underline"
            href="#"
            @click.prevent="goShop"
          >
            View All
          </a>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <div
            v-if="relatedLoading"
            class="col-span-full rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm"
          >
            Đang tải sản phẩm liên quan...
          </div>

          <div
            v-else-if="related.length === 0"
            class="col-span-full rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm"
          >
            Chưa có sản phẩm liên quan.
          </div>

          <article
            v-for="p in related"
            :key="p.id"
            class="group cursor-pointer overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg"
            @click="goDetail(p.slug)"
          >
            <div class="relative overflow-hidden bg-slate-100">
              <img
                class="aspect-square h-full w-full object-cover transition duration-500 group-hover:scale-105"
                :src="buildImageUrl(p.image) || fallbackImage"
                :alt="p.name"
              />

              <button
                class="absolute right-2.5 top-2.5 grid h-9 w-9 place-items-center rounded-full bg-white/90 text-slate-900 shadow-sm backdrop-blur transition hover:text-primary"
                type="button"
                @click.stop="addToWishlist(p)"
                title="Wishlist"
              >
                <span class="material-symbols-outlined text-[18px]">favorite</span>
              </button>
            </div>

            <div class="p-3.5">
              <h4 class="truncate text-sm font-bold text-slate-900">{{ p.name }}</h4>
              <p class="mt-1 text-[13px] text-slate-500">{{ p.brand || "Brand" }}</p>
              <p class="mt-2.5 text-base font-extrabold text-primary">{{ moneyVND(p.price) }}</p>
            </div>
          </article>
        </div>
      </section>

      <!-- Sticky CTA mobile -->
      <div
        v-if="product"
        class="fixed bottom-0 left-0 right-0 z-50 border-t border-slate-200 bg-white/95 backdrop-blur lg:hidden"
      >
        <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-2.5 sm:px-5">
          <div class="min-w-0">
            <div class="truncate text-[11px] text-slate-500">
              {{ product.brand?.name || "Brand" }}
            </div>
            <div class="truncate text-sm font-extrabold text-slate-900">
              {{ moneyVND(displayPrice) }}
            </div>
          </div>

          <button
            class="ml-auto flex h-11 flex-1 items-center justify-center rounded-xl bg-primary px-4 text-sm font-extrabold text-white shadow disabled:cursor-not-allowed disabled:opacity-50"
            type="button"
            :disabled="!canAddToCart || maxQty <= 0"
            @click="addToCart"
          >
            Thêm vào giỏ
          </button>

          <button
            class="grid h-11 w-11 place-items-center rounded-xl border-2 border-slate-200 bg-white"
            type="button"
            @click="addToWishlist"
            title="Wishlist"
          >
            <span class="material-symbols-outlined text-[20px]">favorite</span>
          </button>
        </div>
      </div>
    </template>

    <div
      v-else
      class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm"
    >
      Không tìm thấy sản phẩm.
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import productService from "../../../services/public/productService";
import { useCartStore } from "../../../stores/cart";
import { useAuthStore } from "../../../stores/auth";
import { buildImageUrl } from "../../../utils/image";
import { useAlert } from "../../../composables/useAlert";

const route = useRoute();
const router = useRouter();
const slug = computed(() => String(route.params.slug || ""));
const notify = useAlert();

const loading = ref(false);
const error = ref("");

const product = ref(null);
const activeImage = ref("");
const tab = ref("desc");

const selectedSize = ref(null);
const selectedColor = ref(null);

const qty = ref(1);
const qtyInput = ref("1");

const related = ref([]);
const relatedLoading = ref(false);

const fallbackImage = "https://via.placeholder.com/800x800?text=Shoe";

const variants = computed(() => product.value?.variants || []);
const hasSale = computed(() => !!product.value?.base_sale_price);

const cartStore = useCartStore();
const selectedVariant = ref(null);

const displayPrice = computed(() => {
  if (!product.value) return 0;
  if (selectedVariant.value) {
    return Number(selectedVariant.value.sale_price ?? selectedVariant.value.price ?? 0);
  }
  return Number(product.value.base_sale_price ?? product.value.base_price ?? 0);
});

const discountPercent = computed(() => {
  const base = Number(product.value?.base_price || 0);
  const sale = Number(product.value?.base_sale_price || 0);
  if (!base || !sale || sale >= base) return 0;
  return Math.round(((base - sale) / base) * 100);
});

const firstCategory = computed(() => (product.value?.categories || [])[0] || null);
const categoriesText = computed(
  () => (product.value?.categories || []).map((c) => c.name).join(", ") || "-"
);

const images = computed(() => {
  if (!product.value) return [];
  const imgs = [];
  if (product.value.thumbnail) imgs.push(product.value.thumbnail);
  for (const v of product.value.variants || []) {
    for (const img of v.images || []) {
      if (img.url && !imgs.includes(img.url)) imgs.push(img.url);
    }
  }
  return imgs.length ? imgs : [fallbackImage];
});

const sizes = computed(() => {
  const set = new Set();
  for (const v of variants.value) {
    if (v.size !== null && v.size !== undefined && v.size !== "") {
      set.add(String(v.size));
    }
  }
  return Array.from(set).sort((a, b) => Number(a) - Number(b));
});

const colors = computed(() => {
  const set = new Set();
  for (const v of variants.value) {
    if (v.color) set.add(String(v.color));
  }
  return Array.from(set);
});

const canAddToCart = computed(() => {
  if (!product.value) return false;
  if (variants.value.length) return !!selectedVariant.value;
  return true;
});

const maxQty = computed(() => {
  if (variants.value.length) {
    const s = Number(selectedVariant.value?.stock ?? 0);
    return Number.isFinite(s) ? Math.max(0, s) : 0;
  }
  const ps = Number(product.value?.stock ?? 99);
  return Number.isFinite(ps) ? Math.max(0, ps) : 99;
});

const plainTextDescription = computed(() => {
  const raw = String(product.value?.description || "");
  return raw.replace(/<[^>]*>/g, "").replace(/\s+/g, " ").trim() || "Chưa có mô tả sản phẩm.";
});

function clampQty(n) {
  const max = maxQty.value || 0;
  if (max <= 0) return 1;
  return Math.min(Math.max(1, n), max);
}

function syncQtyWithStock() {
  if (variants.value.length && !selectedVariant.value) {
    qty.value = 1;
    qtyInput.value = "1";
    return;
  }
  const next = clampQty(Number(qty.value || 1));
  qty.value = next;
  qtyInput.value = String(next);
}

function incQty() {
  qty.value = clampQty(Number(qty.value) + 1);
  qtyInput.value = String(qty.value);
}

function decQty() {
  qty.value = clampQty(Number(qty.value) - 1);
  qtyInput.value = String(qty.value);
}

function commitQty() {
  const parsed = Number(String(qtyInput.value).replace(/[^\d]/g, "")) || 1;
  qty.value = clampQty(parsed);
  qtyInput.value = String(qty.value);
}

function selectSize(s) {
  selectedSize.value = s;
  syncVariant();
}

function selectColor(c) {
  selectedColor.value = c;
  syncVariant();
}

function syncVariant() {
  if (!variants.value.length) {
    selectedVariant.value = null;
    syncQtyWithStock();
    return;
  }

  const v = variants.value.find((x) => {
    const okSize = selectedSize.value ? String(x.size) === String(selectedSize.value) : true;
    const okColor = selectedColor.value ? String(x.color) === String(selectedColor.value) : true;
    return okSize && okColor;
  });

  selectedVariant.value = v || null;
  syncQtyWithStock();

  const img0 = (v?.images || [])[0]?.url;
  if (img0) activeImage.value = img0;
}

function moneyVND(v) {
  const n = Number(v || 0);
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(n);
}

function safeHtml(html) {
  return html || "";
}

function colorToCss(name) {
  const s = String(name || "").trim().toLowerCase();
  const map = {
    black: "#0f172a",
    trắng: "#ffffff",
    white: "#ffffff",
    red: "#dc2626",
    xanh: "#16a34a",
    green: "#16a34a",
    blue: "#2563eb",
    vàng: "#f59e0b",
    yellow: "#f59e0b",
    gray: "#94a3b8",
    grey: "#94a3b8",
    pink: "#ec4899",
    beige: "#d6c3a1",
    brown: "#92400e",
    navy: "#1e3a8a",
  };
  if (s.startsWith("#")) return s;
  return map[s] || name;
}

async function fetchDetail() {
  loading.value = true;
  error.value = "";
  product.value = null;
  activeImage.value = "";
  selectedSize.value = null;
  selectedColor.value = null;
  selectedVariant.value = null;
  qty.value = 1;
  qtyInput.value = "1";
  tab.value = "desc";

  try {
    const res = await productService.show(slug.value);
    product.value = res.data?.data ?? res.data ?? null;

    if (!product.value) {
      error.value = "API không trả về data sản phẩm.";
      return;
    }

    activeImage.value = product.value.thumbnail || images.value[0] || "";

    if (sizes.value.length) selectedSize.value = sizes.value[0];
    if (colors.value.length) selectedColor.value = colors.value[0];

    syncVariant();
    syncQtyWithStock();

    fetchRelated();
  } catch (e) {
    error.value =
      e?.response?.data?.message ||
      (e?.response?.status === 404
        ? "Không tìm thấy sản phẩm (404)"
        : "Không tải được chi tiết sản phẩm");
  } finally {
    loading.value = false;
  }
}

async function fetchRelated() {
  relatedLoading.value = true;
  related.value = [];
  try {
    const res = await productService.list({ per_page: 8, page: 1, sort: "latest" });
    const list = (res.data?.data ?? []).filter((p) => p.slug !== product.value?.slug);

    related.value = list.slice(0, 4).map((p) => ({
      id: p.id,
      slug: p.slug,
      name: p.name,
      brand: p.brand?.name ?? "Brand",
      price: Number(p.base_sale_price ?? p.base_price ?? 0),
      image: p.thumbnail || "",
    }));
  } catch {
    related.value = [];
  } finally {
    relatedLoading.value = false;
  }
}

async function addToCart() {
  if (!canAddToCart.value || maxQty.value <= 0) return;

  const product_id = product.value?.id;
  const product_variant_id = selectedVariant.value?.id || null;
  const quantity = Number(qty.value || 1);

  try {
    const auth = useAuthStore();
    if (!auth.user) return router.push("/login");

    await cartStore.addToCart({ product_id, product_variant_id, quantity });
    notify.success("Đã thêm vào giỏ hàng", {
      title: "Thành công",
      duration: 2500,
    });
  } catch (e) {
    notify.error(cartStore.error || "Thêm vào giỏ thất bại", {
      title: "Lỗi",
      duration: 2500,
    });
  }
}

function addToWishlist(p) {
  console.log("WISHLIST", p?.id ? p : { product_id: product.value?.id });
}

function goDetail(s) {
  router.push(`/shop/products/${s}`);
}

function goShop() {
  router.push("/shop/products");
}

function goCategory(c) {
  router.push({ path: "/shop/products", query: { category: c.id } });
}

function goBrand(b) {
  router.push({ path: "/shop/products", query: { brand: b.id } });
}

onMounted(fetchDetail);
watch(slug, fetchDetail);
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: "FILL" 0, "wght" 500, "GRAD" 0, "opsz" 24;
}

.filled-icon {
  font-variation-settings: "FILL" 1;
}
</style>