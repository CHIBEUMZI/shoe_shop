<template>
  <div class="min-h-screen flex flex-col bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-display">
    <AdminHeader />
    
    <div class="flex flex-1 overflow-hidden">
      <AdminSidebar />
      <main class="flex-1 overflow-y-auto p-8">
        <router-view />
      </main>
    </div>

    <AdminFooter />
  </div>
</template>

<script setup>
import AdminHeader from "../../components/admin/Header.vue";
import AdminSidebar from "../../components/admin/Sidebar.vue";
import AdminFooter from "../../components/admin/Footer.vue";
</script>