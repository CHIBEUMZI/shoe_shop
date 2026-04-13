<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import userAdminService from "../../../services/admin/userAdminService";
import orderAdminService from "../../../services/admin/orderAdminService";

const router = useRouter();
const route = useRoute();

const id = computed(() => route.params.id);

const activeTab = ref("info");

const loadingUser = ref(false);
const loadingOrders = ref(false);
const errorUser = ref("");
const errorOrders = ref("");

const user = ref({
  id: null,
  name: "",
  email: "",
  role: "",
  is_active: false,
  avatar: "",
  birth_date: null,
  phone: "",
  address: "",
  created_at: "",
  updated_at: "",
});

const orders = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

function previewSrc(value) {
  const v = String(value || "").trim();
  if (!v) return "";
  if (v.startsWith("http://") || v.startsWith("https://")) return v;
  return `/storage/${v.replace(/^\/+/, "")}`;
}

function formatDate(value) {
  if (!value) return "—";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return String(value);
  return d.toLocaleDateString("vi-VN");
}

function formatDateTime(value) {
  if (!value) return "—";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return String(value);
  return d.toLocaleString("vi-VN");
}

function formatCurrency(value) {
  const n = Number(value || 0);
  return n.toLocaleString("vi-VN") + " đ";
}

function roleLabel(role) {
  return role === "admin" ? "Quản trị viên" : "Khách hàng";
}

function orderStatusLabel(status) {
  const map = {
    pending: "Chờ xử lý",
    confirmed: "Đã xác nhận",
    processing: "Đang xử lý",
    shipping: "Đang giao",
    delivered: "Đã giao",
    completed: "Hoàn thành",
    cancelled: "Đã hủy",
    canceled: "Đã hủy",
    returned: "Hoàn trả",
    failed: "Thất bại",
    paid: "Đã thanh toán",
    unpaid: "Chưa thanh toán",
  };
  return map[String(status || "").toLowerCase()] || status || "—";
}

function paymentStatusLabel(status) {
  const map = {
    pending: "Chờ thanh toán",
    paid: "Đã thanh toán",
    unpaid: "Chưa thanh toán",
    failed: "Thanh toán lỗi",
    refunded: "Đã hoàn tiền",
    cancelled: "Đã hủy",
    canceled: "Đã hủy",
  };
  return map[String(status || "").toLowerCase()] || status || "—";
}

function orderStatusClass(status) {
  const s = String(status || "").toLowerCase();

  if (["completed", "delivered", "paid"].includes(s)) return "success";
  if (["pending", "confirmed", "processing", "shipping", "unpaid"].includes(s)) return "warning";
  if (["cancelled", "canceled", "failed", "returned"].includes(s)) return "danger";

  return "default";
}

async function loadUser(userId) {
  loadingUser.value = true;
  errorUser.value = "";

  try {
    const resp = await userAdminService.show(userId);
    const data = resp?.data?.data ?? resp?.data ?? resp;

    user.value = {
      id: data?.id ?? null,
      name: data?.name ?? "",
      email: data?.email ?? "",
      role: data?.role ?? "customer",
      is_active: !!data?.is_active,
      avatar: data?.avatar ?? "",
      birth_date: data?.birth_date ?? null,
      phone: data?.phone ?? "",
      address: data?.address ?? "",
      created_at: data?.created_at ?? "",
      updated_at: data?.updated_at ?? "",
    };
  } catch (err) {
    errorUser.value =
      err?.response?.data?.message || err?.message || "Không tải được thông tin người dùng";
  } finally {
    loadingUser.value = false;
  }
}

async function loadOrders(page = 1) {
  loadingOrders.value = true;
  errorOrders.value = "";

  try {
    const resp = await orderAdminService.list({
      page,
      per_page: 10,
      user_id: id.value,
    });

    const root = resp?.data ?? resp;
    const items = root?.data ?? [];
    const meta = root?.meta ?? {};

    orders.value = Array.isArray(items) ? items : [];

    pagination.value = {
      current_page: Number(meta?.current_page ?? page),
      last_page: Number(meta?.last_page ?? 1),
      per_page: Number(meta?.per_page ?? 10),
      total: Number(meta?.total ?? orders.value.length),
    };
  } catch (err) {
    errorOrders.value =
      err?.response?.data?.message || err?.message || "Không tải được lịch sử đơn hàng";
    orders.value = [];
  } finally {
    loadingOrders.value = false;
  }
}

function goToPage(page) {
  if (page < 1 || page > pagination.value.last_page) return;
  loadOrders(page);
}

onMounted(async () => {
  await Promise.all([loadUser(id.value), loadOrders(1)]);
});
</script>

