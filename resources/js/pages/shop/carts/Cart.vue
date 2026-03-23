<template>
  <main class="min-h-screen py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Top title -->
      <div class="mb-10 flex items-center justify-between gap-4">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
          Trang giỏ hàng của bạn
        </h1>

        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-primary text-primary font-medium hover:bg-primary/10 transition-colors"
          @click="goShop"
        >
          <span class="material-symbols-outlined text-sm">arrow_back</span>
          Tiếp tục mua sắm
        </button>
      </div>

      <!-- Loading / Error -->
      <div v-if="pageLoading" class="text-sm text-slate-500">Đang tải giỏ hàng...</div>
      <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        <!-- LEFT: Items -->
        <div class="lg:col-span-8 space-y-6">
          <div
            class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
          >
            <div v-if="items.length === 0" class="p-6 text-slate-500">
              Giỏ hàng đang trống.
            </div>

            <template v-else>
              <div
                v-for="(it, idx) in items"
                :key="it.id"
                class="p-6 flex flex-col sm:flex-row items-start gap-6"
                :class="idx !== 0
                  ? 'border-t border-slate-100 dark:border-slate-700'
                  : ''"
              >
                <!-- image -->
                <div
                  class="h-32 w-32 flex-shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-900"
                >
                  <img
                    class="h-full w-full object-cover object-center"
                    :src="buildImageUrl(it.product?.thumbnail) || fallbackImage"
                    :alt="it.product?.name || 'Sản phẩm'"
                  />
                </div>

                <!-- info -->
                <div class="flex flex-1 flex-col w-full min-w-0">
                  <div
                    class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 text-base font-semibold text-slate-900 dark:text-slate-100"
                  >
                    <h3 class="pr-4 break-words">
                      {{ it.product?.name || "Sản phẩm" }}
                    </h3>

                    <p class="whitespace-nowrap">
                      {{ moneyVND(it.unit_price) }}
                    </p>
                  </div>

                  <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    <span v-if="it.variant?.size">Kích thước: {{ it.variant.size }}</span>
                    <span v-if="it.variant?.size && it.variant?.color"> | </span>
                    <span v-if="it.variant?.color">Màu sắc: {{ it.variant.color }}</span>
                  </p>

                  <div
                    class="flex flex-col sm:flex-row sm:items-end sm:justify-between text-sm mt-4 gap-4"
                  >
                    <!-- qty -->
                    <div class="flex items-center gap-3">
                      <div
                        class="flex items-center border border-slate-200 dark:border-slate-600 rounded-lg overflow-hidden"
                      >
                        <button
                          type="button"
                          class="px-3 py-2 bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                          :disabled="isItemBusy(it.id) || Number(it.quantity) <= 1"
                          @click="setQty(it, Number(it.quantity) - 1)"
                          aria-label="Giảm số lượng"
                        >
                          <span class="material-symbols-outlined text-lg leading-none">remove</span>
                        </button>

                        <input
                          class="w-14 border-0 text-center text-sm font-medium focus:ring-0 dark:bg-slate-800"
                          type="number"
                          min="1"
                          :max="it.variant?.stock ?? undefined"
                          :value="it.quantity"
                          :disabled="isItemBusy(it.id)"
                          @change="onQtyInput(it, $event)"
                        />

                        <button
                          type="button"
                          class="px-3 py-2 bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                          :disabled="isItemBusy(it.id) || reachedStockLimit(it)"
                          @click="setQty(it, Number(it.quantity) + 1)"
                          aria-label="Tăng số lượng"
                        >
                          <span class="material-symbols-outlined text-lg leading-none">add</span>
                        </button>
                      </div>

                      <span
                        v-if="isItemBusy(it.id)"
                        class="text-xs text-slate-500 dark:text-slate-400"
                      >
                        Đang cập nhật...
                      </span>
                    </div>

                    <!-- remove + line total -->
                    <div class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto">
                      <div
                        class="font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap"
                      >
                        {{ moneyVND(it.line_total) }}
                      </div>

                      <button
                        type="button"
                        class="flex items-center gap-1 font-medium text-red-600 hover:text-red-500 dark:text-red-400 disabled:opacity-40 disabled:cursor-not-allowed"
                        :disabled="isItemBusy(it.id) || clearBusy"
                        @click="remove(it)"
                      >
                        <span class="material-symbols-outlined text-lg" title="Xóa">delete</span>
                      </button>
                    </div>
                  </div>

                  <p
                    v-if="it.variant?.stock !== null && it.variant?.stock !== undefined"
                    class="mt-2 text-xs text-slate-500"
                  >
                    Tồn kho:
                    <b class="text-slate-900 dark:text-slate-100">{{ it.variant.stock }}</b>
                  </p>
                </div>
              </div>
            </template>
          </div>

          <!-- clear -->
          <div v-if="items.length" class="flex justify-end">
            <button
              type="button"
              class="text-sm font-semibold text-red-600 hover:underline disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="clearBusy || hasBusyItem"
              @click="clearAll"
            >
              {{ clearBusy ? "Đang xóa..." : "Xóa giỏ hàng" }}
            </button>
          </div>
        </div>

        <!-- RIGHT: summary -->
        <div class="lg:col-span-4">
          <div
            class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 p-8 sticky top-8"
          >
            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6">
              Tóm tắt đơn hàng
            </h2>

            <div class="space-y-4">
              <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <span>Tạm tính</span>
                <span class="font-medium text-slate-900 dark:text-slate-100">
                  {{ moneyVND(summary.subtotal || 0) }}
                </span>
              </div>

              <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <span>Phí vận chuyển</span>
                <span class="font-medium text-slate-900 dark:text-slate-100">
                  {{ moneyVND(summary.shipping_fee || 0) }}
                </span>
              </div>

              <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <span>Giảm giá</span>
                <span class="font-medium text-slate-900 dark:text-slate-100">
                  {{ moneyVND(summary.discount_total || 0) }}
                </span>
              </div>

              <div class="pt-4 border-t border-slate-100 dark:border-slate-700">
                <label
                  class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                  for="promo"
                >
                  Mã khuyến mãi
                </label>

                <div class="flex gap-2">
                  <input
                    id="promo"
                    v-model="promoCode"
                    class="rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                    placeholder="Nhập mã"
                    type="text"
                  />

                  <button
                    type="button"
                    class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-sm font-semibold rounded-lg transition-colors"
                    @click="applyPromo"
                  >
                    Áp dụng
                  </button>
                </div>

                <p v-if="promoMsg" class="mt-2 text-xs text-slate-500">{{ promoMsg }}</p>
              </div>

              <div
                class="pt-6 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between"
              >
                <span class="text-lg font-bold text-slate-900 dark:text-slate-100">
                  Tổng thanh toán
                </span>
                <span class="text-2xl font-bold text-primary">
                  {{ moneyVND(summary.grand_total || 0) }}
                </span>
              </div>
            </div>

            <div class="mt-8 space-y-4">
              <button
                type="button"
                class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-lg shadow-md transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="items.length === 0 || hasBusyItem || clearBusy"
                @click="checkout"
              >
                Thanh toán
              </button>

              <p
                class="text-xs text-center text-slate-500 dark:text-slate-400 flex items-center justify-center gap-1"
              >
                <span class="material-symbols-outlined text-base">lock</span>
                Thanh toán an toàn bởi BMC Shoes
              </p>
            </div>

            <div class="mt-8 grid grid-cols-4 gap-4 opacity-50 grayscale">
              <div
                class="h-8 bg-slate-200 dark:bg-slate-700 rounded flex items-center justify-center text-[10px] font-bold"
              >
                VISA
              </div>
              <div
                class="h-8 bg-slate-200 dark:bg-slate-700 rounded flex items-center justify-center text-[10px] font-bold"
              >
                MC
              </div>
              <div
                class="h-8 bg-slate-200 dark:bg-slate-700 rounded flex items-center justify-center text-[10px] font-bold"
              >
                AMEX
              </div>
              <div
                class="h-8 bg-slate-200 dark:bg-slate-700 rounded flex items-center justify-center text-[10px] font-bold"
              >
                PP
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { useCartStore } from "../../../stores/cart";
import { buildImageUrl } from "../../../utils/image";

