<template>
  <div class="review-list space-y-4">
    <h3 class="text-lg font-semibold">Đánh giá từ khách hàng</h3>

    <!-- Loading state -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="reviews.length === 0" class="bg-gray-50 rounded-lg p-8 text-center">
      <p class="text-gray-600">Chưa có đánh giá nào cho sản phẩm này</p>
    </div>

    <!-- Reviews list -->
    <div v-else class="space-y-4">
      <div
        v-for="review in reviews"
        :key="review.id"
        class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
      >
        <!-- Header: Avatar and info -->
        <div class="flex items-start gap-4">
          <div class="flex-shrink-0">
            <img
              v-if="review.user.avatar"
              :src="review.user.avatar"
              :alt="review.user.name"
              class="w-10 h-10 rounded-full object-cover"
            />
            <div v-else class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold">
              {{ review.user.name.charAt(0) }}
            </div>
          </div>

          <div class="flex-1">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="font-semibold text-gray-900">{{ review.user.name }}</h4>
                <p class="text-xs text-gray-500">{{ formatDate(review.created_at) }}</p>
              </div>
              <div class="flex items-center gap-2">
                <div v-if="review.verified_purchase" class="flex items-center gap-1 bg-green-50 border border-green-200 rounded px-2 py-1 text-xs text-green-700">
                  ✓ Xác thực
                </div>
                <!-- Edit button if own review -->
                <button
                  v-if="isOwnReview(review)"
                  @click="openEditModal(review)"
                  class="text-xs text-blue-600 hover:text-blue-700 hover:underline"
                >
                  Sửa
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Stars -->
        <div class="flex gap-1 mt-2">
          <div
            v-for="i in 5"
            :key="i"
            class="text-lg cursor-pointer"
            :class="editingReview?.id === review.id && editingReview.newRating >= i ? 'text-amber-400' : (i <= review.rating ? 'text-amber-400' : 'text-gray-300')"
            @click="isOwnReview(review) && editingReview?.id === review.id ? editingReview.newRating = i : null"
          >
            ★
          </div>
        </div>

        <!-- Comment -->
        <div v-if="review.comment && editingReview?.id !== review.id" class="mt-3">
          <p class="text-gray-700 text-sm leading-relaxed">{{ review.comment }}</p>
        </div>

        <!-- Edit Form -->
        <div v-if="editingReview?.id === review.id" class="mt-3 space-y-3">
          <div class="flex gap-1">
            <div
              v-for="i in 5"
              :key="i"
              class="text-xl cursor-pointer text-amber-400"
              @click="editingReview.newRating = i"
            >
              ★
            </div>
            <span class="ml-2 text-sm text-gray-600 self-center">{{ editingReview.newRating }}/5</span>
          </div>
          <textarea
            v-model="editingReview.newComment"
            class="w-full border border-gray-300 rounded-lg p-3 text-sm resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            rows="3"
            placeholder="Chia sẻ trải nghiệm của bạn..."
          ></textarea>
          <div class="flex gap-2 justify-end">
            <button
              @click="cancelEdit"
              class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50"
            >
              Hủy
            </button>
            <button
              @click="submitEdit"
              :disabled="savingId === review.id"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50"
            >
              {{ savingId === review.id ? 'Đang lưu...' : 'Lưu' }}
            </button>
          </div>
        </div>

        <!-- Admin Reply -->
        <div v-if="review.admin_reply" class="mt-3 pl-4 border-l-4 border-green-500 bg-green-50 rounded-r-lg p-3">
          <div class="flex items-center gap-2 mb-1">
            <span class="text-green-600 font-semibold text-sm">🏪 Phản hồi từ cửa hàng</span>
            <span v-if="review.replied_at" class="text-xs text-gray-500">{{ formatDate(review.replied_at) }}</span>
          </div>
          <p class="text-gray-700 text-sm leading-relaxed">{{ review.admin_reply }}</p>
        </div>

        <!-- Delete button if own review -->
        <div v-if="isOwnReview(review) && editingReview?.id !== review.id" class="flex gap-2 mt-3 pt-3 border-t border-gray-200">
          <button
            @click="deleteReview(review.id)"
            :disabled="deletingId === review.id"
            class="text-xs text-red-600 hover:text-red-700 hover:underline disabled:text-gray-400"
          >
            <span v-if="deletingId === review.id">Đang xóa...</span>
            <span v-else>Xóa</span>
          </button>
        </div>
      </div>

      <!-- Load more button -->
      <div v-if="totalPages > 1 && currentPage < totalPages" class="flex justify-center pt-4">
        <button
          @click="loadMore"
          :disabled="loading"
          class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
        >
          Xem thêm
        </button>
      </div>
    </div>

    <!-- Error message -->
    <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import reviewService from '../../services/public/reviewService'
import { formatDate, formatRelativeTime } from '../../utils/date'

const props = defineProps({
  productId: {
    type: Number,
    required: true,
  },
  refreshTrigger: {
    type: Number,
    default: 0,
  },
})

const authStore = useAuthStore()
const reviews = ref([])
const loading = ref(false)
const error = ref(null)
const deletingId = ref(null)
const savingId = ref(null)
const editingReview = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const currentUserId = computed(() => authStore.user?.id)

const isOwnReview = (review) => {
  return currentUserId.value && review.user.id === parseInt(currentUserId.value)
}

const fetchReviews = async (page = 1) => {
  loading.value = true
  error.value = null
  try {
    const response = await reviewService.listByProduct(props.productId, {
      page: page,
      per_page: 10,
    })
    
    if (page === 1) {
      reviews.value = response.data.data
    } else {
      reviews.value.push(...response.data.data)
    }
    
    currentPage.value = response.data.current_page
    totalPages.value = response.data.last_page
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load reviews'
  } finally {
    loading.value = false
  }
}

const loadMore = () => {
  fetchReviews(currentPage.value + 1)
}

const deleteReview = async (reviewId) => {
  if (!confirm('Bạn chắc chắn muốn xóa đánh giá này?')) return

  deletingId.value = reviewId
  try {
    await reviewService.delete(reviewId)
    reviews.value = reviews.value.filter((r) => r.id !== reviewId)
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete review'
  } finally {
    deletingId.value = null
  }
}

const openEditModal = (review) => {
  editingReview.value = {
    id: review.id,
    newRating: review.rating,
    newComment: review.comment || '',
  }
}

const cancelEdit = () => {
  editingReview.value = null
}

const submitEdit = async () => {
  if (!editingReview.value) return

  savingId.value = editingReview.value.id
  try {
    const response = await reviewService.update(editingReview.value.id, {
      rating: editingReview.value.newRating,
      comment: editingReview.value.newComment,
    })

    // Update the review in the list
    const index = reviews.value.findIndex(r => r.id === editingReview.value.id)
    if (index !== -1) {
      reviews.value[index] = {
        ...reviews.value[index],
        rating: response.data.data?.rating || editingReview.value.newRating,
        comment: response.data.data?.comment || editingReview.value.newComment,
      }
    }

    editingReview.value = null
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update review'
  } finally {
    savingId.value = null
  }
}

onMounted(() => {
  fetchReviews()
})

// Watch for refresh trigger
watch(() => props.refreshTrigger, () => {
  currentPage.value = 1
  fetchReviews()
})
</script>
