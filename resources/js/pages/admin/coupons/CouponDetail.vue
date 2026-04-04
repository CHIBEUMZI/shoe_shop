<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import couponAdminService from "../../../services/admin/couponAdminService";
import productAdminService from "../../../services/admin/productAdminService";
import categoryAdminService from "../../../services/admin/categoryAdminService";
import brandAdminService from "../../../services/admin/brandAdminService";
import { useAlert } from "../../../composables/useAlert";

const route = useRoute();
const router = useRouter();
const notify = useAlert();

const loading = ref(false);
const error = ref("");
const coupon = ref(null);

const products = ref([]);
const categories = ref([]);
const brands = ref([]);

function formatMoney(value) {
  if (!value) return "-";
  return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(value);
}

async function fetchCoupon() {
  loading.value = true;
  error.value = "";

  try {
    const couponRes = await couponAdminService.getCoupon(route.params.id);
    coupon.value = couponRes?.data?.data;

    const promises = [];

    promises.push(
      productAdminService.index({ per_page: 1000 }).then((r) => {
        products.value = r?.data?.data || [];
      }).catch(() => {})
    );

    promises.push(
      categoryAdminService.list({ per_page: 1000 }).then((r) => {
        categories.value = r?.data?.data || [];
      }).catch(() => {})
    );

    promises.push(
      brandAdminService.list({ per_page: 1000 }).then((r) => {
        brands.value = r?.data?.data || [];
      }).catch(() => {})
    );

    await Promise.all(promises);
  } catch (e) {
    error.value = e?.response?.data?.message || "Không tải được dữ liệu";
  } finally {
    loading.value = false;
  }
}

onMounted(fetchCoupon);

function goBack() {
  router.push("/admin/coupons");
}

async function copyCode() {
  try {
    await navigator.clipboard.writeText(coupon.value.code);
    notify.success("Đã sao chép mã giảm giá.", { title: "Thành công", duration: 2000 });
  } catch {
    notify.error("Không thể sao chép mã.", { title: "Lỗi", duration: 2000 });
  }
}

const statusText = computed(() => {
  if (!coupon.value) return "-";
  if (!coupon.value.is_active) return "Vô hiệu hoá";
  if (coupon.value.status) return coupon.value.status;
  return "Hoạt động";
});

const statusClass = computed(() => {
  if (!coupon.value) return "off";
  if (!coupon.value.is_active) return "off";
  if (coupon.value.status === "Đã hết hạn" || coupon.value.status === "Đã dùng hết") return "error";
  if (coupon.value.status === "Chưa bắt đầu") return "warning";
  return "ok";
});

const typeClass = computed(() => {
  if (!coupon.value) return "info";
  return coupon.value.type === "percentage" ? "info" : "success";
});

const detailItems = computed(() => {
  if (!coupon.value) return [];
  return [
    { label: "Mã giảm giá", value: coupon.value.code, code: true },
    { label: "Tên mã giảm giá", value: coupon.value.name || "-" },
    { label: "Loại giảm giá", value: coupon.value.type === "percentage" ? "Phần trăm (%)" : "Số tiền cố định", badge: true },
    { label: "Giá trị giảm", value: coupon.value.value_formatted || formatMoney(coupon.value.value), highlight: true },
  ];
});

const discountItems = computed(() => {
  if (!coupon.value) return [];
  const items = [];
  
  if (coupon.value.max_discount) {
    items.push({ label: "Giảm tối đa", value: coupon.value.max_discount_formatted || formatMoney(coupon.value.max_discount) });
  }
  
  if (coupon.value.min_order_amount) {
    items.push({ label: "Đơn hàng tối thiểu", value: formatMoney(coupon.value.min_order_amount) });
  }
  
  items.push({ label: "Trạng thái", value: statusText.value, status: true });
  
  return items;
});

const usageItems = computed(() => {
  if (!coupon.value) return [];
  return [
    { label: "Đã sử dụng", value: `${coupon.value.used_count || 0} / ${coupon.value.usage_limit || "∞"} lần`, progress: true },
    { label: "Mỗi người dùng", value: `${coupon.value.per_user_limit || 1} lần` },
  ];
});

const scheduleItems = computed(() => {
  if (!coupon.value) return [];
  return [
    { label: "Ngày bắt đầu", value: coupon.value.starts_at || "Không giới hạn" },
    { label: "Ngày hết hạn", value: coupon.value.expires_at || "Không giới hạn" },
  ];
});