const router = useRouter();
const cartStore = useCartStore();

const promoCode = ref("");
const promoMsg = ref("");
const pageLoading = ref(true);
const clearBusy = ref(false);
const busyMap = ref({});

const fallbackImage = "https://via.placeholder.com/400x400?text=Shoe";

const error = computed(() => cartStore.error);
const items = computed(() => cartStore.cart?.items ?? []);
const summary = computed(() => cartStore.cart?.summary ?? {});

const hasBusyItem = computed(() => Object.values(busyMap.value).some(Boolean));

onMounted(async () => {
  pageLoading.value = true;
  try {
    await cartStore.fetchCart();
  } catch (e) {
    // lỗi đã được store xử lý vào cartStore.error
  } finally {
    pageLoading.value = false;
  }
});

function moneyVND(v) {
  const n = Number(v || 0);
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(n);
}

function goShop() {
  router.push("/shop/products");
}

function isItemBusy(id) {
  return !!busyMap.value[id];
}

function setItemBusy(id, state) {
  busyMap.value = {
    ...busyMap.value,
    [id]: state,
  };
}

function reachedStockLimit(it) {
  const stock = it.variant?.stock;
  if (stock === null || stock === undefined) return false;
  return Number(it.quantity) >= Number(stock);
}

async function setQty(it, q) {
  const next = Math.max(1, Number(q || 1));
  const current = Number(it.quantity || 1);

  const stock = it.variant?.stock;
  if (stock !== null && stock !== undefined && Number.isFinite(Number(stock))) {
    const s = Number(stock);
    if (s > 0 && next > s) return;
  }

  if (next === current) return;

  setItemBusy(it.id, true);
  try {
    await cartStore.updateQty(it.id, next);
  } catch (e) {
    alert(cartStore.error || "Không cập nhật được số lượng");
  } finally {
    setItemBusy(it.id, false);
  }
}

function onQtyInput(it, ev) {
  const raw = String(ev.target.value ?? "").replace(/[^\d]/g, "");
  let val = Number(raw) || 1;

  const stock = it.variant?.stock;
  if (stock !== null && stock !== undefined && Number.isFinite(Number(stock))) {
    const s = Number(stock);
    if (s > 0) {
      val = Math.min(val, s);
    }
  }

  ev.target.value = val;
  setQty(it, val);
}

async function remove(it) {
  setItemBusy(it.id, true);
  try {
    await cartStore.removeItem(it.id);
  } catch (e) {
    alert(cartStore.error || "Không xóa được sản phẩm");
  } finally {
    setItemBusy(it.id, false);
  }
}

async function clearAll() {
  if (!confirm("Xóa toàn bộ giỏ hàng?")) return;

  clearBusy.value = true;
  try {
    await cartStore.clearCart();
  } catch (e) {
    alert(cartStore.error || "Không xóa được giỏ hàng");
  } finally {
    clearBusy.value = false;
  }
}

function applyPromo() {
  promoMsg.value = "Chưa có API mã khuyến mãi. Bước tiếp theo có thể thêm coupon hoặc giảm giá.";
}

function checkout() {
  router.push("/shop/checkout");
}
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
}
</style>