import { defineStore } from "pinia";
import { login, logout, me, register } from "../composables/auth";
import { updateProfile, changePassword } from "../composables/profile";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    loaded: false,
  }),

  getters: {
    isLoggedIn: (s) => !!s.user,
    isAuthenticated: (s) => !!s.user,
    isAdmin: (s) => s.user?.role === "admin",
  },

  actions: {
    async fetchMe() {
      try {
        this.user = await me();
      } catch {
        this.user = null;
      } finally {
        this.loaded = true;
      }
    },

    async login(payload) {
      this.user = await login(payload);
      this.loaded = true;
      return this.user;
    },

    async register(payload) {
      this.user = await register(payload);
      this.loaded = true;
      return this.user;
    },

    async logout() {
      await logout();
      this.user = null;
      this.loaded = true;
    },

    async saveProfile(payload) {
      this.user = await updateProfile(payload);
      return this.user;
    },

    async savePassword(payload) {
      return await changePassword(payload);
    },
  },
});
