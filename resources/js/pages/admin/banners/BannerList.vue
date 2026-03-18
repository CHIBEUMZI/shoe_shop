<template>
  <main class="min-h-screen bg-slate-50 p-4 md:p-6">
    <!-- Header -->
    <section class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-2xl md:text-3xl font-black tracking-tight text-slate-900">
          Quản lý banner
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          Quản lý banner hiển thị trên website
        </p>
      </div>

      <router-link
        to="/admin/banners/create"
        class="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
      >
        + Tạo banner
      </router-link>
    </section>

    <!-- Table -->
    <BaseTable
      :columns="columns"
      :items="items"
      :loading="loading"
      :empty-text="error || 'Chưa có banner nào'"
      :searchable="true"
      :search="filters.search"
      search-placeholder="Tìm theo tiêu đề hoặc mô tả..."
      :pagination="pagination"
      :show-per-page="true"
      :per-page="filters.per_page"
      :actions="true"
      :row-actions="rowActions"
      @update:search="onSearchChange"
      @update:perPage="onPerPageChange"
      @page-change="onPageChange"
      @action="onTableAction"
    >
      <template #filters>
        <BaseSelect
          :model-value="filters.position"
          :options="positionOptions"
          placeholder="Chọn vị trí"
          size="sm"
          wrapperClass="!w-[220px]"
          @update:modelValue="onPositionChange"
        />

        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium hover:bg-slate-50"
          @click="resetFilters"
        >
          Đặt lại
        </button>
      </template>

      <template #cell-image="{ item }">
        <div class="flex items-center gap-3">
          <div
            class="h-14 w-24 overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
          >
            <img
              v-if="item.image"
              :src="item.image"
              :alt="item.title"
              class="h-full w-full object-cover"
            />
            <div
              v-else
              class="flex h-full w-full items-center justify-center text-[11px] font-medium text-slate-400"
            >
              No image
            </div>
          </div>

          <div class="min-w-0">
            <p class="truncate font-semibold text-slate-900">
              {{ item.title }}
            </p>
            <p class="mt-1 line-clamp-2 text-xs text-slate-500">
              {{ item.description || "Không có mô tả" }}
            </p>
          </div>
        </div>
      </template>

      <template #cell-position="{ value }">
        <span
          class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700"
        >
          {{ formatPosition(value) }}
        </span>
      </template>

      <template #cell-is_active="{ value }">
        <span
          class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold"
          :class="
            value
              ? 'bg-emerald-100 text-emerald-700'
              : 'bg-slate-200 text-slate-600'
          "
        >
          {{ value ? "Đang bật" : "Đang tắt" }}
        </span>
      </template>

      <template #cell-sort_order="{ value }">
        <span class="font-semibold text-slate-700">
          {{ Number(value || 0) }}
        </span>
      </template>

      <template #cell-time="{ item }">
        <div class="space-y-1 text-xs text-slate-500">
          <div>
            <span class="font-medium text-slate-700">Bắt đầu:</span>
            {{ formatDate(item.starts_at) }}
          </div>
          <div>
            <span class="font-medium text-slate-700">Kết thúc:</span>
            {{ formatDate(item.ends_at) }}
          </div>
        </div>
      </template>
    </BaseTable>
  </main>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";

import BaseTable from "../../../components/BaseTable.vue";
import BaseSelect from "../../../components/BaseSelect.vue";
import bannerAdminService from "../../../services/admin/bannerAdminService";

const router = useRouter();

const loading = ref(false);
const error = ref("");
const items = ref([]);

const filters = reactive({
  search: "",
  position: "",
  page: 1,
  per_page: 10,
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

const columns = [
  {
    key: "image",
    label: "Banner",
    width: "360px",
  },
  {
    key: "position",
    label: "Vị trí",
    width: "180px",
  },
  {
    key: "is_active",
    label: "Trạng thái",
    width: "140px",
    align: "center",
  },
  {
    key: "sort_order",
    label: "Thứ tự",
    width: "100px",
    align: "center",
  },
  {
    key: "time",
    label: "Thời gian hiển thị",
    width: "240px",
  },
];

const rowActions = [
  {
    key: "edit",
    icon: "edit",
    title: "Sửa",
    class: "border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100",
  },
  {
    key: "delete",
    icon: "delete",
    title: "Xoá",
    class: "border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100",
  },
];

const positionOptions = [
  { label: "Tất cả vị trí", value: "" },
  { label: "Home-top", value: "home_top" },
  { label: "Sale", value: "sale" },
];

function formatDate(value) {
  if (!value) return "--";

  try {
    return new Date(value).toLocaleString("vi-VN");
  } catch {
    return "--";
  }
}

function formatPosition(value) {
  const map = {
    home_top: "Home Top",
    sale: "Sale ",
  };

  return map[value] || value || "--";
}

async function fetchItems() {
  loading.value = true;
  error.value = "";

  try {
    const res = await bannerAdminService.list({
      search: filters.search || undefined,
      position: filters.position || undefined,
      page: filters.page,
      per_page: filters.per_page,
    });

    const root = res?.data || {};
    items.value = root?.data || [];

    const meta = root?.meta || {};
    pagination.value = {
      current_page: Number(meta.current_page || 1),
      last_page: Number(meta.last_page || 1),
      per_page: Number(meta.per_page || filters.per_page),
      total: Number(meta.total || 0),
    };
  } catch (e) {
    error.value =
      e?.response?.data?.message || e?.message || "Không tải được danh sách banner.";
    items.value = [];
    pagination.value = {
      current_page: 1,
      last_page: 1,
      per_page: filters.per_page,
      total: 0,
    };
  } finally {
    loading.value = false;
  }
}

function onSearchChange(value) {
  filters.search = String(value || "");
  filters.page = 1;
  fetchItems();
}

function onPositionChange(value) {
  filters.position = String(value || "");
  filters.page = 1;
  fetchItems();
}

function onPerPageChange(value) {
  filters.per_page = Number(value || 10);
  filters.page = 1;
  fetchItems();
}

function onPageChange(page) {
  filters.page = Number(page || 1);
  fetchItems();
}

function resetFilters() {
  filters.search = "";
  filters.position = "";
  filters.page = 1;
  filters.per_page = 10;
  fetchItems();
}

async function removeItem(item) {
  const ok = window.confirm(`Bạn có chắc muốn xoá banner "${item.title}" không?`);
  if (!ok) return;

  try {
    await bannerAdminService.delete(item.id);

    if (items.value.length === 1 && filters.page > 1) {
      filters.page -= 1;
    }
    notify.success("Xoá banner thành công.", {
      title: "Xoá thành công",
      duration: 2500,
    });
    await fetchItems();
  } catch (e) {
    notify.error(e?.response?.data?.message || "Xoá banner thất bại.", {
      title: "Xoá thất bại",
      duration: 2500,
    });
  }
}

function onTableAction({ key, item }) {
  if (key === "edit") {
    router.push(`/admin/banners/${item.id}/edit`);
    return;
  }

  if (key === "delete") {
    removeItem(item);
  }
}

onMounted(fetchItems);
</script>