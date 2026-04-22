<template>
  <main class="space-y-6">
    <!-- Header Section -->
    <section>
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <h1 class="text-2xl font-black tracking-tight text-slate-900">Quản lý đánh giá</h1>
          <p class="mt-1 text-sm text-slate-500">
            Theo dõi, xác nhận và cập nhật trạng thái đánh giá từ khách hàng.
          </p>
        </div>

        <div class="grid w-full grid-cols-2 gap-4 md:grid-cols-4 lg:w-auto">
          <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="text-sm text-slate-500">Tổng đánh giá</div>
            <div class="mt-2 text-2xl font-black text-slate-900">{{ stats.total || 0 }}</div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="text-sm text-slate-500">Đã phản hồi</div>
            <div class="mt-2 text-2xl font-black text-green-600">{{ stats.replied || 0 }}</div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <div class="text-sm text-slate-500">Chưa phản hồi</div>
            <div class="mt-2 text-2xl font-black text-amber-600">{{ stats.unreplied || 0 }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Reviews Table Section -->
    <section >
      <BaseTable
        :columns="columns"
        :items="reviews"
        :loading="loading"
        empty-text="Không có đánh giá nào"
        :searchable="true"
        :search="filters.search"
        search-placeholder="Tìm tên người dùng, sản phẩm hoặc nội dung..."
        :sort-by="filters.sortBy"
        :sort-dir="filters.sortDir"
        :pagination="pagination"
        :per-page="filters.per_page"
        :show-per-page="true"
        :actions="true"
        :row-actions="rowActions"
        @update:search="onSearchChange"
        @update:perPage="onPerPageChange"
        @sort="onSort"
        @page-change="onPageChange"
        @action="onRowAction"
      >
        <!-- Filters -->
        <template #filters>
          <div class="flex flex-wrap items-center gap-2">
            <BaseSelect
              v-model="filters.replied"
              :options="repliedOptions"
              size="sm"
              placeholder="Tất cả"
              wrapperClass="!w-[200px] shrink-0"
              @change="onRepliedChange"
            />

            <button
              type="button"
              class="h-10 shrink-0 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
              @click="resetFilters"
            >
              Làm mới
            </button>
          </div>
        </template>

        <!-- User Column -->
        <template #cell-user="{ item }">
          <div>
            <div class="font-semibold text-slate-900">{{ item.user?.name || 'N/A' }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ item.user?.email || "-" }}</div>
          </div>
        </template>

        <!-- Product Column -->
        <template #cell-product_name="{ value }">
          <div>
            <div class="font-semibold text-slate-900">{{ value }}</div>
          </div>
        </template>

        <!-- Rating Column -->
        <template #cell-rating="{ value }">
          <div class="flex gap-0.5 text-amber-400">
            <span v-for="i in 5" :key="i" :class="i <= value ? '' : 'text-slate-300'">★</span>
          </div>
        </template>

        <!-- Comment Column -->
        <template #cell-comment="{ value }">
          <span class="text-slate-600 text-sm line-clamp-2">
            {{ value || '-' }}
          </span>
        </template>

        <!-- Status Column -->
        <template #cell-admin_reply="{ value }">
          <span
            v-if="value"
            class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold text-green-700"
          >
            <span class="material-symbols-outlined text-sm">check_circle</span>
            Đã phản hồi
          </span>
          <span
            v-else
            class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700"
          >
            <span class="material-symbols-outlined text-sm">pending</span>
            Chưa phản hồi
          </span>
        </template>

        <!-- Created At Column -->
        <template #cell-created_at="{ value }">
          <span class="text-slate-500">{{ formatDateTime(value) }}</span>
        </template>
      </BaseTable>

      <div v-if="error" class="mt-4 text-sm text-red-600">
        {{ error }}
      </div>
    </section>
  </main>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import BaseTable from '../../../components/BaseTable.vue'
import BaseSelect from '../../../components/BaseSelect.vue'
import { useAlert } from '../../../composables/useAlert'
import reviewService from '../../../services/admin/reviewService'
import { formatDateTime } from '../../../utils/date'

const router = useRouter()
const route = useRoute()
const alert = useAlert()

const reviews = ref([])
const stats = ref({})
const loading = ref(false)
const error = ref('')

