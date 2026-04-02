<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { useAlert } from "../../composables/useAlert";
import { uploadAvatar, deleteAvatar } from "../../composables/profile";

const router = useRouter();
const auth = useAuthStore();
const notify = useAlert();

const activeTab = ref("info");

const values = ref({
  name: "",
  email: "",
  avatar: "",
  birth_date: "",
  address: "",
});

const passwordValues = ref({
  current_password: "",
  password: "",
  password_confirmation: "",
});

const loading = ref(false);
const passwordLoading = ref(false);
const uploadLoading = ref(false);

const errors = ref({});
const passwordErrors = ref({});

const previewAvatar = ref("");
const avatarInput = ref(null);

onMounted(() => {
  if (auth.user) {
    loadUserData(auth.user);
  }
});

watch(
  () => auth.user,
  (user) => {
    if (user) loadUserData(user);
  }
);

function loadUserData(user) {
  values.value = {
    name: user?.name || "",
    email: user?.email || "",
    avatar: user?.avatar || "",
    birth_date: user?.birth_date || "",
    address: user?.address || "",
  };
  previewAvatar.value = user?.avatar || "";
}

function triggerAvatarUpload() {
  avatarInput.value?.click();
}

async function handleAvatarChange(event) {
  const file = event.target.files?.[0];
  if (!file) return;

  uploadLoading.value = true;
  try {
    const response = await uploadAvatar(file);
    const url = response?.data?.url || response?.url;

    if (url) {
      values.value.avatar = url;
      previewAvatar.value = url;

      notify.success("Tải ảnh avatar thành công!", {
        title: "Thành công",
        duration: 2500,
      });
    } else {
      throw new Error("Không nhận được URL ảnh");
    }
  } catch (e) {
    const msg =
      e?.response?.data?.message ||
      e?.message ||
      "Tải ảnh avatar thất bại";

    notify.error(msg, {
      title: "Lỗi upload",
      duration: 3500,
    });
  } finally {
    uploadLoading.value = false;
    if (avatarInput.value) {
      avatarInput.value.value = "";
    }
  }
}

async function removeAvatar() {
  if (!previewAvatar.value) return;

  uploadLoading.value = true;
  try {
    await deleteAvatar();
    values.value.avatar = "";
    previewAvatar.value = "";

    notify.success("Xóa avatar thành công!", {
      title: "Thành công",
      duration: 2500,
    });
  } catch (e) {
    const msg =
      e?.response?.data?.message ||
      e?.message ||
      "Xóa avatar thất bại";

    notify.error(msg, {
      title: "Lỗi",
      duration: 3500,
    });
  } finally {
    uploadLoading.value = false;
  }
}

function validateInfo() {
  const errs = {};

  if (!String(values.value.name || "").trim()) {
    errs.name = "Vui lòng nhập họ tên";
  }

  if (values.value.birth_date) {
    const date = new Date(values.value.birth_date);
    const today = new Date();
    if (date >= today) {
      errs.birth_date = "Ngày sinh phải nhỏ hơn ngày hiện tại";
    }
  }

  errors.value = errs;
  return Object.keys(errs).length === 0;
}

function validatePassword() {
  const errs = {};

  if (!String(passwordValues.value.current_password || "").trim()) {
    errs.current_password = "Vui lòng nhập mật khẩu hiện tại";
  }

  if (!String(passwordValues.value.password || "").trim()) {
    errs.password = "Vui lòng nhập mật khẩu mới";
  } else if (passwordValues.value.password.length < 6) {
    errs.password = "Mật khẩu mới phải có ít nhất 6 ký tự";
  }

  if (passwordValues.value.password !== passwordValues.value.password_confirmation) {
    errs.password_confirmation = "Xác nhận mật khẩu không khớp";
  }

  passwordErrors.value = errs;
  return Object.keys(errs).length === 0;
}

async function submitInfo() {
  if (!validateInfo()) return;

  loading.value = true;
  try {
    await auth.saveProfile({
      name: String(values.value.name || "").trim(),
      birth_date: values.value.birth_date || null,
      avatar: values.value.avatar || null,
      address: values.value.address || null,
    });

    notify.success("Cập nhật thông tin thành công!", {
      title: "Thành công",
      duration: 2500,
    });
  } catch (e) {
    const msg =
      e?.response?.data?.message ||
      e?.response?.data?.errors?.name?.[0] ||
      e?.message ||
      "Cập nhật thông tin thất bại";

    notify.error(msg, {
      title: "Lỗi",
      duration: 3500,
    });
  } finally {
    loading.value = false;
  }
}

