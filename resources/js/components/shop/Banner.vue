<template>
  <section v-if="banner" class="mb-12">
    <div
      class="relative w-full aspect-[16/7] rounded-2xl overflow-hidden shadow-xl bg-slate-200 group"
    >
      <img
        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
        :src="banner.image"
        :alt="plainTitle"
      />

      <div
        class="absolute inset-0 bg-gradient-to-r from-slate-900/85 via-slate-900/45 to-transparent flex items-center"
      >
        <div class="px-6 py-10 sm:px-8 lg:px-16 text-white max-w-2xl">
        <h2
          class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-[0.08em] uppercase leading-tight drop-shadow-lg"
          v-html="banner.title"
        ></h2>

          <p v-if="banner.description" class="mt-4 text-sm sm:text-base lg:text-lg text-slate-200">
            {{ banner.description }}
          </p>

          <div v-if="banner.button_text" class="mt-6">
            <button
              type="button"
              class="bg-primary hover:bg-primary/90 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg"
              @click="handleAction"
            >
              {{ banner.button_text }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import bannerPublicService from "../../services/public/bannerService";

const props = defineProps({
  position: {
    type: String,
    required: true,
  },
  fallbackAction: {
    type: Function,
    default: null,
  },
});

const router = useRouter();
const banner = ref(null);

const plainTitle = computed(() =>
  String(banner.value?.title || "")
    .replace(/<br\s*\/?>/gi, " ")
    .replace(/<[^>]*>/g, "")
    .trim()
);

async function fetchBanner() {
  try {
    const res = await bannerPublicService.getByPosition(props.position);
    banner.value = res?.data?.data ?? res?.data ?? null;
  } catch (e) {
    banner.value = null;
  }
}

function handleAction() {
  const link = banner.value?.button_link;

  if (link) {
    if (link.startsWith("http")) {
      window.location.href = link;
      return;
    }

    router.push(link);
    return;
  }

  if (props.fallbackAction) {
    props.fallbackAction();
  }
}

onMounted(fetchBanner);
</script>