<template>
  <main class="max-w-[1440px] mx-auto px-4 lg:px-8 py-8">
    <section class="mb-8">
      <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
            Tất cả sản phẩm
          </h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">
            Khám phá toàn bộ sản phẩm trong cửa hàng
          </p>

          <div
            v-if="routeMinDiscount !== null"
            class="mt-3 inline-flex items-center gap-2 bg-red-50 text-red-600 px-3 py-1.5 text-xs font-bold uppercase tracking-wide"
          >
            <span class="material-symbols-outlined text-[14px]">sell</span>
            Giảm từ {{ routeMinDiscount }}%
          </div>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-primary text-primary font-medium hover:bg-primary/10 transition-colors"
          @click="router.push('/shop')"
        >
          <span class="material-symbols-outlined text-sm">arrow_back</span>
          Quay về trang chủ
        </button>
      </div>
    </section>

    <section ref="productsSection" class="flex flex-col lg:flex-row gap-8 xl:gap-10">
      <!-- Sidebar -->
      <aside class="w-full lg:w-72 xl:w-80 flex-shrink-0">
        <div
          class="bg-white dark:bg-slate-900 rounded-lg p-5 shadow-sm border border-slate-100 dark:border-slate-800 sticky top-24"
        >
          <div
            class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200 dark:border-slate-700"
          >
            <div class="flex items-center gap-2 font-bold text-lg text-slate-900 dark:text-white uppercase tracking-wide">
              <span class="material-symbols-outlined text-primary">tune</span>
              Bộ lọc
            </div>

            <button
              type="button"
              class="text-xs font-bold px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
              @click="resetFilters"
            >
              Tải lại
            </button>
          </div>

          <div v-if="loadingFacets" class="text-xs text-slate-500 mb-3">
            Đang tải bộ lọc...
          </div>

          <div class="mb-4">
            <div class="relative">
              <input
                v-model="filterSearch"
                type="text"
                class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-3 pr-10 text-sm outline-none focus:ring-2 focus:ring-primary/30"
                placeholder="Tìm brand/danh mục..."
              />
              <span
                class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]"
              >
                search
              </span>
            </div>
          </div>

          <div class="space-y-4">
            <!-- PRICE -->
            <FilterSection title="Khoảng giá" v-model:open="sections.price">
              <div class="space-y-1">
                <label
                  v-for="r in priceRangesUI"
                  :key="r.key"
                  class="flex items-center justify-between gap-3 text-sm cursor-pointer px-2 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800"
                >
                  <div class="flex items-center gap-3">
                    <input
                      type="radio"
                      name="priceRange"
                      class="accent-primary"
                      :value="r.key"
                      v-model="filters.priceRange"
                    />
                    <span class="font-medium text-slate-700 dark:text-slate-200">
                      {{ r.label }}
                    </span>
                  </div>
                </label>

                <button
                  v-if="filters.priceRange"
                  type="button"
                  class="mt-2 text-[11px] font-bold px-3 py-1.5 border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                  @click="filters.priceRange = ''"
                >
                  Bỏ chọn giá
                </button>

                <div v-if="filters.priceRange" class="text-[11px] text-slate-500 pt-1">
                  Đang lọc: <span class="font-bold">{{ priceRangeText }}</span>
                </div>
              </div>
            </FilterSection>

            <!-- BRAND -->
            <FilterSection title="Thương hiệu" v-model:open="sections.brand">
              <div class="space-y-1 max-h-56 overflow-auto pr-1">
                <label
                  v-for="b in filteredBrands"
                  :key="b.id"
                  class="flex items-center justify-between gap-3 text-sm cursor-pointer px-2 py-2 hover:bg-slate-50 dark:hover:bg-slate-800"
                  :class="b.count === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                >
                  <div class="flex items-center gap-2">
                    <input
                      type="checkbox"
                      class="accent-primary"
                      :value="b.id"
                      v-model="filters.brandIds"
                      :disabled="b.count === 0"
                    />
                    <span class="font-medium text-slate-700 dark:text-slate-200">
                      {{ b.name }}
                    </span>
                  </div>
                  <span class="text-xs text-slate-500">{{ b.count }}</span>
                </label>

                <div v-if="filteredBrands.length === 0" class="text-xs text-slate-500">
                  Không có brand phù hợp.
                </div>
              </div>
            </FilterSection>

            <!-- CATEGORY -->
            <FilterSection title="Danh mục" v-model:open="sections.category">
              <div class="space-y-1 max-h-56 overflow-auto pr-1">
                <label
                  v-for="c in filteredCategories"
                  :key="c.id"
                  class="flex items-center justify-between gap-3 text-sm cursor-pointer px-2 py-2 hover:bg-slate-50 dark:hover:bg-slate-800"
                  :class="c.count === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                >
                  <div class="flex items-center gap-3">
                    <input
                      type="checkbox"
                      class="accent-primary"
                      :value="c.id"
                      v-model="filters.categoryIds"
                      :disabled="c.count === 0"
                    />
                    <span class="font-medium text-slate-700 dark:text-slate-200">
                      {{ c.name }}
                    </span>
                  </div>
                  <span class="text-xs text-slate-500">{{ c.count }}</span>
                </label>

                <div v-if="filteredCategories.length === 0" class="text-xs text-slate-500">
                  Không có danh mục phù hợp.
                </div>
              </div>
            </FilterSection>

            <!-- SIZE -->
            <FilterSection title="Kích thước" v-model:open="sections.size">
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="s in filteredSizes"
                  :key="String(s.value)"
                  type="button"
                  class="px-3 py-2 border text-sm font-bold transition"
                  :class="[
                    filters.sizes.includes(String(s.value))
                      ? 'bg-slate-900 text-white border-slate-900 dark:bg-primary dark:border-primary'
                      : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-600 hover:border-primary',
                    s.count === 0 ? 'opacity-50 cursor-not-allowed' : ''
                  ]"
                  :disabled="s.count === 0"
                  @click="toggleSize(String(s.value))"
                >
                  {{ s.value }} <span class="text-xs opacity-70">({{ s.count }})</span>
                </button>

                <div v-if="filteredSizes.length === 0" class="text-xs text-slate-500">
                  Không có kích thước.
                </div>
              </div>
            </FilterSection>

            <!-- COLOR -->
            <FilterSection title="Màu sắc" v-model:open="sections.color">
              <div class="space-y-1 max-h-56 overflow-auto pr-1">
                <label
                  v-for="c in filteredColors"
                  :key="String(c.value)"
                  class="flex items-center justify-between gap-3 text-sm cursor-pointer px-2 py-2 hover:bg-slate-50 dark:hover:bg-slate-800"
                  :class="c.count === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                >
                  <div class="flex items-center gap-3">
                    <input
                      type="checkbox"
                      class="accent-primary"
                      :value="String(c.value)"
                      v-model="filters.colors"
                      :disabled="c.count === 0"
                    />
                    <span class="font-medium text-slate-700 dark:text-slate-200">
                      {{ c.value }}
                    </span>
                  </div>
                  <span class="text-xs text-slate-500">{{ c.count }}</span>
                </label>

                <div v-if="filteredColors.length === 0" class="text-xs text-slate-500">
                  Không có màu.
                </div>
              </div>
            </FilterSection>
          </div>
        </div>
      </aside>

      <!-- Products -->
      <div class="flex-1 min-w-0">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
            <span class="text-sm font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
              Sắp xếp:
            </span>

            <BaseSelect
              v-model="sortUI"
              :options="sortOptions"
              placeholder="Chọn kiểu sắp xếp"
              size="md"
              wrapperClass="w-full sm:w-[200px]"
            />
          </div>

          <div class="text-sm text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wide">
            {{ filteredClient.length }} / {{ meta.total }} sản phẩm
          </div>
        </div>

        <div v-if="loading && page === 1" class="py-16 text-sm text-slate-500 text-center border border-slate-200 dark:border-slate-700">
          Đang tải sản phẩm...
        </div>

        <div v-else-if="error" class="py-10 text-sm text-red-600 text-center border border-red-200 dark:border-red-800">
          {{ error }}
        </div>

        <div
          v-else-if="filteredClient.length === 0"
          class="py-16 text-sm text-slate-500 text-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700"
        >
          Không tìm thấy sản phẩm phù hợp.
        </div>

        <div
          v-else
          class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4"
        >
          <article
            v-for="p in filteredClient"
            :key="p.id"
            class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden hover:border-primary transition-colors cursor-pointer flex flex-col"
            @click="goDetail(p.slug)"
          >
            <div class="relative aspect-square bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <span
                v-if="p.salePercent"
                class="absolute top-0 left-0 bg-red-600 text-white text-[10px] font-black px-3 py-1.5 uppercase z-10 tracking-wider rounded-bl-md"
              >
                -{{ p.salePercent }}%
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
        </div>

        <div class="mt-10 flex flex-col items-center gap-4">
          <div v-if="meta.total > 0" class="flex items-center gap-2">
            <button
              type="button"
              class="h-10 px-4 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-sm font-semibold flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors rounded-lg"
              :disabled="loading || meta.current_page <= 1"
              @click="goToPage(meta.current_page - 1)"
            >
              <span class="material-symbols-outlined text-[18px]">chevron_left</span>
            </button>

            <button
              v-for="p in pageButtons"
              :key="p.key"
              type="button"
              class="h-10 min-w-10 px-3 border text-sm font-semibold flex items-center justify-center transition-all rounded-lg"
              :class="p.type === 'page'
                ? p.page === meta.current_page
                  ? 'border-primary bg-primary text-white'
                  : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:border-primary hover:text-primary'
                : 'border-transparent bg-transparent text-slate-400 cursor-default'"
              :disabled="loading || p.type !== 'page'"
              @click="p.type === 'page' ? goToPage(p.page) : undefined"
            >
              {{ p.label }}
            </button>

            <button
              type="button"
              class="h-10 px-4 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-sm font-semibold flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors rounded-lg"
              :disabled="loading || meta.current_page >= meta.last_page"
              @click="goToPage(meta.current_page + 1)"
            >
              <span class="material-symbols-outlined text-[18px]">chevron_right</span>
            </button>
          </div>

          <div class="text-sm text-slate-500 dark:text-slate-400">
            Hiển thị {{ fromToText }}
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRouter, useRoute } from "vue-router";

