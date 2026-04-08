<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import productAdminService from "../../../services/admin/productAdminService";

const route = useRoute();
const router = useRouter();
const id = computed(() => route.params.id);

const loading = ref(false);
const error = ref("");
const product = ref(null);

/** ================= helpers ================= */
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

function goEdit() {
  router.push(`/admin/products/edit/${id.value}`);
}

/** ================= display helpers ================= */
function moneyVND(v) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(Number(v || 0));
}

const COLOR_META = {
  White:  "#ffffff",
  Black:  "#1a1a1a",
  Red:    "#ef4444",
  Blue:   "#3b82f6",
  Green:  "#22c55e",
  Yellow: "#eab308",
  Grey:   "#94a3b8",
  Brown:  "#92400e",
  Cyan:   "#06b6d4",
};

const ALL_COLORS = ["White", "Black", "Red", "Blue", "Green", "Yellow", "Grey", "Brown", "Cyan"];
const ALL_SIZES  = ["36", "37", "38", "39", "40", "41", "42", "43", "44", "45"];

function matrixKey(color, size) {
  return `${color}__${size}`;
}

/** ================= build matrix checked from product ================= */
const matrixChecked = computed(() => {
  const set = new Set();
  for (const v of product.value?.variants || []) {
    if (v.color && v.size) {
      set.add(matrixKey(v.color, v.size));
    }
  }
  return set;
});

/** ================= active colors from product ================= */
const activeColors = computed(() => {
  const colors = new Set();
  for (const v of product.value?.variants || []) {
    if (v.color) colors.add(v.color);
  }
  return Array.from(colors);
});

/** ================= color images (per color) ================= */
const colorImages = computed(() => {
  const map = {};
  for (const v of product.value?.variants || []) {
    if (!v.color) continue;
    if (!map[v.color]) map[v.color] = [];
    for (const img of v.images || []) {
      if (img.url && !map[v.color].some(x => x.url === img.url)) {
        map[v.color].push(img);
      }
    }
  }
  return map;
});

/** ================= thumbnail ================= */
const thumbUrl = computed(() => {
  if (!product.value?.thumbnail) return "";
  return imgSrc(product.value.thumbnail);
});

/** ================= schema display values ================= */
const brandName = computed(() => product.value?.brand?.name || "-");
const categoryNames = computed(() =>
  (product.value?.categories || []).map(c => c.name).join(", ") || "-"
);

const statusText = computed(() =>
  Number(product.value?.status) === 1 ? "Hoạt động" : "Tạm tắt"
);

const featuredText = computed(() =>
  product.value?.is_featured ? "Có" : "Không"
);

/** ================= check state helpers ================= */
function isChecked(color, size) {
  return matrixChecked.value.has(matrixKey(color, size));
}

function rowState(color) {
  const count = ALL_SIZES.filter((s) => matrixChecked.value.has(matrixKey(color, s))).length;
  return { checked: count === ALL_SIZES.length, indeterminate: count > 0 && count < ALL_SIZES.length };
}

function colState(size) {
  const count = ALL_COLORS.filter((c) => matrixChecked.value.has(matrixKey(c, size))).length;
  return { checked: count === ALL_COLORS.length, indeterminate: count > 0 && count < ALL_COLORS.length };
}
</script>