async function submitPassword() {
  if (!validatePassword()) return;

  passwordLoading.value = true;
  try {
    await auth.savePassword({
      current_password: passwordValues.value.current_password,
      password: passwordValues.value.password,
      password_confirmation: passwordValues.value.password_confirmation,
    });

    notify.success("Đổi mật khẩu thành công!", {
      title: "Thành công",
      duration: 2500,
    });

    passwordValues.value = {
      current_password: "",
      password: "",
      password_confirmation: "",
    };
    passwordErrors.value = {};
  } catch (e) {
    const msg =
      e?.response?.data?.message ||
      e?.response?.data?.errors?.current_password?.[0] ||
      e?.message ||
      "Đổi mật khẩu thất bại";

    notify.error(msg, {
      title: "Lỗi",
      duration: 3500,
    });
  } finally {
    passwordLoading.value = false;
  }
}

function goBack() {
  router.push("/shop");
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-background-dark py-8">
    <div class="max-w-3xl mx-auto px-4">
      <!-- Header -->
      <div class="flex items-center gap-4 mb-8">
        <button
          @click="goBack"
          class="p-2 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors"
        >
          <span class="material-symbols-outlined text-slate-600 dark:text-slate-300">
            arrow_back
          </span>
        </button>
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
            Tài khoản của tôi
          </h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            Quản lý thông tin cá nhân của bạn
          </p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl mb-6 w-fit">
        <button
          @click="activeTab = 'info'"
          class="px-5 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="
            activeTab === 'info'
              ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
              : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
          "
        >
          Thông tin cá nhân
        </button>
        <button
          @click="activeTab = 'password'"
          class="px-5 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="
            activeTab === 'password'
              ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
              : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
          "
        >
          Đổi mật khẩu
        </button>
      </div>

      <!-- Tab: Info -->
      <div v-if="activeTab === 'info'" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
            Thông tin cá nhân
          </h2>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Cập nhật thông tin hồ sơ của bạn
          </p>
        </div>

        <form @submit.prevent="submitInfo" class="p-6 space-y-6">
          <!-- Avatar -->
          <div class="flex flex-col sm:flex-row gap-6 items-start">
            <div class="flex-shrink-0 relative">
              <div class="size-24 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 ring-2 ring-slate-200 dark:ring-slate-700">
                <img
                  v-if="previewAvatar"
                  :src="previewAvatar"
                  alt="Avatar"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600">
                    person
                  </span>
                </div>

                <!-- Loading overlay -->
                <div
                  v-if="uploadLoading"
                  class="absolute inset-0 bg-black/50 flex items-center justify-center"
                >
                  <span class="material-symbols-outlined text-2xl text-white animate-spin">
                    progress_activity
                  </span>
                </div>
              </div>

              <!-- Upload/Remove buttons -->
              <div class="flex gap-2 mt-3">
                <button
                  type="button"
                  @click="triggerAvatarUpload"
                  :disabled="uploadLoading"
                  class="px-3 py-1.5 rounded-lg bg-primary/10 text-primary text-xs font-medium hover:bg-primary/20 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">upload</span>
                    Tải lên
                  </span>
                </button>
                <button
                  v-if="previewAvatar"
                  type="button"
                  @click="removeAvatar"
                  :disabled="uploadLoading"
                  class="px-3 py-1.5 rounded-lg bg-red-50 text-red-500 text-xs font-medium hover:bg-red-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <span class="flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">delete</span>
                    Xóa
                  </span>
                </button>
              </div>

              <!-- Hidden file input -->
              <input
                ref="avatarInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="handleAvatarChange"
              />
            </div>

            <div class="flex-1 pt-2">
              <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                Ảnh đại diện
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                PNG, JPG, JPEG, WEBP. Kích thước tối đa 2MB.
              </p>
              <p v-if="values.avatar" class="text-xs text-slate-400 mt-2 truncate">
                URL: {{ values.avatar }}
              </p>
            </div>
          </div>

          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Họ tên <span class="text-red-500">*</span>
            </label>
            <input
              v-model="values.name"
              type="text"
              placeholder="Nhập họ tên của bạn"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border text-slate-900 dark:text-white text-sm transition-all"
              :class="
                errors.name
                  ? 'border-red-400 focus:ring-red-400/50'
                  : 'border-slate-200 dark:border-slate-700 focus:ring-primary/50 focus:border-transparent focus:ring-2'
              "
            />
            <p v-if="errors.name" class="text-xs text-red-500 mt-1.5">
              {{ errors.name }}
            </p>
          </div>

          <!-- Email (readonly) -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Email
            </label>
            <input
              v-model="values.email"
              type="email"
              disabled
              class="w-full px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 text-sm cursor-not-allowed"
            />
            <p class="text-xs text-slate-400 mt-1.5">
              Email không thể thay đổi
            </p>
          </div>

          <!-- Birth Date -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Ngày sinh
            </label>
            <input
              v-model="values.birth_date"
              type="date"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border text-slate-900 dark:text-white text-sm transition-all"
              :class="
                errors.birth_date
                  ? 'border-red-400 focus:ring-red-400/50'
                  : 'border-slate-200 dark:border-slate-700 focus:ring-primary/50 focus:border-transparent focus:ring-2'
              "
            />
            <p v-if="errors.birth_date" class="text-xs text-red-500 mt-1.5">
              {{ errors.birth_date }}
            </p>
          </div>

          <!-- Address -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Địa chỉ
            </label>
            <textarea
              v-model="values.address"
              rows="3"
              placeholder="Nhập địa chỉ của bạn"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary/50 focus:border-transparent focus:ring-2 outline-none transition-all resize-none"
            ></textarea>
          </div>

          <!-- Submit -->
          <div class="flex justify-end pt-2">
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-2.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
            >
              <span
                v-if="loading"
                class="material-symbols-outlined text-lg animate-spin"
              >
                progress_activity
              </span>
              <span v-else class="material-symbols-outlined text-lg">
                save
              </span>
              {{ loading ? "Đang lưu..." : "Lưu thay đổi" }}
            </button>
          </div>
        </form>
      </div>

      <!-- Tab: Password -->
      <div v-if="activeTab === 'password'" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
            Đổi mật khẩu
          </h2>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Cập nhật mật khẩu để bảo vệ tài khoản của bạn
          </p>
        </div>

        <form @submit.prevent="submitPassword" class="p-6 space-y-6">
          <!-- Current Password -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Mật khẩu hiện tại <span class="text-red-500">*</span>
            </label>
            <input
              v-model="passwordValues.current_password"
              type="password"
              placeholder="Nhập mật khẩu hiện tại"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border text-slate-900 dark:text-white text-sm transition-all"
              :class="
                passwordErrors.current_password
                  ? 'border-red-400 focus:ring-red-400/50'
                  : 'border-slate-200 dark:border-slate-700 focus:ring-primary/50 focus:border-transparent focus:ring-2'
              "
            />
            <p v-if="passwordErrors.current_password" class="text-xs text-red-500 mt-1.5">
              {{ passwordErrors.current_password }}
            </p>
          </div>

          <!-- New Password -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Mật khẩu mới <span class="text-red-500">*</span>
            </label>
            <input
              v-model="passwordValues.password"
              type="password"
              placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border text-slate-900 dark:text-white text-sm transition-all"
              :class="
                passwordErrors.password
                  ? 'border-red-400 focus:ring-red-400/50'
                  : 'border-slate-200 dark:border-slate-700 focus:ring-primary/50 focus:border-transparent focus:ring-2'
              "
            />
            <p v-if="passwordErrors.password" class="text-xs text-red-500 mt-1.5">
              {{ passwordErrors.password }}
            </p>
          </div>

          <!-- Confirm Password -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
              Xác nhận mật khẩu mới <span class="text-red-500">*</span>
            </label>
            <input
              v-model="passwordValues.password_confirmation"
              type="password"
              placeholder="Nhập lại mật khẩu mới"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border text-slate-900 dark:text-white text-sm transition-all"
              :class="
                passwordErrors.password_confirmation
                  ? 'border-red-400 focus:ring-red-400/50'
                  : 'border-slate-200 dark:border-slate-700 focus:ring-primary/50 focus:border-transparent focus:ring-2'
              "
            />
            <p v-if="passwordErrors.password_confirmation" class="text-xs text-red-500 mt-1.5">
              {{ passwordErrors.password_confirmation }}
            </p>
          </div>

          <!-- Submit -->
          <div class="flex justify-end pt-2">
            <button
              type="submit"
              :disabled="passwordLoading"
              class="px-6 py-2.5 rounded-xl bg-primary text-white text-sm font-medium hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
            >
              <span
                v-if="passwordLoading"
                class="material-symbols-outlined text-lg animate-spin"
              >
                progress_activity
              </span>
              <span v-else class="material-symbols-outlined text-lg">
                lock_reset
              </span>
              {{ passwordLoading ? "Đang lưu..." : "Đổi mật khẩu" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