import FilterSection from "../../../components/shop/FilterSection.vue";
import productPublicService from "../../../services/public/productService";
import productFacetService from "../../../services/public/productFacetService";
import BaseSelect from "../../../components/BaseSelect.vue";

const props = defineProps({
  q: { type: String, default: "" },
});

const router = useRouter();
const route = useRoute();

const routeSearch = computed(() => String(route.query.search || "").trim());

const sortOptions = [
  { label: "Phổ biến", value: "pop" },
  { label: "Giá từ thấp đến cao", value: "low" },
  { label: "Giá từ cao đến thấp", value: "high" },
  { label: "Mới nhất", value: "new" },
];

const routeCategoryId = computed(() => {
  const v = route.query.category_id;
  if (v === undefined || v === null || v === "") return null;
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
});

const routeSale = computed(() => {
  const v = route.query.sale;
  return v === "1" || v === 1 || v === true;
});

const routeMinDiscount = computed(() => {
  const v = route.query.min_discount;
  if (v === undefined || v === null || v === "") return null;

  const n = Number(v);
  if (!Number.isFinite(n) || n < 0) return null;

  return n;
});

const productsSection = ref(null);

const loading = ref(false);
const error = ref("");

const perPage = ref(12);
const page = ref(1);

const items = ref([]);
const meta = ref({ current_page: 1, last_page: 1, per_page: 12, total: 0 });

