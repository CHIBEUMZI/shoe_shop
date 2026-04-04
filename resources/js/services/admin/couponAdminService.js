import api from "../../api";

export default {
  getCoupons(params = {}) {
    return api.get("/api/v1/admin/coupons", { params });
  },

  getCoupon(id) {
    return api.get(`/api/v1/admin/coupons/${id}`);
  },

  createCoupon(data) {
    return api.post("/api/v1/admin/coupons", data);
  },

  updateCoupon(id, data) {
    return api.put(`/api/v1/admin/coupons/${id}`, data);
  },

  deleteCoupon(id) {
    return api.delete(`/api/v1/admin/coupons/${id}`);
  },

  toggleStatus(id) {
    return api.patch(`/api/v1/admin/coupons/${id}/toggle-status`);
  },
};
