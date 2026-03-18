import api from "../../api";

export default {
  list(params) {
    return api.get("/api/v1/admin/brands", { params });
  },
  show(id) {
    return api.get(`/api/v1/admin/brands/${id}`);
  },
  create(payload) {
    return api.post("/api/v1/admin/brands", payload);
  },
  update(id, payload) {
    return api.put(`/api/v1/admin/brands/${id}`, payload);
  },
  destroy(id) {
    return api.delete(`/api/v1/admin/brands/${id}`);
  },
};