const filters = ref({
  brandIds: [],
  categoryIds: [],
  sizes: [],
  colors: [],
  priceRange: "",
});

const sections = reactive({
  price: true,
  brand: true,
  category: true,
  size: true,
  color: true,
});

const filterSearch = ref("");

const sortUI = ref("pop");

const apiSort = computed(() => {
  if (sortUI.value === "low") return "price_asc";
  if (sortUI.value === "high") return "price_desc";
  if (sortUI.value === "new") return "latest";
  return "popular";
});

const API_BASE = import.meta.env.VITE_API_URL || "";

const priceRangesUI = [
  { key: "lt500", label: "Dưới 500.000đ", min: null, max: 500000 },
  { key: "500-1m", label: "500.000đ – 1.000.000đ", min: 500000, max: 1000000 },
  { key: "1-3m", label: "1.000.000đ – 3.000.000đ", min: 1000000, max: 3000000 },
  { key: "gt3m", label: "Từ 3.000.000đ trở lên", min: 3000000, max: null },
];

const priceRangeText = computed(() => {
  const r = priceRangesUI.find((x) => x.key === filters.value.priceRange);
  return r?.label || "";
});

function selectedPriceMinMax() {
  const r = priceRangesUI.find((x) => x.key === filters.value.priceRange);
  if (!r) return { min: undefined, max: undefined };
  return { min: r.min ?? undefined, max: r.max ?? undefined };
}

