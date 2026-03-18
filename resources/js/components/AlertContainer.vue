<template>
  <Teleport to="body">
    <div
      class="pointer-events-none fixed right-4 top-4 z-[9999] flex w-[calc(100vw-2rem)] max-w-sm flex-col gap-3 sm:right-5 sm:top-5"
    >
      <TransitionGroup name="alert">
        <BaseAlert
          v-for="item in alerts"
          :key="item.id"
          :type="item.type"
          :title="item.title"
          :message="item.message"
          :duration="item.duration"
          :closable="item.closable"
          @close="remove(item.id)"
        />
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import BaseAlert from "./BaseAlert.vue";
import { useAlert } from "../composables/useAlert";

const { alerts, remove } = useAlert();
</script>

<style scoped>
.alert-enter-active,
.alert-leave-active {
  transition: all 0.25s ease;
}

.alert-enter-from {
  opacity: 0;
  transform: translateY(-10px) translateX(12px) scale(0.98);
}

.alert-leave-to {
  opacity: 0;
  transform: translateY(-10px) translateX(12px) scale(0.98);
}

.alert-move {
  transition: transform 0.25s ease;
}
</style>