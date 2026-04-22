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
            <!-- Khi KHÔNG có yêu cầu hủy từ khách: hiển thị panel hành động thông thường -->
            <AdminActionPanel
              v-if="!order.cancellation_requested_at"
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

            <section v-if="order.cancellation_requested_at && order.status !== 'cancelled'" class="section">
              <div class="section-head">
                <div class="section-icon">⚠️</div>
                <div class="section-title">Yêu cầu hủy đơn hàng</div>
              </div>
              <div class="section-body">
                <div class="cancel-info">
                  <div class="cancel-row">
                    <span class="cancel-label">Thời gian yêu cầu:</span>
                    <span class="cancel-value">{{ formatDateTime(order.cancellation_requested_at) }}</span>
                  </div>
                  <div class="cancel-row">
                    <span class="cancel-label">Lý do khách hàng:</span>
                    <span class="cancel-value">{{ order.cancellation_reason || "-" }}</span>
                  </div>
                </div>

                <div class="cancel-actions">
                  <div class="cancel-action-hint">Hành động của bạn với yêu cầu hủy này:</div>
                  <div class="flex gap-3 mt-3">
                    <button
                      class="btn btn-danger"
                      type="button"
                      :disabled="submitting"
                      @click="confirmCancel"
                    >
                      Xác nhận hủy đơn
                    </button>
                    <button
                      class="btn btn-ghost"
                      type="button"
                      :disabled="submitting"
                      @click="rejectCancel"
                    >
                      Từ chối
                    </button>
                  </div>
                </div>

                <div v-if="cancelActionError" class="cancel-error">
                  {{ cancelActionError }}
                </div>
              </div>
            </section>

            <!-- Tóm tắt thanh toán: hiển thị khi đơn chưa bị hủy -->
            <AdminDetailSection
              v-if="order.status !== 'cancelled'"
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

            <!-- Thông tin đã hủy -->
            <section v-if="order.status === 'cancelled'" class="section cancelled-section">
              <div class="section-head">
                <div class="section-icon">🚫</div>
                <div class="section-title">Thông tin hủy đơn</div>
              </div>
              <div class="section-body">
                <div class="cancel-info">
                  <div class="cancel-row">
                    <span class="cancel-label">Ngày hủy:</span>
                    <span class="cancel-value">{{ formatDateTime(order.cancelled_at) }}</span>
                  </div>
                  <div v-if="order.admin_cancellation_reason" class="cancel-row">
                    <span class="cancel-label">Lý do hủy:</span>
                    <span class="cancel-value">{{ order.admin_cancellation_reason }}</span>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </div>
      </template>
    </div>
  </main>

  <!-- Confirm Cancel Modal -->
  <Teleport to="body">
    <div
      v-if="showConfirmCancelModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      @click.self="closeConfirmCancelModal"
    >
      <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-800 shadow-xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700">
          <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">
            Xác nhận hủy đơn hàng
          </h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Hành động này sẽ hủy đơn hàng và khôi phục số lượng tồn kho. Bạn có chắc chắn?
          </p>
        </div>

        <div class="px-6 py-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Lý do hủy (tùy chọn)
            </label>
            <textarea
              v-model="adminCancelReason"
              rows="3"
              placeholder="Nhập lý do hủy đơn hàng (nếu có)..."
              class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 resize-none"
            ></textarea>
          </div>

          <div
            v-if="cancelActionError"
            class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-600 dark:text-red-400"
          >
            {{ cancelActionError }}
          </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
          <button
            type="button"
            class="rounded-xl border border-slate-200 dark:border-slate-700 px-5 py-2.5 text-sm font-semibold text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
            @click="closeConfirmCancelModal"
          >
            Đóng
          </button>
          <button
            type="button"
            class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700 transition disabled:opacity-50"
            :disabled="submitting"
            @click="submitConfirmCancel"
          >
            {{ submitting ? 'Đang xử lý...' : 'Xác nhận hủy đơn' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <ConfirmModal
    :visible="confirmModal.visible"
    :title="confirmModal.title"
    :message="confirmModal.message"
    :variant="confirmModal.variant"
    @confirm="onConfirmModal"
    @cancel="onCancelModal"
  />
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import AdminDetailSection from "../../../components/AdminDetailSection.vue";
import AdminActionPanel from "../../../components/AdminActionPanel.vue";
import orderAdminService from "../../../services/admin/orderAdminService";
import { useAlert } from "../../../composables/useAlert";
import ConfirmModal from "../../../components/ComfirmModal.vue";
import { formatDateTime } from "../../../utils/date";

const route = useRoute();
const router = useRouter();
const notify = useAlert();

const loading = ref(false);
const submitting = ref(false);
const error = ref("");
const actionError = ref("");
const actionMessage = ref("");
const order = ref({});

const confirmModal = ref({
  visible: false,
  targetStatus: null,
  title: "",
  message: "",
  variant: "info",
});

// Cancel order handling
const showConfirmCancelModal = ref(false);
const adminCancelReason = ref("");
const cancelActionError = ref("");
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

function updateStatus(status) {
  const labelMap = {
    confirmed: { title: "Xác nhận đơn hàng", message: "Bạn có chắc muốn xác nhận đơn hàng này?", variant: "success" },
    processing: { title: "Chuyển sang chuẩn bị", message: "Chuyển đơn hàng sang trạng thái đang chuẩn bị?", variant: "info" },
    shipping: { title: "Chuyển sang đang giao", message: "Chuyển đơn hàng sang trạng thái đang giao?", variant: "info" },
    completed: { title: "Hoàn thành đơn hàng", message: "Đánh dấu đơn hàng này là đã hoàn thành?", variant: "success" },
    cancelled: { title: "Hủy đơn hàng", message: "Bạn có chắc muốn hủy đơn hàng này? Hành động này không thể hoàn tác.", variant: "danger" },
  };

  const config = labelMap[status] || { title: "Cập nhật trạng thái", message: "Xác nhận thay đổi trạng thái?", variant: "info" };

  confirmModal.value = {
    visible: true,
    targetStatus: status,
    ...config,
  };
}

async function onConfirmModal() {
  const status = confirmModal.value.targetStatus;
  confirmModal.value.visible = false;

  if (!order.value?.id || !status) return;

  submitting.value = true;
  actionError.value = "";
  actionMessage.value = "";

  try {
    await orderAdminService.updateStatus(order.value.id, { status });

    const successMessage = statusSuccessMessage(status);
    actionMessage.value = successMessage;

    notify.success(successMessage, { title: "Cập nhật thành công", duration: 2600 });

    await fetchDetail();
  } catch (e) {
    const msg = e?.response?.data?.message || statusErrorMessage(status);
    actionError.value = msg;

    notify.error(msg, { title: "Cập nhật thất bại", duration: 3800 });
  } finally {
    submitting.value = false;
  }
}
function onCancelModal() {
  confirmModal.value.visible = false;
  confirmModal.value.targetStatus = null;
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

// ======================
// Hủy đơn hàng
// ======================

function confirmCancel() {
  showConfirmCancelModal.value = true;
  adminCancelReason.value = "";
  cancelActionError.value = "";
}

function closeConfirmCancelModal() {
  showConfirmCancelModal.value = false;
  adminCancelReason.value = "";
  cancelActionError.value = "";
}

async function submitConfirmCancel() {
  cancelActionError.value = "";
  submitting.value = true;

  try {
    await orderAdminService.confirmCancellation(order.value.id, {
      reason: adminCancelReason.value.trim() || null,
    });

    notify.success("Đơn hàng đã được hủy thành công. Tồn kho đã được khôi phục.", {
      title: "Hủy đơn thành công",
      duration: 3000,
    });

    closeConfirmCancelModal();
    await fetchDetail();
  } catch (e) {
    const msg = e?.response?.data?.message || "Không thể hủy đơn hàng.";
    cancelActionError.value = msg;
  } finally {
    submitting.value = false;
  }
}

async function rejectCancel() {
  cancelActionError.value = "";
  submitting.value = true;

  try {
    await orderAdminService.rejectCancellation(order.value.id);

    notify.success("Đã từ chối yêu cầu hủy đơn hàng.", {
      title: "Thành công",
      duration: 2500,
    });

    await fetchDetail();
  } catch (e) {
    const msg = e?.response?.data?.message || "Không thể từ chối yêu cầu hủy đơn.";
    cancelActionError.value = msg;
  } finally {
    submitting.value = false;
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

/* Cancel order section */
.cancelled-section .section-icon {
  background: linear-gradient(135deg, #fee2e2, #fecaca);
}

.cancel-info {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.cancel-row {
  display: flex;
  gap: 12px;
  font-size: 13.5px;
}

.cancel-label {
  flex-shrink: 0;
  width: 160px;
  color: #64748b;
  font-weight: 600;
}

.cancel-value {
  color: #1e293b;
}

.cancel-actions {
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px dashed #e2e8f0;
}

.cancel-action-hint {
  font-size: 13px;
  color: #475569;
  font-weight: 600;
}

.cancel-error {
  margin-top: 12px;
  padding: 10px 14px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  font-size: 13px;
  color: #dc2626;
}

.btn-danger {
  background: #ef4444;
  color: #fff;
  border: none;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}

.flex {
  display: flex;
}

.gap-3 {
  gap: 12px;
}

.mt-3 {
  margin-top: 12px;
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