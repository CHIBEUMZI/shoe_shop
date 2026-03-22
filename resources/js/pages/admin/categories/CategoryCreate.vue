<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import AdminEntityForm from "../../../components/AdminEntityForm.vue";
import categoryAdminService from "../../../services/admin/categoryAdminService";
import { useAlert } from "../../../composables/useAlert";

const router = useRouter();
const notify = useAlert();

const touchedSlug = ref(false);
const searchRef = ref(null);
const parentField = ref(null);

const values = ref({
  parent_id: null,
  name: "",
  slug: "",
  description: "",
  sort_order: 0,
  status: true,
});

function slugify(str) {
  return String(str || "")
    .trim()
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)+/g, "");
}

watch(
  () => values.value.name,
  (val) => {
    if (touchedSlug.value) return;
    values.value.slug = slugify(val);
  }
);

// ===== Parents dropdown =====
const parentLoading = ref(false);
const parentError = ref("");
const parentOptions = ref([]);
const parentSearch = ref("");
const showParentDropdown = ref(false);

const filteredParents = computed(() => {
  const q = parentSearch.value.trim().toLowerCase();
  const list = parentOptions.value || [];
  if (!q) return list.slice(0, 50);
  return list
    .filter(
      (c) =>
        (c.name || "").toLowerCase().includes(q) ||
        (c.slug || "").toLowerCase().includes(q)
    )
    .slice(0, 50);
});

const selectedParent = computed(() => {
  if (!values.value.parent_id) return null;
  return (
    parentOptions.value.find(
      (x) => String(x.id) === String(values.value.parent_id)
    ) || null
  );
});

async function fetchParents() {
  parentLoading.value = true;
  parentError.value = "";
  try {
    const { data } = await categoryAdminService.list({
      page: 1,
      per_page: 200,
      status: 1,
    });
    parentOptions.value = data?.data || [];
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Không tải được danh mục cha";
    parentError.value = msg;
    notify.error(msg, { title: "Lỗi tải dữ liệu", duration: 3500 });
  } finally {
    parentLoading.value = false;
  }
}

function toggleParentDropdown() {
  showParentDropdown.value = !showParentDropdown.value;
  if (showParentDropdown.value) {
    nextTick(() => searchRef.value?.focus());
  } else {
    parentSearch.value = "";
  }
}

function pickParent(c) {
  values.value.parent_id = c.id;
  showParentDropdown.value = false;
  parentSearch.value = "";
  notify.info(`Đã chọn danh mục cha: ${c.name}`, {
    title: "Đã chọn",
    duration: 1800,
  });
}

function pickFirstFiltered() {
  if (filteredParents.value.length > 0) {
    pickParent(filteredParents.value[0]);
  }
}

function clearParent() {
  values.value.parent_id = null;
  notify.info("Đã bỏ chọn danh mục cha.", { title: "Đã bỏ chọn", duration: 1600 });
}

function onClickOutside(e) {
  if (parentField.value && !parentField.value.contains(e.target)) {
    showParentDropdown.value = false;
    parentSearch.value = "";
  }
}

