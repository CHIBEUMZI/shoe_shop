<template>
  <main class="page">
    <div class="wrap">
      <div class="topbar">
        <div class="topbar-left">
          <div>
            <div class="page-title">Chi tiết đơn hàng</div>
            <div class="page-subtitle">Theo dõi và xử lý đơn hàng của khách</div>
          </div>
        </div>

        <div class="topbar-actions">
          <button class="btn btn-ghost" type="button" @click="goBack">Quay lại</button>
        </div>
      </div>

      <div v-if="loading" class="loading">Đang tải...</div>

      <div v-else-if="error" class="alert">
        <div>
          <div class="alert-title">Lỗi</div>
          <div class="alert-body">{{ error }}</div>
        </div>
      </div>

      <template v-else-if="order.id">
        <div class="layout">
          <div class="left">
            <AdminDetailSection
              title="Thông tin đơn hàng"
              subtitle="Thông tin cơ bản và trạng thái xử lý"
              icon="🧾"
              :items="orderInfoItems"
            >
              <template #value:status>
                <span class="badge" :class="orderStatusClass(order.status)">
                  {{ orderStatusText(order.status) }}
                </span>
              </template>

              <template #value:payment_status>
                <span class="badge" :class="paymentStatusClass(order.payment_status)">
                  {{ paymentStatusText(order.payment_status) }}
                </span>
              </template>

              <template #value:grand_total>
                <span class="price">{{ moneyVND(order.grand_total) }}</span>
              </template>
            </AdminDetailSection>

            <AdminDetailSection
              title="Thông tin khách hàng"
              subtitle="Thông tin giao nhận"
              icon="👤"
              :items="customerItems"
            />

            <section class="section">
              <div class="section-head">
                <div class="section-icon">📦</div>
                <div class="section-title">Sản phẩm trong đơn</div>
              </div>

              <div class="section-body">
                <div class="products">
                  <div
                    v-for="item in order.items || []"
                    :key="item.id"
                    class="product-row"
                  >
                    <div class="product-main">
                      <div class="thumb">
                        <img
                          :src="buildImageUrl(item.thumbnail) || fallbackImage"
                          :alt="item.product_name"
                        />
                      </div>

                      <div class="product-info">
                        <div class="product-name">{{ item.product_name }}</div>
                        <div class="product-meta">
                          <span v-if="item.size">Size: {{ item.size }}</span>
                          <span v-if="item.size && item.color"> | </span>
                          <span v-if="item.color">Màu: {{ item.color }}</span>
                        </div>
                        <div class="product-meta">SKU SP: {{ item.product_sku || "-" }}</div>
                        <div class="product-meta">SKU biến thể: {{ item.variant_sku || "-" }}</div>
                      </div>
                    </div>

                    <div class="product-side">
                      <div>Đơn giá: <b>{{ moneyVND(item.unit_price) }}</b></div>
                      <div>Số lượng: <b>{{ item.quantity }}</b></div>
                      <div>Thành tiền: <b>{{ moneyVND(item.line_total) }}</b></div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <div class="right">
            <AdminActionPanel
              title="Thao tác đơn hàng"
              subtitle="Xử lý trạng thái đơn hàng"
              :message="actionMessage"
              :error="actionError"
            >
              <button
                v-for="next in nextStatuses"
                :key="next"
                class="btn btn-ghost"
                type="button"
                :disabled="submitting"
                @click="updateStatus(next)"
              >
                {{ statusActionLabel(next) }}
              </button>
            </AdminActionPanel>

            <AdminDetailSection
              title="Tóm tắt thanh toán"
              subtitle="Chi tiết số tiền"
              icon="💳"
              :items="paymentSummaryItems"
              :columns="1"
            >
              <template #value:subtotal>
                <span>{{ moneyVND(order.subtotal) }}</span>
              </template>
              <template #value:shipping_fee>
                <span>{{ moneyVND(order.shipping_fee) }}</span>
              </template>
              <template #value:discount_total>
                <span>{{ moneyVND(order.discount_total) }}</span>
              </template>
              <template #value:grand_total>
                <span class="price">{{ moneyVND(order.grand_total) }}</span>
              </template>
            </AdminDetailSection>
          </div>
        </div>
      </template>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import AdminDetailSection from "../../../components/AdminDetailSection.vue";
import AdminActionPanel from "../../../components/AdminActionPanel.vue";
import orderAdminService from "../../../services/admin/orderAdminService";
import { useAlert } from "../../../composables/useAlert";

const route = useRoute();
const router = useRouter();
const notify = useAlert();

const loading = ref(false);
const submitting = ref(false);
const error = ref("");
const actionError = ref("");
const actionMessage = ref("");
const order = ref({});

const fallbackImage = "https://via.placeholder.com/400x400?text=Shoe";
const API_BASE = import.meta.env.VITE_API_URL || "";

onMounted(fetchDetail);

