<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import productAdminService from "../../../services/admin/productAdminService";

const route = useRoute();
const router = useRouter();
const id = computed(() => route.params.id);

const loading = ref(false);
const error = ref("");
const product = ref(null);

function imgSrc(url) {
  if (!url) return "";
  return url.startsWith("http") ? url : "/storage/" + url;
}

async function fetchDetail() {
  loading.value = true;
  error.value = "";
  try {
    const { data } = await productAdminService.show(id.value);
    product.value = data?.data ?? data;
  } catch (e) {
    error.value = e?.response?.data?.message || "Không tải được dữ liệu";
  } finally {
    loading.value = false;
  }
}

onMounted(fetchDetail);

function goBack() {
  router.push("/admin/products");
}

const statusText = computed(() =>
  Number(product.value?.status) === 1 ? "Hoạt động" : "Tạm tắt"
);

const statusClass = computed(() =>
  Number(product.value?.status) === 1 ? "ok" : "off"
);

function moneyVND(v) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(Number(v || 0));
}

const basicInfoItems = computed(() => {
  if (!product.value) return [];
  return [
    { label: "Tên sản phẩm", value: product.value.name || "-" },
    { label: "Slug", value: product.value.slug || "-", mono: true },
    { label: "SKU", value: product.value.sku || "-", mono: true },
    { label: "Trạng thái", value: statusText.value, status: true },
    { label: "Nổi bật", value: product.value.is_featured ? "Có" : "Không" },
    { label: "Thương hiệu", value: product.value.brand?.name || "-" },
    {
      label: "Giá gốc",
      value: moneyVND(product.value.base_price),
    },
    {
      label: "Giá khuyến mãi",
      value:
        product.value.base_sale_price !== null &&
        product.value.base_sale_price !== undefined
          ? moneyVND(product.value.base_sale_price)
          : "-",
    },
    {
      label: "Mô tả ngắn",
      value: product.value.short_description || "Không có mô tả ngắn",
      full: true,
      pre: true,
    },
    {
      label: "Mô tả chi tiết",
      value: product.value.description || "Không có mô tả",
      full: true,
      pre: true,
    },
  ];
});
</script>

