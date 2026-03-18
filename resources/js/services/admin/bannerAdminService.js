import api from "../../api";

export default {
  list(params = {}) {
    return api.get("/api/v1/admin/banners", { params });
  },

  show(id) {
    return api.get(`/api/v1/admin/banners/${id}`);
  },

  create(payload) {
    return api.post("/api/v1/admin/banners", payload);
  },

  update(id, payload) {
    return api.put(`/api/v1/admin/banners/${id}`, payload);
  },

  delete(id) {
    return api.delete(`/api/v1/admin/banners/${id}`);
  },
};