<template>
  <div class="min-h-[70vh] flex items-start justify-center px-4">
    <div
      class="w-full max-w-md mt-14 bg-white rounded-2xl shadow-sm ring-1 ring-gray-200 p-6 sm:p-7 animate-fadeIn"
    >
      <!-- Header -->
      <div class="text-center mb-6">
        <div class="mx-auto mb-3 h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center">
          <svg viewBox="0 0 24 24" class="h-7 w-7 text-blue-600" fill="currentColor" aria-hidden="true">
            <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z" />
          </svg>
        </div>

        <h1 class="text-xl font-semibold text-gray-900">Tạo tài khoản mới</h1>
        <p class="text-sm text-gray-500 mt-1">Điền thông tin của bạn để tạo tài khoản mới</p>
      </div>

      <!-- General Error -->
      <div
        v-if="error"
        class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 flex items-start gap-2"
        role="alert"
      >
        <span class="mt-0.5">
          <svg viewBox="0 0 24 24" class="h-5 w-5 text-red-600" fill="currentColor" aria-hidden="true">
            <path
              fill-rule="evenodd"
              d="M12 2a10 10 0 100 20 10 10 0 000-20zm.75 5a.75.75 0 00-1.5 0v6a.75.75 0 001.5 0V7zm-.75 11a1 1 0 100-2 1 1 0 000 2z"
              clip-rule="evenodd"
            />
          </svg>
        </span>
        <span class="leading-5">{{ error }}</span>
      </div>

      <form class="space-y-4" @submit.prevent="onSubmit">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="name">Họ và tên</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M12 12a5 5 0 10-5-5 5 5 0 005 5zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5z"
                />
              </svg>
            </span>

            <input
              id="name"
              v-model="name"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-3 py-2 text-sm outline-none transition
                     focus:ring-2 disabled:opacity-60"
              :class="fieldClass('name')"
              placeholder="Nguyễn Văn A"
              :disabled="loading"
              autocomplete="name"
              @input="onFieldInput('name')"
            />
          </div>
          <p v-if="shouldShowFieldError('name')" class="mt-1 text-xs text-red-600">{{ errors.name }}</p>
        </div>

        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="email">Email</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 2l-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z"
                />
              </svg>
            </span>

            <input
              id="email"
              v-model="email"
              type="email"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-3 py-2 text-sm outline-none transition
                     focus:ring-2 disabled:opacity-60"
              :class="fieldClass('email')"
              placeholder="name@company.com"
              :disabled="loading"
              autocomplete="email"
              @input="onFieldInput('email')"
            />
          </div>
          <p v-if="shouldShowFieldError('email')" class="mt-1 text-xs text-red-600">{{ errors.email }}</p>
        </div>

        <!-- Birth date (nullable like your code) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="birth_date">Ngày sinh</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M7 2h2v2h6V2h2v2h3a2 2 0 012 2v14a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2h3V2zm15 8H2v10h20V10z"
                />
              </svg>
            </span>

            <input
              id="birth_date"
              v-model="birth_date"
              type="date"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-3 py-2 text-sm outline-none transition
                     focus:ring-2 disabled:opacity-60"
              :class="fieldClass('birth_date')"
              :disabled="loading"
              @input="onFieldInput('birth_date')"
            />
          </div>
          <p v-if="shouldShowFieldError('birth_date')" class="mt-1 text-xs text-red-600">
            {{ errors.birth_date }}
          </p>
        </div>

        <!-- Address (nullable like your code) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="address">Địa chỉ</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M12 2a7 7 0 00-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1114.5 9 2.5 2.5 0 0112 11.5z"
                />
              </svg>
            </span>

            <input
              id="address"
              v-model="address"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-3 py-2 text-sm outline-none transition
                     focus:ring-2 disabled:opacity-60"
              :class="fieldClass('address')"
              placeholder="Số nhà, đường, phường/xã..."
              :disabled="loading"
              autocomplete="street-address"
              @input="onFieldInput('address')"
            />
          </div>
          <p v-if="shouldShowFieldError('address')" class="mt-1 text-xs text-red-600">{{ errors.address }}</p>
        </div>

        <!-- Password -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="password">Mật khẩu</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-9a2 2 0 00-2-2zM10 7a2 2 0 114 0v2h-4V7z"
                />
              </svg>
            </span>

            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-10 py-2 text-sm outline-none transition
                     focus:ring-2 disabled:opacity-60"
              :class="fieldClass('password')"
              placeholder="••••••••"
              :disabled="loading"
              autocomplete="new-password"
              @input="onPasswordInput"
            />

            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 disabled:opacity-60"
              @click="showPassword = !showPassword"
              :disabled="loading"
              aria-label="Toggle password visibility"
            >
              <svg v-if="!showPassword" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 110-10 5 5 0 010 10z"
                />
                <path d="M12 9a3 3 0 100 6 3 3 0 000-6z" />
              </svg>
              <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M3 4.27L4.28 3 21 19.72 19.73 21l-2.2-2.2A11.2 11.2 0 0112 20C5 20 2 13 2 13a18.6 18.6 0 014.2-5.6L3 4.27zM12 6c7 0 10 7 10 7a18.4 18.4 0 01-3.38 4.67l-2.12-2.12A5 5 0 008.45 9.1L6.5 7.16A11.2 11.2 0 0112 6z"
                />
              </svg>
            </button>
          </div>

          <p v-if="shouldShowFieldError('password')" class="mt-1 text-xs text-red-600">{{ errors.password }}</p>
          <p v-else class="mt-1 text-xs text-gray-500">Mật khẩu có ít nhất 8 ký tự</p>
        </div>

        <!-- Password confirmation -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="password_confirmation">
            Nhập lại mật khẩu
          </label>

          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-9a2 2 0 00-2-2zM10 7a2 2 0 114 0v2h-4V7z"
                />
              </svg>
            </span>

            <input
              id="password_confirmation"
              v-model="password_confirmation"
              :type="showConfirmPassword ? 'text' : 'password'"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-10 py-2 text-sm outline-none transition
                     focus:ring-2 disabled:opacity-60"
              :class="fieldClass('password_confirmation')"
              placeholder="••••••••"
              :disabled="loading"
              autocomplete="new-password"
              @input="onConfirmPasswordInput"
            />

            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 disabled:opacity-60"
              @click="showConfirmPassword = !showConfirmPassword"
              :disabled="loading"
              aria-label="Toggle confirm password visibility"
            >
              <svg v-if="!showConfirmPassword" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 110-10 5 5 0 010 10z"
                />
                <path d="M12 9a3 3 0 100 6 3 3 0 000-6z" />
              </svg>
              <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M3 4.27L4.28 3 21 19.72 19.73 21l-2.2-2.2A11.2 11.2 0 0112 20C5 20 2 13 2 13a18.6 18.6 0 014.2-5.6L3 4.27zM12 6c7 0 10 7 10 7a18.4 18.4 0 01-3.38 4.67l-2.12-2.12A5 5 0 008.45 9.1L6.5 7.16A11.2 11.2 0 0112 6z"
                />
              </svg>
            </button>
          </div>

          <p v-if="shouldShowFieldError('password_confirmation')" class="mt-1 text-xs text-red-600">
            {{ errors.password_confirmation }}
          </p>
        </div>

        <!-- Submit -->
        <button
          class="w-full inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
                 transition hover:bg-blue-700 active:translate-y-[1px] disabled:opacity-60 disabled:cursor-not-allowed"
          :disabled="loading"
        >
          <span v-if="loading" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
          {{ loading ? "Đang tạo..." : "Đăng ký" }}
        </button>

        <p class="text-center text-sm text-gray-600 pt-2">
          Đã có tài khoản?
          <RouterLink to="/login" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline">
            Đăng nhập
          </RouterLink>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";