<template>
  <!-- ==================== PAGE WRAPPER ==================== -->
  <div class="page">
    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-left">
        <div>
          <div class="page-title">Chi tiết sản phẩm</div>
          <div class="page-subtitle" v-if="product">
            #{{ product.id }} · {{ product.name }}
          </div>
        </div>
      </div>

      <div class="topbar-actions">
        <button class="btn btn-ghost" type="button" @click="goBack">
          <span class="material-symbols-outlined">arrow_back</span>
          Quay lại
        </button>
      </div>
    </div>

    <!-- ERROR -->
    <div v-if="error" class="alert">
      <div>
        <div class="alert-title">Lỗi</div>
        <div class="alert-body">{{ error }}</div>
      </div>
    </div>

    <!-- LOADING -->
    <div v-if="loading" class="loading">Đang tải...</div>

    <!-- ==================== MAIN WRAP ==================== -->
    <div v-if="!loading && product" class="wrap">

      <!-- ==================== THÔNG TIN CƠ BẢN ==================== -->
      <div class="card">
        <div class="card-head">
          <div class="card-head-left">
            <div class="card-head-icon">📝</div>
            <div>
              <div class="card-title">Thông tin cơ bản</div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="form-grid">
            <!-- Tên sản phẩm -->
            <div class="field full">
              <div class="label">Tên sản phẩm</div>
              <div class="control readonly">{{ product.name || "-" }}</div>
            </div>

            <div class="field">
              <div class="label">Slug</div>
              <div class="control readonly mono">{{ product.slug || "-" }}</div>
            </div>

            <div class="field">
              <div class="label">SKU</div>
              <div class="control readonly mono">{{ product.sku || "-" }}</div>
            </div>

            <div class="field">
              <div class="label">Trạng thái</div>
              <div class="control readonly">
                <span class="badge-green" v-if="Number(product.status) === 1">Hoạt động</span>
                <span class="badge-gray" v-else>Tạm tắt</span>
              </div>
            </div>

            <div class="field">
              <div class="label">Nổi bật</div>
              <div class="control readonly">
                <span class="badge-green" v-if="product.is_featured">Có</span>
                <span class="badge-gray" v-else>Không</span>
              </div>
            </div>

            <div class="field">
              <div class="label">Thương hiệu</div>
              <div class="control readonly">{{ brandName }}</div>
            </div>

            <div class="field">
              <div class="label">Danh mục</div>
              <div class="control readonly">
                <div class="chips-wrap">
                  <span class="chip" v-for="c in (product.categories || [])" :key="c.id">
                    {{ c.name }}
                  </span>
                </div>
              </div>
            </div>

            <div class="field">
              <div class="label">Giá gốc</div>
              <div class="control readonly price">{{ moneyVND(product.base_price) }}</div>
            </div>

            <div class="field">
              <div class="label">Giá khuyến mãi</div>
              <div class="control readonly" :class="product.base_sale_price ? 'sale-price' : ''">
                <template v-if="product.base_sale_price !== null && product.base_sale_price !== undefined">
                  {{ moneyVND(product.base_sale_price) }}
                </template>
                <template v-else>-</template>
              </div>
            </div>

            <div class="field full">
              <div class="label">Mô tả ngắn</div>
              <div class="control readonly textarea pre">{{ product.short_description || "Không có mô tả ngắn." }}</div>
            </div>

            <div class="field full">
              <div class="label">Mô tả chi tiết</div>
              <div class="control readonly textarea pre">{{ product.description || "Không có mô tả." }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== MEDIA ==================== -->
      <div class="card">
        <div class="card-head">
          <div class="card-head-left">
            <div class="card-head-icon">🖼️</div>
            <div>
              <div class="card-title">Hình ảnh đại diện</div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="thumb-area">
            <div v-if="thumbUrl" class="thumb-box">
              <img :src="thumbUrl" :alt="product.name" />
            </div>
            <div v-else class="thumb-empty">
              <span class="material-symbols-outlined">image_not_supported</span>
              <span>Không có ảnh đại diện</span>
            </div>
          </div>
        </div>
      </div>


      <!-- ==================== VARIANT TABLE (readonly) ==================== -->
      <div class="card" v-if="(product.variants || []).length > 0">
        <div class="card-head">
          <div class="card-head-left">
            <div class="card-head-icon">📦</div>
            <div>
              <div class="card-title">Chi tiết biến thể</div>
              <div class="card-subtitle">Thông tin giá, tồn kho của từng biến thể.</div>
            </div>
          </div>
          <div class="variant-count-badge">{{ product.variants.length }} biến thể</div>
        </div>

        <div class="card-body">
          <div class="table-wrap">
            <table class="variants">
              <thead>
                <tr>
                  <th>Màu</th>
                  <th>Size</th>
                  <th>SKU</th>
                  <th class="num">Giá</th>
                  <th class="num">Giá KM</th>
                  <th class="num">Tồn kho</th>
                  <th class="center">Trạng thái</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="v in product.variants || []" :key="v.id">
                  <td>
                    <div class="color-cell">
                      <span class="color-swatch sm" :style="{ background: COLOR_META[v.color] || '#94a3b8' }"></span>
                      <span class="color-text">{{ v.color || "-" }}</span>
                    </div>
                  </td>

                  <td>
                    <span class="size-chip">{{ v.size || "-" }}</span>
                  </td>

                  <td>
                    <span class="mono-sm">{{ v.sku || "-" }}</span>
                  </td>

                  <td class="num">
                    {{ moneyVND(v.price) }}
                  </td>

                  <td class="num">
                    <span v-if="v.sale_price !== null && v.sale_price !== undefined">
                      {{ moneyVND(v.sale_price) }}
                    </span>
                    <span v-else class="text-muted">-</span>
                  </td>

                  <td class="num">
                    <span class="stock-val" :class="{ 'stock-zero': Number(v.stock) <= 0 }">
                      {{ v.stock ?? 0 }}
                    </span>
                  </td>

                  <td class="center">
                    <span class="badge-green" v-if="v.is_active">Hoạt động</span>
                    <span class="badge-gray" v-else>Tạm tắt</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ==================== COLOR IMAGES (readonly) ==================== -->
      <div class="card" v-if="activeColors.length > 0">
        <div class="card-head">
          <div class="card-head-left">
            <div class="card-head-icon">🖼️</div>
            <div>
              <div class="card-title">Ảnh theo màu</div>
              <div class="card-subtitle">
                Ảnh của từng màu trong sản phẩm.
                <span class="badge">{{ activeColors.length }} màu</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card-body color-imgs-body">
          <div
            class="color-imgs-block"
            v-for="color in activeColors"
            :key="color"
          >
            <!-- color header -->
            <div class="color-imgs-header">
              <div class="color-imgs-title">
                <span class="color-swatch lg" :style="{ background: COLOR_META[color] }"></span>
                <span class="color-name-lg">{{ color }}</span>
                <span class="color-size-chips">
                  <span
                    class="size-chip xs"
                    v-for="size in ALL_SIZES.filter(s => isChecked(color, s))"
                    :key="size"
                  >{{ size }}</span>
                </span>
              </div>
            </div>

            <!-- image list -->
            <div class="imgs-grid">
              <div
                class="img-row"
                v-for="(img, j) in (colorImages[color] || [])"
                :key="j"
              >
                <div class="img-preview" v-if="img.url">
                  <img :src="imgSrc(img.url)" :alt="`${color} image ${j + 1}`" />
                </div>
                <div class="img-preview placeholder" v-else>Preview</div>

                <div class="fieldx">
                  <div class="labelx">URL / Path</div>
                  <div class="control readonly tcontrol">{{ img.url || "-" }}</div>
                </div>

                <div class="fieldx sortx">
                  <div class="labelx">Sort</div>
                  <div class="control readonly tcontrol">{{ img.sort_order ?? 0 }}</div>
                </div>
              </div>

              <!-- empty images for color -->
              <div
                v-if="!colorImages[color] || colorImages[color].length === 0"
                class="imgs-empty"
              >
                Chưa có ảnh cho màu này.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== THÔNG TIN HỆ THỐNG ==================== -->
      <div class="card" v-if="product.created_at || product.updated_at">
        <div class="card-head">
          <div class="card-head-left">
            <div class="card-head-icon gray-icon">⚙️</div>
            <div>
              <div class="card-title">Thông tin hệ thống</div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="form-grid">
            <div class="field">
              <div class="label">ID</div>
              <div class="control readonly mono">#{{ product.id }}</div>
            </div>
            <div class="field" v-if="product.created_at">
              <div class="label">Ngày tạo</div>
              <div class="control readonly">{{ product.created_at }}</div>
            </div>
            <div class="field" v-if="product.updated_at">
              <div class="label">Cập nhật lần cuối</div>
              <div class="control readonly">{{ product.updated_at }}</div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- NOT FOUND -->
    <div v-if="!loading && !error && !product" class="not-found">
      <span class="material-symbols-outlined">search_off</span>
      <div class="not-found-title">Không tìm thấy sản phẩm</div>
      <button class="btn btn-ghost" type="button" @click="goBack">Quay lại danh sách</button>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap');

* { box-sizing: border-box; }

/* ===== PAGE ===== */
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

/* ===== TOPBAR ===== */
.topbar {
  max-width: 1360px;
  margin: 0 auto 20px;
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 14px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  box-shadow: 0 1px 3px rgba(15,23,42,.05);
}

.topbar-left { display: flex; align-items: center; gap: 14px; }

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
  max-width: 600px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.topbar-actions { display: flex; align-items: center; gap: 10px; }

/* ===== BUTTONS ===== */
.btn {
  border-radius: 10px;
  padding: 9px 18px;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  transition: all .15s;
  font-family: 'DM Sans', sans-serif;
}
.btn .material-symbols-outlined { font-size: 18px; }

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

.btn-primary {
  background: linear-gradient(135deg, #3b82f6, #4f46e5);
  color: #fff;
  box-shadow: 0 2px 8px rgba(59,130,246,.35);
}
.btn-primary:hover {
  background: linear-gradient(135deg, #2563eb, #4338ca);
  box-shadow: 0 4px 14px rgba(59,130,246,.4);
  transform: translateY(-1px);
}

/* ===== ALERT ===== */
.alert {
  max-width: 1360px;
  margin: 0 auto 16px;
  border: 1px solid rgba(239,68,68,.2);
  background: linear-gradient(135deg, rgba(239,68,68,.07), rgba(239,68,68,.03));
  border-radius: 12px;
  padding: 12px 16px;
  color: #991b1b;
  display: flex;
  gap: 10px;
  align-items: flex-start;
  font-size: 13px;
}
.alert::before {
  content: "⚠";
  font-size: 15px;
  flex-shrink: 0;
  margin-top: 1px;
}
.alert-title { font-weight: 700; }
.alert-body { color: #b91c1c; margin-top: 2px; }

/* ===== LOADING ===== */
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
  animation: spin .8s linear infinite;
}

/* ===== CARD ===== */
.card {
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 16px;
  overflow: hidden;
  margin-top: 16px;
  box-shadow: 0 1px 4px rgba(15,23,42,.04);
  transition: box-shadow .2s;
  font-family: 'DM Sans', sans-serif;
}
.card:hover { box-shadow: 0 4px 16px rgba(15,23,42,.07); }

.card-head {
  padding: 16px 20px;
  border-bottom: 1px solid #f1f4fa;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  background: #fafbfd;
}
.card-head-left { display: flex; align-items: center; gap: 12px; }

.card-head-icon {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border-radius: 10px;
  display: grid; place-items: center;
  font-size: 16px; flex-shrink: 0;
}
.gray-icon { background: linear-gradient(135deg, #f8fafc, #f1f5f9); }

.card-title { font-size: 14px; font-weight: 700; color: #0d1117; }
.card-subtitle { margin-top: 2px; font-size: 12px; color: #94a3b8; font-weight: 500; display: flex; align-items: center; gap: 8px; }
.card-body { padding: 20px; }

/* ===== BADGES ===== */
.badge {
  display: inline-flex;
  align-items: center;
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
  border-radius: 20px;
  padding: 1px 9px;
  font-size: 11px;
  font-weight: 700;
}

.badge-green {
  display: inline-flex;
  align-items: center;
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
  border-radius: 20px;
  padding: 3px 10px;
  font-size: 11.5px;
  font-weight: 800;
}

.badge-gray {
  display: inline-flex;
  align-items: center;
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 3px 10px;
  font-size: 11.5px;
  font-weight: 800;
}

.variant-count-badge {
  background: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
  border-radius: 20px;
  padding: 4px 12px;
  font-size: 12px;
  font-weight: 700;
}

/* ===== FORM GRID ===== */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 20px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.field.full { grid-column: 1 / -1; }

.label {
  font-size: 12.5px;
  font-weight: 700;
  color: #374151;
  letter-spacing: .01em;
}

/* ===== READONLY CONTROL ===== */
.control {
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 14px;
  background: #fafbfd;
  color: #0f172a;
  font-family: 'DM Sans', sans-serif;
  font-size: 13.5px;
  line-height: 1.5;
  min-height: 42px;
  display: flex;
  align-items: center;
}
.control.textarea {
  min-height: 80px;
  align-items: flex-start;
}
.control.pre { white-space: pre-wrap; }
.control.readonly { cursor: default; }
.control.price { color: #059669; }
.control.sale-price { color: #dc2626; }


/* ===== CHIPS ===== */
.chips-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}
.chip {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
}

@media (max-width: 700px) {
  .form-grid { grid-template-columns: 1fr; }
}

/* ===== MATRIX ===== */
.matrix-wrap {
  overflow: auto;
  border: 1.5px solid #e8ecf4;
  border-radius: 12px;
}

.matrix {
  border-collapse: collapse;
  width: 100%;
  min-width: 720px;
}

.matrix-corner {
  background: #f5f7ff;
  border-bottom: 1.5px solid #e8ecf4;
  border-right: 1.5px solid #e8ecf4;
  padding: 10px 14px;
  position: relative;
  min-width: 110px;
}
.axis-label {
  position: absolute;
  font-size: 10px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.color-axis { top: 10px; left: 10px; }
.size-axis  { bottom: 10px; right: 10px; }

.matrix-col-head {
  background: #f5f7ff;
  border-bottom: 1.5px solid #e8ecf4;
  border-right: 1px solid #eef0f6;
  padding: 8px 6px;
  user-select: none;
}

.col-head-inner {
  display: flex; flex-direction: column; align-items: center; gap: 4px;
}
.size-label { font-size: 12px; font-weight: 700; color: #374151; }

.matrix-row-head {
  background: #fafbfd;
  border-right: 1.5px solid #e8ecf4;
  border-top: 1px solid #eef0f6;
  padding: 6px 12px;
  user-select: none;
}

.row-head-inner {
  display: flex; align-items: center; gap: 8px;
}

.matrix-cell {
  border-top: 1px solid #eef0f6;
  border-right: 1px solid #eef0f6;
  width: 48px; height: 44px;
  text-align: center;
  user-select: none;
}
.matrix-cell.checked { background: #eff6ff; }

.cell-inner {
  width: 100%; height: 100%;
  display: grid; place-items: center;
}
.check-icon {
  width: 22px; height: 22px;
  background: #3b82f6;
  color: #fff;
  border-radius: 6px;
  display: grid; place-items: center;
  font-size: 13px;
  font-weight: 700;
}

.matrix-cb {
  accent-color: #3b82f6;
  width: 14px; height: 14px;
  flex-shrink: 0;
}

.color-swatch {
  width: 16px; height: 16px;
  border-radius: 50%;
  border: 1.5px solid rgba(0,0,0,.12);
  flex-shrink: 0;
  display: inline-block;
}
.color-swatch.sm { width: 12px; height: 12px; }
.color-name { font-size: 12.5px; font-weight: 600; color: #374151; }

.matrix-empty {
  margin-top: 14px;
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
  font-weight: 600;
  padding: 12px;
  border: 1.5px dashed #e2e8f0;
  border-radius: 10px;
}

/* ===== VARIANT TABLE ===== */
.table-wrap {
  overflow: auto;
  border: 1.5px solid #e8ecf4;
  border-radius: 12px;
}
.variants {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}
.variants thead th {
  position: sticky; top: 0;
  background: #f5f7ff;
  border-bottom: 1.5px solid #e8ecf4;
  padding: 10px 12px;
  text-align: left;
  vertical-align: middle;
  font-size: 11px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .06em;
  font-weight: 700;
}
/* Hai class → specificity cao hơn .variants thead th, tránh text-align: left ghi đè */
.variants thead th.num {
  text-align: right;
}
.variants thead th.center {
  text-align: center;
}
.variants tr:hover td { background: #fafbff; }
.variants td {
  border-top: 1px solid #f1f4fa;
  padding: 10px 12px;
  vertical-align: middle;
  font-size: 13px;
  color: #374151;
}
.num { text-align: right; }
.center { text-align: center; }

.color-cell { display: flex; align-items: center; gap: 7px; }
.color-text { font-size: 13px; font-weight: 600; color: #374151; }

.size-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  color: #374151;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 2px 10px;
  font-size: 12px;
  font-weight: 700;
}

.stock-val { font-weight: 700; color: #0f172a; }
.stock-zero { color: #dc2626; }

.text-muted { color: #94a3b8; }

/* ===== THUMB ===== */
.thumb-area {
  width: 100%;
}
.thumb-box {
  border: 2px dashed #c7d4e8;
  border-radius: 14px;
  padding: 12px;
  background: #fafbfd;
}
.thumb-box img {
  width: 100%;
  border-radius: 12px;
  object-fit: cover;
  max-height: 400px;
  display: block;
}

.thumb-empty {
  border: 2px dashed #e2e8f0;
  border-radius: 14px;
  min-height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #94a3b8;
  font-weight: 600;
  font-size: 13px;
  background: #fafbfd;
}
.thumb-empty .material-symbols-outlined { font-size: 44px; color: #cbd5e1; font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; }

/* ===== COLOR IMAGES ===== */
.color-imgs-body {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.color-imgs-block {
  border: 1.5px solid #e8ecf4;
  border-radius: 12px;
  overflow: hidden;
}

.color-imgs-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  background: #fafbfd;
  border-bottom: 1px solid #f1f4fa;
}

.color-imgs-title {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.color-swatch.lg {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid rgba(0,0,0,.12);
  flex-shrink: 0;
  display: inline-block;
}

.color-name-lg {
  font-size: 13.5px;
  font-weight: 700;
  color: #0d1117;
}

.color-size-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.size-chip.xs {
  padding: 1px 7px;
  font-size: 11px;
  border-radius: 5px;
  background: #eff6ff;
  color: #2563eb;
  border-color: #bfdbfe;
}

.imgs-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px;
}

.img-row {
  display: grid;
  grid-template-columns: 90px 1fr 120px;
  gap: 12px;
  align-items: start;
  padding: 12px;
  border: 1.5px solid #e8ecf4;
  border-radius: 12px;
  background: #fff;
  transition: .15s;
}
.img-row:hover { border-color: #c7d4e8; }

.img-preview {
  width: 90px; height: 90px;
  border-radius: 12px;
  overflow: hidden;
  border: 1.5px solid #e8ecf4;
  display: grid; place-items: center;
  background: #f5f7ff;
  color: #94a3b8;
  font-weight: 600;
  font-size: 11px;
}
.img-preview img { width: 100%; height: 100%; object-fit: cover; }

.fieldx { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.labelx { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }

.tcontrol {
  height: 40px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  padding: 0 10px;
  font-size: 13px;
  font-family: 'DM Sans', sans-serif;
  background: #fafbfd;
  color: #0f172a;
  display: flex;
  align-items: center;
  width: 100%;
}
.tcontrol.readonly { cursor: default; }
.sortx .tcontrol { justify-content: flex-end; text-align: right; }

.imgs-empty {
  text-align: center;
  color: #94a3b8;
  font-weight: 600;
  font-size: 13px;
  padding: 16px;
  border: 1.5px dashed #e2e8f0;
  border-radius: 10px;
}

/* ===== NOT FOUND ===== */
.not-found {
  max-width: 1360px;
  margin: 60px auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  color: #94a3b8;
}
.not-found .material-symbols-outlined { font-size: 60px; color: #cbd5e1; font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; }
.not-found-title { font-size: 18px; font-weight: 700; color: #475569; }

/* ===== ANIMATIONS ===== */
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 700px) {
  .topbar { flex-direction: column; align-items: flex-start; }
  .topbar-actions { width: 100%; }
  .btn { width: 100%; justify-content: center; }
  .img-row { grid-template-columns: 80px 1fr; }
  .sortx { display: none; }
}
</style>