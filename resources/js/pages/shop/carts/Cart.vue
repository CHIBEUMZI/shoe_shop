<template>
  <main class="min-h-screen pb-28 lg:pb-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Top title -->
      <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
          Giỏ hàng
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
      <div v-if="pageLoading" class="flex items-center justify-center py-20">
        <div class="w-8 h-8 border-3 border-primary/30 border-t-primary rounded-full animate-spin"></div>
        <span class="ml-3 text-slate-500">Đang tải giỏ hàng...</span>
      </div>
      <div v-else-if="error" class="p-6 bg-red-50 dark:bg-red-900/20 rounded-lg text-red-600 dark:text-red-400 text-center">
        {{ error }}
      </div>

      <div v-else-if="items.length === 0" class="text-center py-20">
        <div class="w-24 h-24 mx-auto mb-4 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center">
          <span class="material-symbols-outlined text-5xl text-slate-400">shopping_cart</span>
        </div>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-2">Giỏ hàng trống</h2>
        <p class="text-slate-500 dark:text-slate-400 mb-6">Hãy thêm sản phẩm vào giỏ hàng của bạn</p>
        <button
          type="button"
          class="px-6 py-3 bg-primary hover:bg-primary/90 text-white font-semibold rounded-lg transition-colors"
          @click="goShop"
        >
          Mua sắm ngay
        </button>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT: Items -->
        <div class="lg:col-span-8 space-y-4">
          <!-- Shopee-style header -->
          <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <!-- Select all header -->
            <div class="px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
              <label class="flex items-center gap-3 cursor-pointer select-none">
                <div
                  class="w-5 h-5 rounded border-2 flex items-center justify-center transition-all"
                  :class="allSelected
                    ? 'bg-primary border-primary'
                    : 'border-slate-300 dark:border-slate-600 hover:border-primary'"
                  @click="toggleSelectAll"
                >
                  <svg v-if="allSelected" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                  Chọn tất cả
                </span>
              </label>

              <div class="flex items-center gap-4">
                <span class="text-sm text-slate-500 dark:text-slate-400">
                  {{ selectedCount }}/{{ items.length }} sản phẩm
                </span>
                <button
                  v-if="selectedCount > 0"
                  type="button"
                  class="text-sm font-medium text-red-500 hover:text-red-600 hover:underline transition-colors"
                  @click="removeSelected"
                >
                  Xóa
                </button>
              </div>
            </div>

            <!-- Items list -->
            <div
              v-for="(it, idx) in items"
              :key="it.id"
              class="px-4 py-4 flex items-start gap-4 transition-colors"
              :class="[
                idx !== 0 ? 'border-t border-slate-100 dark:border-slate-700' : '',
                selectedItems.has(it.id) ? 'bg-primary/5' : 'bg-white dark:bg-slate-800'
              ]"
            >
              <!-- checkbox -->
              <div
                class="w-5 h-5 mt-8 flex-shrink-0 rounded border-2 flex items-center justify-center cursor-pointer transition-all"
                :class="selectedItems.has(it.id)
                  ? 'bg-primary border-primary'
                  : 'border-slate-300 dark:border-slate-600 hover:border-primary'"
                @click="toggleItem(it.id)"
              >
                <svg v-if="selectedItems.has(it.id)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>

              <!-- image -->
              <div
                class="w-20 h-20 flex-shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-900 cursor-pointer"
                @click="goProduct(it.product?.slug)"
              >
                <img
                  class="h-full w-full object-cover object-center"
                  :src="buildImageUrl(it.product?.thumbnail) || fallbackImage"
                  :alt="it.product?.name || 'Sản phẩm'"
                />
              </div>

              <!-- info -->
              <div class="flex-1 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                  <div class="flex-1 min-w-0">
                    <h3
                      class="text-sm font-medium text-slate-900 dark:text-slate-100 line-clamp-2 cursor-pointer hover:text-primary transition-colors"
                      @click="goProduct(it.product?.slug)"
                    >
                      {{ it.product?.name || "Sản phẩm" }}
                    </h3>

                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                      <span v-if="it.variant?.size">Kích thước: {{ it.variant.size }}</span>
                      <span v-if="it.variant?.size && it.variant?.color"> · </span>
                      <span v-if="it.variant?.color">Màu: {{ it.variant.color }}</span>
                    </p>
                  </div>

                  <!-- price -->
                  <div class="text-right flex-shrink-0">
                    <p class="text-base font-semibold text-primary">
                      {{ moneyVND(it.unit_price) }}
                    </p>
                    <p
                      v-if="it.variant?.stock !== null && it.variant?.stock !== undefined"
                      class="text-xs text-slate-400 mt-0.5"
                    >
                      Tồn kho: {{ it.variant.stock }}
                    </p>
                  </div>
                </div>

                <!-- qty + actions -->
                <div class="flex items-center justify-between mt-3">
                  <!-- qty -->
                  <div class="flex items-center">
                    <div class="flex items-center border border-slate-200 dark:border-slate-600 rounded-md overflow-hidden">
                      <button
                        type="button"
                        class="w-8 h-8 flex items-center justify-center bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        :disabled="isItemBusy(it.id) || Number(it.quantity) <= 1"
                        @click="setQty(it, Number(it.quantity) - 1)"
                      >
                        <span class="material-symbols-outlined text-base leading-none">remove</span>
                      </button>

                      <input
                        class="w-12 h-8 border-0 border-x border-slate-200 dark:border-slate-600 text-center text-sm font-medium focus:ring-0 bg-white dark:bg-slate-800"
                        type="number"
                        min="1"
                        :max="it.variant?.stock ?? undefined"
                        :value="it.quantity"
                        :disabled="isItemBusy(it.id)"
                        @change="onQtyInput(it, $event)"
                      />

                      <button
                        type="button"
                        class="w-8 h-8 flex items-center justify-center bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        :disabled="isItemBusy(it.id) || reachedStockLimit(it)"
                        @click="setQty(it, Number(it.quantity) + 1)"
                      >
                        <span class="material-symbols-outlined text-base leading-none">add</span>
                      </button>
                    </div>

                    <span
                      v-if="isItemBusy(it.id)"
                      class="ml-2 text-xs text-slate-500"
                    >
                      Đang cập nhật...
                    </span>
                  </div>

                  <!-- line total + remove -->
                  <div class="flex items-center gap-4">
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                      {{ moneyVND(it.line_total) }}
                    </p>

                    <button
                      type="button"
                      class="p-1.5 text-slate-400 hover:text-red-500 transition-colors disabled:opacity-40"
                      :disabled="isItemBusy(it.id)"
                      @click="remove(it)"
                    >
                      <span class="material-symbols-outlined text-xl">delete</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: summary -->
        <div class="lg:col-span-4">
          <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5 sticky top-20">
            <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4">
              Tóm tắt đơn hàng
            </h2>

            <!-- Selected items summary -->
            <div class="space-y-3 pb-4 border-b border-slate-100 dark:border-slate-700">
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600 dark:text-slate-400">
                  Sản phẩm đã chọn
                </span>
                <span class="font-medium text-slate-900 dark:text-slate-100">
                  {{ selectedCount }}
                </span>
              </div>

              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600 dark:text-slate-400">
                  Tạm tính
                </span>
                <span class="font-medium text-slate-900 dark:text-slate-100">
                  {{ moneyVND(selectedSubtotal) }}
                </span>
              </div>

              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600 dark:text-slate-400">
                  Giảm giá
                </span>
                <span class="font-medium text-green-600 dark:text-green-400">
                  -{{ moneyVND(summary.discount_total || 0) }}
                </span>
              </div>
            </div>

            <!-- Total -->
            <div class="pt-4 flex items-center justify-between">
              <span class="text-base font-medium text-slate-900 dark:text-slate-100">
                Tổng thanh toán
              </span>
              <span class="text-xl font-bold text-primary">
                {{ moneyVND(selectedSubtotal - (summary.discount_total || 0)) }}
              </span>
            </div>

            <!-- Checkout button -->
            <button
              type="button"
              class="w-full mt-5 bg-primary hover:bg-primary/90 text-white font-bold py-3.5 rounded-lg shadow-md transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
              :disabled="selectedCount === 0 || hasBusyItem"
              @click="checkout"
            >
              <span v-if="selectedCount === 0">Chọn sản phẩm để thanh toán</span>
              <span v-else>Mua {{ selectedCount }} sản phẩm</span>
            </button>

            <!-- Security badge -->
            <div class="mt-4 flex items-center justify-center gap-1.5 text-xs text-slate-400">
              <span class="material-symbols-outlined text-base">lock</span>
              <span>Thanh toán an toàn</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile sticky checkout bar -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 shadow-lg p-4 z-50">
      <div class="flex items-center justify-between gap-4 max-w-7xl mx-auto">
        <div class="flex items-center gap-3">
          <label class="flex items-center gap-2 cursor-pointer select-none" @click="toggleSelectAll">
            <div
              class="w-5 h-5 rounded border-2 flex items-center justify-center transition-all"
              :class="allSelected
                ? 'bg-primary border-primary'
                : 'border-slate-300 dark:border-slate-600'"
            >
              <svg v-if="allSelected" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="text-sm text-slate-600 dark:text-slate-400">Chọn tất cả</span>
          </label>
        </div>

        <div class="flex items-center gap-4">
          <div class="text-right">
            <p class="text-xs text-slate-500 dark:text-slate-400">Tổng thanh toán</p>
            <p class="text-lg font-bold text-primary">{{ moneyVND(selectedSubtotal - (summary.discount_total || 0)) }}</p>
          </div>

          <button
            type="button"
            class="px-6 py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="selectedCount === 0 || hasBusyItem"
            @click="checkout"
          >
            Mua hàng
          </button>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { useCartStore } from "../../../stores/cart";
