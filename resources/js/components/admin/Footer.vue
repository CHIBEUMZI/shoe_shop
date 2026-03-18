<template>
  <footer class="border-t border-slate-200">
    <div class="bg-slate-900 text-slate-100">
      <div class="px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <!-- LEFT -->
        <div class="space-y-1">
          <div class="text-sm font-semibold">
            © {{ year }} {{ appName }}. Tất cả quyền được bảo lưu
          </div>
          <div class="text-xs text-slate-300">
            Phiên bản: {{ version }} | Cập nhật lần cuối: {{ lastUpdated }}
          </div>
        </div>

        <!-- RIGHT -->
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
          <a href="#" class="inline-flex items-center gap-2 text-slate-200 hover:text-white transition">
            <span class="material-symbols-outlined text-[18px]">help</span>
            Câu hỏi thường gặp
          </a>

          <a href="#" class="inline-flex items-center gap-2 text-slate-200 hover:text-white transition">
            <span class="material-symbols-outlined text-[18px]">policy</span>
            Chính sách
          </a>

          <a href="#" class="inline-flex items-center gap-2 text-slate-200 hover:text-white transition">
            <span class="material-symbols-outlined text-[18px]">school</span>
            Đào tạo
          </a>

          <a href="#" class="inline-flex items-center gap-2 text-slate-200 hover:text-white transition">
            <span class="material-symbols-outlined text-[18px]">support_agent</span>
            Hỗ trợ IT
          </a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
const year = new Date().getFullYear();

// bạn đổi mấy cái này theo dự án
const appName = "BMC Shoes Admin";
const version = "1.0.0";
const lastUpdated = "01/06/2026";
</script>