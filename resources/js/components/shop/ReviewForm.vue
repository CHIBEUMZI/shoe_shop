<template>
  <div class="review-form bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h3 class="text-lg font-semibold mb-4">Viết đánh giá</h3>

    <!-- Not logged in message -->
    <div v-if="!isLoggedIn" class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center mb-4">
      <p class="text-sm text-gray-700">
        Bạn cần <router-link to="/login" class="text-blue-600 font-semibold hover:underline">đăng nhập</router-link> để viết đánh giá
      </p>
    </div>

    <!-- Not purchased message -->
    <div v-else-if="!hasPurchased" class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-center">
      <p class="text-sm text-amber-700">
        Chỉ khách hàng đã mua sản phẩm mới có thể viết đánh giá
      </p>
    </div>

    <!-- Already reviewed message -->
    <div v-else-if="hasReviewed" class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
      <p class="text-sm text-gray-600">Bạn đã viết đánh giá cho sản phẩm này</p>
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="submitReview" class="space-y-4">
      <!-- Rating -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Đánh giá <span class="text-red-500">*</span>
        </label>
        <div class="flex gap-2">
          <button
            v-for="i in 5"
            :key="i"
            type="button"
            @click="form.rating = i"
            :class="[
              'text-3xl transition-all',
              i <= form.rating ? 'text-amber-400 scale-110' : 'text-gray-300 hover:text-amber-300',
            ]"
          >
            ★
          </button>
        </div>
        <p v-if="errors.rating" class="text-sm text-red-500 mt-1">{{ errors.rating }}</p>
      </div>

      <!-- Comment -->
      <div>
        <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
          Nhận xét (tùy chọn)
        </label>
        <textarea
          id="comment"
          v-model="form.comment"
          rows="4"
          placeholder="Chia sẻ trải nghiệm của bạn với sản phẩm này..."
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
          maxlength="1000"
        ></textarea>
        <p class="text-xs text-gray-500 mt-1">
          {{ form.comment.length }}/1000 ký tự
        </p>
        <p v-if="errors.comment" class="text-sm text-red-500 mt-1">{{ errors.comment }}</p>
      </div>

      <!-- Submit Button -->
      <div class="flex gap-2 pt-2">
        <button
          type="submit"
          :disabled="loading || !form.rating"
          class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 text-white font-medium py-2 px-4 rounded-lg transition-colors"
        >
          <span v-if="loading">Đang gửi...</span>
          <span v-else>Gửi đánh giá</span>
        </button>
      </div>

      <!-- Messages -->
      <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700">
        {{ successMessage }}
      </div>
      <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
        {{ errorMessage }}
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import reviewService from '../../services/public/reviewService'

const props = defineProps({
  productId: {
    type: Number,
    required: true,
  },
  onSuccess: {
    type: Function,
    default: () => {},
  },
})

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  rating: 0,
  comment: '',
})

const loading = ref(false)
const errors = ref({})
const successMessage = ref('')
const errorMessage = ref('')
const hasReviewed = ref(false)
const hasPurchased = ref(false)
const isLoggedIn = computed(() => authStore.isLoggedIn)

const submitReview = async () => {
  errors.value = {}
  successMessage.value = ''
  errorMessage.value = ''

  if (!form.value.rating) {
    errors.value.rating = 'Vui lòng chọn đánh giá'
    return
  }

  loading.value = true
  try {
    await reviewService.create({
      product_id: props.productId,
      rating: form.value.rating,
      comment: form.value.comment || null,
    })

    successMessage.value = 'Đánh giá của bạn đã được gửi thành công!'
    hasReviewed.value = true
    form.value = { rating: 0, comment: '' }

    setTimeout(() => {
      props.onSuccess()
    }, 1500)
  } catch (err) {
    if (err.response?.status === 422) {
      const data = err.response.data
      if (data.errors) {
        errors.value = data.errors
      } else if (data.message) {
        errorMessage.value = data.message
        hasReviewed.value = true
      }
    } else {
      errorMessage.value = err.response?.data?.message || 'Không thể gửi đánh giá'
    }
  } finally {
    loading.value = false
  }
}

const checkExistingReview = async () => {
  if (!isLoggedIn.value) return

  try {
    // Check if user has purchased this product
    const purchaseResponse = await reviewService.checkPurchase(props.productId)
    hasPurchased.value = purchaseResponse.data.has_purchased || false

    // Check if user has already reviewed this product
    const reviewResponse = await reviewService.myReviews()
    if (reviewResponse.data.data && reviewResponse.data.data.length > 0) {
      const hasReviewForProduct = reviewResponse.data.data.some(
        (review) => review.product_id === props.productId
      )
      hasReviewed.value = hasReviewForProduct
    }
  } catch (err) {
    console.error('Error checking review status:', err)
    // If error (maybe not logged in), assume not purchased
    hasPurchased.value = false
  }
}

onMounted(() => {
  checkExistingReview()
})
</script>
