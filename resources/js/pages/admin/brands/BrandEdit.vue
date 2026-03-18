<script setup>
import { computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import AdminEntityForm from "../../../components/AdminEntityForm.vue";

import brandAdminService from "../../../services/admin/brandAdminService";
import uploadAdminService from "../../../services/admin/uploadAdminService";
import { useAlert } from "../../../composables/useAlert";

function slugify(str) {
  return (str || "")
    .toString()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)+/g, "");
}

const route = useRoute();
const router = useRouter();
const notify = useAlert();

const id = computed(() => route.params.id);

const touchedSlug = ref(true);
const lastAutoSlug = ref("");

const values = ref({
  name: "",
  slug: "",
  logo: "",
  status: true,
});

watch(
  () => values.value.name,
  (name) => {
    if (touchedSlug.value) return;

    const auto = slugify(name);
    const cur = String(values.value.slug || "").trim();

    if (!cur || cur === lastAutoSlug.value) {
      values.value.slug = auto;
      lastAutoSlug.value = auto;
    }
  }
);

watch(
  () => values.value.slug,
  (val) => {
    const cur = String(val || "").trim();

    if (!cur) {
      touchedSlug.value = false;
      return;
    }

    if (!touchedSlug.value && lastAutoSlug.value && cur !== lastAutoSlug.value) {
      touchedSlug.value = true;
    }
  }
);

async function uploadBrandLogoWithAlert(file) {
  try {
    return await uploadAdminService.uploadBrandLogo(file);
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Tải logo thương hiệu thất bại.";

    notify.error(msg, {
      title: "Upload thất bại",
      duration: 3500,
    });

    throw e;
  }
}

const schema = computed(() => [
  {
    group: "general",
    groupTitle: "Thông tin thương hiệu",
    name: "name",
    label: "Tên thương hiệu",
    type: "text",
    required: true,
    placeholder: "Nike",
  },
  {
    group: "general",
    name: "slug",
    label: "Slug",
    type: "text",
    required: true,
    help: "Edit: mặc định không auto. Xóa trống để auto lại theo tên.",
  },
  {
    group: "settings",
    name: "status",
    label: "Trạng thái",
    type: "switch",
    onText: "Đang hoạt động",
    offText: "Tạm ngưng",
  },
  {
    group: "media",
    groupTitle: "Logo",
    groupSubtitle: "Tải ảnh hoặc dán link/path",
    name: "logo",
    label: "Logo",
    type: "file",
    full: true,
    accept: "image/*",
    uploadText: "Chọn logo",
    uploadHint: "PNG, JPG, JPEG, WEBP",
    placeholder: "Hoặc dán link/path logo...",
    upload: uploadBrandLogoWithAlert,
  },
]);

function validate(v) {
  const errs = {};

  if (!String(v.name || "").trim()) errs.name = "Tên thương hiệu là bắt buộc";
  if (!String(v.slug || "").trim()) errs.slug = "Slug là bắt buộc";
  if (typeof v.status !== "boolean") errs.status = "Trạng thái không hợp lệ";

  return errs;
}

function buildPayload(v) {
  return {
    name: String(v.name || "").trim(),
    slug: String(v.slug || "").trim(),
    logo: String(v.logo || "").trim() ? String(v.logo).trim() : null,
    status: v.status ? 1 : 0,
  };
}

async function loadBrand(brandId) {
  try {
    const { data } = await brandAdminService.show(brandId);
    const b = data?.data || data;

    const loaded = {
      name: b?.name ?? "",
      slug: b?.slug ?? "",
      logo: b?.logo ?? "",
      status: Number(b?.status ?? 1) === 1,
    };

    touchedSlug.value = true;
    lastAutoSlug.value = String(loaded.slug || "").trim() || slugify(loaded.name);

    return loaded;
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Không tải được thông tin thương hiệu.";

    notify.error(msg, {
      title: "Lỗi tải dữ liệu",
      duration: 3500,
    });

    throw e;
  }
}

async function onSubmit(payload) {
  try {
    await brandAdminService.update(id.value, payload);

    notify.success("Cập nhật thương hiệu thành công.", {
      title: "Lưu thành công",
      duration: 2500,
    });

    router.push("/admin/brands");
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Cập nhật thương hiệu thất bại.";

    notify.error(msg, {
      title: "Lưu thất bại",
      duration: 3500,
    });

    throw e;
  }
}
</script>

<template>
  <AdminEntityForm
    mode="edit"
    :entity-id="id"
    :load="loadBrand"
    title="Chỉnh sửa thương hiệu"
    subtitle="Quản lý tên, slug, logo và trạng thái."
    v-model:values="values"
    :schema="schema"
    :validate="validate"
    :transform-before-submit="buildPayload"
    :on-submit="onSubmit"
    :on-cancel="() => router.push('/admin/brands')"
    submit-text="Lưu"
    cancel-text="Hủy"
  >
    <template #field:slug="{ values: v, errors }">
      <div class="field">
        <label class="label">Slug <span class="req">*</span></label>

        <input v-model="v.slug" class="control" placeholder="nike" />

        <div class="hint">
          Edit: mặc định không auto theo tên.
          <span style="margin-left: 6px;">Xóa trống để auto lại theo tên.</span>
          <span v-if="touchedSlug" style="margin-left: 6px; color: #64748b;">
            (đang khóa auto)
          </span>
        </div>

        <div v-if="errors.slug" class="err">{{ errors.slug }}</div>
      </div>
    </template>
  </AdminEntityForm>
</template>