import { buildImageUrl } from "../../../utils/image";
import { useAlert } from "../../../composables/useAlert";

const router = useRouter();
const cartStore = useCartStore();
const notify = useAlert();

const pageLoading = ref(true);
const busyMap = ref({});
const selectedItems = ref(new Set());

const fallbackImage = "https://via.placeholder.com/400x400?text=Shoe";

const error = computed(() => cartStore.error);
const items = computed(() => cartStore.cart?.items ?? []);
const summary = computed(() => cartStore.cart?.summary ?? {});

const hasBusyItem = computed(() => Object.values(busyMap.value).some(Boolean));

const allSelected = computed(() => items.value.length > 0 && selectedItems.value.size === items.value.length);
const selectedCount = computed(() => selectedItems.value.size);

const selectedItemsList = computed(() => {
  return items.value.filter(it => selectedItems.value.has(it.id));
});

const selectedSubtotal = computed(() => {
  return selectedItemsList.value.reduce((sum, it) => sum + Number(it.line_total || 0), 0);
});

function toggleItem(id) {
  const next = new Set(selectedItems.value);
  if (next.has(id)) {
    next.delete(id);
  } else {
    next.add(id);
  }
  selectedItems.value = next;
}

function toggleSelectAll() {
  if (allSelected.value) {
    selectedItems.value = new Set();
  } else {
    selectedItems.value = new Set(items.value.map(it => it.id));
  }
}

