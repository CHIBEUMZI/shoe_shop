import api from "../../api";

export default {
  list(params) {
    return api.get("/api/v1/admin/orders", { params });
  },
  show(id) {
    return api.get(`/api/v1/admin/orders/${id}`);
  },
  updateStatus(id, payload) {
    return api.patch(`/api/v1/admin/orders/${id}/status`, payload);
  },
  confirmCancellation(id, payload) {
    return api.patch(`/api/v1/admin/orders/${id}/confirm-cancellation`, payload);
  },
  rejectCancellation(id) {
    return api.patch(`/api/v1/admin/orders/${id}/reject-cancellation`);
  },
};