const router = useRouter();
const auth = useAuthStore();

const name = ref("");
const email = ref("");
const birth_date = ref("");
const address = ref("");
const password = ref("");
const password_confirmation = ref("");

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const loading = ref(false);
const error = ref("");
const submitted = ref(false);

const errors = reactive({
  name: "",
  email: "",
  birth_date: "",
  address: "",
  password: "",
  password_confirmation: "",
});

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function clearAllErrors() {
  error.value = "";
  Object.keys(errors).forEach((k) => (errors[k] = ""));
}

function shouldShowFieldError(field) {
  return submitted.value && !!errors[field];
}

function fieldClass(field) {
  // nếu chưa submit thì luôn border bình thường
  if (!submitted.value) return "border-gray-200 focus:border-blue-500 focus:ring-blue-200";
  if (errors[field]) return "border-red-300 focus:border-red-500 focus:ring-red-200";
  return "border-gray-200 focus:border-blue-500 focus:ring-blue-200";
}

function validateField(field) {
  errors[field] = "";

  if (field === "name") {
    if (!name.value.trim()) errors.name = "Vui lòng nhập họ và tên";
  }

  if (field === "email") {
    const v = email.value.trim();
    if (!v) errors.email = "Email là bắt buộc";
    else if (!EMAIL_RE.test(v)) errors.email = "Email không đúng định dạng";
  }

  if (field === "password") {
    if (!password.value) errors.password = "Mật khẩu là bắt buộc";
    else if (password.value.length < 8) errors.password = "Mật khẩu tối thiểu 8 ký tự";
  }

  if (field === "password_confirmation") {
    if (!password_confirmation.value) errors.password_confirmation = "Vui lòng nhập lại mật khẩu";
    else if (password_confirmation.value !== password.value)
      errors.password_confirmation = "Mật khẩu nhập lại không khớp";
  }

  // birth_date/address: đang nullable như code bạn => không bắt buộc
  // Nếu muốn bắt buộc thì mở 2 dòng dưới:
  // if (field === "birth_date" && !birth_date.value) errors.birth_date = "Vui lòng chọn ngày sinh";
  // if (field === "address" && !address.value.trim()) errors.address = "Vui lòng nhập địa chỉ";
}

