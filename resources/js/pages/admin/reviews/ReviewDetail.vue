<template>
  <div class="page">
    <!-- Header -->
    <div class="topbar">
      <div class="topbar-left">
        <div>
          <div class="page-title">Chi tiết đánh giá</div>
          <div class="page-subtitle" v-if="review">
            {{ review.user?.name }} - Sản phẩm: {{ review.product?.name }}
          </div>
        </div>
      </div>

      <div class="topbar-actions">
        <button class="btn btn-ghost" type="button" @click="goBack">
          Quay lại
        </button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="alert">
      <div>
        <div class="alert-title">Lỗi</div>
        <div class="alert-body">{{ error }}</div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading">Đang tải...</div>

    <!-- Content -->
    <div v-else class="wrap">
      <div v-if="review" class="layout two-col">
        <!-- LEFT -->
        <div class="left">
          <!-- Reviewer Info Section -->
          <div class="section">
            <div class="section-head">
              <div class="section-icon">👤</div>
              <div class="section-title">Thông tin người đánh giá</div>
            </div>

            <div class="section-body">
              <div class="reviewer-card">
                <div class="reviewer-header">
                  <div>
                    <img
                      v-if="review.user?.avatar"
                      :src="buildImageUrl(review.user.avatar)"
                      :alt="review.user.name"
                      class="reviewer-avatar"
                    />
                    <div v-else class="reviewer-avatar-placeholder">
                      {{ review.user?.name?.charAt(0) || '?' }}
                    </div>
                  </div>
                  <div class="reviewer-info">
                    <div class="reviewer-name">{{ review.user?.name }}</div>
                    <div class="reviewer-email">{{ review.user?.email }}</div>
                  </div>
                </div>
                <div class="reviewer-meta">
                  <span class="meta-text">{{ formatDate(review.created_at) }}</span>
                  <span v-if="review.verified_purchase" class="verified-badge">
                    ✓ Xác thực mua hàng
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Review Content Section -->
          <div class="section">
            <div class="section-head">
              <div class="section-icon">⭐</div>
              <div class="section-title">Nội dung đánh giá</div>
            </div>

            <div class="section-body">
              <div class="review-rating">
                <div class="rating-label">Đánh giá</div>
                <div class="stars">
                  <span v-for="i in 5" :key="i" class="star" :class="i <= review.rating ? 'filled' : 'empty'">★</span>
                </div>
              </div>

              <div v-if="review.comment" class="review-comment">
                <div class="comment-label">Nhận xét</div>
                <div class="comment-box">
                  {{ review.comment }}
                </div>
              </div>
            </div>
          </div>

          <!-- Product Info Section -->
          <div class="section">
            <div class="section-head">
              <div class="section-icon">📦</div>
              <div class="section-title">Sản phẩm được đánh giá</div>
            </div>

            <div class="section-body">
              <div class="product-card">
                <div>
                  <img
                    v-if="review.product?.thumbnail"
                    :src="buildImageUrl(review.product.thumbnail)"
                    :alt="review.product.name"
                    class="product-image"
                  />
                  <div v-else class="product-image-placeholder">
                    <span class="material-symbols-outlined">image</span>
                  </div>
                </div>
                <div class="product-info">
                  <div class="product-name">{{ review.product?.name }}</div>
                  <div class="product-sku">{{ review.product?.sku }}</div>
                  <div class="product-price">{{ moneyVND(review.product?.base_price) }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- System Info Section -->
          <div class="section" v-if="review.created_at || review.updated_at">
            <div class="section-head">
              <div class="section-icon">🕒</div>
              <div class="section-title">Thông tin hệ thống</div>
            </div>

            <div class="section-body">
              <div class="detail-grid">
                <div class="detail-item" v-if="review.created_at">
                  <div class="detail-label">Ngày tạo</div>
                  <div class="detail-value">{{ formatFullDate(review.created_at) }}</div>
                </div>

                <div class="detail-item" v-if="review.updated_at">
                  <div class="detail-label">Cập nhật lần cuối</div>
                  <div class="detail-value">{{ formatFullDate(review.updated_at) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="right">
          <!-- Status Card -->
          <div class="section">
            <div class="section-head">
              <div class="section-icon">🔔</div>
              <div class="section-title">Trạng thái</div>
            </div>

            <div class="section-body">
              <div class="status-box">
                <span class="pill" :class="statusClass(review.status)">
                  {{ statusLabel(review.status) }}
                </span>
              </div>

              <div class="button-group">
                <button
                  v-if="review.status !== 'approved'"
                  @click="approveReview"
                  :disabled="actionLoading"
                  class="btn btn-approve"
                >
                  <span class="material-symbols-outlined">check_circle</span>
                  Duyệt
                </button>

                <button
                  v-if="review.status !== 'rejected'"
                  @click="rejectReview"
                  :disabled="actionLoading"
                  class="btn btn-reject"
                >
                  <span class="material-symbols-outlined">cancel</span>
                  Từ chối
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="section">
        <div class="section-body empty-state">
          Không có dữ liệu đánh giá.
        </div>
      </div>
    </div>

    <!-- Alerts -->
    <AlertContainer />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import AlertContainer from '../../../components/AlertContainer.vue'
import { useAlert } from '../../../composables/useAlert'
import reviewService from '../../../services/admin/reviewService'
import { buildImageUrl } from '../../../utils/image'

const router = useRouter()
const route = useRoute()
const alert = useAlert()

const reviewId = route.params.id
const review = ref(null)
const loading = ref(false)
const actionLoading = ref(false)
const error = ref(null)

const statusLabel = (status) => {
  const labels = {
    approved: 'Đã duyệt',
    rejected: 'Bị từ chối',
    pending: 'Chờ duyệt',
  }
  return labels[status] || status
}

const statusClass = (status) => {
  const classes = {
    approved: 'ok',
    rejected: 'off',
    pending: 'pending',
  }
  return classes[status] || 'pending'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatFullDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

const moneyVND = (v) => {
  const n = Number(v || 0)
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
  }).format(n)
}

const fetchReview = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await reviewService.show(reviewId)
    review.value = response.data.data || response.data
  } catch (err) {
    console.error('Fetch review error:', err.response?.status, err.response?.data)
    error.value = err.response?.data?.message || 'Không thể tải chi tiết đánh giá'
  } finally {
    loading.value = false
  }
}

