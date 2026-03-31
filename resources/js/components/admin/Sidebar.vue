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
    <nav class="flex-1 px-3 space-y-1 mt-2">
      <router-link
        v-for="item in menus"
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
    </nav>
  </aside>
</template>

<script setup>
import { useAdminUiStore } from "@/stores/adminUi";

const ui = useAdminUiStore();

const menus = [
  { to: "/admin/dashboard", icon: "dashboard", label: "Bảng điều khiển" },
  { to: "/admin/categories", icon: "category", label: "Quản lý danh mục" },
  { to: "/admin/brands", icon: "verified", label: "Quản lý thương hiệu" },
  { to: "/admin/products", icon: "inventory_2", label: "Quản lý sản phẩm" },
  { to: "/admin/orders", icon: "shopping_cart", label: "Quản lý đơn hàng" },
  // { to: "/admin/settings", icon: "settings", label: "Settings" },
  { to: "/admin/users", icon: "people", label: "Quản lý người dùng" },
  { to: "/admin/reviews", icon: "rate_review", label: "Quản lý đánh giá" },
  { to: "/admin/banners", icon: "image", label: "Quản lý banner" },
  
];
</script>