onMounted(async () => {
  pageLoading.value = true;
  try {
    await cartStore.fetchCart();
    selectedItems.value = new Set(items.value.map(it => it.id));
  } catch (e) {
    // error handled by store
  } finally {
    pageLoading.value = false;
  }
});

watch(items, (newItems) => {
  const ids = new Set(newItems.map(it => it.id));
  const next = new Set([...selectedItems.value].filter(id => ids.has(id)));
  selectedItems.value = next;
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

function goProduct(slug) {
  if (slug) {
    router.push(`/shop/products/${slug}`);
  }
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
    if (s > 0 && next > s) {
      notify.warning("Không được vượt quá số lượng trong kho", {
        title: "Cảnh báo",
        duration: 2000,
      });
      return;
    }
  }

  if (next === current) return;

  setItemBusy(it.id, true);
  try {
    await cartStore.updateQty(it.id, next);
  } catch (e) {
    notify.error(cartStore.error || "Không cập nhật được số lượng", {
      title: "Lỗi",
      duration: 2500,
    });
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
    if (s > 0 && val > s) {
      notify.warning("Không được vượt quá số lượng trong kho", {
        title: "Cảnh báo",
        duration: 2000,
      });
      val = s;
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
    notify.error(cartStore.error || "Không xóa được sản phẩm", {
      title: "Lỗi",
      duration: 2500,
    });
  } finally {
    setItemBusy(it.id, false);
  }
}

async function removeSelected() {
  const ids = [...selectedItems.value];
  if (ids.length === 0) return;
  if (!confirm(`Xóa ${ids.length} sản phẩm đã chọn?`)) return;

  for (const id of ids) {
    setItemBusy(id, true);
  }

  try {
    for (const id of ids) {
      await cartStore.removeItem(id);
    }
    selectedItems.value = new Set();
  } catch (e) {
    notify.error(cartStore.error || "Không xóa được sản phẩm", {
      title: "Lỗi",
      duration: 2500,
    });
  } finally {
    for (const id of ids) {
      setItemBusy(id, false);
    }
  }
}

function checkout() {
  if (selectedCount.value === 0) {
    notify.warning("Vui lòng chọn ít nhất một sản phẩm để thanh toán");
    return;
  }
  router.push({
    path: "/shop/checkout",
    query: { items: [...selectedItems.value].join(',') }
  });
}
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-clamp: 2;
}
</style>