const applicableItems = computed(() => {
  if (!coupon.value) return [];
  const items = [];

  items.push({ label: "Phạm vi", value: coupon.value.applicable_to_label || "-" });

  let ids = coupon.value.applicable_ids;
  if (typeof ids === "string") {
    try {
      ids = JSON.parse(ids);
    } catch {
      ids = [];
    }
  }

  if (Array.isArray(ids) && ids.length > 0) {
    const names = ids.map((id) => getNameById(id));
    items.push({ label: "Danh sách áp dụng", value: names.join(", "), full: true });
  }

  return items;
});

function getNameById(id) {
  const numId = Number(id);
  
  if (coupon.value?.applicable_to === "specific_products") {
    const product = products.value.find((p) => p.id === numId);
    return product?.name || `#${id}`;
  }
  
  if (coupon.value?.applicable_to === "specific_categories") {
    const category = categories.value.find((c) => c.id === numId);
    return category?.name || `#${id}`;
  }
  
  if (coupon.value?.applicable_to === "specific_brands") {
    const brand = brands.value.find((b) => b.id === numId);
    return brand?.name || `#${id}`;
  }
  
  return `#${id}`;
}

const usagePercent = computed(() => {
  if (!coupon.value?.usage_limit) return 0;
  return Math.min(100, (coupon.value.used_count / coupon.value.usage_limit) * 100);
});
</script>

