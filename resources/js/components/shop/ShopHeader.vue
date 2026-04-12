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
              class="absolute left-1/2 -translate-x-1/2 top-full pt-3 hidden group-hover:block"
            >
              <div
                class="w-64 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 bg-white dark:bg-[#1e1e2e] shadow-2xl shadow-slate-900/10 overflow-hidden backdrop-blur-lg"
              >
                <div
                  class="px-4 py-3 text-xs font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800/50 bg-gradient-to-r from-slate-50 to-white dark:from-slate-800/50 dark:to-slate-900/50"
                >
                  {{ item.label }}
                </div>

                <ul class="py-2">
                  <li v-for="child in item.children" :key="child.id || child.slug">
                    <a
                      class="block px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:text-primary hover:bg-gradient-to-r hover:from-primary/5 hover:to-transparent transition-colors flex items-center gap-2"
                      href="#"
                      @click.prevent="emitNav({ parent: item, child })"
                    >
                      <span
                        class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600 group-hover:bg-primary group-hover:scale-125 transition-all"
                      ></span>
                      {{ child.label }}
                    </a>
                  </li>

                  <li class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/50">
                    <a
                      class="mx-2 block px-4 py-2.5 text-sm font-semibold text-primary hover:bg-primary/10 rounded-xl transition-colors flex items-center gap-2"
                      href="#"
                      @click.prevent="emitNav(item)"
                    >
                      <span class="material-symbols-outlined text-lg">arrow_forward</span>
                      Xem tất cả {{ item.label }}
                    </a>
                  </li>
                </ul>
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
/**
 * ShopHeader.vue - 商店顶部导航组件
 * 
 * 功能说明：
 * - Logo 区域：品牌标识，点击返回首页
 * - 搜索框：支持实时搜索建议、键盘导航、快捷清空
 * - 导航菜单：分类菜单，支持多级下拉
 * - 右侧按钮：登录/账户、收藏、购物车
 * - Sale 按钮：促销入口，带闪烁动画
 * - Mã giảm giá：优惠券入口
 * 
 * 作者：BMC Team
 * 更新日期：2026-04-10
 */

import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import productPublicService from "../../services/public/productService";

// ==================== PROPS & EMITS ====================
const props = defineProps({
  // v-model 搜索关键词
  modelValue: { type: String, default: "" },
  // 是否已登录
  isLoggedIn: { type: Boolean, default: false },
  // 用户头像 URL
  avatar: { type: String, default: "" },
  // 用户名称
  userName: { type: String, default: "" },
  // 购物车商品数量
  cartCount: { type: Number, default: 0 },
  // 收藏商品数量
  wishlistCount: { type: Number, default: 0 },
  // 分类菜单数据
  menu: { type: Array, default: () => [] },
});

// 定义组件事件
const emit = defineEmits([
  "update:modelValue",  // 搜索关键词更新
  "login",              // 登录按钮点击
  "logout",             // 登出按钮点击
  "profile",            // 个人中心点击
  "wishlist",           // 收藏页点击
  "cart",               // 购物车点击
  "nav",                // 菜单导航点击
  "sale",               // Sale 促销点击
  "searchAll",          // 查看所有搜索结果
  "search",             // 移动端搜索按钮点击
]);

const router = useRouter();

// ==================== 菜单导航 ====================
/**
 * 触发菜单导航事件
 * @param {Object} payload - 菜单项数据
 */
function emitNav(payload) {
  closeSuggest();
  closeAccountMenu();
  emit("nav", payload);
}

/**
 * Sale 促销按钮点击处理
 */
function onSaleClick() {
  closeSuggest();
  closeAccountMenu();
  emit("sale");
}

/**
 * 跳转优惠券页面
 */
function goCoupons() {
  closeSuggest();
  closeAccountMenu();
  router.push("/shop/coupons");
}

// ==================== 账户下拉菜单 ====================
const accountWrap = ref(null);       // 账户下拉容器引用
const showAccountMenu = ref(false);  // 下拉菜单显示状态

/**
 * 切换账户下拉菜单
 */
function toggleAccountMenu() {
  closeSuggest();
  showAccountMenu.value = !showAccountMenu.value;
}

/**
 * 关闭账户下拉菜单
 */
function closeAccountMenu() {
  showAccountMenu.value = false;
}

/**
 * 登录按钮处理
 */
function login() {
  closeAccountMenu();
  emit("login");
}

/**
 * 登出按钮处理
 */
function logout() {
  closeAccountMenu();
  emit("logout");
}

/**
 * 跳转个人中心
 */
function goProfile() {
  closeAccountMenu();
  emit("profile");
}

/**
 * 跳转收藏页面
 */
function goWishlist() {
  closeAccountMenu();
  emit("wishlist");
}

