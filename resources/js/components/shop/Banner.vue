<template>
  <section v-if="banners.length > 0">
    <div class="group relative aspect-[16/7] w-full overflow-hidden">
      <transition name="fade" mode="out-in">
        <button
          :key="banners[currentIndex]?.id || currentIndex"
          type="button"
          class="absolute inset-0 block h-full w-full cursor-pointer"
          @click="handleAction"
        >
          <img
            :src="banners[currentIndex]?.image"
            :alt="getPlainTitle(banners[currentIndex]) || 'Banner image'"
            :style="getImageStyle(banners[currentIndex])"
            class="h-full w-full object-cover bg-white"
            @load="handleImageLoad(banners[currentIndex], $event)"
          />
        </button>
      </transition>

      <div
        v-if="banners.length > 1"
        class="absolute inset-0 z-10 flex items-center justify-between px-4"
      >
        <button
          @click="previousBanner"
          class="rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition-all duration-300 hover:scale-110 hover:bg-white/40"
        >
          ‹
        </button>

        <button
          @click="nextBanner"
          class="rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition-all duration-300 hover:scale-110 hover:bg-white/40"
        >
          ›
        </button>
      </div>

      <!-- Dots -->
      <div
        v-if="banners.length > 1"
        class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 gap-2"
      >
        <button
          v-for="(_, idx) in banners"
          :key="idx"
          @click="goToBanner(idx)"
          :class="[
            'h-2 rounded-full transition-all duration-300',
            idx === currentIndex
              ? 'w-8 bg-white'
              : 'w-2 bg-white/50 hover:bg-white/75',
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
const imageStyles = ref({});
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

function handleImageLoad(banner, event) {
  const img = event?.target;
  if (!banner?.id || !img) return;

  const naturalWidth = img.naturalWidth || 0;
  const naturalHeight = img.naturalHeight || 0;
  if (!naturalWidth || !naturalHeight) return;

  const bannerRatio = 16 / 7;
  const imageRatio = naturalWidth / naturalHeight;

  const objectPosition =
    imageRatio < bannerRatio ? "center top" : "center center";

  imageStyles.value = {
    ...imageStyles.value,
    [banner.id]: {
      objectPosition,
    },
  };
}

function getImageStyle(banner) {
  return banner?.id ? imageStyles.value[banner.id] || {} : {};
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