async function fetchDetail(showSuccessToast = false) {
  loading.value = true;
  error.value = "";

  try {
    const res = await orderAdminService.show(route.params.id);
    order.value = res?.data?.data || {};

    if (showSuccessToast) {
      notify.success("Dữ liệu đơn hàng đã được cập nhật.", {
        title: "Tải dữ liệu thành công",
        duration: 2200,
      });
    }
  } catch (e) {
    const msg = e?.response?.data?.message || "Không tải được chi tiết đơn hàng.";
    error.value = msg;

    notify.error(msg, {
      title: "Lỗi tải dữ liệu",
      duration: 3500,
    });
  } finally {
    loading.value = false;
  }
}

const orderInfoItems = computed(() => [
  { key: "code", label: "Mã đơn", value: order.value.code || "-" },
  {
    key: "created_at",
    label: "Ngày tạo",
    value: formatDateTime(order.value.created_at),
  },
  {
    key: "payment_method",
    label: "Phương thức thanh toán",
    value: paymentMethodText(order.value.payment_method),
  },
  {
    key: "payment_status",
    label: "Trạng thái thanh toán",
    value: order.value.payment_status || "-",
  },
  { key: "status", label: "Trạng thái đơn", value: order.value.status || "-" },
  { key: "grand_total", label: "Tổng tiền", value: order.value.grand_total || 0 },
]);

const customerItems = computed(() => [
  { key: "customer_name", label: "Họ tên", value: order.value.customer_name || "-" },
  { key: "customer_phone", label: "Số điện thoại", value: order.value.customer_phone || "-" },
  { key: "customer_email", label: "Email", value: order.value.customer_email || "-" },
  { key: "address", label: "Địa chỉ", value: fullAddress.value, full: true },
  { key: "note", label: "Ghi chú", value: order.value.note || "-", full: true },
]);

const paymentSummaryItems = computed(() => [
  { key: "subtotal", label: "Tạm tính", value: order.value.subtotal || 0 },
  { key: "shipping_fee", label: "Phí vận chuyển", value: order.value.shipping_fee || 0 },
  { key: "discount_total", label: "Giảm giá", value: order.value.discount_total || 0 },
  { key: "grand_total", label: "Tổng cộng", value: order.value.grand_total || 0 },
]);

const fullAddress = computed(() => {
  return [
    order.value.address_line,
    order.value.ward,
    order.value.district,
    order.value.province,
  ]
    .filter(Boolean)
    .join(", ") || "-";
});

const canConfirmCod = computed(() => {
  return (
    order.value.payment_method === "cod" &&
    order.value.status === "pending" &&
    !order.value.stock_deducted_at
  );
});

const nextStatuses = computed(() => {
  const map = {
    pending: ["confirmed", "cancelled"],
    confirmed: ["processing", "cancelled"],
    processing: ["shipping", "cancelled"],
    shipping: ["completed"],
    completed: [],
    cancelled: [],
  };

  return map[order.value.status] || [];
});

async function updateStatus(status) {
  if (!order.value?.id) return;

  submitting.value = true;
  actionError.value = "";
  actionMessage.value = "";

  try {
    await orderAdminService.updateStatus(order.value.id, { status });

    const successMessage = statusSuccessMessage(status);
    actionMessage.value = successMessage;

    notify.success(successMessage, {
      title: "Cập nhật thành công",
      duration: 2600,
    });

    await fetchDetail();
  } catch (e) {
    const msg = e?.response?.data?.message || statusErrorMessage(status);
    actionError.value = msg;

    notify.error(msg, {
      title: "Cập nhật thất bại",
      duration: 3800,
    });
  } finally {
    submitting.value = false;
  }
}

function statusActionLabel(status) {
  switch (status) {
    case "confirmed":
      return "Xác nhận đơn";
    case "processing":
      return "Chuyển sang chuẩn bị";
    case "shipping":
      return "Chuyển sang đang giao";
    case "completed":
      return "Đánh dấu hoàn thành";
    case "cancelled":
      return "Hủy đơn";
    default:
      return status;
  }
}

function statusSuccessMessage(status) {
  switch (status) {
    case "confirmed":
      return "Đơn hàng đã được xác nhận thành công.";
    case "processing":
      return "Đơn hàng đã được chuyển sang trạng thái đang chuẩn bị.";
    case "shipping":
      return "Đơn hàng đã được chuyển sang trạng thái đang giao.";
    case "completed":
      return "Đơn hàng đã được đánh dấu hoàn thành.";
    case "cancelled":
      return "Đơn hàng đã được hủy thành công.";
    default:
      return "Trạng thái đơn hàng đã được cập nhật thành công.";
  }
}

