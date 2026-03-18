import api from "../../api";

export default {
  overview(params = {}) {
    return api.get("/api/v1/admin/dashboard", { params });
  },
}