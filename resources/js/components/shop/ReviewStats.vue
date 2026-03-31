<template>
  <div class="review-stats bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h3 class="text-lg font-semibold mb-4">Đánh giá sản phẩm</h3>
    
    <div class="space-y-4">
      <!-- Average Rating -->
      <div class="flex items-center gap-4">
        <div class="text-center min-w-20">
          <div class="text-4xl font-bold text-amber-500">{{ stats.average_rating }}</div>
          <div class="text-sm text-gray-600">trên 5</div>
        </div>
        
        <!-- Star display -->
        <div class="flex-1">
          <div class="flex items-center gap-1 mb-2">
            <div v-for="i in 5" :key="i" class="text-yellow-400 text-lg">
              ★
            </div>
          </div>
          <div class="text-sm text-gray-600">{{ stats.total_reviews }} đánh giá</div>
        </div>
      </div>

      <!-- Rating Distribution -->
      <div class="pt-4 border-t border-gray-200 space-y-2">
        <div v-for="rating in [5, 4, 3, 2, 1]" :key="rating" class="flex items-center gap-2">
          <span class="text-sm text-gray-600 min-w-8">{{ rating }}★</span>
          <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
            <div
              class="bg-amber-400 h-full transition-all"
              :style="{ width: getPercentage(rating) + '%' }"
            ></div>
          </div>
          <span class="text-sm text-gray-600 min-w-10">
            {{ formatCount(stats.rating_distribution[rating]) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import reviewService from '../../services/public/reviewService'

const props = defineProps({
  productId: {
    type: Number,
    required: true,
  },
})

const stats = ref({
  total_reviews: 0,
  average_rating: 0,
  rating_distribution: {
    5: 0,
    4: 0,
    3: 0,
    2: 0,
    1: 0,
  },
})

const loading = ref(false)
const error = ref(null)

const getPercentage = (rating) => {
  if (stats.value.total_reviews === 0) return 0
  return Math.round(
    (stats.value.rating_distribution[rating] / stats.value.total_reviews) * 100
  )
}

const formatCount = (count) => {
  return count || 0
}

const fetchStats = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await reviewService.getProductStats(props.productId)
    stats.value = response.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load review stats'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStats()
})
</script>
