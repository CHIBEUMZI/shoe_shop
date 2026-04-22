<template>
  <div class="auth-page min-h-screen flex items-center justify-center relative overflow-hidden py-8">
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
    <div class="relative z-10 w-full max-w-lg mx-4">
      <div class="bg-white rounded-lg shadow-lg shadow-gray-900/20 ring-1 ring-white/50 p-8 animate-fadeIn">
        <!-- Decorative top border -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2">
          <div class="flex gap-2">
            <div class="w-12 h-2 bg-blue-600 rounded-full"></div>
            <div class="w-12 h-2 bg-blue-500 rounded-full"></div>
            <div class="w-12 h-2 bg-cyan-500 rounded-full"></div>
          </div>
        </div>

        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-gray-900 mb-2">
            {{ currentStep === 'register' ? 'Tạo tài khoản mới' : 'Xác nhận email' }}
          </h1>
          <p class="text-sm text-gray-500">
            {{ currentStep === 'register' ? 'Điền thông tin để đăng ký tài khoản' : 'Nhập mã xác nhận đã gửi đến email của bạn' }}
          </p>
        </div>

        <!-- Step Indicator -->
        <div v-if="currentStep === 'verify'" class="mb-6 p-4 bg-blue-50 rounded-xl">
          <div class="flex items-center justify-center gap-3 text-sm">
            <span class="flex items-center gap-1.5 text-blue-600 font-medium">
              <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
              Đăng ký
            </span>
            <svg viewBox="0 0 24 24" class="h-4 w-4 text-gray-300" fill="currentColor"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
            <span class="flex items-center gap-1.5 text-blue-600 font-medium">
              <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 2l-8 5-8-5h16z"/></svg>
              Xác nhận
            </span>
          </div>
          <p class="text-center text-xs text-gray-500 mt-2">
            Mã đã gửi đến: <strong class="text-blue-600">{{ pendingEmail }}</strong>
          </p>
        </div>

        <!-- Error Alert -->
        <div v-if="error" class="mb-5 rounded-xl border border-red-200/50 bg-gradient-to-r from-red-50 to-orange-50 px-4 py-3 text-sm text-red-700 flex items-start gap-3 animate-shake" role="alert">
          <svg viewBox="0 0 24 24" class="h-5 w-5 text-red-500 flex-shrink-0" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
          </svg>
          <span>{{ error }}</span>
        </div>

        <!-- Registration Form -->
        <form v-if="currentStep === 'register'" class="space-y-5" @submit.prevent="onSubmit">
          <!-- Name, Email & Phone Row -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2" for="name">Họ và tên</label>
              <div class="relative">
                <input id="name" v-model="name" class="w-full rounded-xl border bg-gray-50/80 px-4 py-3 pl-11 pr-4 text-sm outline-none transition-all duration-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="fieldClass('name')" placeholder="Nguyễn Văn A" :disabled="loading" autocomplete="name" @input="onFieldInput('name')"/>
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                  <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M12 12a5 5 0 10-5-5 5 5 0 005 5zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5z"/></svg>
                </span>
              </div>
              <p v-if="shouldShowFieldError('name')" class="mt-1.5 text-xs text-red-600">{{ errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2" for="email">Email</label>
              <div class="relative">
                <input id="email" v-model="email" type="email" class="w-full rounded-xl border bg-gray-50/80 px-4 py-3 pl-11 pr-4 text-sm outline-none transition-all duration-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="fieldClass('email')" placeholder="name@company.com" :disabled="loading" autocomplete="email" @input="onFieldInput('email')"/>
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                  <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 2l-8 5-8-5h16z"/></svg>
                </span>
              </div>
              <p v-if="shouldShowFieldError('email')" class="mt-1.5 text-xs text-red-600">{{ errors.email }}</p>
            </div>
          </div>

          <!-- Phone Row -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2" for="phone">Số điện thoại</label>
            <div class="relative">
              <input id="phone" v-model="phone" type="tel" class="w-full rounded-xl border bg-gray-50/80 px-4 py-3 pl-11 pr-4 text-sm outline-none transition-all duration-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="fieldClass('phone')" placeholder="0123 456 789" :disabled="loading" autocomplete="tel" @input="onFieldInput('phone')"/>
              <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M6.62 10.79a15.05 15.05 0 016.59-6.59l2.6 2.6a8 8 0 119.18 9.18l-2.6-2.6a15.05 15.05 0 01-6.59 6.59z"/></svg>
              </span>
            </div>
            <p v-if="shouldShowFieldError('phone')" class="mt-1.5 text-xs text-red-600">{{ errors.phone }}</p>
          </div>

          <!-- Birth date & Address Row -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2" for="birth_date">
                Ngày sinh <span class="text-xs text-gray-400 font-normal">(Tùy chọn)</span>
              </label>
              <div class="relative">
                <input id="birth_date" v-model="birth_date" type="date" class="w-full rounded-xl border bg-gray-50/80 px-4 py-3 pl-11 pr-4 text-sm outline-none transition-all duration-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="fieldClass('birth_date')" :disabled="loading"/>
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                  <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M7 2h2v2h6V2h2v2h3a2 2 0 012 2v14a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2h3V2z"/></svg>
                </span>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2" for="address">
                Địa chỉ <span class="text-xs text-gray-400 font-normal">(Tùy chọn)</span>
              </label>
              <div class="relative">
                <input id="address" v-model="address" class="w-full rounded-xl border bg-gray-50/80 px-4 py-3 pl-11 pr-4 text-sm outline-none transition-all duration-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="fieldClass('address')" placeholder="Số nhà, đường..." :disabled="loading" autocomplete="street-address"/>
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                  <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M12 2a7 7 0 00-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 00-7-7z"/></svg>
                </span>
              </div>
            </div>
          </div>

          <!-- Password Row -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2" for="password">Mật khẩu</label>
              <div class="relative">
                <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'" class="w-full rounded-xl border bg-gray-50/80 px-4 py-3 pl-11 pr-12 text-sm outline-none transition-all duration-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="fieldClass('password')" placeholder="Ít nhất 8 ký tự" :disabled="loading" autocomplete="new-password" @input="onPasswordInput"/>
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                  <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-9a2 2 0 00-2-2z"/></svg>
                </span>
                <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600" @click="showPassword = !showPassword" :disabled="loading">
                  <svg v-if="!showPassword" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/><path d="M12 9a3 3 0 100 6 3 3 0 000-6z"/></svg>
                  <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M3 4.27L4.28 3 21 19.72 19.73 21l-2.2-2.2A11.2 11.2 0 0112 20C5 20 2 13 2 13a18.6 18.6 0 014.2-5.6L3 4.27z"/></svg>
                </button>
              </div>
              <p v-if="shouldShowFieldError('password')" class="mt-1.5 text-xs text-red-600">{{ errors.password }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2" for="password_confirmation">Xác nhận mật khẩu</label>
              <div class="relative">
                <input id="password_confirmation" v-model="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" class="w-full rounded-xl border bg-gray-50/80 px-4 py-3 pl-11 pr-12 text-sm outline-none transition-all duration-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="fieldClass('password_confirmation')" placeholder="Nhập lại mật khẩu" :disabled="loading" autocomplete="new-password" @input="onConfirmPasswordInput"/>
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                  <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M17 9h-1V7a4 4 0 10-8 0v2H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-9a2 2 0 00-2-2z"/></svg>
                </span>
                <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600" @click="showConfirmPassword = !showConfirmPassword" :disabled="loading">
                  <svg v-if="!showConfirmPassword" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/><path d="M12 9a3 3 0 100 6 3 3 0 000-6z"/></svg>
                  <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M3 4.27L4.28 3 21 19.72 19.73 21l-2.2-2.2A11.2 11.2 0 0112 20C5 20 2 13 2 13a18.6 18.6 0 014.2-5.6L3 4.27z"/></svg>
                </button>
              </div>
              <p v-if="shouldShowFieldError('password_confirmation')" class="mt-1.5 text-xs text-red-600">{{ errors.password_confirmation }}</p>
            </div>
          </div>

          <!-- Submit Button -->
          <button class="relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 px-4 py-4 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition-all duration-200 hover:shadow-xl hover:shadow-blue-500/40 hover:from-blue-700 hover:to-cyan-700 active:translate-y-0.5 active:shadow-md disabled:opacity-60 disabled:cursor-not-allowed mt-6" :disabled="loading">
            <span v-if="loading" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
            <span class="relative z-10">{{ loading ? "Đang gửi mã xác nhận..." : "Đăng ký tài khoản" }}</span>
          </button>

          <!-- Terms -->
          <p class="text-xs text-gray-400 text-center">
            Bằng việc đăng ký, bạn đồng ý với <a href="#" class="text-blue-600 hover:underline">Điều khoản dịch vụ</a> và <a href="#" class="text-blue-600 hover:underline">Chính sách bảo mật</a>
          </p>
        </form>

        <!-- Verification Form -->
        <form v-else-if="currentStep === 'verify'" class="space-y-5" @submit.prevent="onVerifySubmit">
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
              <svg viewBox="0 0 24 24" class="h-8 w-8 text-blue-600" fill="currentColor">
                <path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 2l-8 5-8-5h16z"/>
              </svg>
            </div>
            <p class="text-sm text-gray-500">Nhập mã 6 chữ số đã được gửi đến email của bạn</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2" for="code">Mã xác nhận</label>
            <div class="relative">
              <input id="code" v-model="verificationCode" type="text" maxlength="6" class="w-full rounded-xl border bg-gray-50/80 px-4 py-4 text-sm outline-none transition-all duration-200 text-center text-2xl tracking-[0.4em] font-mono placeholder:text-gray-300 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-60" :class="fieldClass('code')" placeholder="- - - - - -" :disabled="loading" @input="onCodeInput"/>
            </div>
            <p v-if="shouldShowFieldError('code')" class="mt-2 text-xs text-red-600 text-center">{{ errors.code }}</p>
          </div>

          <button class="relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 px-4 py-4 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition-all duration-200 hover:shadow-xl hover:shadow-blue-500/40 hover:from-blue-700 hover:to-cyan-700 active:translate-y-0.5 active:shadow-md disabled:opacity-60 disabled:cursor-not-allowed" :disabled="loading">
            <span v-if="loading" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
            <span class="relative z-10">{{ loading ? "Đang xác nhận..." : "Xác nhận" }}</span>
          </button>

          <div class="text-center">
            <p class="text-sm text-gray-500">
              Không nhận được mã?
              <button v-if="!canResend" class="font-medium text-gray-400 ml-1" disabled>Gửi lại sau {{ resendCountdown }}s</button>
              <button v-else class="font-semibold text-blue-600 hover:text-violet-700 hover:underline ml-1" @click="onResendCode" :disabled="resending">{{ resending ? 'Đang gửi...' : 'Gửi lại mã' }}</button>
            </p>
          </div>

          <div class="text-center pt-4 border-t border-gray-100">
            <button type="button" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center gap-1.5" @click="onBackToRegister">
              <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
              Quay lại đăng ký
            </button>
          </div>
        </form>

        <!-- Footer -->
        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
          <p class="text-sm text-gray-500">
            Đã có tài khoản?
            <RouterLink to="/login" class="font-semibold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-600 hover:opacity-80 transition-opacity ml-1">
              Đăng nhập ngay
            </RouterLink>
          </p>
        </div>
      </div>

      <!-- Back to Home -->
      <div class="mt-6 text-center">
        <RouterLink to="/" class="inline-flex items-center gap-1.5 text-sm text-white/80 hover:text-white transition-colors">
          <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
          Quay về trang chủ
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { initRegister, verifyEmail, resendVerificationCode } from "../../composables/auth";
import { useAlert } from "../../composables/useAlert";

const router = useRouter();
const auth = useAuthStore();
const alert = useAlert();

const name = ref("");
const email = ref("");
const phone = ref("");
const birth_date = ref("");
const address = ref("");
const password = ref("");
const password_confirmation = ref("");

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const loading = ref(false);
const error = ref("");
const submitted = ref(false);

const currentStep = ref("register");
const pendingEmail = ref("");
const verificationCode = ref("");
const resendCountdown = ref(60);
const canResend = ref(false);
const resending = ref(false);

const errors = reactive({
  name: "", email: "", phone: "", birth_date: "", address: "", password: "", password_confirmation: "", code: "",
});

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function clearAllErrors() {
  error.value = "";
  Object.keys(errors).forEach((k) => (errors[k] = ""));
}

function shouldShowFieldError(field) { return submitted.value && !!errors[field]; }
function fieldClass(field) {
  if (!submitted.value) return "border-gray-200";
  if (errors[field]) return "border-red-300 bg-red-50/50";
  return "border-gray-200";
}

function validateField(field) {
  errors[field] = "";
  if (field === "name") { if (!name.value.trim()) errors.name = "Vui lòng nhập họ và tên"; }
  if (field === "email") {
    const v = email.value.trim();
    if (!v) errors.email = "Email là bắt buộc";
    else if (!EMAIL_RE.test(v)) errors.email = "Email không đúng định dạng";
  }
  if (field === "phone") {
    const v = phone.value.trim();
    if (!v) errors.phone = "Số điện thoại là bắt buộc";
    else if (!/^(0[0-9]{9,10})$/.test(v.replace(/\s/g, ""))) errors.phone = "Số điện thoại không hợp lệ (10-11 số, bắt đầu bằng 0)";
  }
  if (field === "password") {
    if (!password.value) errors.password = "Mật khẩu là bắt buộc";
    else if (password.value.length < 8) errors.password = "Mật khẩu tối thiểu 8 ký tự";
  }
  if (field === "password_confirmation") {
    if (!password_confirmation.value) errors.password_confirmation = "Vui lòng nhập lại mật khẩu";
    else if (password_confirmation.value !== password.value) errors.password_confirmation = "Mật khẩu nhập lại không khớp";
  }
  if (field === "code") {
    const v = verificationCode.value.trim();
    if (!v) errors.code = "Vui lòng nhập mã xác nhận";
    else if (v.length !== 6) errors.code = "Mã xác nhận gồm 6 chữ số";
    else if (!/^\d{6}$/.test(v)) errors.code = "Mã xác nhận chỉ chứa số";
  }
}

function validateAll() {
  validateField("name"); validateField("email"); validateField("phone"); validateField("password"); validateField("password_confirmation");
  return !errors.name && !errors.email && !errors.phone && !errors.password && !errors.password_confirmation;
}
function validateCode() { validateField("code"); return !errors.code; }

function onFieldInput(field) { if (!submitted.value) return; validateField(field); }
function onPasswordInput() { if (!submitted.value) return; validateField("password"); if (password_confirmation.value) validateField("password_confirmation"); }
function onConfirmPasswordInput() { if (!submitted.value) return; validateField("password_confirmation"); }
function onCodeInput() { verificationCode.value = verificationCode.value.replace(/\D/g, "").slice(0, 6); if (submitted.value) validateField("code"); }

function applyBackendErrors(e) {
  const data = e?.response?.data;
  const backendErrors = data?.errors;
  if (backendErrors && typeof backendErrors === "object") {
    Object.keys(backendErrors).forEach((k) => { if (k in errors) errors[k] = Array.isArray(backendErrors[k]) ? backendErrors[k][0] : String(backendErrors[k]); });
  }
}

async function onSubmit() {
  submitted.value = true; clearAllErrors();
  if (!validateAll()) return;
  loading.value = true;
  try {
    await initRegister({ name: name.value, email: email.value, phone: phone.value, birth_date: birth_date.value || null, address: address.value || null, password: password.value, password_confirmation: password_confirmation.value });
    pendingEmail.value = email.value; currentStep.value = "verify"; submitted.value = false;
    alert.success("Mã xác nhận đã được gửi đến email của bạn.", { title: "Đăng ký" });
    startResendCountdown();
  } catch (e) {
    if (e?.response?.status === 422) { applyBackendErrors(e); error.value = e?.response?.data?.message || "Dữ liệu không hợp lệ"; }
    else if (e?.response?.status === 429) { error.value = e?.response?.data?.message || "Vui lòng chờ trước khi thử lại."; }
    else { error.value = e?.response?.data?.message || "Đăng ký thất bại. Vui lòng thử lại."; }
  } finally { loading.value = false; }
}

async function onVerifySubmit() {
  submitted.value = true; clearAllErrors();
  if (!validateCode()) return;
  loading.value = true;
  try {
    const user = await verifyEmail({ email: pendingEmail.value, code: verificationCode.value });
    auth.user = user; auth.loaded = true;
    alert.success(`Chào mừng ${user.name}! Đăng ký thành công.`, { title: "Xác nhận thành công" });
    setTimeout(() => { router.push(user.role === "admin" ? "/admin" : "/shop"); }, 1500);
  } catch (e) {
    if (e?.response?.status === 422) { applyBackendErrors(e); error.value = e?.response?.data?.message || "Mã xác nhận không hợp lệ."; }
    else if (e?.response?.status === 429) { error.value = e?.response?.data?.message || "Quá nhiều yêu cầu."; }
    else { error.value = e?.response?.data?.message || "Xác nhận thất bại."; }
  } finally { loading.value = false; }
}

let countdownInterval = null;
function startResendCountdown() {
  canResend.value = false; resendCountdown.value = 60;
  if (countdownInterval) clearInterval(countdownInterval);
  countdownInterval = setInterval(() => {
    resendCountdown.value--;
    if (resendCountdown.value <= 0) { canResend.value = true; clearInterval(countdownInterval); }
  }, 1000);
}

async function onResendCode() {
  if (!canResend.value || resending.value) return;
  resending.value = true; clearAllErrors();
  try {
    await resendVerificationCode(pendingEmail.value);
    alert.success("Mã xác nhận mới đã được gửi.", { title: "Gửi lại mã" });
    startResendCountdown();
  } catch (e) { error.value = e?.response?.data?.message || "Gửi lại mã thất bại."; }
  finally { resending.value = false; }
}

function onBackToRegister() {
  currentStep.value = "register"; verificationCode.value = ""; submitted.value = false; clearAllErrors();
  if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
}
</script>

<style scoped>
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes shake { 0%, 100% { transform: translateX(0); } 10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); } 20%, 40%, 60%, 80% { transform: translateX(4px); } }
.animate-fadeIn { animation: fadeIn 0.5s ease-out both; }
.animate-shake { animation: shake 0.5s ease-in-out; }
</style>
