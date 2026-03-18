<template>
  <div
    class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 min-h-screen"
  >
    <ShopHeader
      v-model="headerQ"
      :isLoggedIn="auth.isLoggedIn"
      :cartCount="cartCount"
      :wishlistCount="wishlistCount"
      :menu="menu"
      @login="goLogin"
      @logout="doLogout"
      @cart="goCart"
      @profile="goProfile"
      @nav="onNav"
      @sale="onSale"
      @wishlist="goWishlist"
      @searchAll="onSearchAll"
    />

    <router-view v-slot="{ Component }">
      <component :is="Component" :q="appliedQ" />
    </router-view>
    <ChatBot />
    <ShopFooter />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { useCartStore } from "../../stores/cart";

import ShopHeader from "../../components/shop/ShopHeader.vue";
import ShopFooter from "../../components/shop/ShopFooter.vue";
import ChatBot from "../../components/ChatBot.vue";

import categoryService from "../../services/public/categoryService";

const router = useRouter();
const route = useRoute();

const auth = useAuthStore();
const cartStore = useCartStore();

const appliedQ = ref(String(route.query.search || ""));
const headerQ = ref(appliedQ.value);

const wishlistCount = ref(2);

const cartCount = computed(() => {
  if (!auth.isLoggedIn) return 0;

  const sum = cartStore.cart?.summary?.quantity_sum;
  if (sum !== undefined && sum !== null) return Number(sum) || 0;

  const items = cartStore.cart?.items || [];
  return items.reduce((s, it) => s + Number(it.quantity || 0), 0);
});

const categories = ref([]);

const menu = computed(() =>
  (categories.value || []).map((c) => ({
    id: c.id,
    label: c.name,
    slug: c.slug,
    children: (c.children || []).map((ch) => ({
      id: ch.id,
      label: ch.name,
      slug: ch.slug,
    })),
  }))
);

function findCategoryIdBySlug(slug) {
  const s = String(slug || "");
  if (!s) return null;

  for (const c of categories.value || []) {
    if (c.slug === s) return c.id;
    for (const ch of c.children || []) {
      if (ch.slug === s) return ch.id;
    }
  }

  return null;
}

onMounted(async () => {
  try {
    const res = await categoryService.list({ with_children: 1 });
    categories.value = res?.data?.data ?? [];
  } catch (e) {
    console.error("Load categories failed:", e);
    categories.value = [];
  }

  if (auth.isLoggedIn) {
    cartStore.fetchCart().catch(() => {});
  }
});

watch(
  () => auth.isLoggedIn,
  (v) => {
    if (v) cartStore.fetchCart().catch(() => {});
    else cartStore.cart = null;
  }
);

watch(
  () => route.query.search,
  (v) => {
    const s = String(v || "");
    appliedQ.value = s;
    headerQ.value = s;
  }
);

function onSearchAll(keyword) {
  const s = String(keyword || headerQ.value || "").trim();

  appliedQ.value = s;
  headerQ.value = s;

  router.push({
    path: "/shop/products",
    query: {
      search: s || undefined,
      page: 1,
    },
  });
}

function onNav(payload) {
  const slug = payload?.child?.slug || payload?.slug;
  if (!slug) return;

  const id = findCategoryIdBySlug(slug);
  if (!id) return;

  router.push({
    path: "/shop/products",
    query: {
      category_id: id,
      page: 1,
      search: appliedQ.value?.trim() || undefined,
      sale: undefined,
    },
  });
}

function onSale() {
  router.push({
    path: "/shop/products",
    query: {
      sale: 1,
      page: 1,
      category_id: undefined,
      search: undefined,
    },
  });
}

function goLogin() {
  router.push({
    path: "/login",
    query: { redirect: router.currentRoute.value.fullPath },
  });
}

async function doLogout() {
  if (typeof auth.logout === "function") await auth.logout();
  cartStore.cart = null;
  router.push("/shop");
}

function goCart() {
  if (!auth.isLoggedIn) return goLogin();
  router.push("/shop/cart");
}

function goWishlist() {
  if (!auth.isLoggedIn) return goLogin();
  router.push("/shop/wishlist");
}

function goProfile() {
  if (!auth.isLoggedIn) return goLogin();
  router.push("/shop/profile");
}
</script>