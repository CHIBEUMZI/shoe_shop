<template>
  <aside
    :class="[
      'flex-none bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col',
      'transition-[width] duration-300 ease-in-out will-change-[width]',
      'pt-6', 
      ui.collapsed ? 'w-20' : 'w-64'
    ]"
  >
    <!-- MENU -->
    <nav class="flex-1 px-3 space-y-4 mt-2 overflow-y-auto">
      <div v-for="group in menuGroups" :key="group.title" class="space-y-1">
        <h3
          v-if="!ui.collapsed"
          class="px-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2"
        >
          {{ group.title }}
        </h3>
        <div v-if="ui.collapsed" class="w-8 h-px bg-slate-200 dark:bg-slate-700 mx-auto mb-2"></div>

        <router-link
          v-for="item in group.items"
          :key="item.to"
          :to="item.to"
          :title="ui.collapsed ? item.label : ''"
          :class="[
            'rounded-lg transition-colors text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
            'flex items-center py-3',
            ui.collapsed ? 'justify-center px-0' : 'gap-3 px-4'
          ]"
          active-class="bg-primary/10 text-primary font-semibold"
        >
          <span class="material-symbols-outlined text-[20px]">
            {{ item.icon }}
          </span>

          <span v-if="!ui.collapsed" class="text-sm font-medium">
            {{ item.label }}
          </span>
        </router-link>
      </div>
    </nav>
  </aside>
</template>

<script setup>
import { useAdminUiStore } from "@/stores/adminUi";

const ui = useAdminUiStore();

const menuGroups = [
  {
    title: "Tổng quan",
    items: [
      { to: "/admin/dashboard", icon: "dashboard", label: "Bảng điều khiển" },
    ]
  },
  {
    title: "Sản phẩm",
    items: [
      { to: "/admin/categories", icon: "category", label: "Quản lý danh mục" },
      { to: "/admin/brands", icon: "verified", label: "Quản lý thương hiệu" },
      { to: "/admin/products", icon: "inventory_2", label: "Quản lý sản phẩm" },
    ]
  },
  {
    title: "Kinh doanh",
    items: [
      { to: "/admin/orders", icon: "shopping_cart", label: "Quản lý đơn hàng" },
      { to: "/admin/coupons", icon: "sell", label: "Mã giảm giá" },
    ]
  },
  {
    title: "Người dùng",
    items: [
      { to: "/admin/users", icon: "people", label: "Quản lý người dùng" },
      { to: "/admin/reviews", icon: "rate_review", label: "Quản lý đánh giá" },
    ]
  },
  {
    title: "Marketing",
    items: [
      { to: "/admin/banners", icon: "image", label: "Quản lý banner" },
    ]
  },
];
</script>