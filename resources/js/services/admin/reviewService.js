import api from "../../api";

export default {
  // Get all reviews with filters
  list(params = {}) {
    return api.get("/api/v1/admin/reviews", { params });
  },

  // Get a specific review
  show(reviewId) {
    return api.get(`/api/v1/admin/reviews/${reviewId}`);
  },

  // Reply to a review
  reply(reviewId, data) {
    return api.post(`/api/v1/admin/reviews/${reviewId}/reply`, data);
  },

  // Delete a review
  delete(reviewId) {
    return api.delete(`/api/v1/admin/reviews/${reviewId}`);
  },

  // Get review statistics
  getStats() {
    return api.get("/api/v1/admin/reviews/stats");
  },
};
