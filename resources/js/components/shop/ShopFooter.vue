<template>
  <footer class="bg-slate-900 text-slate-300 pt-16 pb-8 border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
        <div>
          <h6 class="text-white font-bold text-lg mb-6">BMC Shoes</h6>
          <p class="text-sm leading-relaxed mb-6">
            Cửa hàng cung cấp các mẫu giày chính hãng từ các thương hiệu hàng đầu thế giới.
            Cam kết chất lượng và dịch vụ tận tâm.
          </p>
          <div class="flex gap-4">
            <a class="size-10 bg-slate-800 hover:bg-primary transition-colors flex items-center justify-center rounded-lg" href="#">
              <span class="material-symbols-outlined text-white">social_leaderboard</span>
            </a>
            <a class="size-10 bg-slate-800 hover:bg-primary transition-colors flex items-center justify-center rounded-lg" href="#">
              <span class="material-symbols-outlined text-white">camera</span>
            </a>
            <a class="size-10 bg-slate-800 hover:bg-primary transition-colors flex items-center justify-center rounded-lg" href="#">
              <span class="material-symbols-outlined text-white">alternate_email</span>
            </a>
          </div>
        </div>

        <div>
          <h6 class="text-white font-bold text-lg mb-6">Policies</h6>
          <ul class="space-y-4 text-sm">
            <li><a class="hover:text-primary transition-colors" href="#">Chính sách bảo hành</a></li>
            <li><a class="hover:text-primary transition-colors" href="#">Chính sách đổi trả</a></li>
            <li><a class="hover:text-primary transition-colors" href="#">Chính sách vận chuyển</a></li>
            <li><a class="hover:text-primary transition-colors" href="#">Bảo mật thông tin</a></li>
          </ul>
        </div>

        <div>
          <h6 class="text-white font-bold text-lg mb-6">Customer Guide</h6>
          <ul class="space-y-4 text-sm">
            <li><a class="hover:text-primary transition-colors" href="#">Hướng dẫn chọn size</a></li>
            <li><a class="hover:text-primary transition-colors" href="#">Hướng dẫn thanh toán</a></li>
            <li><a class="hover:text-primary transition-colors" href="#">Tra cứu đơn hàng</a></li>
            <li><a class="hover:text-primary transition-colors" href="#">Câu hỏi thường gặp</a></li>
          </ul>
        </div>

        <div>
          <h6 class="text-white font-bold text-lg mb-6">Newsletter</h6>
          <p class="text-sm mb-4">Đăng ký để nhận thông tin khuyến mãi mới nhất.</p>
          <div class="flex gap-2">
            <input
              class="bg-slate-800 border-none rounded-lg text-sm flex-1 focus:ring-primary"
              placeholder="Email của bạn"
              type="email"
              v-model="email"
            />
            <button class="bg-primary px-4 py-2 rounded-lg text-white" type="button" @click="submit">
              <span class="material-symbols-outlined">send</span>
            </button>
          </div>
        </div>
      </div>

      <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-500">
        <p>© 2026 BMC Shoes Ecommerce. All rights reserved.</p>
        <div class="flex gap-6">
          <a class="hover:text-white transition-colors" href="#">Terms of Service</a>
          <a class="hover:text-white transition-colors" href="#">Privacy Policy</a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { ref } from "vue";

const email = ref("");

function submit() {
  email.value = "";
}
</script>