onMounted(() => {
  fetchParents();
  document.addEventListener("mousedown", onClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("mousedown", onClickOutside);
});

function initials(name) {
  return String(name || "")
    .split(" ")
    .map((w) => w[0])
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

// ===== Schema =====
const schema = computed(() => [
  {
    group: "general",
    groupTitle: "Thông tin cơ bản",
    name: "name",
    label: "Tên danh mục",
    type: "text",
    required: true,
    placeholder: "Ví dụ: Giày nam, Sneaker...",
  },
  {
    group: "general",
    name: "slug",
    label: "Slug",
    type: "text",
    required: true,
    full: true,
  },
  {
    group: "general",
    name: "description",
    label: "Mô tả",
    type: "textarea",
    full: true,
    rows: 5,
    placeholder: "Mô tả ngắn (không bắt buộc)...",
  },
  {
    group: "settings",
    groupTitle: "Cài đặt",
    name: "parent_id",
    label: "Danh mục cha",
    type: "slot",
    full: true,
  },
  {
    group: "settings",
    name: "sort_order",
    label: "Thứ tự hiển thị",
    type: "number",
    min: 0,
    placeholder: "0",
  },
  {
    group: "settings",
    name: "status",
    label: "Trạng thái",
    type: "switch",
    onText: "Đang hoạt động",
    offText: "Tạm ngưng",
  },
]);

function validate(v) {
  const errs = {};
  if (!String(v.name || "").trim()) errs.name = "Tên danh mục là bắt buộc";
  if (!String(v.slug || "").trim()) errs.slug = "Slug là bắt buộc";
  if (
    v.sort_order !== null &&
    v.sort_order !== undefined &&
    Number(v.sort_order) < 0
  ) {
    errs.sort_order = "Thứ tự không hợp lệ";
  }
  return errs;
}

function buildPayload(v) {
  return {
    parent_id: v.parent_id ? Number(v.parent_id) : null,
    name: String(v.name || "").trim(),
    slug: String(v.slug || "").trim(),
    description: String(v.description || "").trim() || null,
    sort_order: Number(v.sort_order || 0),
    status: v.status ? 1 : 0,
  };
}

async function onSubmit(payload) {
  try {
    await categoryAdminService.create(payload);
    notify.success("Tạo danh mục thành công.", {
      title: "Lưu thành công",
      duration: 2500,
    });
    router.push("/admin/categories");
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Tạo danh mục thất bại.";
    notify.error(msg, { title: "Lưu thất bại", duration: 3500 });
    throw e;
  }
}


</script>

<template>
  <AdminEntityForm
    mode="create"
    title="Tạo danh mục"
    subtitle="Danh mục cha • thứ tự hiển thị • trạng thái"
    v-model:values="values"
    :schema="schema"
    :validate="validate"
    :transform-before-submit="buildPayload"
    :on-submit="onSubmit"
    :on-cancel="() => router.back()"
    submit-text="Lưu"
    cancel-text="Hủy"
  >
    <!-- Custom field: slug -->
    <template #field:slug="{ values: v, errors }">
      <div class="field full">
        <label class="field-label">
          Slug <span class="req">*</span>
        </label>
        <input
          v-model="v.slug"
          class="control"
          placeholder="giay-nam"
          @input="touchedSlug = true"
        />
        <div class="hint">
          Tự tạo theo tên — chỉnh thủ công nếu cần.
        </div>
        <div v-if="errors.slug" class="err">{{ errors.slug }}</div>
      </div>
    </template>

    <!-- Custom field: parent_id dropdown -->
    <template #field:parent_id="{ values: v }">
      <div class="field full parent-field" ref="parentField">
        <label class="field-label">Danh mục cha</label>

        <!-- Trigger button -->
        <div
          class="parent-trigger"
          :class="{ open: showParentDropdown }"
          tabindex="0"
          role="combobox"
          :aria-expanded="showParentDropdown"
          @click="toggleParentDropdown"
          @keydown.enter.prevent="toggleParentDropdown"
          @keydown.space.prevent="toggleParentDropdown"
          @keydown.escape="showParentDropdown = false; parentSearch = ''"
        >
          <!-- Left icon -->
          <svg class="trigger-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
            <path d="M2 4h12M2 8h8M2 12h5" stroke-linecap="round" />
          </svg>

          <!-- Selected text / placeholder -->
          <div class="trigger-text">
            <template v-if="selectedParent">
              <div class="trigger-name">{{ selectedParent.name }}</div>
              
            </template>
            <div v-else class="trigger-placeholder">Chọn danh mục cha...</div>
          </div>

          <!-- Badge khi đã chọn -->
          <span v-if="selectedParent" class="trigger-badge">Đã chọn</span>

          <!-- Chevron -->
          <svg
            class="trigger-chevron"
            :class="{ open: showParentDropdown }"
            viewBox="0 0 16 16"
            width="14"
            height="14"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
          >
            <path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>

        <!-- Dropdown panel -->
        <div v-if="showParentDropdown" class="dropdownx">

          <!-- Search bar -->
          <div class="dropdownx-search">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" class="search-icon">
              <circle cx="6.5" cy="6.5" r="4" />
              <path d="M11 11l2.5 2.5" stroke-linecap="round" />
            </svg>
            <input
              ref="searchRef"
              v-model="parentSearch"
              class="dropdownx-input"
              placeholder="Tìm tên hoặc slug..."
              autocomplete="off"
              spellcheck="false"
              :disabled="parentLoading"
              @mousedown.stop
              @click.stop
              @keydown.escape="showParentDropdown = false; parentSearch = ''"
              @keydown.enter.prevent="pickFirstFiltered"
            />
            <span v-if="parentSearch.trim()" class="search-count">
              {{ filteredParents.length }} kết quả
            </span>
          </div>

          <!-- Item list -->
          <div class="dropdownx-body">
            <div v-if="parentLoading" class="dropdownx-empty">
              <span>Đang tải danh mục...</span>
            </div>
            <div v-else-if="parentError" class="dropdownx-empty is-error">
              {{ parentError }}
            </div>
            <template v-else>
              <button
                v-for="c in filteredParents"
                :key="c.id"
                type="button"
                class="dropdownx-item"
                :class="{ 'is-active': String(v.parent_id) === String(c.id) }"
                @mousedown.prevent="pickParent(c)"
              >
                <div class="item-avatar">{{ initials(c.name) }}</div>
                <div class="item-info">
                  <div class="item-name">{{ c.name }}</div>
                </div>
                <svg
                  v-if="String(v.parent_id) === String(c.id)"
                  class="item-check"
                  viewBox="0 0 14 14"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path d="M2.5 7l3 3 6-6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>

              <div v-if="filteredParents.length === 0" class="dropdownx-empty">
                Không có kết quả phù hợp
              </div>
            </template>
          </div>

          <!-- Footer -->
          <div class="dropdownx-footer">
            <span class="footer-hint">
              <kbd>Enter</kbd> để chọn &nbsp;·&nbsp; <kbd>Esc</kbd> để đóng
            </span>
            <button
              v-if="v.parent_id"
              type="button"
              class="footer-clear"
              @mousedown.prevent="clearParent"
            >
              Bỏ chọn
            </button>
          </div>
        </div>
      </div>
    </template>
  </AdminEntityForm>
</template>

<style scoped>
/* ─── Field wrapper ─────────────────────────────────── */
.parent-field {
  position: relative;
}

.field-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  margin-bottom: 6px;
}

/* ─── Trigger button ─────────────────────────────────── */
.parent-trigger {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 12px;
  height: 40px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
  user-select: none;
}

.parent-trigger:hover {
  border-color: #cbd5e1;
}

.parent-trigger.open {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.trigger-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  color: #94a3b8;
}

.trigger-text {
  flex: 1;
  min-width: 0;
}

.trigger-name {
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.trigger-slug {
  font-size: 11px;
  color: #94a3b8;
  font-family: ui-monospace, monospace;
  margin-top: 1px;
}

.trigger-placeholder {
  font-size: 13px;
  color: #94a3b8;
}

.trigger-badge {
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 4px;
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
}

.trigger-chevron {
  flex-shrink: 0;
  color: #94a3b8;
  transition: transform 0.18s ease;
}

.trigger-chevron.open {
  transform: rotate(180deg);
}

/* ─── Dropdown panel ────────────────────────────────── */
.dropdownx {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 6px);
  z-index: 9999;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.07),
    0 10px 24px -4px rgba(0, 0, 0, 0.08);
}