<template>
  <div class="page">
    <div class="topbar">
      <div class="topbar-left">
        <div>
          <div class="page-title">Chi tiết người dùng</div>
          <div class="page-subtitle">Xem thông tin tài khoản và lịch sử đơn hàng</div>
        </div>
      </div>

      <div class="topbar-actions">
        <button class="btn btn-ghost" type="button" @click="router.push('/admin/users')">
          Quay lại
        </button>
      </div>
    </div>

    <div v-if="errorUser" class="alert">
      <div class="alert-title">Lỗi</div>
      <div class="alert-body">{{ errorUser }}</div>
    </div>

    <div v-if="loadingUser" class="loading">Đang tải dữ liệu người dùng...</div>

    <div v-else class="wrap">
      <section class="section profile-card">
        <div class="profile-head">
          <div class="avatar-wrap">
            <img
              v-if="user.avatar"
              :src="previewSrc(user.avatar)"
              alt="avatar"
              class="avatar"
            />
            <div v-else class="avatar avatar-placeholder">
              {{ String(user.name || "U").charAt(0).toUpperCase() }}
            </div>
          </div>

          <div class="profile-meta">
            <div class="profile-name">{{ user.name || "Chưa có tên" }}</div>
            <div class="profile-email">{{ user.email || "—" }}</div>

            <div class="chips">
              <span class="chip role">
                {{ roleLabel(user.role) }}
              </span>

              <span class="chip" :class="user.is_active ? 'active' : 'inactive'">
                {{ user.is_active ? "Đang hoạt động" : "Đang khóa" }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <div class="tabs">
        <button
          class="tab-btn"
          :class="{ active: activeTab === 'info' }"
          type="button"
          @click="activeTab = 'info'"
        >
          Thông tin
        </button>

        <button
          class="tab-btn"
          :class="{ active: activeTab === 'orders' }"
          type="button"
          @click="activeTab = 'orders'"
        >
          Lịch sử đơn hàng
        </button>
      </div>

      <section v-if="activeTab === 'info'" class="section">
        <div class="section-head">
          <div class="section-icon">👤</div>
          <div class="section-title">Thông tin tài khoản</div>
        </div>

        <div class="section-body">
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">ID</div>
              <div class="info-value">{{ user.id ?? "—" }}</div>
            </div>

            <div class="info-item">
              <div class="info-label">Họ tên</div>
              <div class="info-value">{{ user.name || "—" }}</div>
            </div>

            <div class="info-item full">
              <div class="info-label">Email</div>
              <div class="info-value">{{ user.email || "—" }}</div>
            </div>

            <div class="info-item">
              <div class="info-label">Ngày sinh</div>
              <div class="info-value">{{ formatDate(user.birth_date) }}</div>
            </div>

            <div class="info-item">
              <div class="info-label">Số điện thoại</div>
              <div class="info-value">{{ user.phone || "—" }}</div>
            </div>

            <div class="info-item full">
              <div class="info-label">Địa chỉ</div>
              <div class="info-value">{{ user.address || "—" }}</div>
            </div>

            <div class="info-item">
              <div class="info-label">Quyền</div>
              <div class="info-value">{{ roleLabel(user.role) }}</div>
            </div>

            <div class="info-item">
              <div class="info-label">Trạng thái tài khoản</div>
              <div class="info-value">
                <span class="status-text" :class="user.is_active ? 'ok' : 'off'">
                  {{ user.is_active ? "Đang hoạt động" : "Đang khóa" }}
                </span>
              </div>
            </div>

            <div class="info-item">
              <div class="info-label">Ngày tạo</div>
              <div class="info-value">{{ formatDateTime(user.created_at) }}</div>
            </div>

            <div class="info-item">
              <div class="info-label">Cập nhật lần cuối</div>
              <div class="info-value">{{ formatDateTime(user.updated_at) }}</div>
            </div>
          </div>
        </div>
      </section>

      <section v-else class="section">
        <div class="section-head">
          <div class="section-icon">📦</div>
          <div class="section-title">Lịch sử đơn hàng</div>
        </div>

        <div class="section-body">
          <div v-if="errorOrders" class="inline-error">
            {{ errorOrders }}
          </div>

          <div v-else-if="loadingOrders" class="loading-inner">
            Đang tải lịch sử đơn hàng...
          </div>

          <template v-else>
            <div v-if="!orders.length" class="empty-state">
              Người dùng này chưa có đơn hàng nào.
            </div>

            <div v-else class="table-wrap">
              <table class="orders-table">
                <thead>
                  <tr>
                    <th>Mã đơn</th>
                    <th>Ngày tạo</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái đơn</th>
                    <th>Thanh toán</th>
                    <th class="center">Chi tiết</th>
                  </tr>
                </thead>

                <tbody>
                  <tr v-for="order in orders" :key="order.id">
                    <td>
                      <div class="order-code">{{ order.code || `#${order.id}` }}</div>
                    </td>

                    <td>{{ formatDateTime(order.created_at) }}</td>

                    <td>{{ formatCurrency(order.grand_total ?? order.total ?? 0) }}</td>

                    <td>
                      <span class="badge" :class="orderStatusClass(order.status)">
                        {{ orderStatusLabel(order.status) }}
                      </span>
                    </td>

                    <td>
                      <span
                        class="badge"
                        :class="orderStatusClass(order.payment_status || order.payment?.status)"
                      >
                        {{ paymentStatusLabel(order.payment_status || order.payment?.status) }}
                      </span>
                    </td>

                    <td class="center">
                      <button
                        class="btn btn-ghost btn-sm"
                        type="button"
                        @click="router.push(`/admin/orders/${order.id}`)"
                      >
                        Xem
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="pagination.last_page > 1" class="pagination">
              <button
                class="btn btn-ghost btn-sm"
                type="button"
                :disabled="pagination.current_page <= 1"
                @click="goToPage(pagination.current_page - 1)"
              >
                Trước
              </button>

              <div class="page-info">
                Trang {{ pagination.current_page }} / {{ pagination.last_page }}
                <span class="page-total">({{ pagination.total }} đơn)</span>
              </div>

              <button
                class="btn btn-ghost btn-sm"
                type="button"
                :disabled="pagination.current_page >= pagination.last_page"
                @click="goToPage(pagination.current_page + 1)"
              >
                Sau
              </button>
            </div>
          </template>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap");

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

.alert {
  max-width: 1360px;
  margin: 0 auto 16px;
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

.loading,
.loading-inner {
  color: #94a3b8;
  font-weight: 600;
  font-size: 14px;
}

.loading {
  max-width: 1360px;
  margin: 0 auto;
  padding: 40px 0;
}

.section {
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 16px;
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

.profile-head {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 20px;
}

.avatar-wrap {
  flex-shrink: 0;
}

.avatar {
  width: 88px;
  height: 88px;
  border-radius: 18px;
  object-fit: cover;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
}

.avatar-placeholder {
  display: grid;
  place-items: center;
  font-size: 28px;
  font-weight: 700;
  color: #475569;
  background: linear-gradient(135deg, #eff6ff, #e0e7ff);
}

.profile-meta {
  min-width: 0;
}

.profile-name {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}

.profile-email {
  margin-top: 6px;
  color: #64748b;
  font-size: 14px;
}

.chips {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 12px;
}

.chip {
  display: inline-flex;
  align-items: center;
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #334155;
}

.chip.role {
  background: #eef2ff;
  border-color: #c7d2fe;
  color: #4338ca;
}

.chip.active {
  background: #ecfdf5;
  border-color: #bbf7d0;
  color: #15803d;
}

.chip.inactive {
  background: #fef2f2;
  border-color: #fecaca;
  color: #dc2626;
}

.tabs {
  max-width: 1360px;
  margin: 0 auto 16px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.tab-btn {
  border: 1.5px solid #e2e8f0;
  background: #fff;
  color: #475569;
  padding: 10px 16px;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 700;
  font-size: 13px;
  transition: all 0.15s;
}

.tab-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.tab-btn.active {
  color: #2563eb;
  border-color: #93c5fd;
  background: #eff6ff;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 18px;
}

@media (max-width: 780px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.info-item.full {
  grid-column: 1 / -1;
}

.info-label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-value {
  min-height: 42px;
  display: flex;
  align-items: center;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 13px;
  background: #fafbfd;
  color: #0f172a;
  font-size: 14px;
  word-break: break-word;
}

.status-text.ok {
  color: #15803d;
  font-weight: 700;
}

.status-text.off {
  color: #dc2626;
  font-weight: 700;
}

.inline-error {
  border: 1px solid rgba(239, 68, 68, 0.18);
  background: rgba(239, 68, 68, 0.05);
  color: #b91c1c;
  border-radius: 10px;
  padding: 12px 14px;
  font-size: 13px;
  font-weight: 600;
}

.empty-state {
  border: 1.5px dashed #dbe3f0;
  border-radius: 12px;
  padding: 28px 18px;
  text-align: center;
  color: #64748b;
  font-weight: 600;
  background: #fafcff;
}

.table-wrap {
  overflow: auto;
  border: 1.5px solid #e8ecf4;
  border-radius: 12px;
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}

.orders-table thead th {
  background: #f5f7ff;
  border-bottom: 1.5px solid #e8ecf4;
  padding: 12px;
  text-align: left;
  font-size: 11px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-weight: 700;
}

.orders-table td {
  border-top: 1px solid #f1f4fa;
  padding: 12px;
  vertical-align: middle;
  font-size: 13px;
  color: #0f172a;
}

.orders-table tr:hover td {
  background: #fafbff;
}

.order-code {
  font-weight: 700;
  color: #111827;
}

.center {
  text-align: center;
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 700;
  border: 1px solid transparent;
}

.badge.success {
  background: #ecfdf5;
  border-color: #bbf7d0;
  color: #15803d;
}

.badge.warning {
  background: #fffbeb;
  border-color: #fde68a;
  color: #b45309;
}

.badge.danger {
  background: #fef2f2;
  border-color: #fecaca;
  color: #dc2626;
}

.badge.default {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #475569;
}

.pagination {
  margin-top: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.page-info {
  font-size: 13px;
  font-weight: 700;
  color: #334155;
}

.page-total {
  color: #64748b;
  font-weight: 600;
  margin-left: 4px;
}

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

.btn-sm {
  padding: 7px 12px;
  font-size: 12px;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>