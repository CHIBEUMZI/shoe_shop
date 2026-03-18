<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import AdminEntityForm from "../../../components/AdminEntityForm.vue";
import categoryAdminService from "../../../services/admin/categoryAdminService";
import { useAlert } from "../../../composables/useAlert";

const router = useRouter();
const notify = useAlert();

const touchedSlug = ref(false);

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

// ===== Parents dropdown (search) =====
const parentLoading = ref(false);
const parentError = ref("");
const parentOptions = ref([]);

const parentSearch = ref("");
const showParentDropdown = ref(false);

const filteredParents = computed(() => {
  const q = parentSearch.value.trim().toLowerCase();
  const list = parentOptions.value || [];
  if (!q) return list.slice(0, 30);
  return list
    .filter(
      (c) =>
        (c.name || "").toLowerCase().includes(q) ||
        (c.slug || "").toLowerCase().includes(q)
    )
    .slice(0, 30);
});

const selectedParent = computed(() => {
  if (!values.value.parent_id) return null;
  return (
    parentOptions.value.find((x) => String(x.id) === String(values.value.parent_id)) ||
    null
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

    notify.error(msg, {
      title: "Lỗi tải dữ liệu",
      duration: 3500,
    });
  } finally {
    parentLoading.value = false;
  }
}

function openParentDropdown() {
  showParentDropdown.value = true;
}

function pickParent(c) {
  values.value.parent_id = c.id;
  showParentDropdown.value = false;
  parentSearch.value = "";

  notify.info(`Đã chọn danh mục cha: ${c.name}`, {
    title: "Đã chọn danh mục cha",
    duration: 1800,
  });
}

function clearParent() {
  values.value.parent_id = null;

  notify.info("Đã bỏ chọn danh mục cha.", {
    title: "Đã bỏ chọn",
    duration: 1600,
  });
}

function onBlurParent() {
  setTimeout(() => (showParentDropdown.value = false), 150);
}

// ===== schema =====
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

    notify.error(msg, {
      title: "Lưu thất bại",
      duration: 3500,
    });

    throw e;
  }
}

onMounted(fetchParents);
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
        <label class="label">Slug (Tiếng Việt) <span class="req">*</span></label>
        <input
          v-model="v.slug"
          class="control"
          placeholder="giay-nam"
          @input="touchedSlug = true"
        />
        <div class="hint">
          Tự tạo theo tên (cho tới khi bạn sửa slug thủ công).
        </div>
        <div v-if="errors.slug" class="err">{{ errors.slug }}</div>
      </div>
    </template>

    <!-- Custom field: parent_id dropdown -->
    <template #field:parent_id="{ values: v }">
      <div class="field full parent-field" @focusout="onBlurParent">
        <label class="label">Danh mục cha</label>

        <div
          class="parent-select"
          role="button"
          tabindex="0"
          @click="openParentDropdown"
          @keydown.enter.prevent="openParentDropdown"
          @keydown.space.prevent="openParentDropdown"
        >
          <div class="parent-selected">
            <div class="muted small">Đang chọn</div>
            <div class="fw">{{ selectedParent ? selectedParent.name : "Không có" }}</div>
            <div class="muted small" v-if="selectedParent">{{ selectedParent.slug }}</div>
          </div>

          <div class="parent-actions">
            <button
              type="button"
              class="btn-mini danger"
              :disabled="!v.parent_id"
              @click.stop="clearParent"
            >
              Bỏ chọn
            </button>
          </div>
        </div>

        <div class="dropdownx" v-if="showParentDropdown">
          <div class="dropdownx-head">
            <span class="icon">🔎</span>
            <input
              v-model="parentSearch"
              class="control"
              placeholder="Tìm danh mục cha..."
              :disabled="parentLoading"
              @mousedown.stop
              @click.stop
            />
          </div>

          <div class="dropdownx-body">
            <div v-if="parentLoading" class="p3 muted">Đang tải...</div>
            <div v-else-if="parentError" class="p3 err2">{{ parentError }}</div>

            <template v-else>
              <button
                v-for="c in filteredParents"
                :key="c.id"
                type="button"
                class="dropdownx-item"
                @mousedown.prevent="pickParent(c)"
              >
                <div class="fw">{{ c.name }}</div>
                <div class="small muted">{{ c.slug }}</div>
              </button>

              <div v-if="filteredParents.length === 0" class="p3 muted">
                Không có kết quả phù hợp
              </div>
            </template>
          </div>
        </div>
      </div>
    </template>
  </AdminEntityForm>
</template>

<style scoped>
.parent-field {
  position: relative;
}

.parent-select {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 10px 12px;
  background: #fff;
  cursor: pointer;
}

.parent-select:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
  border-color: #60a5fa;
}

.parent-selected {
  min-width: 0;
}

.parent-actions {
  display: flex;
  gap: 8px;
}

.muted {
  color: #64748b;
}

.small {
  font-size: 12px;
}

.fw {
  font-weight: 800;
  color: #0f172a;
}

.btn-mini {
  border: 1px solid #e5e7eb;
  background: #fff;
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 800;
  font-size: 12px;
  cursor: pointer;
}

.btn-mini:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-mini.danger {
  border-color: rgba(239, 68, 68, 0.35);
  background: rgba(239, 68, 68, 0.06);
  color: #ef4444;
}

.dropdownx {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 8px);
  z-index: 9999;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 12px 26px rgba(0, 0, 0, 0.1);
}

.dropdownx-head {
  padding: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  background: rgba(248, 249, 250, 0.7);
}

.icon {
  opacity: 0.7;
}

.dropdownx-body {
  max-height: 260px;
  overflow: auto;
}

.dropdownx-item {
  width: 100%;
  border: 0;
  text-align: left;
  padding: 10px 12px;
  background: #fff;
}

.dropdownx-item:hover {
  background: rgba(37, 99, 235, 0.06);
}

.p3 {
  padding: 12px;
}

.err2 {
  color: #ef4444;
  font-weight: 700;
}
</style>