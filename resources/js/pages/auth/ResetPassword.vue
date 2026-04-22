<template>
  <div class="auth-page min-h-screen flex items-center justify-center relative overflow-hidden">
    <!-- Full Screen Background -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500">
      <div class="absolute top-1/4 left-1/4 w-[500px] h-[500px] bg-blue-500/30 rounded-full blur-[120px] animate-pulse"></div>
      <div class="absolute bottom-1/4 right-1/4 w-[400px] h-[400px] bg-blue-400/30 rounded-full blur-[100px] animate-pulse" style="animation-delay: 1s;"></div>
      <div class="absolute top-1/2 right-1/3 w-[300px] h-[300px] bg-cyan-400/20 rounded-full blur-[80px] animate-pulse" style="animation-delay: 2s;"></div>
      <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>

    <!-- Logo Top Left -->
    <div class="absolute top-6 left-6 flex items-center gap-3 z-20">
      <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center">
        <svg viewBox="0 0 24 24" class="h-7 w-7 text-white" fill="currentColor">
          <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/>
        </svg>
      </div>
      <span class="text-xl font-bold text-white tracking-tight">BMC Shoes</span>
    </div>

    <!-- Centered Form Card -->
    <div class="relative z-10 w-full max-w-md mx-4">
      <div class="bg-white rounded-lg shadow-lg shadow-gray-900/20 ring-1 ring-white/50 p-6 sm:p-8 animate-fadeIn">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2">
          <div class="flex gap-1.5">
            <div class="w-10 h-1.5 bg-blue-600 rounded-full"></div>
            <div class="w-10 h-1.5 bg-blue-500 rounded-full"></div>
            <div class="w-10 h-1.5 bg-cyan-500 rounded-full"></div>
          </div>
        </div>

        <div class="text-center mb-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-1">Đặt lại mật khẩu</h1>
          <p class="text-sm text-gray-500">Nhập mã xác nhận và tạo mật khẩu mới.</p>
        </div>

        <div v-if="error" class="mb-5 rounded-xl border border-red-200/50 bg-gradient-to-r from-red-50 to-orange-50 px-4 py-3 text-sm text-red-700 flex items-start gap-3 animate-shake" role="alert">
          <span class="mt-0.5 flex-shrink-0">
            <svg viewBox="0 0 24 24" class="h-5 w-5 text-red-500" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
          </span>
          <span class="leading-5">{{ error }}</span>
        </div>

        <div v-if="successMessage" class="mb-5 rounded-xl border border-emerald-200/50 bg-gradient-to-r from-emerald-50 to-teal-50 px-4 py-3 text-sm text-emerald-700 flex items-start gap-3" role="alert">
          <span class="mt-0.5 flex-shrink-0">
            <svg viewBox="0 0 24 24" class="h-5 w-5 text-emerald-500" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
          </span>
          <span class="leading-5">{{ successMessage }}</span>
        </div>

        <form class="space-y-4" @submit.prevent="onSubmit" v-if="!successMessage">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="email">Email</label>
            <div class="relative group">
              <input id="email" class="w-full rounded-xl border bg-gray-50/80 px-4 py-3 pl-11 pr-4 text-sm outline-none transition-all duration-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="emailError ? 'border-red-300 bg-red-50/50' : 'border-gray-200 hover:border-gray-300'" v-model="email" type="email" placeholder="abc123@gmail.com" autocomplete="email" :disabled="loading" autofocus/>
              <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 2l-8 5-8-5h16zm0 12H4V8l8 5 8-5v10z"/></svg>
              </span>
            </div>
            <p v-if="emailError" class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
              <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="currentColor"><path fill-rule="evenodd" d="M12 2a10 10 0 100 20 10 10 0 000-20zm.75 5a.75.75 0 00-1.5 0v6a.75.75 0 001.5 0V7zm-.75 11a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
              {{ emailError }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="code">Mã xác nhận</label>
            <div class="relative group">
              <input id="code" class="w-full rounded-xl border bg-gray-50/80 px-4 py-3 pl-11 pr-4 text-sm outline-none transition-all duration-200 text-center text-2xl tracking-[0.3em] font-mono placeholder:text-gray-300 placeholder:tracking-normal placeholder:text-base focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="codeError ? 'border-red-300 bg-red-50/50' : 'border-gray-200 hover:border-gray-300'" v-model="code" type="text" placeholder="● ● ● ● ● ●" maxlength="6" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" :disabled="loading"/>
              <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M9 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V4zm6 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V4z"/></svg>
              </span>
            </div>
            <p v-if="codeError" class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
              <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="currentColor"><path fill-rule="evenodd" d="M12 2a10 10 0 100 20 10 10 0 000-20zm.75 5a.75.75 0 00-1.5 0v6a.75.75 0 001.5 0V7zm-.75 11a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
              {{ codeError }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="password">Mật khẩu mới</label>
            <div class="relative group">
              <input id="password" class="w-full rounded-xl border bg-gray-50/80 px-4 py-3 pl-11 pr-12 text-sm outline-none transition-all duration-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="passwordError ? 'border-red-300 bg-red-50/50' : 'border-gray-200 hover:border-gray-300'" v-model="password" :type="showPassword ? 'text' : 'password'" placeholder="Tối thiểu 6 ký tự" autocomplete="new-password" :disabled="loading"/>
              <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-9a2 2 0 00-2-2zM10 7a2 2 0 114 0v2h-4V7z"/></svg>
              </span>
              <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition-colors disabled:opacity-60" @click="showPassword = !showPassword" :disabled="loading" aria-label="Toggle password visibility">
                <svg v-if="!showPassword" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/><path d="M12 9a3 3 0 100 6 3 3 0 000-6z"/></svg>
                <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M3 4.27L4.28 3 21 19.72 19.73 21l-2.2-2.2A11.2 11.2 0 0112 20C5 20 2 13 2 13a18.6 18.6 0 014.2-5.6L3 4.27zM12 6c7 0 10 7 10 7a18.4 18.4 0 01-3.38 4.67l-2.12-2.12A5 5 0 008.45 9.1L6.5 7.16A11.2 11.2 0 0112 6z"/></svg>
              </button>
            </div>
            <p v-if="passwordError" class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
              <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="currentColor"><path fill-rule="evenodd" d="M12 2a10 10 0 100 20 10 10 0 000-20zm.75 5a.75.75 0 00-1.5 0v6a.75.75 0 001.5 0V7zm-.75 11a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
              {{ passwordError }}
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="password_confirmation">Xác nhận mật khẩu</label>
            <div class="relative group">
              <input id="password_confirmation" class="w-full rounded-xl border bg-gray-50/80 px-4 py-3 pl-11 pr-12 text-sm outline-none transition-all duration-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="confirmError ? 'border-red-300 bg-red-50/50' : 'border-gray-200 hover:border-gray-300'" v-model="password_confirmation" :type="showPassword ? 'text' : 'password'" placeholder="Nhập lại mật khẩu" autocomplete="new-password" :disabled="loading"/>
              <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-500 transition-colors">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-9a2 2 0 00-2-2zM10 7a2 2 0 114 0v2h-4V7z"/></svg>
              </span>
              <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 transition-colors disabled:opacity-60" @click="showPassword = !showPassword" :disabled="loading" aria-label="Toggle confirm password visibility">
                <svg v-if="!showPassword" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/><path d="M12 9a3 3 0 100 6 3 3 0 000-6z"/></svg>
                <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M3 4.27L4.28 3 21 19.72 19.73 21l-2.2-2.2A11.2 11.2 0 0112 20C5 20 2 13 2 13a18.6 18.6 0 014.2-5.6L3 4.27zM12 6c7 0 10 7 10 7a18.4 18.4 0 01-3.38 4.67l-2.12-2.12A5 5 0 008.45 9.1L6.5 7.16A11.2 11.2 0 0112 6z"/></svg>
              </button>
            </div>
            <p v-if="confirmError" class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
              <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="currentColor"><path fill-rule="evenodd" d="M12 2a10 10 0 100 20 10 10 0 000-20zm.75 5a.75.75 0 00-1.5 0v6a.75.75 0 001.5 0V7zm-.75 11a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
              {{ confirmError }}
            </p>
          </div>

          <button class="relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition-all duration-200 hover:shadow-xl hover:shadow-blue-500/40 hover:from-blue-700 hover:to-cyan-700 active:translate-y-0.5 active:shadow-md disabled:opacity-60 disabled:cursor-not-allowed" :disabled="loading">
            <span v-if="loading" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
            <span class="relative z-10">{{ loading ? 'Đang xử lý...' : 'Đặt lại mật khẩu' }}</span>
          </button>
        </form>

        <div class="mt-6 pt-5 border-t border-gray-100 text-center">
          <RouterLink to="/login" class="font-semibold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-600 hover:opacity-80 transition-opacity inline-flex items-center gap-1.5">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Quay lại đăng nhập
          </RouterLink>
        </div>

        <div class="mt-4 text-center">
          <RouterLink to="/" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-white transition-colors">
            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Quay về trang chủ
          </RouterLink>
        </div>
      </div>
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
    const res = await api.post("/api/auth/verify-reset-code", { email: email.value.trim(), code: cleanCode });
    const token = res.data.token;
    await api.post("/api/auth/reset-password", { token: token, password: password.value, password_confirmation: password_confirmation.value });
    successMessage.value = "Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập với mật khẩu mới.";
    setTimeout(() => { router.push("/login"); }, 3000);
  } catch (e) {
    const msg = e?.response?.data?.message || e?.response?.data?.errors?.code?.[0] || e?.response?.data?.errors?.token?.[0] || "Đã xảy ra lỗi. Vui lòng thử lại.";
    error.value = msg;
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
@keyframes shake { 0%, 100% { transform: translateX(0); } 10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); } 20%, 40%, 60%, 80% { transform: translateX(4px); } }
.animate-fadeIn { animation: fadeIn 0.5s ease-out both; }
.animate-shake { animation: shake 0.5s ease-in-out; }
</style>
