import { defineStore } from "pinia";
import categoryService from "../services/public/categoryService"; 

export const useCategoryShopStore = defineStore("categoryShop", {
  state: () => ({
    loading: false,
    items: [],
    error: "",
    loaded: false,
  }),

  getters: {
    menu(state) {
      return (state.items || []).map((c) => ({
        id: c.id,
        label: c.name,
        slug: c.slug,
        children: (c.children || []).map((ch) => ({
          id: ch.id,
          label: ch.name,
          slug: ch.slug,
        })),
      }));
    },
  },

  actions: {
    async fetchMenu(force = false) {
      if (this.loaded && !force) return;

      this.loading = true;
      this.error = "";
      try {
        const res = await categoryService.list({ with_children: 1 });
        this.items = res?.data?.data ?? [];
        this.loaded = true;
      } catch (e) {
        this.error = e?.response?.data?.message || e?.message || "Không tải được danh mục";
      } finally {
        this.loading = false;
      }
    },
  },
});