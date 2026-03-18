<template>
  <AdminEntityForm
    mode="edit"
    :entityId="id"
    title="Cập nhật banner"
    subtitle="Chỉnh sửa thông tin banner"
    v-model:values="values"
    :schema="schema"
    :load="loadBanner"
    :onSubmit="handleSubmit"
    :onCancel="handleCancel"
    :transformBeforeSubmit="transformBeforeSubmit"
    :validate="validate"
    :storageBaseUrl="storageBaseUrl"
    submitText="Lưu thay đổi"
    cancelText="Quay lại"
    @submitted="onSubmitted"
  />
</template>

<script setup>
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import AdminEntityForm from "../../../components/AdminEntityForm.vue";
import bannerAdminService from "../../../services/admin/bannerAdminService";
import uploadAdminService from "../../../services/admin/uploadAdminService";
import { useAlert } from "../../../composables/useAlert";

const notify = useAlert();
const route = useRoute();
const router = useRouter();

const id = computed(() => route.params.id);
const API_BASE = import.meta.env.VITE_API_URL || "";
const storageBaseUrl = API_BASE ? `${API_BASE}/storage/` : "/storage/";

const values = ref({
  title: "",
  description: "",
  image: "",
  button_text: "",
  button_link: "",
  position: "",
  is_active: true,
  sort_order: 0,
  starts_at: "",
  ends_at: "",
});

async function uploadBannerImage(file) {
  try {
    return await uploadAdminService.upload(file, "banners");
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Tải ảnh banner thất bại.";

    notify.error(msg, {
      title: "Upload thất bại",
      duration: 3500,
    });

    throw e;
  }
}

const positionOptions = [
  { label: "Home-top", value: "home_top" },
  { label: "Sale", value: "sale" },
];

const schema = [
  {
    name: "title",
    label: "Tiêu đề",
    type: "text",
    group: "general",
    groupTitle: "Thông tin cơ bản",
    required: true,
    placeholder: "Ví dụ: Summer Collection 2026",
    full: true,
  },
  {
    name: "description",
    label: "Mô tả",
    type: "textarea",
    group: "general",
    placeholder: "Nhập mô tả banner...",
    rows: 5,
    full: true,
  },
  {
    name: "position",
    label: "Vị trí hiển thị",
    type: "select",
    group: "settings",
    groupTitle: "Cài đặt",
    required: true,
    options: positionOptions,
    help: "Chọn vị trí banner sẽ hiển thị trên website.",
  },
  {
    name: "button_text",
    label: "Text nút",
    type: "text",
    group: "settings",
    placeholder: "Ví dụ: Mua ngay",
  },
  {
    name: "button_link",
    label: "Link nút",
    type: "text",
    group: "settings",
    placeholder: "/shop/products?sale=1",
    help: "Có thể nhập đường dẫn nội bộ hoặc URL đầy đủ.",
  },
  {
    name: "sort_order",
    label: "Thứ tự hiển thị",
    type: "number",
    group: "settings",
    min: 0,
    step: 1,
    placeholder: "0",
  },
  {
    name: "is_active",
    label: "Trạng thái",
    type: "switch",
    group: "settings",
    onText: "Đang bật",
    offText: "Đang tắt",
    help: "Banner chỉ hiển thị ngoài website khi đang bật và còn trong thời gian hiệu lực.",
  },
  {
    name: "starts_at",
    label: "Bắt đầu hiển thị",
    type: "datetime-local",
    group: "settings",
    full: true,
    placeholder: "Chọn ngày giờ bắt đầu",
    help: "Chọn ngày và giờ banner bắt đầu hiển thị.",
  },
  {
    name: "ends_at",
    label: "Kết thúc hiển thị",
    type: "datetime-local",
    group: "settings",
    full: true,
    placeholder: "Chọn ngày giờ kết thúc",
    help: "Chọn ngày và giờ banner kết thúc hiển thị.",
  },
  {
    name: "image",
    label: "Ảnh banner",
    type: "file",
    group: "media",
    groupTitle: "Media",
    required: true,
    upload: uploadBannerImage,
    accept: "image/*",
    uploadText: "Chọn ảnh banner",
    uploadHint: "PNG, JPG, WEBP...",
    placeholder: "Hoặc nhập path/url ảnh",
    help: "Bạn có thể upload ảnh mới hoặc giữ ảnh hiện tại.",
  },
];

function formatDateForInput(value) {
  if (!value) return "";

  try {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "";

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");

    return `${year}-${month}-${day}T${hours}:${minutes}`;
  } catch {
    return "";
  }
}

async function loadBanner(bannerId) {
  try {
    const res = await bannerAdminService.show(bannerId);
    const data = res?.data?.data ?? res?.data ?? {};

    return {
      title: data.title ?? "",
      description: data.description ?? "",
      image: data.image ?? "",
      button_text: data.button_text ?? "",
      button_link: data.button_link ?? "",
      position: data.position ?? "",
      is_active: !!data.is_active,
      sort_order: Number(data.sort_order ?? 0),
      starts_at: formatDateForInput(data.starts_at),
      ends_at: formatDateForInput(data.ends_at),
    };
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Không tải được thông tin banner.";

    notify.error(msg, {
      title: "Lỗi tải dữ liệu",
      duration: 3500,
    });

    throw e;
  }
}

function normalizeDateTime(value) {
  const v = String(value || "").trim();
  if (!v) return null;

  return `${v}:00`;
}

function transformBeforeSubmit(formValues) {
  return {
    title: formValues.title?.trim(),
    description: formValues.description?.trim() || null,
    image: formValues.image?.trim() || null,
    button_text: formValues.button_text?.trim() || null,
    button_link: formValues.button_link?.trim() || null,
    position: formValues.position || null,
    is_active: !!formValues.is_active,
    sort_order: Number(formValues.sort_order || 0),
    starts_at: normalizeDateTime(formValues.starts_at),
    ends_at: normalizeDateTime(formValues.ends_at),
  };
}

function validate(formValues) {
  const errors = {};

  if (!String(formValues.title || "").trim()) {
    errors.title = "Vui lòng nhập tiêu đề banner.";
  }

  if (!String(formValues.position || "").trim()) {
    errors.position = "Vui lòng chọn vị trí hiển thị.";
  }

  if (!String(formValues.image || "").trim()) {
    errors.image = "Vui lòng chọn hoặc nhập ảnh banner.";
  }

  if (formValues.starts_at && Number.isNaN(new Date(formValues.starts_at).getTime())) {
    errors.starts_at = "Thời gian bắt đầu không hợp lệ.";
  }

  if (formValues.ends_at && Number.isNaN(new Date(formValues.ends_at).getTime())) {
    errors.ends_at = "Thời gian kết thúc không hợp lệ.";
  }

  if (
    formValues.starts_at &&
    formValues.ends_at &&
    new Date(formValues.ends_at).getTime() < new Date(formValues.starts_at).getTime()
  ) {
    errors.ends_at = "Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu.";
  }

  return errors;
}

async function handleSubmit(payload) {
  try {
    await bannerAdminService.update(id.value, payload);
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Cập nhật banner thất bại.";

    notify.error(msg, {
      title: "Lưu thất bại",
      duration: 3500,
    });

    throw e;
  }
}

function onSubmitted() {
  notify.success("Cập nhật banner thành công.", {
    title: "Lưu thành công",
    duration: 2500,
  });

  router.push("/admin/banners");
}

function handleCancel() {
  router.push("/admin/banners");
}
</script>