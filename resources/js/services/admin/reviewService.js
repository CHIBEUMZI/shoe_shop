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

  // Approve a review
  approve(reviewId) {
    return api.patch(`/api/v1/admin/reviews/${reviewId}/approve`);
  },

  // Reject a review
  reject(reviewId) {
    return api.patch(`/api/v1/admin/reviews/${reviewId}/reject`);
  },

  // Delete a review
  delete(reviewId) {
    return api.delete(`/api/v1/admin/reviews/${reviewId}`);
  },

  // Bulk action on reviews
//   bulkAction(data) {
//     return api.post("/api/v1/admin/reviews/bulk-action", data);
//   },

  // Get review statistics
  getStats() {
    return api.get("/api/v1/admin/reviews/stats");
  },
};
