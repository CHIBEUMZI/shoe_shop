<template>
  <header
    class="sticky top-0 z-50 w-full bg-white/90 dark:bg-background-dark/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800"
  >
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
      <div class="flex items-center justify-between h-16 lg:h-20 gap-4">
        <!-- LEFT -->
        <div class="flex items-center gap-2 flex-shrink-0 cursor-pointer" @click="goHome">
          <div class="size-10 bg-primary rounded-xl flex items-center justify-center text-white">
            <span class="material-symbols-outlined text-2xl">trolley</span>
          </div>
          <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            BMC Shoes
          </h1>
        </div>

        <!-- SEARCH (md+) -->
        <div class="hidden md:flex flex-1 max-w-lg px-4">
          <div class="relative w-full" ref="searchWrap">
            <span
              class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
            >
              search
            </span>

            <input
              :value="modelValue"
              @input="onInput"
              @focus="onFocus"
              @keydown.down.prevent="onArrowDown"
              @keydown.up.prevent="onArrowUp"
              @keydown.enter.prevent="onEnter"
              @keydown.esc="closeSuggest"
              class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-xl py-2.5 pl-10 pr-10 text-sm focus:ring-2 focus:ring-primary/50 transition-all"
              placeholder="Tìm giày, thương hiệu..."
              type="text"
              autocomplete="off"
            />

            <!-- clear -->
            <button
              v-if="modelValue"
              type="button"
              class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-lg hover:bg-slate-200/70 dark:hover:bg-slate-700/70"
              @click="clearSearch"
              title="Xoá"
            >
              <span class="material-symbols-outlined text-slate-500 text-[18px]">close</span>
            </button>

            <!-- Suggest dropdown -->
            <div
              v-show="showSuggest"
              class="absolute left-0 right-0 top-full mt-2 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark shadow-xl overflow-hidden"
            >
              <div
                class="px-3 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between"
              >
                <div class="text-xs font-bold text-slate-500 dark:text-slate-400">
                  Gợi ý sản phẩm
                </div>
                <div class="text-xs text-slate-400" v-if="loadingSuggest">Đang tìm...</div>
              </div>

              <div
                v-if="!loadingSuggest && suggestions.length === 0"
                class="px-4 py-4 text-sm text-slate-500"
              >
                Không có sản phẩm phù hợp.
              </div>

              <ul v-else class="max-h-96 overflow-auto">
                <li
                  v-for="(p, idx) in suggestions"
                  :key="p.id"
                  class="px-3 py-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800"
                  :class="idx === activeIndex ? 'bg-slate-50 dark:bg-slate-800' : ''"
                  @mouseenter="activeIndex = idx"
                  @click="goProduct(p.slug)"
                >
                  <div class="flex items-center gap-3">
                    <div
                      class="size-12 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 flex-shrink-0"
                    >
                      <img
                        v-if="p.image"
                        :src="p.image"
                        :alt="p.name"
                        class="w-full h-full object-cover"
                      />
                    </div>

                    <div class="min-w-0 flex-1">
                      <div class="text-sm font-bold text-slate-900 dark:text-white truncate">
                        {{ p.name }}
                      </div>
                      <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                        {{ p.brand || "Brand" }}
                      </div>
                    </div>

                    <div class="text-sm font-extrabold text-primary">
                      {{ moneyVND(p.price) }}
                    </div>
                  </div>
                </li>
              </ul>

              <div class="border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  class="w-full text-left px-4 py-3 text-sm font-semibold text-primary hover:bg-primary/10 transition-colors"
                  @click="emitSearchAll"
                >
                  Xem tất cả kết quả cho: “{{ modelValue }}”
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="flex items-center gap-2 lg:gap-4">
          <button
            v-if="!isLoggedIn"
            class="hidden lg:flex items-center justify-center px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/10 rounded-xl transition-colors"
            type="button"
            @click="$emit('login')"
          >
            Đăng nhập
          </button>

          <div
            class="flex items-center gap-1 border-l border-slate-200 dark:border-slate-800 pl-2 lg:pl-4"
          >
            <!-- Account dropdown -->
            <div class="relative" ref="accountWrap">
              <button
                class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors relative"
                type="button"
                @click="toggleAccountMenu"
                title="Tài khoản"
              >
                <span class="material-symbols-outlined text-slate-700 dark:text-slate-300">
                  person
                </span>
              </button>

              <transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0 scale-95 -translate-y-1"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-95 -translate-y-1"
              >
                <div
                  v-if="showAccountMenu"
                  class="absolute right-0 top-full mt-2 w-64 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark shadow-xl overflow-hidden"
                >
                  <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="text-sm font-bold text-slate-900 dark:text-white">
                      Tài khoản
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                      {{
                        isLoggedIn
                          ? "Quản lý tài khoản và đơn hàng của bạn"
                          : "Đăng nhập để mua sắm thuận tiện hơn"
                      }}
                    </div>
                  </div>

                  <div v-if="isLoggedIn" class="py-2">
                    <button
                      type="button"
                      class="w-full px-4 py-3 text-left text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center gap-3"
                      @click="goProfile"
                    >
                      <span class="material-symbols-outlined text-[20px] text-slate-500">
                        person
                      </span>
                      <span>Tài khoản của tôi</span>
                    </button>

                    <button
                      type="button"
                      class="w-full px-4 py-3 text-left text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center gap-3"
                      @click="goOrders"
                    >
                      <span class="material-symbols-outlined text-[20px] text-slate-500">
                        receipt_long
                      </span>
                      <span>Đơn hàng của tôi</span>
                    </button>

                    <button
                      type="button"
                      class="w-full px-4 py-3 text-left text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center gap-3"
                      @click="goWishlist"
                    >
                      <span class="material-symbols-outlined text-[20px] text-slate-500">
                        favorite
                      </span>
                      <span>Danh sách yêu thích</span>
                    </button>

                    <button
                      type="button"
                      class="w-full px-4 py-3 text-left text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center gap-3"
                      @click="goMyCoupons"
                    >
                      <span class="material-symbols-outlined text-[20px] text-slate-500">
                        local_offer
                      </span>
                      <span>Mã giảm giá của tôi</span>
                    </button>

                    <div class="border-t border-slate-100 dark:border-slate-800 my-2"></div>

                    <button
                      type="button"
                      class="w-full px-4 py-3 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors flex items-center gap-3"
                      @click="logout"
                    >
                      <span class="material-symbols-outlined text-[20px]">logout</span>
                      <span>Đăng xuất</span>
                    </button>
                  </div>

                  <div v-else class="py-2">
                    <button
                      type="button"
                      class="w-full px-4 py-3 text-left text-sm text-primary hover:bg-primary/10 transition-colors flex items-center gap-3"
                      @click="login"
                    >
                      <span class="material-symbols-outlined text-[20px]">login</span>
                      <span>Đăng nhập</span>
                    </button>
                  </div>
                </div>
              </transition>
            </div>

            <button
              class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors relative"
              type="button"
              @click="$emit('wishlist')"
              title="Yêu thích"
            >
              <span class="material-symbols-outlined text-slate-700 dark:text-slate-300">
                favorite
              </span>
              <span
                v-if="wishlistCount > 0"
                class="absolute top-1 right-1 size-4 bg-primary text-white text-[10px] flex items-center justify-center rounded-full border-2 border-white dark:border-background-dark font-bold"
              >
                {{ wishlistCount }}
              </span>
            </button>

            <button
              class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors relative"
              type="button"
              @click="$emit('cart')"
              title="Giỏ hàng"
            >
              <span class="material-symbols-outlined text-slate-700 dark:text-slate-300">
                shopping_cart
              </span>
              <span
                v-if="cartCount > 0"
                class="absolute top-1 right-1 size-4 bg-primary text-white text-[10px] flex items-center justify-center rounded-full border-2 border-white dark:border-background-dark font-bold"
              >
                {{ cartCount }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- NAV (lg+): parent + children options -->
    <nav
      class="hidden lg:block border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-background-dark"
    >
      <div class="max-w-7xl mx-auto px-8">
        <ul class="flex items-center gap-2 h-12">
          <li v-for="item in menu" :key="item.id || item.slug || item.label" class="relative group">
            <a
              class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-primary hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
              href="#"
              @click.prevent="emitNav(item)"
            >
              {{ item.label }}
              <span
                v-if="item.children?.length"
                class="material-symbols-outlined text-[18px] opacity-70"
              >
                expand_more
              </span>
            </a>

            <div
              v-if="item.children?.length"
              class="absolute left-0 top-full pt-2 hidden group-hover:block"
            >
              <div
                class="w-64 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark shadow-lg overflow-hidden"
              >
                <div
                  class="px-3 py-2 text-xs font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800"
                >
                  {{ item.label }}
                </div>

                <ul class="py-2">
                  <li v-for="child in item.children" :key="child.id || child.slug">
                    <a
                      class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-primary transition-colors"
                      href="#"
                      @click.prevent="emitNav({ parent: item, child })"
                    >
                      {{ child.label }}
                    </a>
                  </li>

                  <li class="mt-1 border-t border-slate-100 dark:border-slate-800">
                    <a
                      class="block px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/10 transition-colors"
                      href="#"
                      @click.prevent="emitNav(item)"
                    >
                      Xem tất cả {{ item.label }}
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </li>

          <li class="ml-2">
            <a
              class="inline-flex items-center px-3 py-2 rounded-xl text-sm font-bold text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors uppercase tracking-wider"
              href="#"
              @click.prevent="onSaleClick"
            >
              Sale
            </a>
          </li>

          <li>
            <a
              class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-primary hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
              href="#"
              @click.prevent="goCoupons"
            >
              <span class="material-symbols-outlined text-[18px]">local_offer</span>
              Mã giảm giá
            </a>
          </li>
        </ul>
      </div>
    </nav>
  </header>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import productPublicService from "../../services/public/productService";

const props = defineProps({
  modelValue: { type: String, default: "" },
  isLoggedIn: { type: Boolean, default: false },
  cartCount: { type: Number, default: 0 },
  wishlistCount: { type: Number, default: 0 },
  menu: { type: Array, default: () => [] },
});

const emit = defineEmits([
  "update:modelValue",
  "login",
  "logout",
  "profile",
  "wishlist",
  "cart",
  "nav",
  "sale",
  "searchAll",
]);

const router = useRouter();

/** ===== MENU click => emit nav ===== */
function emitNav(payload) {
  closeSuggest();
  closeAccountMenu();
  emit("nav", payload);
}

function onSaleClick() {
  closeSuggest();
  closeAccountMenu();
  emit("sale");
}

function goCoupons() {
  closeSuggest();
  closeAccountMenu();
  router.push("/shop/coupons");
}

/** ===== ACCOUNT DROPDOWN ===== */
const accountWrap = ref(null);
const showAccountMenu = ref(false);

function toggleAccountMenu() {
  closeSuggest();
  showAccountMenu.value = !showAccountMenu.value;
}

function closeAccountMenu() {
  showAccountMenu.value = false;
}

function login() {
  closeAccountMenu();
  emit("login");
}

function logout() {
  closeAccountMenu();
  emit("logout");
}

function goProfile() {
  closeAccountMenu();
  emit("profile");
}

function goWishlist() {
  closeAccountMenu();
  emit("wishlist");
}

function goOrders() {
  closeAccountMenu();
  router.push("/shop/orders");
}

function goMyCoupons() {
  closeAccountMenu();
  router.push("/shop/my-coupons");
}

/** ===== SEARCH SUGGEST ===== */
const searchWrap = ref(null);
const loadingSuggest = ref(false);
const suggestions = ref([]);
const showSuggest = ref(false);
const activeIndex = ref(-1);

const API_BASE = import.meta.env.VITE_API_URL || "";

function buildImageUrl(pathOrUrl) {
  if (!pathOrUrl) return "";
  if (String(pathOrUrl).startsWith("http")) return pathOrUrl;
  if (String(pathOrUrl).startsWith("/")) return `${API_BASE}${pathOrUrl}`;
  return `${API_BASE}/storage/${pathOrUrl}`;
}

function getPrice(p) {
  const sale =
    p.base_sale_price !== null && p.base_sale_price !== undefined
      ? Number(p.base_sale_price)
      : null;
  const base = p.base_price !== null && p.base_price !== undefined ? Number(p.base_price) : 0;
  return sale !== null ? sale : base;
}

function mapSuggest(p) {
  return {
    id: p.id,
    slug: p.slug,
    name: p.name,
    brand: p.brand?.name ?? "Brand",
    price: getPrice(p),
    image: buildImageUrl(p.thumbnail || ""),
  };
}

function moneyVND(v) {
  const n = Number(v || 0);
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(n);
}

async function fetchSuggest(q) {
  const keyword = (q || "").trim();
  if (!keyword) {
    suggestions.value = [];
    loadingSuggest.value = false;
    showSuggest.value = false;
    activeIndex.value = -1;
    return;
  }

  loadingSuggest.value = true;
  showSuggest.value = true;

  try {
    const res = await productPublicService.list({
      per_page: 6,
      page: 1,
      search: keyword,
      sort: "popular",
    });

    suggestions.value = (res.data?.data ?? []).map(mapSuggest);
    activeIndex.value = suggestions.value.length ? 0 : -1;
  } catch (e) {
    suggestions.value = [];
    activeIndex.value = -1;
  } finally {
    loadingSuggest.value = false;
  }
}

/** debounce typing */
let t = null;

function onInput(e) {
  const v = e?.target?.value ?? "";
  emit("update:modelValue", v);

  clearTimeout(t);
  t = setTimeout(() => fetchSuggest(v), 250);
}

function onFocus() {
  if ((props.modelValue || "").trim()) {
    showSuggest.value = true;
    if (!suggestions.value.length) fetchSuggest(props.modelValue);
  }
}

function closeSuggest() {
  showSuggest.value = false;
  activeIndex.value = -1;
}

function clearSearch() {
  emit("update:modelValue", "");
  suggestions.value = [];
  closeSuggest();
}

function goProduct(slug) {
  closeSuggest();
  closeAccountMenu();
  router.push(`/shop/products/${slug}`);
}

function emitSearchAll() {
  emit("searchAll", props.modelValue);
  closeSuggest();
}

function onArrowDown() {
  if (!showSuggest.value || !suggestions.value.length) return;
  activeIndex.value = Math.min(activeIndex.value + 1, suggestions.value.length - 1);
}

function onArrowUp() {
  if (!showSuggest.value || !suggestions.value.length) return;
  activeIndex.value = Math.max(activeIndex.value - 1, 0);
}

function onEnter() {
  if (!showSuggest.value) return;

  if (activeIndex.value >= 0 && suggestions.value[activeIndex.value]) {
    goProduct(suggestions.value[activeIndex.value].slug);
  } else {
    emitSearchAll();
  }
}

/** click outside => close dropdown */
function onDocClick(e) {
  const searchEl = searchWrap.value;
  const accountEl = accountWrap.value;

  if (searchEl && !searchEl.contains(e.target)) {
    closeSuggest();
  }

  if (accountEl && !accountEl.contains(e.target)) {
    closeAccountMenu();
  }
}

onMounted(() => {
  document.addEventListener("click", onDocClick);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", onDocClick);
});

watch(
  () => props.modelValue,
  (v) => {
    if (!String(v || "").trim()) {
      suggestions.value = [];
      closeSuggest();
    }
  }
);

function goHome() {
  closeSuggest();
  closeAccountMenu();
  router.push("/shop");
}
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
}
</style>