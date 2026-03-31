import api from "../../api";

export default {
  // Get reviews for a product
  listByProduct(productId, params = {}) {
    return api.get(`/api/v1/products/${productId}/reviews`, { params });
  },

  // Get stats for a product
  getProductStats(productId) {
    return api.get(`/api/v1/products/${productId}/reviews/stats`);
  },

  // Create a new review
  create(data) {
    return api.post("/api/v1/reviews", data);
  },

  // Get a specific review
  show(reviewId) {
    return api.get(`/api/v1/reviews/${reviewId}`);
  },

  // Update a review
  update(reviewId, data) {
    return api.patch(`/api/v1/reviews/${reviewId}`, data);
  },

  // Delete a review
  delete(reviewId) {
    return api.delete(`/api/v1/reviews/${reviewId}`);
  },

  // Get current user's reviews
  myReviews(params = {}) {
    return api.get("/api/v1/reviews/my", { params });
  },
};
