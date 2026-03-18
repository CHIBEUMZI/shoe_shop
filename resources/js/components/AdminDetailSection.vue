<template>
  <section class="section">
    <div class="section-head">
      <div class="section-icon">{{ icon }}</div>
      <div>
        <div class="section-title">{{ title }}</div>
        <div v-if="subtitle" class="section-subtitle">{{ subtitle }}</div>
      </div>
    </div>

    <div class="section-body">
      <div class="detail-grid" :class="columns === 1 ? 'one' : 'two'">
        <div
          v-for="item in items"
          :key="item.key"
          class="detail-item"
          :class="item.full ? 'full' : ''"
        >
          <div class="detail-label">{{ item.label }}</div>
          <div class="detail-value" :class="item.valueClass">
            <slot :name="`value:${item.key}`" :item="item">
              {{ item.value || "-" }}
            </slot>
          </div>
        </div>
      </div>

      <div v-if="$slots.default" class="detail-extra">
        <slot />
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: "" },
  icon: { type: String, default: "📋" },
  columns: { type: Number, default: 2 },
  items: {
    type: Array,
    default: () => [],
  },
});
</script>

<style scoped>
.section {
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
}

.section-head {
  padding: 16px 20px 14px;
  border-bottom: 1px solid #f1f4fa;
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-icon {
  width: 34px;
  height: 34px;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border-radius: 10px;
  display: grid;
  place-items: center;
  font-size: 15px;
  flex-shrink: 0;
}

.section-title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.01em;
}

.section-subtitle {
  margin-top: 3px;
  font-size: 12px;
  color: #64748b;
}

.section-body {
  padding: 18px 20px 20px;
}

.detail-grid {
  display: grid;
  gap: 16px 20px;
}

.detail-grid.one {
  grid-template-columns: 1fr;
}

.detail-grid.two {
  grid-template-columns: 1fr 1fr;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.detail-item.full {
  grid-column: 1 / -1;
}

.detail-label {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.detail-value {
  min-height: 42px;
  display: flex;
  align-items: center;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 12px;
  background: #fafbfd;
  color: #0f172a;
  font-size: 14px;
  font-weight: 500;
  word-break: break-word;
}

.detail-extra {
  margin-top: 18px;
}

@media (max-width: 980px) {
  .detail-grid.two {
    grid-template-columns: 1fr;
  }
}
</style>