<template>
  <div class="page">
    <!-- header -->
    <div class="topbar">
      <div class="topbar-left">
        <div>
          <div class="page-title">Chi tiết mã giảm giá</div>
          <div class="page-subtitle" v-if="coupon">
            {{ coupon.code }}
          </div>
        </div>
      </div>

      <div class="topbar-actions">
        <button class="btn btn-ghost" type="button" @click="goBack">
          Quay lại
        </button>
      </div>
    </div>

    <!-- error -->
    <div v-if="error" class="alert">
      <div>
        <div class="alert-title">Lỗi</div>
        <div class="alert-body">{{ error }}</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải...</div>

    <div v-else class="wrap">
      <div v-if="coupon" class="layout">
        <!-- LEFT -->
        <div class="left">
          <!-- Thông tin cơ bản -->
          <div class="section">
            <div class="section-head">
              <div class="section-icon">📋</div>
              <div class="section-title">Thông tin cơ bản</div>
            </div>

            <div class="section-body">
              <div class="detail-grid">
                <div
                  v-for="(item, idx) in detailItems"
                  :key="idx"
                  class="detail-item"
                  :class="item.full ? 'full' : ''"
                >
                  <div class="detail-label">{{ item.label }}</div>

                  <div v-if="item.code" class="detail-value code-value">
                    <span class="code-text">{{ item.value }}</span>
                    <button type="button" class="copy-btn" @click="copyCode" title="Sao chép">
                      📋
                    </button>
                  </div>

                  <div v-else-if="item.highlight" class="detail-value highlight">
                    {{ item.value }}
                  </div>

                  <div v-else-if="item.badge" class="detail-value">
                    <span class="pill" :class="typeClass">
                      {{ item.value }}
                    </span>
                  </div>

                  <div v-else class="detail-value">
                    {{ item.value }}
                  </div>
                </div>

                <!-- Mô tả -->
                <div class="detail-item full" v-if="coupon.description">
                  <div class="detail-label">Mô tả</div>
                  <div class="detail-value">
                    {{ coupon.description }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Giá trị giảm giá -->
          <div class="section" v-if="discountItems.length">
            <div class="section-head">
              <div class="section-icon">💰</div>
              <div class="section-title">Giá trị giảm giá</div>
            </div>

            <div class="section-body">
              <div class="detail-grid">
                <div
                  v-for="(item, idx) in discountItems"
                  :key="idx"
                  class="detail-item"
                >
                  <div class="detail-label">{{ item.label }}</div>

                  <div v-if="item.status" class="detail-value">
                    <span class="pill" :class="statusClass">
                      {{ item.value }}
                    </span>
                  </div>

                  <div v-else class="detail-value">
                    {{ item.value }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Giới hạn sử dụng -->
          <div class="section" v-if="usageItems.length">
            <div class="section-head">
              <div class="section-icon">📊</div>
              <div class="section-title">Giới hạn sử dụng</div>
            </div>

            <div class="section-body">
              <div class="detail-grid">
                <div
                  v-for="(item, idx) in usageItems"
                  :key="idx"
                  class="detail-item"
                >
                  <div class="detail-label">{{ item.label }}</div>

                  <div v-if="item.progress" class="detail-value progress-value">
                    <span class="progress-text">{{ item.value }}</span>
                    <div class="progress-bar">
                      <div
                        class="progress-fill"
                        :style="{ width: usagePercent + '%' }"
                      ></div>
                    </div>
                  </div>

                  <div v-else class="detail-value">
                    {{ item.value }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Thời gian hiệu lực -->
          <div class="section" v-if="scheduleItems.length">
            <div class="section-head">
              <div class="section-icon">🗓️</div>
              <div class="section-title">Thời gian hiệu lực</div>
            </div>

            <div class="section-body">
              <div class="detail-grid">
                <div
                  v-for="(item, idx) in scheduleItems"
                  :key="idx"
                  class="detail-item"
                >
                  <div class="detail-label">{{ item.label }}</div>
                  <div class="detail-value">{{ item.value }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Áp dụng cho -->
          <div class="section" v-if="applicableItems.length">
            <div class="section-head">
              <div class="section-icon">🎯</div>
              <div class="section-title">Áp dụng cho</div>
            </div>

            <div class="section-body">
              <div class="detail-grid">
                <div
                  v-for="(item, idx) in applicableItems"
                  :key="idx"
                  class="detail-item"
                  :class="item.full ? 'full' : ''"
                >
                  <div class="detail-label">{{ item.label }}</div>
                  <div class="detail-value">{{ item.value }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Thông tin hệ thống -->
          <div class="section" v-if="coupon.created_at || coupon.updated_at">
            <div class="section-head">
              <div class="section-icon">🕒</div>
              <div class="section-title">Thông tin hệ thống</div>
            </div>

            <div class="section-body">
              <div class="detail-grid">
                <div class="detail-item" v-if="coupon.created_at">
                  <div class="detail-label">Ngày tạo</div>
                  <div class="detail-value">{{ coupon.created_at }}</div>
                </div>

                <div class="detail-item" v-if="coupon.updated_at">
                  <div class="detail-label">Cập nhật lần cuối</div>
                  <div class="detail-value">{{ coupon.updated_at }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="section">
        <div class="section-body empty-state">
          Không có dữ liệu mã giảm giá.
        </div>
      </div>
    </div>
  </div>
</template>

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
.layout {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.left {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* ── Section ── */
.section {
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 16px;
  overflow: hidden;
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

.detail-item.full {
  grid-column: 1 / -1;
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
}

.detail-value.highlight {
  color: #3b82f6;
  font-size: 16px;
}

.code-value {
  background: #f0f7ff;
  border-color: #bfdbfe;
  gap: 8px;
  padding: 6px 10px;
}

.code-text {
  flex: 1;
  font-size: 15px;
  font-weight: 700;
  color: #1d4ed8;
}

.copy-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  padding: 4px;
  border-radius: 6px;
  transition: background 0.15s;
}

.copy-btn:hover {
  background: #dbeafe;
}

.progress-value {
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  background: #fafbfd;
}

.progress-text {
  font-size: 13px;
  color: #64748b;
}

.progress-bar {
  width: 100%;
  height: 6px;
  background: #e2e8f0;
  border-radius: 99px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #6366f1);
  border-radius: 99px;
  transition: width 0.3s ease;
}

/* ── Pill ── */
.pill {
  display: inline-flex;
  padding: 4px 10px;
  border-radius: 999px;
  font-weight: 800;
  font-size: 12px;
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

.pill.warning {
  background: #fffbeb;
  color: #b45309;
  border-color: #fde68a;
}

.pill.error {
  background: #fef2f2;
  color: #dc2626;
  border-color: #fecaca;
}

.pill.info {
  background: #eff6ff;
  color: #1d4ed8;
  border-color: #bfdbfe;
}

.pill.success {
  background: #ecfdf5;
  color: #047857;
  border-color: #a7f3d0;
}

/* ── Buttons ── */
.btn {
  border-radius: 10px;
  padding: 9px 18px;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  display: inline-flex;
  align-items: center;
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

.empty-state {
  color: #64748b;
  font-weight: 600;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 980px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }

  .topbar {
    flex-direction: column;
    align-items: flex-start;
  }

  .topbar-actions {
    width: 100%;
  }
}
</style>
