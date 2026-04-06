<template>
  <div class="min-h-[70vh] flex items-start justify-center px-4">
    <div
      class="w-full max-w-md mt-14 bg-white rounded-2xl shadow-sm ring-1 ring-gray-200 p-6 sm:p-7 animate-fadeIn"
    >
      <!-- Header -->
      <div class="text-center mb-6">
        <div class="mx-auto mb-3 h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center">
          <!-- icon kiểu "grid" -->
          <svg
            viewBox="0 0 24 24"
            class="h-7 w-7 text-blue-600"
            fill="currentColor"
            aria-hidden="true"
          >
            <path
              d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"
            />
          </svg>
        </div>

        <h1 class="text-xl font-semibold text-gray-900">Trang đăng nhập</h1>
        <p class="text-sm text-gray-500 mt-1">Nhập thông tin tài khoản của bạn để truy cập.</p>
      </div>

      <!-- Error Alert -->
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
        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="email">Email</label>

          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <!-- mail icon -->
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 2l-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z"
                />
              </svg>
            </span>

            <input
              id="email"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-3 py-2 text-sm outline-none transition
                     focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:opacity-60"
              :class="emailError ? 'border-red-300 focus:border-red-500 focus:ring-red-200' : 'border-gray-200'"
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

        <!-- Password -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700" for="password">Mật khẩu</label>
          </div>

          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <!-- lock icon -->
              <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                <path
                  d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-9a2 2 0 00-2-2zM10 7a2 2 0 114 0v2h-4V7z"
                />
              </svg>
            </span>

            <input
              id="password"
              class="w-full rounded-lg border bg-gray-50 pl-10 pr-10 py-2 text-sm outline-none transition
                     focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:opacity-60"
              :class="passwordError ? 'border-red-300 focus:border-red-500 focus:ring-red-200' : 'border-gray-200'"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="current-password"
              :disabled="loading"
            />

            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
              @click="showPassword = !showPassword"
              :disabled="loading"
              aria-label="Toggle password visibility"
            >
              <!-- eye / eye-off -->
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

          <p v-if="passwordError" class="mt-1 text-xs text-red-600">{{ passwordError }}</p>
          
        </div>

        <!-- Remember me -->
        <div class="flex items-center gap-2">
          <input
            id="rememberMe"
            type="checkbox"
            v-model="rememberMe"
            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-200"
            :disabled="loading"
          />
          <label for="rememberMe" class="text-sm text-gray-600">Ghi nhớ tài khoản</label>
        </div>

        <!-- Submit -->
        <button
          class="w-full inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
                 transition hover:bg-blue-700 active:translate-y-[1px] disabled:opacity-60 disabled:cursor-not-allowed"
          :disabled="loading"
        >
          <span v-if="loading" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
          {{ loading ? "Đang đăng nhập..." : "Đăng nhập" }}
        </button>

        <!-- Divider -->
        <div class="relative py-2">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
          </div>
          <div class="relative flex justify-center">
            <span class="bg-white px-3 text-xs font-semibold text-gray-400">Hoặc tiếp tục với SSO</span>
          </div>
        </div>

        <!-- SSO buttons (UI only) -->
        <div class="grid grid-cols-2 gap-2">
          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700
                   hover:bg-gray-50 disabled:opacity-60"
            :disabled="loading"
          >
            <svg viewBox="0 0 24 24" class="h-4 w-4" aria-hidden="true">
              <path
                d="M21.8 10.2H12v3.7h5.6c-.8 2.3-2.8 3.7-5.6 3.7a6.4 6.4 0 110-12.8c1.7 0 3.1.6 4.2 1.6l2.6-2.6A9.9 9.9 0 0012 2.2 9.8 9.8 0 1021.8 12c0-.6-.1-1.2-.2-1.8z"
                fill="currentColor"
              />
            </svg>
            Google
          </button>

          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700
                   hover:bg-gray-50 disabled:opacity-60"
            :disabled="loading"
          >
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
              <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z" />
            </svg>
            Microsoft
          </button>
        </div>
        <p class="text-center text-sm text-gray-600 pt-2">
          Bạn không có tài khoản?
          <RouterLink to="/register" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline">
            Đăng ký ngay
          </RouterLink>
        </p>

        <p class="text-center text-sm text-gray-500">
          Quên mật khẩu?
          <RouterLink to="/forgot-password" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline">
            Khôi phục mật khẩu
          </RouterLink>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";

const router = useRouter();
const auth = useAuthStore();

const email = ref("");
const password = ref("");
const rememberMe = ref(false);

const showPassword = ref(false);
const loading = ref(false);
const error = ref("");

const emailError = ref("");
const passwordError = ref("");

function validate() {
  emailError.value = "";
  passwordError.value = "";

  const emailTrim = (email.value || "").trim();

  if (!emailTrim) emailError.value = "Điach email là bắt buộc";
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailTrim))
    emailError.value = "Vui lòng nhập địa chỉ email hợp lệ";

  if (!password.value) passwordError.value = "Mật khẩu là bắt buộc";

  return !emailError.value && !passwordError.value;
}

async function onSubmit() {
  error.value = "";
  if (!validate()) return;

  loading.value = true;
  try {
    const user = await auth.login({
      email: email.value,
      password: password.value,
      rememberMe: rememberMe.value,
    });

    router.push(user.role === "admin" ? "/admin" : "/shop");
  } catch (e) {
    error.value = e?.response?.data?.message || "Sai email hoặc mật khẩu";
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