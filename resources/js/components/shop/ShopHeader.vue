<template>
  <header
    class="sticky top-0 z-50 w-full bg-white/95 dark:bg-background-dark/95 backdrop-blur-xl shadow-sm border-b border-slate-200/50 dark:border-slate-700/50"
  >
    <!-- z-index above <nav> so search/account dropdowns paint over category row -->
    <div class="relative z-30 max-w-7xl mx-auto px-4 lg:px-8">
      <div class="flex items-center justify-between h-16 lg:h-[72px] gap-4">
        <!-- LEFT: Logo -->
        <div class="flex items-center gap-3 flex-shrink-0 cursor-pointer group" @click="goHome">
          <div
            class="size-11 bg-gradient-to-br from-primary to-primary/80 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary/25 group-hover:shadow-xl group-hover:shadow-primary/30 group-hover:scale-105 transition-all duration-300"
          >
            <span class="material-symbols-outlined text-2xl">trolley</span>
          </div>
          <div class="flex flex-col">
            <h1
              class="text-xl font-bold tracking-tight text-slate-900 dark:text-white leading-tight"
            >
              BMC Shoes
            </h1>
            <span class="text-[10px] text-slate-400 font-medium tracking-wider hidden sm:block"
              >Premium Footwear</span
            >
          </div>
        </div>

        <!-- SEARCH (md+) -->
        <div class="hidden md:flex flex-1 max-w-xl px-4 lg:px-8">
          <div class="relative w-full" ref="searchWrap">
            <!-- Search icon wrapper -->
            <div
              class="absolute left-3 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center pointer-events-none"
            >
              <span class="material-symbols-outlined text-slate-400 text-lg"> search </span>
            </div>

            <input
              :value="modelValue"
              @input="onInput"
              @focus="onFocus"
              @keydown.down.prevent="onArrowDown"
              @keydown.up.prevent="onArrowUp"
              @keydown.enter.prevent="onEnter"
              @keydown.esc="closeSuggest"
              class="w-full bg-slate-100 dark:bg-slate-800/80 border-2 border-transparent rounded-lg py-2.5 pl-11 pr-11 text-sm placeholder:text-slate-400 focus:bg-white dark:focus:bg-slate-800 focus:border-primary/30 focus:ring-4 focus:ring-primary/10 transition-all duration-200"
              placeholder="Tìm giày, thương hiệu..."
              type="text"
              autocomplete="off"
            />

            <!-- clear button -->
            <button
              v-if="modelValue"
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 flex items-center justify-center transition-colors"
              @click="clearSearch"
              title="Xoá"
            >
              <span class="material-symbols-outlined text-slate-500 dark:text-slate-400 text-sm">
                close
              </span>
            </button>

            <!-- Suggest dropdown -->
            <div
              v-show="showSuggest"
              class="absolute left-0 right-0 top-full mt-3 rounded-lg border border-slate-200/80 dark:border-slate-700/80 bg-white dark:bg-[#1e1e2e] shadow-2xl shadow-slate-900/10 overflow-hidden backdrop-blur-lg"
            >
              <div
                class="px-4 py-3 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between bg-gradient-to-r from-slate-50 to-white dark:from-slate-800/50 dark:to-slate-900/50"
              >
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary text-lg">auto_awesome</span>
                  <span class="text-xs font-bold text-slate-600 dark:text-slate-300">
                    Gợi ý sản phẩm
                  </span>
                </div>
                <div
                  class="text-xs text-slate-400 flex items-center gap-1"
                  v-if="loadingSuggest"
                >
                  <span
                    class="w-3 h-3 border-2 border-primary/30 border-t-primary rounded-full animate-spin"
                  ></span>
                  Đang tìm...
                </div>
              </div>

              <div
                v-if="!loadingSuggest && suggestions.length === 0"
                class="px-4 py-8 text-sm text-slate-400 text-center"
              >
                <span class="material-symbols-outlined text-4xl mb-2">search_off</span>
                <p>Không có sản phẩm phù hợp.</p>
              </div>

              <ul v-else class="max-h-80 overflow-auto divide-y divide-slate-100 dark:divide-slate-800/50">
                <li
                  v-for="(p, idx) in suggestions"
                  :key="p.id"
                  class="px-3 py-2.5 cursor-pointer hover:bg-gradient-to-r hover:from-primary/5 to-transparent dark:hover:bg-primary/10 transition-all duration-150"
                  :class="idx === activeIndex ? 'bg-gradient-to-r from-primary/10 to-transparent' : ''"
                  @mouseenter="activeIndex = idx"
                  @click="goProduct(p.slug)"
                >
                  <div class="flex items-center gap-3">
                    <div
                      class="size-12 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 flex-shrink-0 ring-1 ring-slate-200 dark:ring-slate-700"
                    >
                      <img
                        v-if="p.image"
                        :src="p.image"
                        :alt="p.name"
                        class="w-full h-full object-cover"
                      />
                    </div>

                    <div class="min-w-0 flex-1">
                      <div
                        class="text-sm font-semibold text-slate-900 dark:text-white truncate"
                      >
                        {{ p.name }}
                      </div>
                      <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">verified</span>
                        {{ p.brand || "Brand" }}
                      </div>
                    </div>

                    <div
                      class="px-2.5 py-1 bg-primary/10 rounded-lg text-sm font-bold text-primary">
                      {{ moneyVND(p.price) }}
                    </div>
                  </div>
                </li>
              </ul>

              <div class="border-t border-slate-100 dark:border-slate-800/50 p-2 bg-slate-50/50 dark:bg-slate-800/30">
                <button
                  type="button"
                  class="w-full text-left px-4 py-2.5 text-sm font-semibold text-primary hover:bg-primary/10 rounded-xl transition-colors flex items-center gap-2"
                  @click="emitSearchAll"
                >
                  <span class="material-symbols-outlined text-lg">arrow_forward</span>
                  Xem tất cả kết quả cho: "{{ modelValue }}"
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- MOBILE SEARCH BUTTON -->
        <button
          class="md:hidden p-2.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
          type="button"
          @click="$emit('search')"
          title="Tìm kiếm"
        >
          <span class="material-symbols-outlined text-slate-700 dark:text-slate-300"> search </span>
        </button>

        <!-- RIGHT: Action buttons -->
        <div class="flex items-center gap-1.5 lg:gap-2">
          <button
            v-if="!isLoggedIn"
            class="hidden lg:flex items-center justify-center px-5 py-2.5 text-sm font-bold transition-all duration-200 hover:-translate-y-0.5"
            type="button"
            @click="$emit('login')"
          >
            <span class="material-symbols-outlined mr-1.5 text-lg">login</span>
            Đăng nhập
          </button>

          <!-- Account dropdown -->
          <div class="relative" ref="accountWrap">
            <button
              class="p-2.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all duration-200 hover:scale-105 relative group"
              type="button"
              @click="toggleAccountMenu"
              title="Tài khoản"
            >
              <span
                class="material-symbols-outlined text-slate-700 dark:text-slate-300 group-hover:text-primary transition-colors">
                person
              </span>
              <!-- Tooltip -->
              <span
                class="absolute -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-slate-900 dark:bg-slate-700 text-white text-[10px] font-medium rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none"
              >
                Tài khoản
              </span>
            </button>

            <transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 scale-95 -translate-y-2"
              enter-to-class="opacity-100 scale-100 translate-y-0"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100 scale-100 translate-y-0"
              leave-to-class="opacity-0 scale-95 -translate-y-2"
            >
              <div
                v-if="showAccountMenu"
                class="absolute right-0 top-full mt-2 w-72 rounded-lg border border-slate-200/80 dark:border-slate-700/80 bg-white dark:bg-[#1e1e2e] shadow-2xl shadow-slate-900/10 overflow-hidden backdrop-blur-lg"
              >
                <!-- Header -->
                <div
                  class="px-4 py-4 border-b border-slate-100 dark:border-slate-800/50 bg-gradient-to-r from-slate-50 to-white dark:from-slate-800/50 dark:to-slate-900/50"
                >
                  <div class="flex items-center gap-3">
                    <!-- Avatar -->
                    <div
                      v-if="isLoggedIn && avatar"
                      class="w-11 h-11 rounded-full overflow-hidden ring-2 ring-primary/20 shadow-lg"
                    >
                      <img
                        :src="avatar"
                        :alt="userName || 'Avatar'"
                        class="w-full h-full object-cover"
                      />
                    </div>
                    <div
                      v-else
                      class="w-11 h-11 rounded-full bg-gradient-to-br from-primary to-primary/70 flex items-center justify-center text-white shadow-lg shadow-primary/20"
                    >
                      <span class="material-symbols-outlined text-xl">person</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-sm font-bold text-slate-900 dark:text-white truncate">
                        {{ isLoggedIn && userName ? userName : "Khách" }}
                      </div>
                      <div class="text-xs text-slate-500 dark:text-slate-400 truncate">
                        {{
                          isLoggedIn
                            ? "Chào mừng quay lại!"
                            : "Đăng nhập để mua sắm thuận tiện"
                        }}
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Logged in menu -->
                <div v-if="isLoggedIn" class="py-2">
                  <button
                    type="button"
                    class="w-full px-4 py-2.5 text-left text-sm text-slate-700 dark:text-slate-200 hover:bg-primary/5 transition-colors flex items-center gap-3"
                    @click="goProfile"
                  >
                    <span
                      class="w-8 rounded-lg flex items-center justify-center material-symbols-outlined text-[18px] text-slate-500">
                      person
                    </span>
                    <span>Tài khoản của tôi</span>
                  </button>

                  <button
                    type="button"
                    class="w-full px-4 py-2.5 text-left text-sm text-slate-700 dark:text-slate-200 hover:bg-primary/5 transition-colors flex items-center gap-3"
                    @click="goOrders"
                  >
                    <span
                      class="w-8 rounded-lg flex items-center justify-center material-symbols-outlined text-[18px] text-slate-500">
                      receipt_long
                    </span>
                    <span>Đơn hàng của tôi</span>
                  </button>

                  <button
                    type="button"
                    class="w-full px-4 py-2.5 text-left text-sm text-slate-700 dark:text-slate-200 hover:bg-primary/5 transition-colors flex items-center gap-3"
                    @click="goWishlist"
                  >
                    <span
                      class="w-8 rounded-lg flex items-center justify-center material-symbols-outlined text-[18px] text-slate-500">
                      favorite
                    </span>
                    <span>Danh sách yêu thích</span>
                  </button>

                  <button
                    type="button"
                    class="w-full px-4 py-2.5 text-left text-sm text-slate-700 dark:text-slate-200 hover:bg-primary/5 transition-colors flex items-center gap-3"
                    @click="goMyCoupons"
                  >
                    <span
                      class="w-8 rounded-lg flex items-center justify-center material-symbols-outlined text-[18px] text-slate-500">
                      local_offer
                    </span>
                    <span>Mã giảm giá của tôi</span>
                  </button>

                  <div class="mx-4 my-2 border-t border-slate-100 dark:border-slate-800/50"></div>

                  <button
                    type="button"
                    class="w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors flex items-center gap-3"
                    @click="logout"
                  >
                    <span
                      class="w-8 rounded-lg bg-red-50 dark:bg-red-500/10 flex items-center justify-center material-symbols-outlined text-[18px]">
                      logout
                    </span>
                    <span>Đăng xuất</span>
                  </button>
                </div>

                <!-- Guest menu -->
                <div v-else class="py-3 px-4">
                  <button
                    type="button"
                    class="w-full px-4 py-3 text-sm font-bold text-white bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary shadow-lg shadow-primary/25 hover:shadow-primary/40 rounded-xl transition-all duration-200 flex items-center justify-center gap-2"
                    @click="login"
                  >
                    <span class="material-symbols-outlined">login</span>
                    <span>Đăng nhập</span>
                  </button>
                </div>
              </div>
            </transition>
          </div>

          <!-- Wishlist button -->
          <button
            class="p-2.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all duration-200 hover:scale-105 relative group"
            type="button"
            @click="$emit('wishlist')"
            title="Yêu thích"
          >
            <span
              class="material-symbols-outlined text-slate-700 dark:text-slate-300 group-hover:text-red-500 transition-colors">
              favorite
            </span>
            <span
              v-if="wishlistCount > 0"
              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-gradient-to-r from-red-500 to-red-400 text-white text-[10px] flex items-center justify-center rounded-full border-2 border-white dark:border-background-dark font-bold shadow-lg shadow-red-500/30 animate-bounce-subtle"
            >
              {{ wishlistCount > 99 ? "99+" : wishlistCount }}
            </span>
            <!-- Tooltip -->
            <span
              class="absolute -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-slate-900 dark:bg-slate-700 text-white text-[10px] font-medium rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none"
            >
              Yêu thích
            </span>
          </button>

          <!-- Cart button -->
          <button
            class="p-2.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all duration-200 hover:scale-105 relative group"
            type="button"
            @click="$emit('cart')"
            title="Giỏ hàng"
          >
            <span
              class="material-symbols-outlined text-slate-700 dark:text-slate-300 group-hover:text-primary transition-colors">
              shopping_cart
            </span>
            <span
              v-if="cartCount > 0"
              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-gradient-to-r from-primary to-primary/80 text-white text-[10px] flex items-center justify-center rounded-full border-2 border-white dark:border-background-dark font-bold shadow-lg shadow-primary/30 animate-bounce-subtle"
            >
              {{ cartCount > 99 ? "99+" : cartCount }}
            </span>
            <!-- Tooltip -->
            <span
              class="absolute -bottom-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-slate-900 dark:bg-slate-700 text-white text-[10px] font-medium rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none"
            >
              Giỏ hàng
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- NAV (lg+): category menu -->
    <nav
      class="relative z-10 hidden lg:block border-t border-slate-100/80 dark:border-slate-800/50 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md"
    >
      <div class="max-w-7xl mx-auto px-8">
        <ul class="flex items-center gap-1 h-11">
          <li
            v-for="item in menu"
            :key="item.id || item.slug || item.label"
            class="relative group"
          >
            <a
              class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-primary hover:bg-primary/5 transition-all duration-200 relative"
              href="#"
              @click.prevent="emitNav(item)"
            >
              {{ item.label }}
              <span
                v-if="item.children?.length"
                class="material-symbols-outlined text-[16px] opacity-60 group-hover:rotate-180 group-hover:opacity-100 transition-all duration-200"
              >
                expand_more
              </span>
              <!-- Hover underline effect -->
              <span
                class="absolute bottom-0 left-1/2 -translate-x-1/2 w-0 h-0.5 bg-primary rounded-full group-hover:w-full transition-all duration-300"
              ></span>
            </a>

            <!-- Dropdown menu -->
            <div
              v-if="item.children?.length"
              class="absolute left-1/2 -translate-x-1/2 top-full pt-1 hidden group-hover:block"
            >
              <div
                class="w-80 rounded-lg border border-slate-200/60 dark:border-slate-700/60 bg-white dark:bg-[#1e1e2e] shadow-lg shadow-slate-900/15 overflow-hidden backdrop-blur-lg"
              >
                <!-- Child items -->
                <ul class="p-2 grid grid-cols-1 gap-1">
                  <li v-for="child in item.children" :key="child.id || child.slug">
                    <a
                      class="group/item flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-slate-700 dark:text-slate-200 hover:bg-gradient-to-r hover:from-primary/10 hover:to-transparent dark:hover:from-primary/20 transition-all duration-200"
                      href="#"
                      @click.prevent="emitNav({ parent: item, child })"
                    >
                      <span class="font-medium">{{ child.label }}</span>
                      <span class="material-symbols-outlined text-lg ml-auto opacity-0 group-hover/item:opacity-100 transition-opacity text-primary">arrow_forward</span>
                    </a>
                  </li>
                </ul>

                <!-- Footer -->
                <div class="px-3 pb-3">
                  <a
                    class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-bold text-primary bg-primary/5 hover:bg-primary/15 rounded-lg transition-colors"
                    href="#"
                    @click.prevent="emitNav(item)"
                  >
                    Xem tất cả {{ item.label }}
                  </a>
                </div>
              </div>
            </div>
          </li>

          <!-- Sale badge -->
          <li>
            <a
              class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all duration-200 relative group"
              href="#"
              @click.prevent="onSaleClick"
            >
              <span
                class="material-symbols-outlined text-lg text-red-500 group-hover:text-red-600 transition-colors">local_fire_department</span>
              <span>Sale</span>
            </a>
          </li>

          <!-- Coupons -->
          <li>
            <a
              class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-primary hover:text-primary hover:bg-primary/5 dark:hover:bg-primary/10 transition-all duration-200 relative group"
              href="#"
              @click.prevent="goCoupons"
            >
              <span
                class="material-symbols-outlined text-lg text-primary group-hover:text-primary transition-colors">local_offer</span>
              <span>Mã giảm giá</span>

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

// ==================== PROPS & EMITS ====================
const props = defineProps({
  modelValue: { type: String, default: "" },
  isLoggedIn: { type: Boolean, default: false },
  avatar: { type: String, default: "" },
  userName: { type: String, default: "" },
  cartCount: { type: Number, default: 0 },
  wishlistCount: { type: Number, default: 0 },
  menu: { type: Array, default: () => [] },
});

// 定义组件事件
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
  "search",             
]);

const router = useRouter();

/**
    * 
 * @param {Object} payload 
 */
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

@keyframes bounce-subtle {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-2px);
  }
}

.animate-bounce-subtle {
  animation: bounce-subtle 2s ease-in-out infinite;
}
</style>
