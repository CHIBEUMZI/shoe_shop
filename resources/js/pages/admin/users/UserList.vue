<template>
  <div class="mx-auto max-w-[1200px] p-6">
    <div class="mb-4 flex items-end justify-between gap-4">
      <div>
        <h2 class="m-0 text-2xl font-extrabold">Quản lý người dùng</h2>
        <div class="mt-1 text-sm text-slate-500">Xem danh sách người dùng, tìm kiếm và xem chi tiết thông tin</div>
      </div>
    </div>

    <div
      v-if="error"
      class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"
    >
      <div class="font-extrabold">Có lỗi</div>
      <div class="mt-1 text-sm">{{ error }}</div>
    </div>

    <BaseTable
      :columns="columns"
      :items="items"
      :loading="loading"
      :pagination="meta"
      :search="q"
      :perPage="perPage"
      searchPlaceholder="Tìm theo tên hoặc email..."
      :showPerPage="true"
      :perPageOptions="[10, 20, 50]"
      :actions="true"
      :rowActions="rowActions"
      @update:search="onSearch"
      @update:perPage="onPerPage"
      @page-change="onPage"
      @action="onRowAction"
    >
      <template #filters>
        <div class="flex flex-wrap items-center gap-2">
          <BaseSelect
            v-model="role"
            :options="roleOptions"
            size="sm"
            placeholder="Tất cả quyền"
            wrapperClass="!w-[180px] shrink-0"
            @change="onRoleChange"
          />

          <BaseSelect
            v-model="isActive"
            :options="activeOptions"
            size="sm"
            placeholder="Tất cả trạng thái"
            wrapperClass="!w-[190px] shrink-0"
            @change="onActiveChange"
          />

          <button
            class="h-10 shrink-0 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold hover:bg-slate-50 disabled:opacity-60"
            :disabled="loading"
            @click="resetFilters"
          >
            Làm mới
          </button>
        </div>
      </template>

      <template #cell-avatar="{ value, item }">
        <div class="flex items-center gap-3">
          <img
            v-if="value"
            :src="toAvatarSrc(value)"
            class="h-10 w-10 rounded-full border border-slate-200 object-cover"
            alt="avatar"
          />
          <div
            v-else
            class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-100"
          >
            <span class="material-symbols-outlined text-slate-500">person</span>
          </div>

          <div class="min-w-0">
            <div class="truncate font-semibold text-slate-900">{{ item.name }}</div>
            <div class="truncate text-xs text-slate-500">{{ item.email }}</div>
          </div>
        </div>
      </template>

      <template #cell-role="{ value }">
        <span
          class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
          :class="
            value === 'admin'
              ? 'border-indigo-200 bg-indigo-50 text-indigo-700'
              : 'border-slate-200 bg-slate-50 text-slate-700'
          "
        >
          {{ value }}
        </span>
      </template>

      <template #cell-is_active="{ value }">
        <span
          class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
          :class="
            value
              ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
              : 'border-rose-200 bg-rose-50 text-rose-700'
          "
        >
          {{ value ? "Hoạt động" : "Khóa" }}
        </span>
      </template>

      <template #cell-created_at="{ value }">
        <span class="text-slate-700">{{ formatDateTime(value) }}</span>
      </template>
    </BaseTable>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import BaseTable from "../../../components/BaseTable.vue";
import BaseSelect from "../../../components/BaseSelect.vue";
import userAdminService from "../../../services/admin/userAdminService";

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const error = ref("");

const items = ref([]);
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

const q = ref(String(route.query.search || ""));
const role = ref(String(route.query.role || ""));
const isActive = ref(
  route.query.is_active === undefined || route.query.is_active === ""
    ? ""
    : String(route.query.is_active)
);
const perPage = ref(Number(route.query.per_page || 10));
const page = ref(Number(route.query.page || 1));

const roleOptions = [
  { label: "Tất cả quyền", value: "" },
  { label: "admin", value: "admin" },
  { label: "customer", value: "customer" },
];

const activeOptions = [
  { label: "Tất cả trạng thái", value: "" },
  { label: "Đang hoạt động", value: "1" },
  { label: "Đang khóa", value: "0" },
];

const columns = computed(() => [
  { key: "avatar", label: "Người dùng", width: "380px" },
  { key: "role", label: "Quyền", width: "140px" },
  { key: "is_active", label: "Trạng thái", width: "140px" },
  { key: "created_at", label: "Tạo lúc", width: "180px" },
]);

const rowActions = computed(() => [
  {
    key: "view",
    icon: "visibility",
    title: "Xem",
    class: "border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100",
  },
  {
    key: "edit",
    icon: "edit",
    title: "Sửa",
    class: "border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100",
  },
]);

function toAvatarSrc(v) {
  const s = String(v || "").trim();
  if (!s) return "";
  if (s.startsWith("http://") || s.startsWith("https://")) return s;
  return ("/storage/" + s).replace(/\/{2,}/g, "/");
}

function formatDateTime(iso) {
  if (!iso) return "";
  try {
    const d = new Date(iso);
    return new Intl.DateTimeFormat('vi-VN', {
      dateStyle: 'short',
      timeStyle: 'short',
      timeZone: 'Asia/Ho_Chi_Minh',
    }).format(d);
  } catch {
    return String(iso);
  }
}

function syncQuery(next = {}) {
  const query = {
    ...route.query,
    ...next,
  };

  Object.keys(query).forEach((key) => {
    if (query[key] === "" || query[key] === undefined || query[key] === null) {
      delete query[key];
    }
  });

  router.replace({ query });
}

watch(
  () => route.query,
  () => {
    q.value = String(route.query.search || "");
    role.value = String(route.query.role || "");
    isActive.value =
      route.query.is_active === undefined || route.query.is_active === ""
        ? ""
        : String(route.query.is_active);
    perPage.value = Number(route.query.per_page || 10);
    page.value = Number(route.query.page || 1);
    fetchList();
  }
);

async function fetchList() {
  loading.value = true;
  error.value = "";

  try {
    const resp = await userAdminService.list({
      search: q.value,
      role: role.value,
      is_active: isActive.value,
      per_page: perPage.value,
      page: page.value,
    });

    items.value = resp?.data?.data ?? resp?.data ?? [];
    meta.value = resp?.data?.meta ?? resp?.meta ?? meta.value;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || "Load failed";
  } finally {
    loading.value = false;
  }
}

function onSearch(val) {
  syncQuery({ search: val, page: 1 });
}

function onRole(val) {
  syncQuery({ role: val, page: 1 });
}

function onActive(val) {
  syncQuery({ is_active: val, page: 1 });
}

function onRoleChange(value) {
  role.value = value == null ? "" : String(value);
  onRole(role.value);
}

function onActiveChange(value) {
  isActive.value = value == null ? "" : String(value);
  onActive(isActive.value);
}

function onPerPage(val) {
  syncQuery({ per_page: val, page: 1 });
}

function onPage(p) {
  syncQuery({ page: p });
}

function resetFilters() {
  q.value = "";
  role.value = "";
  isActive.value = "";
  perPage.value = 10;
  page.value = 1;
  syncQuery({ search: "", role: "", is_active: "", per_page: 10, page: 1 });
}

function onRowAction({ key, item }) {
  if (key === "view") {
    router.push({ name: "admin.users.view", params: { id: item.id } });
    return;
  }

  if (key === "edit") {
    router.push({ name: "admin.users.edit", params: { id: item.id } });
  }
}

onMounted(fetchList);
</script>