import api from "../../api";

export default {
  createOrder(payload) {
    return api.post("/api/v1/orders", payload);
  },

  getMyOrders(params) {
    return api.get("/api/v1/orders", { params });
  },

  getMyOrderDetail(id) {
    return api.get(`/api/v1/orders/${id}`);
  },

  createPayment(orderId) {
    return api.post(`/api/v1/orders/${orderId}/payment`);
  },

  requestCancellation(orderId, reason) {
    return api.post(`/api/v1/orders/${orderId}/cancellation`, { reason });
  },
};