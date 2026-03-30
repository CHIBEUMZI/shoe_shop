<template>
  <section v-if="banners.length > 0" class="mb-12">
    <div
      class="relative w-full aspect-[16/7] rounded-2xl overflow-hidden shadow-xl bg-slate-200 group"
    >
      <div class="relative w-full h-full">
      <transition name="fade" mode="out-in">
        <div
          class="absolute inset-0 cursor-pointer"
          @click="handleAction"
        >
          <!-- Background -->
          <img
            :src="banners[currentIndex]?.image"
            class="w-full h-full object-cover blur-2xl scale-110 opacity-40"
          />

          <!-- Main image -->
          <img
            :key="banners[currentIndex]?.id || currentIndex"
            :src="banners[currentIndex]?.image"
            class="absolute inset-0 w-full h-full object-contain"
          />
        </div>
      </transition>
      </div>

      <div
        v-if="banners.length > 1"
        class="absolute inset-0 flex items-center justify-between px-4 z-10"
      >
        <button
          @click="previousBanner"
          class="bg-white/20 hover:bg-white/40 backdrop-blur-sm text-white rounded-full p-2 transition-all duration-300 hover:scale-110"
        >
          ‹
        </button>

        <button
          @click="nextBanner"
          class="bg-white/20 hover:bg-white/40 backdrop-blur-sm text-white rounded-full p-2 transition-all duration-300 hover:scale-110"
        >
          ›
        </button>
      </div>

      <!-- Dots -->
      <div
        v-if="banners.length > 1"
        class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10"
      >
        <button
          v-for="(_, idx) in banners"
          :key="idx"
          @click="goToBanner(idx)"
          :class="[
            'rounded-full transition-all duration-300',
            idx === currentIndex
              ? 'bg-white w-8 h-2'
              : 'bg-white/50 hover:bg-white/75 w-2 h-2',
          ]"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import bannerPublicService from "../../services/public/bannerService";

const props = defineProps({
  position: String,
  fallbackAction: Function,
  autoRotateInterval: {
    type: Number,
    default: 5000,
  },
});

const router = useRouter();
const banners = ref([]);
const currentIndex = ref(0);
let timer = null;

// Clean title
function getPlainTitle(banner) {
  return String(banner?.title || "")
    .replace(/<br\s*\/?>/gi, " ")
    .replace(/<[^>]*>/g, "")
    .trim();
}

async function fetchBanners() {
  try {
    const res = await bannerPublicService.list({ position: props.position });
    banners.value = res?.data?.data ?? res?.data ?? [];

    if (banners.value.length > 1) {
      startAuto();
    }
  } catch {
    banners.value = [];
  }
}

function nextBanner() {
  currentIndex.value = (currentIndex.value + 1) % banners.value.length;
  resetAuto();
}

function previousBanner() {
  currentIndex.value =
    (currentIndex.value - 1 + banners.value.length) %
    banners.value.length;
  resetAuto();
}

function goToBanner(i) {
  currentIndex.value = i;
  resetAuto();
}

// Auto slide
function startAuto() {
  timer = setInterval(nextBanner, props.autoRotateInterval);
}

function resetAuto() {
  clearInterval(timer);
  startAuto();
}

// Click action
function handleAction() {
  const link = banners.value[currentIndex.value]?.button_link;

  if (!link) {
    props.fallbackAction?.();
    return;
  }

  if (link.startsWith("http")) {
    window.location.href = link;
  } else {
    router.push(link);
  }
}

onMounted(fetchBanners);

onBeforeUnmount(() => {
  clearInterval(timer);
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>