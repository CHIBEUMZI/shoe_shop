<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import categoryAdminService from "../../../services/admin/categoryAdminService";

const route = useRoute();
const router = useRouter();
const id = computed(() => route.params.id);

const loading = ref(false);
const error = ref("");
const cat = ref(null);

async function fetchDetail() {
  loading.value = true;
  error.value = "";
  try {
    const { data } = await categoryAdminService.show(id.value);
    cat.value = data?.data ?? data;
  } catch (e) {
    error.value = e?.response?.data?.message || "Không tải được dữ liệu";
  } finally {
    loading.value = false;
  }
}

onMounted(fetchDetail);

function goBack() {
  router.push("/admin/categories");
}

const statusText = computed(() =>
  Number(cat.value?.status) === 1 ? "Hoạt động" : "Tạm tắt"
);

const statusClass = computed(() =>
  Number(cat.value?.status) === 1 ? "ok" : "off"
);

const detailItems = computed(() => {
  if (!cat.value) return [];
  return [
    { label: "Tên danh mục", value: cat.value.name || "-" },
    { label: "Slug", value: cat.value.slug || "-", mono: true },
    { label: "Danh mục cha", value: cat.value.parent?.name || "-" },
    { label: "Thứ tự sắp xếp", value: cat.value.sort_order ?? "-" },
    { label: "Trạng thái", value: statusText.value, status: true },
    {
      label: "Mô tả",
      value: cat.value.description || "Không có mô tả",
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
          <div class="page-title">Chi tiết danh mục</div>
          <div class="page-subtitle" v-if="cat">
            {{ cat.name }}
          </div>
        </div>
      </div>

      <div class="topbar-actions">
        <button class="btn btn-ghost" type="button" @click="goBack">
          Quay lại
        </button>
      </div>
    </div>

    <!-- server error -->
    <div v-if="error" class="alert">
      <div>
        <div class="alert-title">Lỗi</div>
        <div class="alert-body">{{ error }}</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải...</div>

    <div v-else class="wrap">
      <div v-if="cat" class="layout one-col">
        <div class="left">
          <!-- section 1 -->
          <div class="section">
            <div class="section-head">
              <div class="section-icon">📁</div>
              <div class="section-title">Thông tin danh mục</div>
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

                  <div
                    v-if="item.status"
                    class="detail-value"
                  >
                    <span class="pill" :class="statusClass">
                      {{ item.value }}
                    </span>
                  </div>

                  <div
                    v-else
                    class="detail-value"
                    :class="[
                      item.mono ? 'mono' : '',
                      item.pre ? 'pre' : ''
                    ]"
                  >
                    {{ item.value }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- optional metadata -->
          <div class="section" v-if="cat.created_at || cat.updated_at">
            <div class="section-head">
              <div class="section-icon">🕒</div>
              <div class="section-title">Thông tin hệ thống</div>
            </div>

            <div class="section-body">
              <div class="detail-grid">
                <div class="detail-item" v-if="cat.created_at">
                  <div class="detail-label">Ngày tạo</div>
                  <div class="detail-value">{{ cat.created_at }}</div>
                </div>

                <div class="detail-item" v-if="cat.updated_at">
                  <div class="detail-label">Cập nhật lần cuối</div>
                  <div class="detail-value">{{ cat.updated_at }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="section">
        <div class="section-body empty-state">
          Không có dữ liệu danh mục.
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
.layout.one-col {
  display: block;
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
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 13px;
  background: #fafbfd;
  color: #0f172a;
  font-size: 14px;
  font-weight: 600;
  min-height: 44px;
  display: flex;
  align-items: center;
}

.detail-value.mono,
.mono {
  font-family: "DM Sans", sans-serif;
  font-size: 12.5px;
}

.detail-value.pre,
.pre {
  white-space: pre-wrap;
  align-items: flex-start;
  line-height: 1.6;
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