const approveReview = async () => {
  if (actionLoading.value) return
  
  actionLoading.value = true
  try {
    console.log('Approving review:', reviewId)
    await reviewService.approve(reviewId)
    alert.success('Đánh giá đã được duyệt')
    setTimeout(() => {
      router.push('/admin/reviews')
    }, 1500)
  } catch (err) {
    console.error('Approve error:', err.response?.status, err.response?.data)
    alert.error(err.response?.data?.message || 'Lỗi phê duyệt')
  } finally {
    actionLoading.value = false
  }
}

const rejectReview = async () => {
  if (actionLoading.value) return
  
  actionLoading.value = true
  try {
    console.log('Rejecting review:', reviewId)
    await reviewService.reject(reviewId)
    alert.success('Đánh giá đã bị từ chối')
    setTimeout(() => {
      router.push('/admin/reviews')
    }, 1500)
  } catch (err) {
    console.error('Reject error:', err.response?.status, err.response?.data)
    alert.error(err.response?.data?.message || 'Lỗi từ chối')
  } finally {
    actionLoading.value = false
  }
}

const goBack = () => {
  router.push('/admin/reviews')
}

onMounted(fetchReview)
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap");

* {
  box-sizing: border-box;
}

.page {
  min-height: 100vh;
  padding: 24px 28px 40px;
  color: #0f172a;
  font-family: "DM Sans", sans-serif;
}

.wrap {
  max-width: 1360px;
  margin: 0 auto;
}