/**
 * 跳转订单页面
 */
function goOrders() {
  closeAccountMenu();
  router.push("/shop/orders");
}

/**
 * 跳转我的优惠券页面
 */
function goMyCoupons() {
  closeAccountMenu();
  router.push("/shop/my-coupons");
}

// ==================== 搜索建议 ====================
const searchWrap = ref(null);         // 搜索容器引用
const loadingSuggest = ref(false);     // 加载状态
const suggestions = ref([]);          // 搜索建议列表
const showSuggest = ref(false);        // 建议下拉显示状态
const activeIndex = ref(-1);           // 键盘导航当前索引

// API 基础地址
const API_BASE = import.meta.env.VITE_API_URL || "";

/**
 * 构建图片完整 URL
 * @param {String} pathOrUrl - 图片路径或完整 URL
 * @returns {String} 完整的图片 URL
 */
function buildImageUrl(pathOrUrl) {
  if (!pathOrUrl) return "";
  if (String(pathOrUrl).startsWith("http")) return pathOrUrl;
  if (String(pathOrUrl).startsWith("/")) return `${API_BASE}${pathOrUrl}`;
  return `${API_BASE}/storage/${pathOrUrl}`;
}

/**
 * 获取商品价格（优先使用促销价）
 * @param {Object} p - 商品对象
 * @returns {Number} 商品价格
 */
function getPrice(p) {
  const sale =
    p.base_sale_price !== null && p.base_sale_price !== undefined
      ? Number(p.base_sale_price)
      : null;
  const base = p.base_price !== null && p.base_price !== undefined ? Number(p.base_price) : 0;
  return sale !== null ? sale : base;
}

/**
 * 映射搜索建议数据结构
 * @param {Object} p - 商品原始数据
 * @returns {Object} 映射后的数据
 */
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

/**
 * 格式化越南盾金额
 * @param {Number} v - 金额
 * @returns {String} 格式化后的金额字符串
 */
function moneyVND(v) {
  const n = Number(v || 0);
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(n);
}

/**
 * 获取搜索建议
 * @param {String} q - 搜索关键词
 */
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

// 防抖定时器
let t = null;

/**
 * 搜索输入事件处理（带防抖）
 * @param {Event} e - 输入事件
 */
function onInput(e) {
  const v = e?.target?.value ?? "";
  emit("update:modelValue", v);

  clearTimeout(t);
  t = setTimeout(() => fetchSuggest(v), 250);
}

/**
 * 搜索框获得焦点事件
 */
function onFocus() {
  if ((props.modelValue || "").trim()) {
    showSuggest.value = true;
    if (!suggestions.value.length) fetchSuggest(props.modelValue);
  }
}

/**
 * 关闭搜索建议下拉
 */
function closeSuggest() {
  showSuggest.value = false;
  activeIndex.value = -1;
}

/**
 * 清空搜索内容
 */
function clearSearch() {
  emit("update:modelValue", "");
  suggestions.value = [];
  closeSuggest();
}

/**
 * 跳转商品详情页
 * @param {String} slug - 商品 slug
 */
function goProduct(slug) {
  closeSuggest();
  closeAccountMenu();
  router.push(`/shop/products/${slug}`);
}

/**
 * 触发查看所有搜索结果
 */
function emitSearchAll() {
  emit("searchAll", props.modelValue);
  closeSuggest();
}

/**
 * 键盘下箭头导航
 */
function onArrowDown() {
  if (!showSuggest.value || !suggestions.value.length) return;
  activeIndex.value = Math.min(activeIndex.value + 1, suggestions.value.length - 1);
}

/**
 * 键盘上箭头导航
 */
function onArrowUp() {
  if (!showSuggest.value || !suggestions.value.length) return;
  activeIndex.value = Math.max(activeIndex.value - 1, 0);
}

/**
 * 回车键确认选择
 */
function onEnter() {
  if (!showSuggest.value) return;

  if (activeIndex.value >= 0 && suggestions.value[activeIndex.value]) {
    goProduct(suggestions.value[activeIndex.value].slug);
  } else {
    emitSearchAll();
  }
}

// ==================== 点击外部关闭下拉 ====================
/**
 * 文档点击事件（关闭下拉菜单）
 * @param {Event} e - 点击事件
 */
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

// 监听搜索关键词变化
watch(
  () => props.modelValue,
  (v) => {
    if (!String(v || "").trim()) {
      suggestions.value = [];
      closeSuggest();
    }
  }
);

/**
 * 跳转首页
 */
function goHome() {
  closeSuggest();
  closeAccountMenu();
  router.push("/shop");
}
</script>

<style scoped>
/* Material Symbols 字体配置 */
.material-symbols-outlined {
  font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
}

/* Badge 轻微弹跳动画 */
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
