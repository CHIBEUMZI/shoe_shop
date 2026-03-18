import api from "../../api";

export default {
  list(params) {
    return api.get("/api/v1/products", { params });
  },
  show(slug) {
    return api.get(`/api/v1/products/${slug}`);
  },
};