/* ── Topbar ── */
.topbar {
  max-width: 1360px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 14px;
  padding: 16px 20px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.topbar-left::before {
  content: "";
  display: block;
  width: 4px;
  height: 36px;
  background: linear-gradient(180deg, #3b82f6, #6366f1);
  border-radius: 99px;
  flex-shrink: 0;
}

.page-title {
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: #0d1117;
  line-height: 1.2;
}

.page-subtitle {
  margin-top: 3px;
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* ── Alert ── */
.alert {
  max-width: 1360px;
  margin: 0 auto 16px;
  border: 1px solid rgba(239, 68, 68, 0.2);
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.07), rgba(239, 68, 68, 0.03));
  border-radius: 12px;
  padding: 12px 16px;
  color: #991b1b;
  display: flex;
  gap: 10px;
  align-items: flex-start;
}

.alert::before {
  content: "⚠";
  font-size: 15px;
  flex-shrink: 0;
  margin-top: 1px;
}

.alert-title {
  font-weight: 700;
  font-size: 13px;
}

.alert-body {
  margin-top: 2px;
  font-size: 13px;
  color: #b91c1c;
}

/* ── Loading ── */
.loading {
  max-width: 1360px;
  margin: 0 auto;
  padding: 40px 0;
  color: #94a3b8;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
}

.loading::before {
  content: "";
  width: 18px;
  height: 18px;
  border: 2px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* ── Layout ── */
.layout.two-col {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 18px;
}

.left,
.right {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

@media (max-width: 980px) {
  .layout.two-col {
    grid-template-columns: 1fr;
  }
}

/* ── Section ── */
.section {
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 16px;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
  transition: box-shadow 0.2s;
}

.section:hover {
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.07);
}

.section-head {
  padding: 16px 20px 14px;
  border-bottom: 1px solid #f1f4fa;
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-icon {
  width: 34px;
  height: 34px;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border-radius: 10px;
  display: grid;
  place-items: center;
  font-size: 15px;
  flex-shrink: 0;
}

.section-title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.01em;
}

.section-body {
  padding: 18px 20px 20px;
}

/* ── Detail Grid ── */
.detail-grid {
  display: grid;
  gap: 16px 20px;
  grid-template-columns: 1fr 1fr;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.detail-label {
  font-size: 12.5px;
  font-weight: 600;
  color: #374151;
  letter-spacing: 0.01em;
}

.detail-value {
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 13px;
  outline: none;
  background: #fafbfd;
  color: #0f172a;
  font-family: "DM Sans", sans-serif;
  font-size: 14px;
  font-weight: 600;
  min-height: 44px;
  display: flex;
  align-items: center;
  word-break: break-word;
}

.mono {
  font-family: "DM Mono", monospace;
  font-size: 12.5px;
}

@media (max-width: 980px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}

/* ── Reviewer Card ── */
.reviewer-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.reviewer-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.reviewer-avatar {
  width: 52px;
  height: 52px;
  border-radius: 999px;
  object-fit: cover;
  flex-shrink: 0;
  border: 2px solid #e2e8f0;
}

.reviewer-avatar-placeholder {
  width: 52px;
  height: 52px;
  border-radius: 999px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  display: grid;
  place-items: center;
  color: white;
  font-weight: 700;
  font-size: 18px;
  flex-shrink: 0;
  border: 2px solid #e2e8f0;
}

.reviewer-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.reviewer-name {
  font-weight: 700;
  color: #0f172a;
  font-size: 14px;
}

.reviewer-email {
  font-size: 12.5px;
  color: #64748b;
}

.reviewer-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.meta-text {
  font-size: 12px;
  color: #64748b;
}

.verified-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
}

/* ── Review Rating ── */
.review-rating {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.rating-label {
  font-size: 12.5px;
  font-weight: 600;
  color: #374151;
  letter-spacing: 0.01em;
}

.stars {
  display: flex;
  gap: 6px;
}

.star {
  font-size: 28px;
  display: block;
}

.star.filled {
  color: #fbbf24;
}

.star.empty {
  color: #e5e7eb;
}

/* ── Review Comment ── */
.review-comment {
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.comment-label {
  font-size: 12.5px;
  font-weight: 600;
  color: #374151;
  letter-spacing: 0.01em;
}

.comment-box {
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px 14px;
  background: #fafbfd;
  color: #1f2937;
  font-size: 14px;
  line-height: 1.6;
  white-space: pre-wrap;
  word-break: break-word;
}

/* ── Product Card ── */
.product-card {
  display: flex;
  align-items: center;
  gap: 14px;
}

.product-image {
  width: 64px;
  height: 64px;
  border-radius: 10px;
  object-fit: cover;
  border: 1px solid #e2e8f0;
  flex-shrink: 0;
}

.product-image-placeholder {
  width: 64px;
  height: 64px;
  border-radius: 10px;
  background: #f1f5f9;
  border: 1.5px solid #e2e8f0;
  display: grid;
  place-items: center;
  color: #94a3b8;
  flex-shrink: 0;
}

.product-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.product-name {
  font-weight: 700;
  color: #0f172a;
  font-size: 14px;
}

.product-sku {
  font-size: 12px;
  color: #64748b;
  font-family: "DM Mono", monospace;
}

.product-price {
  font-weight: 700;
  color: #0f172a;
  font-size: 14px;
}

/* ── Status Box ── */
.status-box {
  margin-bottom: 14px;
}

.button-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* ── Pill ── */
.pill {
  display: inline-flex;
  padding: 6px 14px;
  border-radius: 999px;
  font-weight: 700;
  font-size: 13px;
  border: 1px solid transparent;
}

.pill.ok {
  background: #ecfdf5;
  color: #047857;
  border-color: #a7f3d0;
}

.pill.off {
  background: #f1f5f9;
  color: #475569;
  border-color: #e2e8f0;
}

.pill.pending {
  background: #fef3c7;
  color: #b45309;
  border-color: #fcd34d;
}

/* ── Buttons ── */
.btn {
  border-radius: 10px;
  padding: 10px 16px;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.15s;
  font-family: "DM Sans", sans-serif;
}

.btn-ghost {
  background: #fff;
  border: 1.5px solid #e2e8f0;
  color: #475569;
}

.btn-ghost:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #334155;
}

.btn-approve {
  width: 100%;
  background: #10b981;
  color: white;
  border: none;
}

.btn-approve:hover:not(:disabled) {
  background: #059669;
}

.btn-approve:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-reject {
  width: 100%;
  background: #ef4444;
  color: white;
  border: none;
}

.btn-reject:hover:not(:disabled) {
  background: #dc2626;
}

.btn-reject:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ── Empty State ── */
.empty-state {
  color: #64748b;
  font-weight: 600;
  text-align: center;
  padding: 40px 20px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 980px) {
  .topbar {
    flex-direction: column;
    align-items: flex-start;
  }

  .topbar-actions {
    width: 100%;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
