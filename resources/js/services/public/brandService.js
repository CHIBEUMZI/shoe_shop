import api from "../../api"; 

export default {
  list(params) {
    return api.get("/api/v1/brands", { params });
  },
  show(slug) {
    return api.get(`/api/v1/brands/${slug}`);
  },
};
