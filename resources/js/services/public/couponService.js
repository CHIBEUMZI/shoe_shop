import api from "../../api";

export default {
  validateCoupon(code) {
    return api.post("/api/v1/coupons/validate", { code });
  },

  getAvailableCoupons() {
    return api.get("/api/v1/coupons/available");
  },

  claimCoupon(code) {
    return api.post("/api/v1/coupons/claim", { code });
  },

  getMyCoupons() {
    return api.get("/api/v1/coupons/my");
  },
};
