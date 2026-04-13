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
        <h1 class="text-xl font-semibold text-gray-900">Quên mật khẩu?</h1>
        <p class="text-sm text-gray-500 mt-1">Nhập email đã đăng ký để nhận mã xác nhận.</p>
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

      <form class="space-y-4" @submit.prevent="onSubmit">
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

        <button
          class="w-full inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
                 transition hover:bg-blue-700 active:translate-y-[1px] disabled:opacity-60 disabled:cursor-not-allowed"
          :disabled="loading"
        >
          <span v-if="loading" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
          {{ loading ? 'Đang gửi mã...' : 'Gửi mã xác nhận' }}
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
import { useRouter } from "vue-router";
import api from "../../api";

const router = useRouter();

const email = ref("");
const loading = ref(false);
const error = ref("");
const emailError = ref("");

function validate() {
  emailError.value = "";
  const emailTrim = (email.value || "").trim();
  if (!emailTrim) {
    emailError.value = "Email là bắt buộc";
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailTrim)) {
    emailError.value = "Vui lòng nhập địa chỉ email hợp lệ";
  }
  return !emailError.value;
}

async function onSubmit() {
  error.value = "";
  if (!validate()) return;

  loading.value = true;
  try {
    const response = await api.post("/api/auth/forgot-password", {
      email: email.value.trim(),
    });
    if (response.data.message) {
      error.value = "";
      router.push({
        path: "/reset-password",
        query: { email: email.value.trim() },
      });
    }
  } catch (e) {
    error.value = e?.response?.data?.message || e?.response?.data?.errors?.email?.[0] || "Đã xảy ra lỗi. Vui lòng thử lại.";
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
