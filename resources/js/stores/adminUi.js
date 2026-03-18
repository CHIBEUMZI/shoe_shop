import { defineStore } from "pinia";

export const useAdminUiStore = defineStore("adminUi", {
  state: () => ({
    collapsed: false,
  }),
  actions: {
    toggleSidebar() {
      this.collapsed = !this.collapsed;
    },
    setCollapsed(value) {
      this.collapsed = !!value;
    },
  },
});