import api from "../../api"; 

export default {
  list(params = {}) {
    return api.get("/api/v1/banners", { params });
  },

  getByPosition(position) {
    return api.get(`/api/v1/banners/position/${position}`);
  },
};