function validateAll() {
  validateField("name");
  validateField("email");
  validateField("password");
  validateField("password_confirmation");

  return !errors.name && !errors.email && !errors.password && !errors.password_confirmation && !errors.birth_date && !errors.address;
}
function onFieldInput(field) {
  if (!submitted.value) return;
  validateField(field);
}

function onPasswordInput() {
  if (!submitted.value) return;
  validateField("password");
  if (password_confirmation.value) validateField("password_confirmation");
}

function onConfirmPasswordInput() {
  if (!submitted.value) return;
  validateField("password_confirmation");
}
function applyBackendErrors(e) {
  const data = e?.response?.data;
  const backendErrors = data?.errors;

  if (backendErrors && typeof backendErrors === "object") {
    Object.keys(backendErrors).forEach((k) => {
      if (k in errors) errors[k] = Array.isArray(backendErrors[k]) ? backendErrors[k][0] : String(backendErrors[k]);
    });
  }
}

async function onSubmit() {
  submitted.value = true;

  clearAllErrors();

  if (!validateAll()) return;

  loading.value = true;
  try {
    const user = await auth.register({
      name: name.value,
      email: email.value,
      birth_date: birth_date.value || null,
      address: address.value || null,
      password: password.value,
      password_confirmation: password_confirmation.value,
    });

    router.push(user.role === "admin" ? "/admin" : "/shop");
  } catch (e) {
    if (e?.response?.status === 422) {
      applyBackendErrors(e);
      error.value = e?.response?.data?.message || "Dữ liệu không hợp lệ";
    } else {
      error.value = e?.response?.data?.message || "Đăng ký thất bại";
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.animate-fadeIn {
  animation: fadeIn 0.6s ease-out both;
}
</style>