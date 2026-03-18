<template>
  <header
    class="h-20 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 text-white flex items-center justify-between px-8 shadow"
  >
    <!-- LEFT -->
    <div class="flex items-center gap-4 min-w-0">
      <!-- Logo + Title -->
      <div class="flex items-center gap-3 min-w-0">
        <div class="size-12 rounded-full bg-white/10 flex items-center justify-center ring-2 ring-white/15">
          <span class="material-symbols-outlined text-[24px]">admin_panel_settings</span>
        </div>
        <div class="text-2xl font-extrabold tracking-tight truncate">BMC Shoes</div>
      </div>

      <!-- Toggle -->
      <button
        class="inline-flex items-center justify-center size-11 rounded-xl hover:bg-white/10 transition"
        title="Menu"
        @click="ui.toggleSidebar()"
      >
        <span class="material-symbols-outlined text-[24px]">menu</span>
      </button>

      <!-- Go to website -->
      <router-link
        to="/"
        class="hidden sm:inline-flex items-center gap-2 rounded-xl hover:bg-white/10 transition px-5 py-2.5 text-sm font-semibold"
        title="Truy cập trang web"
      >
        <span class="material-symbols-outlined text-[22px]">open_in_new</span>
        Truy cập trang web
      </router-link>
    </div>

    <!-- RIGHT -->
    <div class="flex items-center gap-3">
      <!-- Language -->
      <!-- <button
        class="hidden md:inline-flex items-center gap-2 rounded-xl hover:bg-white/10 transition px-4 py-2.5 text-sm font-semibold"
        title="Ngôn ngữ"
      >
        <span class="text-base">🇻🇳</span>
        Tiếng Việt
        <span class="material-symbols-outlined text-[20px] opacity-90">expand_more</span>
      </button> -->

      <!-- Notifications -->
      <button
        class="relative inline-flex items-center justify-center size-11 rounded-xl hover:bg-white/10 transition"
        title="Thông báo"
      >
        <span class="material-symbols-outlined text-[24px]">notifications</span>
        <span
          class="absolute -top-1 -right-1 min-w-[20px] h-[20px] px-1 rounded-full bg-red-500 text-[11px] font-extrabold leading-[20px] text-center border-2 border-blue-700"
        >
          99+
        </span>
      </button>

      <!-- User -->
      <div class="relative" ref="menuRef">
        <button
          class="flex items-center gap-3 rounded-xl hover:bg-white/10 transition px-4 py-2.5"
          @click="toggle"
        >
          <div class="size-10 rounded-full bg-white/15 overflow-hidden flex items-center justify-center">
            <img v-if="avatarUrl" :src="avatarUrl" class="w-full h-full object-cover" alt="avatar" />
            <span v-else class="text-sm font-extrabold">{{ initial }}</span>
          </div>

          <div class="hidden sm:block text-left leading-tight">
            <div class="text-sm font-extrabold">{{ displayName }}</div>
            <div class="text-xs text-white/80">{{ role }}</div>
          </div>

          <span class="material-symbols-outlined text-[20px] opacity-90">expand_more</span>
        </button>

        <!-- Dropdown -->
        <div
          v-if="open"
          class="absolute right-0 mt-2 w-64 bg-white text-slate-900 border border-slate-200 rounded-2xl shadow-lg overflow-hidden z-50"
        >
          <div class="px-4 py-3 border-b border-slate-200">
            <div class="text-sm font-extrabold truncate">{{ displayName }}</div>
            <div class="text-xs text-slate-500 truncate">{{ email }}</div>
          </div>

          <div class="py-2">
            <router-link
              to="/admin/settings"
              class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50"
              @click="open = false"
            >
              <span class="material-symbols-outlined text-[20px]">settings</span>
              Cài đặt
            </router-link>

            <button
              class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-red-50 text-red-600"
              @click="handleLogout"
              :disabled="loading"
            >
              <span class="material-symbols-outlined text-[20px]">logout</span>
              {{ loading ? "Đang đăng xuất..." : "Đăng xuất" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useAdminUiStore } from "@/stores/adminUi";

const router = useRouter();
const auth = useAuthStore();
const ui = useAdminUiStore();

const open = ref(false);
const loading = ref(false);
const menuRef = ref(null);

const displayName = computed(() => auth.user?.name || "Super Admin");
const email = computed(() => auth.user?.email || "superadmin@example.com");
const avatarUrl = computed(() => auth.user?.avatar_url || "");
const role = computed(() => {
  if (!auth.user) return "Guest";
  if (auth.user.is_superadmin) return "Super Admin";
  return "Admin";
});
const initial = computed(() => String(displayName.value).trim().charAt(0).toUpperCase());

function toggle() {
  open.value = !open.value;
}

async function handleLogout() {
  loading.value = true;
  try {
    await auth.logout();
    open.value = false;
    router.push("/login");
  } finally {
    loading.value = false;
  }
}

function onClickOutside(e) {
  if (!menuRef.value) return;
  if (!menuRef.value.contains(e.target)) open.value = false;
}

onMounted(() => document.addEventListener("click", onClickOutside));
onBeforeUnmount(() => document.removeEventListener("click", onClickOutside));
</script>