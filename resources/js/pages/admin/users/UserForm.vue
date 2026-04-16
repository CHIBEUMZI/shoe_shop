<script setup>
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import AdminEntityForm from "../../../components/AdminEntityForm.vue";
import userAdminService from "../../../services/admin/userAdminService";
import uploadAdminService from "../../../services/admin/uploadAdminService";
import { useAlert } from "../../../composables/useAlert";

const route = useRoute();
const router = useRouter();
const notify = useAlert();

const id = computed(() => route.params.id);

const values = ref({
  id: null,
  name: "",
  email: "",
  avatar: "",
  birth_date: null,
  phone: "",
  role: "customer",
  is_active: true,
});

async function uploadAvatarWithAlert(file) {
  try {
    return await uploadAdminService.uploadAvatar(file);
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Tải ảnh avatar thất bại";

    notify.error(msg, {
      title: "Upload avatar thất bại",
      duration: 3500,
    });

    throw e;
  }
}

const schema = computed(() => [
  {
    name: "avatar",
    label: "Avatar",
    type: "file",
    group: "general",
    full: true,
    uploadText: "Chọn ảnh avatar",
    uploadHint: "PNG, JPG, JPEG, WEBP",
    accept: "image/*",
    placeholder: "path/url avatar...",
    upload: uploadAvatarWithAlert,
    disabledWhen: () => true,
  },
  {
    name: "name",
    label: "Họ tên",
    type: "text",
    group: "general",
    required: true,
    placeholder: "Nhập họ tên...",
    disabledWhen: () => true,
  },
  {
    name: "email",
    label: "Email",
    type: "text",
    group: "general",
    disabledWhen: () => true,
  },
  {
    name: "birth_date",
    label: "Ngày sinh",
    type: "text",
    group: "general",
    placeholder: "YYYY-MM-DD",
    disabledWhen: () => true,
  },
  {
    name: "phone",
    label: "Số điện thoại",
    type: "text",
    group: "general",
    placeholder: "Nhập số điện thoại...",
    disabledWhen: () => true,
  },
  {
    name: "role",
    label: "Quyền",
    type: "select",
    group: "settings",
    required: true,
    options: [
      { value: "admin", label: "admin" },
      { value: "customer", label: "customer" },
    ],
  },
  {
    name: "is_active",
    label: "Trạng thái tài khoản",
    type: "switch",
    group: "settings",
    disabledWhen: (vals) => vals.role === "admin",
    help: values.value.role === "admin" ? "Tài khoản admin không thể bị khóa" : "",
    onText: "Đang hoạt động",
    offText: "Đang khóa",
  },
]);

function validate(v) {
  const errors = {};

  if (!["admin", "customer"].includes(String(v.role || ""))) {
    errors.role = "Role không hợp lệ";
  }

  return errors;
}

async function loadUser(userId) {
  try {
    const resp = await userAdminService.show(userId);
    const data = resp?.data?.data ?? resp?.data ?? resp;

    return {
      id: data?.id ?? null,
      name: data?.name ?? "",
      email: data?.email ?? "",
      avatar: data?.avatar ?? "",
      birth_date: data?.birth_date ?? null,
      phone: data?.phone ?? "",
      role: data?.role ?? "customer",
      is_active: !!data?.is_active,
    };
  } catch (e) {
    const msg =
      e?.response?.data?.message ||
      e?.message ||
      "Không tải được thông tin người dùng";

    notify.error(msg, {
      title: "Lỗi tải dữ liệu",
      duration: 3500,
    });

    throw e;
  }
}

async function submitUser(payload, ctx) {
  try {
    await userAdminService.update(ctx.entityId, {
      name: payload.name,
      birth_date: payload.birth_date,
      phone: payload.phone,
      avatar: payload.avatar,
      role: payload.role,
      is_active: !!payload.is_active,
    });

    notify.success("Cập nhật người dùng thành công.", {
      title: "Lưu thành công",
      duration: 2500,
    });

    router.push("/admin/users");
  } catch (e) {
    const msg =
      e?.response?.data?.message ||
      e?.message ||
      "Cập nhật người dùng thất bại";

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
    :entityId="id"
    title="Chỉnh sửa người dùng"
    subtitle="Cập nhật thông tin và phân quyền tài khoản"
    v-model:values="values"
    :schema="schema"
    :load="loadUser"
    :onSubmit="submitUser"
    :validate="validate"
    submitText="Lưu"
    cancelText="Quay lại"
  />
</template>