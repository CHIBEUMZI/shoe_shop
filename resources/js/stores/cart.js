import { defineStore } from "pinia";
import cartService from "../services/public/cartService";

function pickCartData(resp) {

  return resp?.data?.data ?? resp?.data ?? null;
}

export const useCartStore = defineStore("cart", {
  state: () => ({
    cart: null,
    loading: false,
    error: "",
  }),

  getters: {
    items(state) {
      return state.cart?.items ?? state.cart?.items ?? [];
    },
    summary(state) {
      return state.cart?.summary ?? null;
    },
    itemsCount(state) {
      const items = state.cart?.items ?? [];
      return items.reduce((sum, it) => sum + Number(it.quantity || 0), 0);
    },
  },

  actions: {
    async fetchCart() {
      this.loading = true;
      this.error = "";
      try {
        const res = await cartService.getCart();
        this.cart = pickCartData(res);
      } catch (e) {
        this.error = e?.response?.data?.message || "Không tải được giỏ hàng";
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async addToCart({ product_id, product_variant_id = null, quantity = 1 }) {
      this.loading = true;
      this.error = "";
      try {
        const res = await cartService.addItem({ product_id, product_variant_id, quantity });
        this.cart = pickCartData(res);
        return res?.data?.message || "Đã thêm vào giỏ";
      } catch (e) {
        this.error = e?.response?.data?.message || "Không thêm được vào giỏ";
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async updateQty(itemId, quantity) {
      this.loading = true;
      this.error = "";
      try {
        const res = await cartService.updateItem(itemId, { quantity });
        this.cart = pickCartData(res);
      } catch (e) {
        this.error = e?.response?.data?.message || "Không cập nhật số lượng";
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async removeItem(itemId) {
      this.loading = true;
      this.error = "";
      try {
        const res = await cartService.removeItem(itemId);
        this.cart = pickCartData(res);
      } catch (e) {
        this.error = e?.response?.data?.message || "Không xóa được sản phẩm";
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async clearCart() {
      this.loading = true;
      this.error = "";
      try {
        const res = await cartService.clear();
        this.cart = pickCartData(res);
      } catch (e) {
        this.error = e?.response?.data?.message || "Không xóa được giỏ hàng";
        throw e;
      } finally {
        this.loading = false;
      }
    },
  },
});