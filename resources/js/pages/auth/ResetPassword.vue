<template>
  <div class="min-h-[70vh] flex items-start justify-center px-4">
    <div
      class="w-full max-w-md mt-14 bg-white rounded-2xl shadow-sm ring-1 ring-gray-200 p-6 sm:p-7 animate-fadeIn"
    >
      <!-- Header -->
      <div class="text-center mb-6">
        <div class="mx-auto mb-3 h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center">
          <svg viewBox="0 0 24 24" class="h-7 w-7 text-blue-600" fill="currentColor" aria-hidden="true">
            <path d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-9a2 2 0 00-2-2zM10 7a2 2 0 114 0v2h-4V7z"/>
          </svg>
        </div>
        <h1 class="text-xl font-semibold text-gray-900">Đặt lại mật khẩu</h1>
        <p class="text-sm text-gray-500 mt-1">Nhập mã xác nhận đã gửi qua email và tạo mật khẩu mới.</p>
      </div>

      <!-- Error Alert -->
      <div
        v-if="error"
        class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 flex items-start gap-2"
        role="alert"
      >
        <span class="mt-0.5">
          <svg viewBox="0 0 24 24" class="h-5 w-5 text-red-600" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M12 2a10 10 0 100 20 10 10 0 000-20zm.75 5a.75.75 0 00-1.5 0v6a.75.75 0 001.5 0V7zm-.75 11a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
          </svg>
        </span>
        <span class="leading-5">{{ error }}</span>
      </div>

      <!-- Success Alert -->
      <div
        v-if="successMessage"
        class="mb-4 rounded-lg border border-green-200 bg-green-50 px-3 py-3 text-sm text-green-700 flex items-start gap-2"
        role="alert"
      >
        <span class="mt-0.5">
          <svg viewBox="0 0 24 24" class="h-5 w-5 text-green-600" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
        </span>
        <span class="leading-5">{{ successMessage }}</span>
      </div>

      <form class="space-y-4" @submit.prevent="onSubmit" v-if="!successMessage">
        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="email">Email</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 2l-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z"/>
              </svg>
            </span>
            <input
              id="email"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-3 py-2 text-sm outline-none transition
                     focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:opacity-60"
              :class="emailError ? 'border-red-300' : 'border-gray-200'"
              v-model="email"
              type="email"
              placeholder="abc123@gmail.com"
              autocomplete="email"
              :disabled="loading"
              autofocus
            />
          </div>
          <p v-if="emailError" class="mt-1 text-xs text-red-600">{{ emailError }}</p>
        </div>

        <!-- Verification Code -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="code">Mã xác nhận</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path d="M9 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V4zm6 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V4zm-6 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H10a1 1 0 01-1-1v-4zm6 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
              </svg>
            </span>
            <input
              id="code"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-3 py-2 text-sm outline-none transition text-center tracking-widest font-mono text-lg
                     focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:opacity-60"
              :class="codeError ? 'border-red-300' : 'border-gray-200'"
              v-model="code"
              type="text"
              placeholder="123456"
              maxlength="6"
              inputmode="numeric"
              pattern="[0-9]*"
              autocomplete="one-time-code"
              :disabled="loading"
            />
          </div>
          <p v-if="codeError" class="mt-1 text-xs text-red-600">{{ codeError }}</p>
        </div>

        <!-- New Password -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="password">Mật khẩu mới</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-9a2 2 0 00-2-2zM10 7a2 2 0 114 0v2h-4V7z"/>
              </svg>
            </span>
            <input
              id="password"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-10 py-2 text-sm outline-none transition
                     focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:opacity-60"
              :class="passwordError ? 'border-red-300' : 'border-gray-200'"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Tối thiểu 6 ký tự"
              autocomplete="new-password"
              :disabled="loading"
            />
            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
              @click="showPassword = !showPassword"
              :disabled="loading"
              aria-label="Toggle password visibility"
            >
              <svg v-if="!showPassword" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/>
                <path d="M12 9a3 3 0 100 6 3 3 0 000-6z"/>
              </svg>
              <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path d="M3 4.27L4.28 3 21 19.72 19.73 21l-2.2-2.2A11.2 11.2 0 0112 20C5 20 2 13 2 13a18.6 18.6 0 014.2-5.6L3 4.27zM12 6c7 0 10 7 10 7a18.4 18.4 0 01-3.38 4.67l-2.12-2.12A5 5 0 008.45 9.1L6.5 7.16A11.2 11.2 0 0112 6z"/>
              </svg>
            </button>
          </div>
          <p v-if="passwordError" class="mt-1 text-xs text-red-600">{{ passwordError }}</p>
        </div>

        <!-- Confirm Password -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="password_confirmation">Xác nhận mật khẩu</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-9a2 2 0 00-2-2zM10 7a2 2 0 114 0v2h-4V7z"/>
              </svg>
            </span>
            <input
              id="password_confirmation"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-10 py-2 text-sm outline-none transition
                     focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:opacity-60"
              :class="confirmError ? 'border-red-300' : 'border-gray-200'"
              v-model="password_confirmation"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Nhập lại mật khẩu"
              autocomplete="new-password"
              :disabled="loading"
            />
          </div>
          <p v-if="confirmError" class="mt-1 text-xs text-red-600">{{ confirmError }}</p>
        </div>

        <button
          class="w-full inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
                 transition hover:bg-blue-700 active:translate-y-[1px] disabled:opacity-60 disabled:cursor-not-allowed"
          :disabled="loading"
        >
          <span v-if="loading" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
          {{ loading ? 'Đang xử lý...' : 'Đặt lại mật khẩu' }}
        </button>
      </form>

      <!-- Back to login -->
      <p class="text-center text-sm text-gray-600 pt-2">
        <RouterLink to="/login" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline">
          Quay lại đăng nhập
        </RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import api from "../../api";

