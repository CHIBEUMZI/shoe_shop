import api from "../../api";

export default {
  list(params) {
    return api.get("/api/v1/admin/users", { params });
  },
  show(id) {
    return api.get(`/api/v1/admin/users/${id}`);
  },
  update(id, payload) {
    return api.put(`/api/v1/admin/users/${id}`, payload);
  },
};