function statusErrorMessage(status) {
  switch (status) {
    case "confirmed":
      return "Không thể xác nhận đơn hàng.";
    case "processing":
      return "Không thể chuyển đơn hàng sang trạng thái đang chuẩn bị.";
    case "shipping":
      return "Không thể chuyển đơn hàng sang trạng thái đang giao.";
    case "completed":
      return "Không thể đánh dấu đơn hàng hoàn thành.";
    case "cancelled":
      return "Không thể hủy đơn hàng.";
    default:
      return "Không thể cập nhật trạng thái đơn hàng.";
  }
}

function buildImageUrl(pathOrUrl) {
  if (!pathOrUrl) return "";
  if (String(pathOrUrl).startsWith("http")) return pathOrUrl;
  if (String(pathOrUrl).startsWith("/")) return `${API_BASE}${pathOrUrl}`;
  return `${API_BASE}/storage/${pathOrUrl}`;
}

function goBack() {
  router.push("/admin/orders");
}

function moneyVND(v) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(Number(v || 0));
}

function formatDateTime(v) {
  if (!v) return "-";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return v;

  return new Intl.DateTimeFormat("vi-VN", {
    dateStyle: "short",
    timeStyle: "short",
  }).format(d);
}

function paymentMethodText(v) {
  switch (v) {
    case "cod":
      return "COD";
    case "vnpay":
      return "VNPay";
    case "momo":
      return "MoMo";
    default:
      return v || "-";
  }
}

function paymentStatusText(v) {
  switch (v) {
    case "unpaid":
      return "Chưa thanh toán";
    case "pending":
      return "Chờ thanh toán";
    case "paid":
      return "Đã thanh toán";
    case "failed":
      return "Thất bại";
    case "refunded":
      return "Hoàn tiền";
    default:
      return v || "-";
  }
}

function orderStatusText(v) {
  switch (v) {
    case "pending":
      return "Chờ xử lý";
    case "confirmed":
      return "Đã xác nhận";
    case "processing":
      return "Đang chuẩn bị";
    case "shipping":
      return "Đang giao";
    case "completed":
      return "Hoàn thành";
    case "cancelled":
      return "Đã hủy";
    default:
      return v || "-";
  }
}

function paymentStatusClass(v) {
  switch (v) {
    case "unpaid":
      return "bg-slate";
    case "pending":
      return "bg-amber";
    case "paid":
      return "bg-green";
    case "failed":
      return "bg-red";
    case "refunded":
      return "bg-purple";
    default:
      return "bg-slate";
  }
}

function orderStatusClass(v) {
  switch (v) {
    case "pending":
      return "bg-amber";
    case "confirmed":
      return "bg-blue";
    case "processing":
      return "bg-indigo";
    case "shipping":
      return "bg-sky";
    case "completed":
      return "bg-green";
    case "cancelled":
      return "bg-red";
    default:
      return "bg-slate";
  }
}
</script>

<style scoped>
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

.topbar {
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

.layout {
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

.section {
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
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
}

.section-body {
  padding: 18px 20px 20px;
}

.products {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.product-row {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f1f4fa;
}

.product-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.product-main {
  display: flex;
  gap: 14px;
  min-width: 0;
}

.thumb {
  width: 82px;
  height: 82px;
  border-radius: 14px;
  overflow: hidden;
  background: #f8fafc;
  flex-shrink: 0;
}

.thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-info {
  min-width: 0;
}

.product-name {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.product-meta {
  margin-top: 4px;
  font-size: 12.5px;
  color: #64748b;
}

.product-side {
  font-size: 13px;
  color: #475569;
  text-align: right;
  white-space: nowrap;
}

.alert {
  margin-bottom: 16px;
  border: 1px solid rgba(239, 68, 68, 0.2);
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.07), rgba(239, 68, 68, 0.03));
  border-radius: 12px;
  padding: 12px 16px;
  color: #991b1b;
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

.loading {
  padding: 40px 0;
  color: #94a3b8;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
}

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
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-ghost {
  background: #fff;
  border: 1.5px solid #e2e8f0;
  color: #475569;
}

.btn-ghost:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #334155;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6, #4f46e5);
  color: #fff;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.35);
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 700;
}

.bg-slate {
  background: #f1f5f9;
  color: #475569;
}

.bg-amber {
  background: #fef3c7;
  color: #b45309;
}

.bg-blue {
  background: #dbeafe;
  color: #1d4ed8;
}

.bg-indigo {
  background: #e0e7ff;
  color: #4338ca;
}

.bg-sky {
  background: #e0f2fe;
  color: #0369a1;
}

.bg-green {
  background: #dcfce7;
  color: #15803d;
}

.bg-red {
  background: #fee2e2;
  color: #dc2626;
}

.bg-purple {
  background: #f3e8ff;
  color: #7e22ce;
}

.price {
  font-weight: 800;
  color: #2563eb;
}

@media (max-width: 980px) {
  .layout {
    grid-template-columns: 1fr;
  }

  .product-row {
    flex-direction: column;
  }

  .product-side {
    text-align: left;
  }
}
</style>