import api from "../../api";

const categoryAdminService = {
  list(params) {
    return api.get("/api/v1/admin/categories", { params });
  },
  show(id) {
    return api.get(`/api/v1/admin/categories/${id}`);
  },
  create(payload) {
    return api.post("/api/v1/admin/categories", payload);
  },
  update(id, payload) {
    return api.put(`/api/v1/admin/categories/${id}`, payload);
  },
  remove(id) {
    return api.delete(`/api/v1/admin/categories/${id}`);
  },
};

export default categoryAdminService;