/* ─── Search bar ─────────────────────────────────────── */
.dropdownx-search {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  background: #f8fafc;
  border-bottom: 1px solid #f1f5f9;
}

.search-icon {
  flex-shrink: 0;
  color: #94a3b8;
}

.dropdownx-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 13px;
  color: #0f172a;
  outline: none;
  font-family: inherit;
}

.dropdownx-input::placeholder {
  color: #94a3b8;
}

.dropdownx-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.search-count {
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 500;
  color: #64748b;
  background: #f1f5f9;
  padding: 2px 7px;
  border-radius: 999px;
  font-variant-numeric: tabular-nums;
}

/* ─── Item list ──────────────────────────────────────── */
.dropdownx-body {
  max-height: 240px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #e2e8f0 transparent;
}

.dropdownx-body::-webkit-scrollbar {
  width: 4px;
}

.dropdownx-body::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 4px;
}

.dropdownx-empty {
  padding: 20px 16px;
  text-align: center;
  font-size: 13px;
  color: #94a3b8;
}

.dropdownx-empty.is-error {
  color: #ef4444;
  font-weight: 600;
}

.dropdownx-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  background: #ffffff;
  border: none;
  border-bottom: 1px solid #f8fafc;
  text-align: left;
  cursor: pointer;
  transition: background 0.1s;
}

.dropdownx-item:last-child {
  border-bottom: none;
}

.dropdownx-item:hover {
  background: #f8fafc;
}

.dropdownx-item.is-active {
  background: #eff6ff;
}

.item-avatar {
  width: 30px;
  height: 30px;
  border-radius: 7px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  background: #eff6ff;
  color: #2563eb;
  letter-spacing: 0.02em;
}

.is-active .item-avatar {
  background: #dbeafe;
  color: #1d4ed8;
}

.item-info {
  flex: 1;
  min-width: 0;
}

.item-name {
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-slug {
  font-size: 11px;
  color: #94a3b8;
  font-family: ui-monospace, monospace;
  margin-top: 1px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-check {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
  color: #2563eb;
}

/* ─── Footer ─────────────────────────────────────────── */
.dropdownx-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
}

.footer-hint {
  font-size: 11px;
  color: #94a3b8;
}

.footer-hint kbd {
  font-family: ui-monospace, monospace;
  font-size: 10px;
  padding: 1px 5px;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #64748b;
  font-weight: 500;
}

.footer-clear {
  font-size: 11px;
  font-weight: 600;
  color: #ef4444;
  background: none;
  border: none;
  cursor: pointer;
  padding: 3px 6px;
  border-radius: 4px;
  transition: background 0.12s;
}

.footer-clear:hover {
  background: #fef2f2;
}

/* ─── Shared utils ───────────────────────────────────── */
.req {
  color: #ef4444;
  margin-left: 2px;
}

.hint {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 4px;
}

.err {
  font-size: 12px;
  color: #ef4444;
  margin-top: 4px;
  font-weight: 500;
}
</style>