function firstVariantImageUrl(p) {
  const v0 = Array.isArray(p.variants) ? p.variants[0] : null;
  const imgs = v0 && Array.isArray(v0.images) ? v0.images : [];
  return imgs.length ? imgs[0].url : "";
}

function buildImageUrl(pathOrUrl) {
  if (!pathOrUrl) return "";
  if (String(pathOrUrl).startsWith("http")) return pathOrUrl;
  if (String(pathOrUrl).startsWith("/")) return `${API_BASE}${pathOrUrl}`;
  return `${API_BASE}/storage/${pathOrUrl}`;
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

function getSalePercent(basePrice, salePrice) {
  const base = Number(basePrice || 0);
  const sale = Number(salePrice || 0);

  if (!base || !sale || sale >= base) return null;

  return Math.round(((base - sale) / base) * 100);
}

function mapProduct(p) {
  const price = getPrice(p);
  const compareAt = getCompareAt(p);
  const salePercent =
    compareAt && compareAt > price ? getSalePercent(compareAt, price) : null;

  return {
    id: p.id,
    name: p.name,
    slug: p.slug,
    brand: p.brand?.name ?? "Brand",
    image: getThumbnailUrl(p),
    price,
    compareAt: compareAt && compareAt > price ? compareAt : null,
    salePercent,
  };
}

function moneyVND(v) {
  const n = Number(v || 0);
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(n);
}

function goDetail(slug) {
  router.push(`/shop/products/${slug}`);
}

const facets = reactive({
  brands: [],
  categories: [],
  sizes: [],
  colors: [],
  price: { min: 0, max: 0 },
});

const loadingFacets = ref(false);

function toggleSize(v) {
  const arr = filters.value.sizes;
  const idx = arr.indexOf(v);
  if (idx >= 0) arr.splice(idx, 1);
  else arr.push(v);
}

function buildQueryParams() {
  const { min, max } = selectedPriceMinMax();

  const effectiveSearch = routeSearch.value || String(props.q || "").trim() || undefined;

  const effectiveCategoryIds =
    routeCategoryId.value != null ? [routeCategoryId.value] : filters.value.categoryIds;

  return {
    per_page: perPage.value,
    page: page.value,
    search: effectiveSearch,
    sort: apiSort.value,
    brand: filters.value.brandIds,
    category: effectiveCategoryIds,
    size: filters.value.sizes,
    color: filters.value.colors,
    sale: routeSale.value ? 1 : undefined,
    min_discount: routeMinDiscount.value ?? undefined,
    price_ranges: filters.value.priceRange || undefined,
    price_min: min,
    price_max: max,
  };
}

async function fetchProducts({ append = false } = {}) {
  loading.value = true;
  error.value = "";

  try {
    const params = buildQueryParams();
    const res = await productPublicService.list(params);

    const data = res.data;
    const listRaw = data?.data ?? [];
    const list = listRaw.map(mapProduct);

    items.value = append ? [...items.value, ...list] : list;

    const m = data?.meta;
    meta.value = {
      current_page: Number(m?.current_page ?? page.value),
      last_page: Number(m?.last_page ?? 1),
      per_page: Number(m?.per_page ?? perPage.value),
      total: Number(m?.total ?? (append ? items.value.length : list.length)),
    };
  } catch (e) {
    error.value =
      e?.response?.data?.message || e?.message || "Không tải được danh sách sản phẩm";
  } finally {
    loading.value = false;
  }
}

async function fetchFacets() {
  loadingFacets.value = true;

  try {
    const params = buildQueryParams();
    delete params.per_page;
    delete params.page;
    delete params.sort;

    const res = await productFacetService.facets(params);

    const f = res.data || {};
    facets.brands = Array.isArray(f.brands) ? f.brands : [];
    facets.categories = Array.isArray(f.categories) ? f.categories : [];
    facets.sizes = Array.isArray(f.sizes) ? f.sizes : [];
    facets.colors = Array.isArray(f.colors) ? f.colors : [];
    facets.price = f.price || { min: 0, max: 0 };
  } catch (e) {
    facets.brands = [];
    facets.categories = [];
    facets.sizes = [];
    facets.colors = [];
    facets.price = { min: 0, max: 0 };
  } finally {
    loadingFacets.value = false;
  }
}

const filteredClient = computed(() => {
  let list = [...items.value];

  const key = filters.value.priceRange;
  if (key) {
    const r = priceRangesUI.find((x) => x.key === key);
    if (r) {
      list = list.filter((p) => {
        const price = Number(p.price || 0);
        const okMin = r.min == null ? true : price >= r.min;
        const okMax = r.max == null ? true : price <= r.max;
        return okMin && okMax;
      });
    }
  }

  if (routeMinDiscount.value !== null) {
    list = list.filter((p) => Number(p.salePercent || 0) >= routeMinDiscount.value);
  }

  return list;
});

async function goToPage(p) {
  if (loading.value) return;
  const target = Math.max(1, Math.min(p, meta.value.last_page));
  if (target === meta.value.current_page) return;
  page.value = target;
  await fetchProducts();
  productsSection.value?.scrollIntoView({ behavior: "smooth", block: "start" });
}

const fromToText = computed(() => {
  const total = Number(meta.value.total || 0);
  if (!total) return "0 sản phẩm";
  const cur = Number(meta.value.current_page || 1);
  const pp = Number(meta.value.per_page || 12);
  const from = (cur - 1) * pp + 1;
  const to = Math.min(cur * pp, total);
  return `${from}-${to} / ${total} sản phẩm`;
});

type PageBtn =
  | { key: string; type: "page"; page: number; label: string }
  | { key: string; type: "dots"; label: string };

const pageButtons = computed<PageBtn[]>(() => {
  const last = meta.value.last_page;
  if (last <= 1) return [];

  const cur = meta.value.current_page;
  const btns: PageBtn[] = [];

  const addPage = (p) => btns.push({ key: `p-${p}`, type: "page", page: p, label: String(p) });
  const addDots = (k) => btns.push({ key: `d-${k}`, type: "dots", label: "..." });

  if (last <= 7) {
    for (let p = 1; p <= last; p++) addPage(p);
    return btns;
  }

  addPage(1);

  const left = Math.max(2, cur - 1);
  const right = Math.min(last - 1, cur + 1);

  if (left > 2) addDots("l");
  for (let p = left; p <= right; p++) addPage(p);
  if (right < last - 1) addDots("r");

  addPage(last);
  return btns;
});

function resetFilters() {
  filters.value = {
    brandIds: [],
    categoryIds: [],
    sizes: [],
    colors: [],
    priceRange: "",
  };
  filterSearch.value = "";
  sortUI.value = "pop";
  page.value = 1;

  router.replace({
    path: route.path,
    query: {
      ...(routeSearch.value ? { search: routeSearch.value } : {}),
      ...(routeSale.value ? { sale: 1 } : {}),
    },
  });

  fetchProducts();
  fetchFacets();
}

const filteredBrands = computed(() => {
  const s = (filterSearch.value || "").trim().toLowerCase();
  const list = facets.brands || [];
  if (!s) return list;
  return list.filter((b) => String(b.name || "").toLowerCase().includes(s));
});

const filteredCategories = computed(() => {
  const s = (filterSearch.value || "").trim().toLowerCase();
  const list = facets.categories || [];
  if (!s) return list;
  return list.filter((c) => String(c.name || "").toLowerCase().includes(s));
});

const filteredSizes = computed(() => {
  const s = (filterSearch.value || "").trim().toLowerCase();
  const list = facets.sizes || [];
  if (!s) return list;
  return list.filter((x) => String(x.value || "").toLowerCase().includes(s));
});

const filteredColors = computed(() => {
  const s = (filterSearch.value || "").trim().toLowerCase();
  const list = facets.colors || [];
  if (!s) return list;
  return list.filter((x) => String(x.value || "").toLowerCase().includes(s));
});

let tRoute = null;
watch(
  () => [routeSearch.value, routeCategoryId.value, routeSale.value, routeMinDiscount.value],
  () => {
    clearTimeout(tRoute);
    tRoute = setTimeout(() => {
      page.value = 1;

      if (routeCategoryId.value != null) {
        filters.value.categoryIds = [routeCategoryId.value];
      }

      fetchProducts();
      fetchFacets();
    }, 50);
  }
);

let tFilters = null;
watch(
  () => [sortUI.value, filters.value, perPage.value],
  () => {
    clearTimeout(tFilters);
    tFilters = setTimeout(() => {
      page.value = 1;
      fetchProducts();
      fetchFacets();
    }, 250);
  },
  { deep: true }
);

onMounted(() => {
  fetchFacets();
  fetchProducts();
});
</script>