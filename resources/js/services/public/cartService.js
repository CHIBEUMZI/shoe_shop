import api from "../../api";

export default {
  getCart() {
    return api.get("/api/v1/cart");
  },
  addItem(payload) {
    // payload: { product_id, product_variant_id?, quantity? }
    return api.post("/api/v1/cart/items", payload);
  },

  updateItem(itemId, payload) {
    return api.patch(`/api/v1/cart/items/${itemId}`, payload);
  },

  removeItem(itemId) {
    return api.delete(`/api/v1/cart/items/${itemId}`);
  },

  clear() {
    return api.delete("/api/v1/cart/clear");
  },
};