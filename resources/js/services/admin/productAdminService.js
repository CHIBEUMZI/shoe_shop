import api from "../../api"; 

export default {
  index(params) {
    return api.get("/api/v1/admin/products", { params });
  },
  show(id) {
    return api.get(`/api/v1/admin/products/${id}`);
  },
  create(payload) {
    return api.post("/api/v1/admin/products", payload);
  },
  update(id, payload) {
    return api.put(`/api/v1/admin/products/${id}`, payload);
  },
  destroy(id) {
    return api.delete(`/api/v1/admin/products/${id}`);
  },
};