const router = useRouter();
const route = useRoute();

const email = ref(route.query.email || "");
const code = ref("");
const password = ref("");
const password_confirmation = ref("");
const showPassword = ref(false);

const loading = ref(false);
const error = ref("");
const successMessage = ref("");

const emailError = ref("");
const codeError = ref("");
const passwordError = ref("");
const confirmError = ref("");

function validate() {
  emailError.value = "";
  codeError.value = "";
  passwordError.value = "";
  confirmError.value = "";

  const emailTrim = (email.value || "").trim();
  if (!emailTrim) emailError.value = "Email là bắt buộc";
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailTrim)) emailError.value = "Email không hợp lệ";

  const codeTrim = (code.value || "").trim().replace(/\s/g, "");
  if (!codeTrim) codeError.value = "Mã xác nhận là bắt buộc";
  else if (!/^\d{6}$/.test(codeTrim)) codeError.value = "Mã xác nhận gồm 6 chữ số";

  if (!password.value) passwordError.value = "Mật khẩu là bắt buộc";
  else if (password.value.length < 6) passwordError.value = "Mật khẩu tối thiểu 6 ký tự";

  if (!password_confirmation.value) confirmError.value = "Xác nhận mật khẩu là bắt buộc";
  else if (password_confirmation.value !== password.value) confirmError.value = "Mật khẩu xác nhận không khớp";

  return !emailError.value && !codeError.value && !passwordError.value && !confirmError.value;
}

async function onSubmit() {
  error.value = "";
  if (!validate()) return;

  loading.value = true;
  try {
    const cleanCode = code.value.trim().replace(/\s/g, "");
    const res = await api.post("/api/auth/verify-reset-code", {
      email: email.value.trim(),
      code: cleanCode,
    });

    const token = res.data.token;

    await api.post("/api/auth/reset-password", {
      token: token,
      password: password.value,
      password_confirmation: password_confirmation.value,
    });

    successMessage.value = "Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập với mật khẩu mới.";
    setTimeout(() => {
      router.push("/login");
    }, 3000);
  } catch (e) {
    const msg = e?.response?.data?.message
      || e?.response?.data?.errors?.code?.[0]
      || e?.response?.data?.errors?.token?.[0]
      || "Đã xảy ra lỗi. Vui lòng thử lại.";
    error.value = msg;
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.6s ease-out both;
}
</style>
