<template>
  <section class="panel">
    <div class="panel-head">
      <div class="panel-icon">⚡</div>
      <div>
        <div class="panel-title">{{ title }}</div>
        <div v-if="subtitle" class="panel-subtitle">{{ subtitle }}</div>
      </div>
    </div>

    <div class="panel-body">
      <div class="actions">
        <slot />
      </div>

      <div v-if="message" class="msg success">{{ message }}</div>
      <div v-if="error" class="msg error">{{ error }}</div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, default: "Thao tác" },
  subtitle: { type: String, default: "" },
  message: { type: String, default: "" },
  error: { type: String, default: "" },
});
</script>

<style scoped>
.panel {
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
}

.panel-head {
  padding: 16px 20px 14px;
  border-bottom: 1px solid #f1f4fa;
  display: flex;
  align-items: center;
  gap: 12px;
}

.panel-icon {
  width: 34px;
  height: 34px;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border-radius: 10px;
  display: grid;
  place-items: center;
  font-size: 15px;
  flex-shrink: 0;
}

.panel-title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.panel-subtitle {
  margin-top: 3px;
  font-size: 12px;
  color: #64748b;
}

.panel-body {
  padding: 18px 20px 20px;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.msg {
  margin-top: 14px;
  font-size: 13px;
  font-weight: 600;
}

.msg.success {
  color: #15803d;
}

.msg.error {
  color: #dc2626;
}
</style>