<template>
  <div class="page">
    <!-- header -->
    <div class="topbar">
      <div class="topbar-left">
        <div>
          <div class="page-title">Chi tiết sản phẩm</div>
          <div class="page-subtitle" v-if="product">
            #{{ product.id }} • {{ product.name }}
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
      <div v-if="product" class="layout two-col">
        <!-- LEFT -->
        <div class="left">
          <div class="section">
            <div class="section-head">
              <div class="section-icon">📦</div>
              <div class="section-title">Thông tin sản phẩm</div>
            </div>

            <div class="section-body">
              <div class="detail-grid">
                <div
                  v-for="(item, idx) in basicInfoItems"
                  :key="idx"
                  class="detail-item"
                  :class="item.full ? 'full' : ''"
                >
                  <div class="detail-label">{{ item.label }}</div>

                  <div v-if="item.status" class="detail-value">
                    <span class="pill" :class="statusClass">
                      {{ item.value }}
                    </span>
                  </div>

                  <div
                    v-else
                    class="detail-value"
                    :class="[item.mono ? 'mono' : '', item.pre ? 'pre' : '']"
                  >
                    {{ item.value }}
                  </div>
                </div>

                <div class="detail-item full">
                  <div class="detail-label">Danh mục</div>
                  <div class="detail-value chips-wrap">
                    <template v-if="(product.categories || []).length">
                      <span
                        class="chip"
                        v-for="c in product.categories || []"
                        :key="c.id"
                      >
                        {{ c.name }}
                      </span>
                    </template>
                    <template v-else>-</template>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- variants -->
          <div class="section">
            <div class="section-head">
              <div class="section-icon">🧩</div>
              <div class="section-title">Biến thể sản phẩm</div>
            </div>

            <div class="section-body">
              <div v-if="!(product.variants || []).length" class="empty-state">
                Chưa có biến thể nào.
              </div>

              <div v-else class="variants">
                <div
                  v-for="v in product.variants || []"
                  :key="v.id"
                  class="variant-card"
                >
                  <div class="variant-top">
                    <div>
                      <div class="variant-title">
                        {{ v.color || "-" }} / {{ v.size || "-" }}
                      </div>
                      <div class="variant-sub mono">
                        {{ v.sku || "-" }}
                      </div>
                    </div>

                    <span class="pill" :class="v.is_active ? 'ok' : 'off'">
                      {{ v.is_active ? "Hoạt động" : "Tạm tắt" }}
                    </span>
                  </div>

                  <div class="variant-meta">
                    <div class="meta-box">
                      <div class="meta-label">Giá</div>
                      <div class="meta-value">{{ moneyVND(v.price) }}</div>
                    </div>

                    <div class="meta-box">
                      <div class="meta-label">Giá sale</div>
                      <div class="meta-value">
                        {{
                          v.sale_price !== null && v.sale_price !== undefined
                            ? moneyVND(v.sale_price)
                            : "-"
                        }}
                      </div>
                    </div>

                    <div class="meta-box">
                      <div class="meta-label">Tồn kho</div>
                      <div class="meta-value">{{ v.stock ?? 0 }}</div>
                    </div>
                  </div>

                  <div v-if="(v.images || []).length" class="variant-images">
                    <img
                      v-for="img in v.images || []"
                      :key="img.id || img.url"
                      :src="imgSrc(img.url)"
                      :alt="`${product.name} variant`"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- metadata -->
          <div class="section" v-if="product.created_at || product.updated_at">
            <div class="section-head">
              <div class="section-icon">🕒</div>
              <div class="section-title">Thông tin hệ thống</div>
            </div>

            <div class="section-body">
              <div class="detail-grid">
                <div class="detail-item">
                  <div class="detail-label">ID</div>
                  <div class="detail-value mono">#{{ product.id }}</div>
                </div>

                <div class="detail-item" v-if="product.created_at">
                  <div class="detail-label">Ngày tạo</div>
                  <div class="detail-value">{{ product.created_at }}</div>
                </div>

                <div class="detail-item" v-if="product.updated_at">
                  <div class="detail-label">Cập nhật lần cuối</div>
                  <div class="detail-value">{{ product.updated_at }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="right">
          <div class="section">
            <div class="section-head">
              <div class="section-icon">🖼️</div>
              <div class="section-title">Ảnh đại diện</div>
            </div>

            <div class="section-body">
              <div v-if="product.thumbnail" class="thumb-box">
                <img :src="imgSrc(product.thumbnail)" :alt="product.name" />
              </div>
              <div v-else class="empty-thumb">
                Không có ảnh đại diện
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="section">
        <div class="section-body empty-state">
          Không có dữ liệu sản phẩm.
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
  border: 1.5px solid #dbe3ee;
  border-radius: 10px;
  padding: 11px 14px;
  background: #ffffff;
  color: #0f172a;
  font-family: "DM Sans", sans-serif;
  font-size: 14px;
  font-weight: 700;
  line-height: 1.6;
  min-height: 46px;
  display: flex;
  align-items: center;
}

.detail-value.pre {
  white-space: pre-wrap;
  align-items: flex-start;
  line-height: 1.6;
}

.mono {
  font-family: "DM Sans", sans-serif;
  font-size: 12.5px;
}

.chips-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.chip {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  color: #334155;
}

@media (max-width: 980px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}

/* ── Pills ── */
.pill {
  display: inline-flex;
  align-items: center;
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

/* ── Thumb ── */
.thumb-box {
  width: 100%;
  border: 2px dashed #c7d4e8;
  border-radius: 14px;
  padding: 12px;
  background: #fafbfd;
}

.thumb-box img {
  width: 100%;
  border-radius: 12px;
  object-fit: cover;
  max-height: 320px;
  display: block;
}

.empty-thumb {
  border: 2px dashed #e2e8f0;
  border-radius: 14px;
  min-height: 220px;
  display: grid;
  place-items: center;
  color: #94a3b8;
  font-weight: 600;
  background: #fafbfd;
}

/* ── Variants ── */
.variants {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.variant-card {
  border: 1px solid #e8ecf4;
  border-radius: 14px;
  padding: 14px;
  background: #fbfdff;
}

.variant-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}

.variant-title {
  font-size: 14px;
  font-weight: 800;
  color: #0f172a;
}

.variant-sub {
  margin-top: 4px;
  color: #64748b;
}

.variant-meta {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-top: 12px;
}

.meta-box {
  border: 1px solid #e8ecf4;
  border-radius: 12px;
  background: #fff;
  padding: 10px 12px;
}

.meta-label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #94a3b8;
  font-weight: 700;
}

.meta-value {
  margin-top: 4px;
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.variant-images {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.variant-images img {
  width: 76px;
  height: 76px;
  object-fit: cover;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
}

@media (max-width: 980px) {
  .variant-meta {
    grid-template-columns: 1fr;
  }
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
  .topbar {
    flex-direction: column;
    align-items: flex-start;
  }

  .topbar-actions {
    width: 100%;
  }
}
</style>