const filters = reactive({
  search: String(route.query.search || ''),
  replied: String(route.query.replied || ''),
  page: Number(route.query.page || 1),
  per_page: Number(route.query.per_page || 10),
  sortBy: String(route.query.sortBy || 'created_at'),
  sortDir: String(route.query.sortDir || 'desc'),
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})

const repliedOptions = [
  { label: 'Tất cả', value: '' },
  { label: 'Đã phản hồi', value: '1' },
  { label: 'Chưa phản hồi', value: '0' },
]

// Table columns configuration
const columns = [
  { key: 'user', label: 'Người dùng', width: '200px' },
  { key: 'product_name', label: 'Sản phẩm', width: '200px' },
  { key: 'rating', label: 'Đánh giá', width: '120px' },
  { key: 'comment', label: 'Nội dung', width: '250px' },
  { key: 'admin_reply', label: 'Phản hồi', width: '140px' },
  { key: 'created_at', label: 'Ngày tạo', sortable: true, width: '160px' },
]

// Row actions configuration
const rowActions = [
  {
    key: 'view',
    icon: 'visibility',
    title: 'Chi tiết',
    class: 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100'
  },
  {
    key: 'delete',
    icon: 'delete',
    title: 'Xóa',
    danger: true,
  },
]

const fetchReviews = async () => {
  loading.value = true
  error.value = ''
  try {
    const params = {
      search: filters.search || undefined,
      page: filters.page,
      per_page: filters.per_page,
      sort_by: filters.sortBy || undefined,
      sort_dir: filters.sortDir || undefined,
    }

    if (filters.replied === '1') {
      params.has_reply = true
    } else if (filters.replied === '0') {
      params.has_reply = false
    }
    
    const response = await reviewService.list(params)
    
    // Transform reviews to include product_name field for display
    reviews.value = (response.data.data || []).map(review => ({
      ...review,
      product_name: review.product?.name || 'N/A'
    }))
    
    pagination.value = {
      current_page: response.data.current_page || 1,
      last_page: response.data.last_page || 1,
      per_page: response.data.per_page || filters.per_page,
      total: response.data.total || 0,
    }

    // Fetch stats
    const statsResponse = await reviewService.getStats()
    stats.value = statsResponse.data || {}
  } catch (err) {
    console.error('Fetch reviews error:', err)
    error.value = err.response?.data?.message || 'Không tải được danh sách đánh giá.'
    alert.error(error.value)
  } finally {
    loading.value = false
  }
}

const onRepliedChange = (value) => {
  filters.replied = value == null ? '' : String(value)
  filters.page = 1
  fetchReviews()
}

const onSearchChange = (value) => {
  filters.search = value
  filters.page = 1
  fetchReviews()
}

const onPerPageChange = (value) => {
  filters.per_page = Number(value || 10)
  filters.page = 1
  fetchReviews()
}

const onSort = ({ sortBy, sortDir }) => {
  filters.sortBy = sortBy
  filters.sortDir = sortDir
  filters.page = 1
  fetchReviews()
}

const onPageChange = (page) => {
  filters.page = page
  fetchReviews()
}

const resetFilters = () => {
  filters.search = ''
  filters.replied = ''
  filters.page = 1
  filters.per_page = 10
  filters.sortBy = 'created_at'
  filters.sortDir = 'desc'
  fetchReviews()
}

const onRowAction = async (payload) => {
  const { key, item } = payload
  
  if (!item || !item.id) {
    alert.error('Không thể xác định đánh giá')
    return
  }

  try {
    switch (key) {
      case 'view':
        router.push(`/admin/reviews/${item.id}`)
        break
      case 'delete':
        if (!confirm('Bạn có chắc chắn muốn xóa đánh giá này?')) return
        await reviewService.delete(item.id)
        alert.success('Đánh giá đã được xóa')
        await fetchReviews()
        break
      default:
        console.warn('Unknown action:', key)
    }
  } catch (err) {
    console.error('Action error:', err)
    let errorMessage = 'Lỗi thực hiện hành động'
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.message) {
      errorMessage = err.message
    }
    alert.error(errorMessage)
  }
}

onMounted(fetchReviews)
</script>
