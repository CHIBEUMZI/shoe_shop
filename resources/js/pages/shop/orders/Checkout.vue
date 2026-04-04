<template>
  <main class="min-h-screen py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8 flex items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
            Thanh toán
          </h1>
          <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Vui lòng kiểm tra thông tin nhận hàng và xác nhận đơn hàng của bạn.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-primary text-primary font-medium hover:bg-primary/10 transition-colors"
          @click="goCart"
        >
          <span class="material-symbols-outlined text-sm">arrow_back</span>
          Quay lại giỏ hàng
        </button>
      </div>

      <div v-if="pageLoading" class="text-sm text-slate-500">Đang tải dữ liệu...</div>
      <div v-else-if="pageError" class="text-sm text-red-600">{{ pageError }}</div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-8 space-y-6">
          <section class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-5">
              Thông tin nhận hàng
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                  Họ và tên <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.customer_name"
                  type="text"
                  class="w-full rounded-md border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                  placeholder="Nhập họ và tên"
                />
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                  Số điện thoại <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.customer_phone"
                  type="text"
                  class="w-full rounded-md border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                  placeholder="Nhập số điện thoại"
                />
              </div>

              <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                  Email
                </label>
                <input
                  v-model="form.customer_email"
                  type="email"
                  class="w-full rounded-md border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                  placeholder="Nhập email"
                />
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                  Tỉnh / Thành phố
                </label>
                <div class="relative">
                  <select
                    v-model="form.province_obj"
                    @change="selectProvince"
                    :disabled="loadingProvinces"
                    class="w-full rounded-md border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 appearance-none cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    <option :value="null">Chọn tỉnh / thành phố</option>
                    <option v-for="prov in provinces" :key="prov.value" :value="prov">
                      {{ prov.label }}
                    </option>
                  </select>
                  <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 dark:text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                  </span>
                </div>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                  Quận / Huyện
                </label>
                <div class="relative">
                  <select
                    v-model="form.district_obj"
                    @change="selectDistrict"
                    :disabled="!form.province_obj || loadingDistricts"
                    class="w-full rounded-md border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 appearance-none cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    <option :value="null">Chọn quận / huyện</option>
                    <option v-for="dist in districts" :key="dist.value" :value="dist">
                      {{ dist.label }}
                    </option>
                  </select>
                  <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 dark:text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                  </span>
                </div>
              </div>

              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                  Phường / Xã
                </label>
                <div class="relative">
                  <select
                    v-model="form.ward_obj"
                    @change="selectWard"
                    :disabled="!form.district_obj || loadingWards"
                    class="w-full rounded-md border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 appearance-none cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    <option :value="null">Chọn phường / xã</option>
                    <option v-for="w in wards" :key="w.value" :value="w">
                      {{ w.label }}
                    </option>
                  </select>
                  <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 dark:text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                  </span>
                </div>
              </div>

              <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                  Địa chỉ chi tiết <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.address_line"
                  type="text"
                  class="w-full rounded-md border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                  placeholder="Số nhà, tên đường..."
                />
              </div>

              <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">
                  Ghi chú
                </label>
                <textarea
                  v-model="form.note"
                  rows="4"
                  class="w-full rounded-md border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                  placeholder="Ví dụ: Giao giờ hành chính..."
                ></textarea>
              </div>
            </div>
          </section>

          <section class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-5">
              Phương thức vận chuyển
            </h2>
            <div class="space-y-3">
              <label class="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-4 cursor-pointer">
                <input v-model="form.shipping_method" type="radio" value="standard" class="mt-1" />
                <div class="flex-1">
                  <div class="font-semibold text-slate-900 dark:text-slate-100">Giao hàng tiêu chuẩn</div>
                  <div class="text-sm text-slate-500 dark:text-slate-400">Thời gian dự kiến 3 - 5 ngày</div>
                </div>
                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ moneyVND(15000) }}</div>
              </label>
              <label class="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-4 cursor-pointer">
                <input v-model="form.shipping_method" type="radio" value="express" class="mt-1" />
                <div class="flex-1">
                  <div class="font-semibold text-slate-900 dark:text-slate-100">Giao hàng nhanh</div>
                  <div class="text-sm text-slate-500 dark:text-slate-400">Thời gian dự kiến 1 - 2 ngày</div>
                </div>
                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ moneyVND(30000) }}</div>
              </label>
            </div>
          </section>

          <section class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-5">
              Phương thức thanh toán
            </h2>
            <div class="space-y-3">
              <label class="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-4 cursor-pointer">
                <input v-model="form.payment_method" type="radio" value="cod" class="mt-1" />
                <div>
                  <div class="font-semibold text-slate-900 dark:text-slate-100">Thanh toán khi nhận hàng (COD)</div>
                  <div class="text-sm text-slate-500 dark:text-slate-400">Bạn thanh toán bằng tiền mặt khi nhận hàng.</div>
                </div>
              </label>
              <label class="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-4 cursor-pointer">
                <input v-model="form.payment_method" type="radio" value="momo" class="mt-1" />
                <div>
                  <div class="font-semibold text-slate-900 dark:text-slate-100">Thanh toán online (MoMo)</div>
                  <div class="text-sm text-slate-500 dark:text-slate-400">Bạn sẽ được chuyển sang cổng thanh toán MoMo để hoàn tất đơn hàng.</div>
                </div>
              </label>
              <label class="flex items-start gap-3 rounded-lg border border-slate-200 dark:border-slate-700 p-4 cursor-pointer">
                <input v-model="form.payment_method" type="radio" value="vnpay" class="mt-1" />
                <div>
                  <div class="font-semibold text-slate-900 dark:text-slate-100">Thanh toán online (VNPay)</div>
                  <div class="text-sm text-slate-500 dark:text-slate-400">Bạn sẽ được chuyển sang cổng thanh toán VNPay để hoàn tất đơn hàng.</div>
                </div>
              </label>
            </div>
          </section>

          <div v-if="submitError" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
            {{ submitError }}
          </div>
        </div>

        <div class="lg:col-span-4">
          <div class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-6 shadow-md sticky top-8">
            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-5">Đơn hàng của bạn</h2>

            <div v-if="items.length === 0" class="text-sm text-slate-500">Giỏ hàng đang trống.</div>

            <div v-else class="space-y-4">
              <div
                v-for="it in items"
                :key="it.id"
                class="flex gap-3 border-b border-slate-100 dark:border-slate-700 pb-4"
              >
                <div class="h-16 w-16 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-900 flex-shrink-0">
                  <img
                    :src="buildImageUrl(it.product?.thumbnail) || fallbackImage"
                    :alt="it.product?.name || 'Sản phẩm'"
                    class="h-full w-full object-cover"
                  />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="font-semibold text-sm text-slate-900 dark:text-slate-100 line-clamp-2">
                    {{ it.product?.name || "Sản phẩm" }}
                  </div>
                  <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span v-if="it.variant?.size">Size: {{ it.variant.size }}</span>
                    <span v-if="it.variant?.size && it.variant?.color"> | </span>
                    <span v-if="it.variant?.color">Màu: {{ it.variant.color }}</span>
                  </div>
                  <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Số lượng: {{ it.quantity }}</div>
                </div>
                <div class="text-sm font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                  {{ moneyVND(it.line_total || computedLineTotal(it)) }}
                </div>
              </div>

              <div class="rounded-lg bg-slate-50 dark:bg-slate-700/30 p-4">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">
                  Mã giảm giá
                </h3>
                <div class="flex gap-2">
                  <input
                    v-model="couponCode"
                    type="text"
                    class="flex-1 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm outline-none focus:border-primary"
                    placeholder="Nhập mã giảm giá"
                    :disabled="applyingCoupon"
                  />
                  <button
                    v-if="!appliedCoupon"
                    type="button"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-50"
                    :disabled="!couponCode.trim() || applyingCoupon"
                    @click="applyCoupon"
                  >
                    {{ applyingCoupon ? "..." : "Áp dụng" }}
                  </button>
                  <button
                    v-else
                    type="button"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-100"
                    @click="removeCoupon"
                  >
                    Bỏ
                  </button>
                </div>

                <div v-if="couponError" class="mt-2 text-xs text-red-500">
                  {{ couponError }}
                </div>
                <div v-if="appliedCoupon" class="mt-2 text-xs text-emerald-600 dark:text-emerald-400">
                  <span class="font-medium">{{ appliedCoupon.name }}</span>
                  - Giảm {{ appliedCoupon.type === 'percentage' ? appliedCoupon.value + '%' : moneyVND(appliedCoupon.value) }}
                </div>
              </div>

              <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-400">
                  <span>Tạm tính</span>
                  <span class="font-medium text-slate-900 dark:text-slate-100">{{ moneyVND(subtotal) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-400">
                  <span>Phí vận chuyển</span>
                  <span class="font-medium text-slate-900 dark:text-slate-100">{{ moneyVND(shippingFee) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm text-emerald-600 dark:text-emerald-400">
                  <span>Giảm giá</span>
                  <span class="font-medium">-{{ moneyVND(discountAmount) }}</span>
                </div>
                <div class="border-t border-slate-100 dark:border-slate-700 pt-4 flex items-center justify-between">
                  <span class="text-base font-bold text-slate-900 dark:text-slate-100">Tổng thanh toán</span>
                  <span class="text-xl font-bold text-primary">{{ moneyVND(grandTotal) }}</span>
                </div>
              </div>

              <button
                type="button"
                class="mt-6 w-full bg-primary hover:bg-primary/90 text-white font-bold py-3.5 rounded-lg shadow-md transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="submitting || items.length === 0"
                @click="submitOrder"
              >
                {{ submitting ? "Đang xử lý..." : buttonText }}
              </button>

              <p class="text-xs text-center text-slate-500 dark:text-slate-400">
                Bằng việc đặt hàng, bạn đồng ý với các điều khoản mua hàng của Shoe Shop.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useCartStore } from "../../../stores/cart";
import { buildImageUrl } from "../../../utils/image";
import orderService from "../../../services/public/orderService";
import couponService from "../../../services/public/couponService";
import { useAlert } from "../../../composables/useAlert";
import { useAddress } from "../../../composables/useAddress";

const router = useRouter();
const route = useRoute();
const cartStore = useCartStore();
const notify = useAlert();

const pageLoading = ref(true);
const pageError = ref("");
const submitting = ref(false);
const submitError = ref("");
const couponCode = ref("");
const applyingCoupon = ref(false);
const couponError = ref("");
const appliedCoupon = ref(null);
const discountAmount = ref(0);

const fallbackImage = "https://via.placeholder.com/400x400?text=Shoe";

const form = reactive({
  customer_name: "",
  customer_phone: "",
  customer_email: "",
  province_obj: null,
  district_obj: null,
  ward_obj: null,
  province: "",
  district: "",
  ward: "",
  address_line: "",
  note: "",
  shipping_method: "standard",
  payment_method: "cod",
});

const { provinces, districts, wards, loadingProvinces, loadingDistricts, loadingWards } =
  useAddress(form);

const items = computed(() => cartStore.cart?.items ?? []);

const subtotal = computed(() =>
  items.value.reduce((sum, it) => sum + Number(it.line_total || computedLineTotal(it)), 0)
);

const shippingFee = computed(() => (form.shipping_method === "express" ? 30000 : 15000));

const grandTotal = computed(() => Math.max(0, subtotal.value + shippingFee.value - discountAmount.value));

const buttonText = computed(() => {
  if (form.payment_method === "vnpay") return "Tiếp tục đến VNPay";
  if (form.payment_method === "momo") return "Tiếp tục đến MoMo";
  return "Đặt hàng";
});

onMounted(async () => {
  pageLoading.value = true;
  pageError.value = "";
  submitError.value = "";
  try {
    await cartStore.fetchCart();
    if (!items.value.length) {
      pageError.value = "Giỏ hàng đang trống. Vui lòng thêm sản phẩm trước khi thanh toán.";
      return;
    }
    const failMsg = route.query.message;
    if (failMsg) submitError.value = String(failMsg);
  } catch {
    pageError.value = cartStore.error || "Không tải được giỏ hàng.";
  } finally {
    pageLoading.value = false;
  }
});

function moneyVND(v) {
  return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(Number(v || 0));
}

function computedUnitPrice(it) {
  const variant = it.variant || {};
  const product = it.product || {};
  const variantSale = Number(variant.sale_price || 0);
  const variantPrice = Number(variant.price || 0);
  const productSale = Number(product.base_sale_price || 0);
  const productPrice = Number(product.base_price || 0);
  if (variantSale > 0) return variantSale;
  if (variantPrice > 0) return variantPrice;
  if (productSale > 0) return productSale;
  return productPrice;
}

function computedLineTotal(it) {
  return computedUnitPrice(it) * Number(it.quantity || 0);
}

function goCart() {
  router.push("/shop/cart");
}

async function applyCoupon() {
  if (!couponCode.value.trim()) return;

  applyingCoupon.value = true;
  couponError.value = "";

  try {
    const res = await couponService.validateCoupon(couponCode.value.trim().toUpperCase());
    const data = res?.data;

    if (data?.valid) {
      appliedCoupon.value = data.coupon;
      discountAmount.value = Number(data.discount) || 0;
      couponError.value = "";
      const msg = data.message || "";
      if (discountAmount.value <= 0) {
        notify.warning(msg || "Mã hợp lệ nhưng không áp dụng được cho giỏ hàng hiện tại.", {
          title: "Lưu ý",
          duration: 3500,
        });
      } else {
        notify.success(msg || "Áp dụng mã giảm giá thành công.", {
          title: "Thành công",
          duration: 2000,
        });
      }
    } else {
      couponError.value = data?.message || "Mã giảm giá không hợp lệ.";
      appliedCoupon.value = null;
      discountAmount.value = 0;
    }
  } catch (e) {
    const errMsg = e?.response?.data?.message || "Mã giảm giá không hợp lệ hoặc đã hết hạn.";
    couponError.value = errMsg;
    appliedCoupon.value = null;
    discountAmount.value = 0;
  } finally {
    applyingCoupon.value = false;
  }
}

function removeCoupon() {
  appliedCoupon.value = null;
  discountAmount.value = 0;
  couponCode.value = "";
  couponError.value = "";
}

function validateForm() {
  if (!form.customer_name.trim()) return "Vui lòng nhập họ và tên.";
  if (!form.customer_phone.trim()) return "Vui lòng nhập số điện thoại.";
  if (!form.address_line.trim()) return "Vui lòng nhập địa chỉ chi tiết.";
  if (!form.shipping_method) return "Vui lòng chọn phương thức vận chuyển.";
  if (!form.payment_method) return "Vui lòng chọn phương thức thanh toán.";
  if (!["cod", "vnpay", "momo"].includes(form.payment_method))
    return "Phương thức thanh toán chưa được hỗ trợ.";
  return "";
}

async function submitOrder() {
  submitError.value = "";
  const msg = validateForm();
  if (msg) {
    submitError.value = msg;
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      customer_name: form.customer_name,
      customer_phone: form.customer_phone,
      customer_email: form.customer_email || null,
      province: form.province || null,
      district: form.district || null,
      ward: form.ward || null,
      address_line: form.address_line,
      note: form.note || null,
      shipping_method: form.shipping_method,
      payment_method: form.payment_method,
      coupon_code: appliedCoupon.value ? appliedCoupon.value.code : null,
    };

    const res = await orderService.createOrder(payload);
    const order = res?.data?.data || {};

    if (!order?.id) throw new Error("Không tạo được đơn hàng.");

    if (form.payment_method === "cod") {
      notify.success("Đặt hàng thành công", { title: "Thành công", duration: 2200 });
      router.push(`/shop/orders/success/${order.id}`);
      return;
    }

    const payRes = await orderService.createPayment(order.id);
    const paymentUrl = payRes?.data?.data?.payment_url;

    if (!paymentUrl) {
      throw new Error(
        form.payment_method === "momo"
          ? "Không tạo được liên kết thanh toán MoMo."
          : "Không tạo được liên kết thanh toán VNPay."
      );
    }

    window.location.href = paymentUrl;
  } catch (e) {
    const errMsg = e?.response?.data?.message || e?.message || "Không thể tạo đơn hàng. Vui lòng thử lại.";
    submitError.value = errMsg;
    notify.error(errMsg, { title: "Đặt hàng thất bại", duration: 4000 });
  } finally {
    submitting.value = false;
  }
}

function selectProvince() {
  if (form.province_obj) {
    form.province = form.province_obj.label || "";
  } else {
    form.province = "";
  }
  form.district_obj = null;
  form.ward_obj = null;
  form.district = "";
  form.ward = "";
}

function selectDistrict() {
  if (form.district_obj) {
    form.district = form.district_obj.label || "";
  } else {
    form.district = "";
  }
  form.ward_obj = null;
  form.ward = "";
}

function selectWard() {
  if (form.ward_obj) {
    form.ward = form.ward_obj.label || "";
  } else {
    form.ward = "";
  }
}
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
}
</style>
