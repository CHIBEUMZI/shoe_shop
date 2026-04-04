<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import AdminEntityForm from "../../../components/AdminEntityForm.vue";
import BaseMultiSelect from "../../../components/BaseMultiSelect.vue";
import couponAdminService from "../../../services/admin/couponAdminService";
import productAdminService from "../../../services/admin/productAdminService";
import categoryAdminService from "../../../services/admin/categoryAdminService";
import brandAdminService from "../../../services/admin/brandAdminService";
import { useAlert } from "../../../composables/useAlert";

const router = useRouter();
const notify = useAlert();

const values = ref({
  code: "",
  name: "",
  description: "",
  type: "percentage",
  value: "",
  max_discount: "",
  min_order_amount: "",
  usage_limit: "",
  per_user_limit: 1,
  is_active: true,
  starts_at: "",
  expires_at: "",
  applicable_to: "all",
  applicable_ids: [],
});

const products = ref([]);
const categories = ref([]);
const brands = ref([]);

// Clear applicable_ids when switching to 'all'
watch(() => values.value.applicable_to, (newVal) => {
  if (newVal === "all") {
    values.value.applicable_ids = [];
  }
});

const productOptions = computed(() =>
  products.value.map((p) => ({ label: p.name, value: Number(p.id) }))
);

const categoryOptions = computed(() =>
  categories.value.map((c) => ({ label: c.name, value: Number(c.id) }))
);

const brandOptions = computed(() =>
  brands.value.map((b) => ({ label: b.name, value: Number(b.id) }))
);

onMounted(async () => {
  try {
    const productsRes = await productAdminService.index({ per_page: 1000 });
    products.value = productsRes?.data?.data || [];
  } catch (e) {
    console.error("Failed to load products:", e);
  }

  try {
    const categoriesRes = await categoryAdminService.list({ per_page: 1000 });
    categories.value = categoriesRes?.data?.data || [];
  } catch (e) {
    console.error("Failed to load categories:", e);
  }

  try {
    const brandsRes = await brandAdminService.list({ per_page: 1000 });
    brands.value = brandsRes?.data?.data || [];
  } catch (e) {
    console.error("Failed to load brands:", e);
  }
});

const applicableToOptions = [
  { label: "Tất cả sản phẩm", value: "all" },
  { label: "Sản phẩm cụ thể", value: "specific_products" },
  { label: "Danh mục cụ thể", value: "specific_categories" },
  { label: "Thương hiệu cụ thể", value: "specific_brands" },
];

const typeOptions = [
  { label: "Phần trăm (%)", value: "percentage" },
  { label: "Số tiền cố định (VNĐ)", value: "fixed" },
];

const schema = computed(() => [
  {
    group: "general",
    groupTitle: "Thông tin cơ bản",
    name: "code",
    label: "Mã giảm giá",
    type: "text",
    required: true,
    placeholder: "VD: SUMMER2026",
  },
  {
    group: "general",
    name: "name",
    label: "Tên mã giảm giá",
    type: "text",
    required: true,
    placeholder: "VD: Giảm giá mùa hè",
  },
  {
    group: "general",
    name: "description",
    label: "Mô tả",
    type: "textarea",
    full: true,
    rows: 3,
    placeholder: "Mô tả chi tiết về mã giảm giá...",
  },
  {
    group: "discount",
    groupTitle: "Giá trị giảm giá",
    name: "type",
    label: "Loại giảm giá",
    type: "select",
    options: typeOptions,
  },
  {
    group: "discount",
    name: "value",
    label: "Giá trị giảm",
    type: "number",
    required: true,
    min: 0,
  },
  {
    group: "discount",
    name: "max_discount",
    label: "Giảm tối đa",
    type: "number",
    min: 0,
    help: "Áp dụng cho loại phần trăm",
  },
  {
    group: "discount",
    name: "min_order_amount",
    label: "Đơn hàng tối thiểu",
    type: "number",
    min: 0,
  },
  {
    group: "limits",
    groupTitle: "Giới hạn sử dụng",
    name: "usage_limit",
    label: "Số lần sử dụng tối đa",
    type: "number",
    min: 0,
    help: "Để trống nếu không giới hạn",
  },
  {
    group: "limits",
    name: "per_user_limit",
    label: "Số lần mỗi người dùng",
    type: "number",
    min: 1,
  },
  {
    group: "limits",
    name: "is_active",
    label: "Trạng thái",
    type: "switch",
    onText: "Kích hoạt",
    offText: "Vô hiệu hoá",
  },
  {
    group: "schedule",
    groupTitle: "Thời gian hiệu lực",
    name: "starts_at",
    label: "Ngày bắt đầu",
    type: "datetime-local",
  },
  {
    group: "schedule",
    name: "expires_at",
    label: "Ngày hết hạn",
    type: "datetime-local",
  },
  {
    group: "applicability",
    groupTitle: "Áp dụng cho",
    name: "applicable_to",
    label: "Áp dụng cho",
    type: "select",
    options: applicableToOptions,
    full: true,
  },
  {
    group: "applicability",
    name: "applicable_ids_slot",
    label: "Chọn đối tượng áp dụng",
    type: "slot",
    full: true,
  },
]);

function validate(v) {
  const errs = {};

  if (!String(v.code || "").trim()) {
    errs.code = "Mã giảm giá là bắt buộc";
  }

  if (!String(v.name || "").trim()) {
    errs.name = "Tên mã giảm giá là bắt buộc";
  }

  if (!v.value || Number(v.value) <= 0) {
    errs.value = "Giá trị giảm giá phải lớn hơn 0";
  }

  if (v.type === "percentage" && Number(v.value) > 100) {
    errs.value = "Phần trăm giảm không được vượt quá 100%";
  }

  if (v.starts_at && v.expires_at) {
    const start = new Date(v.starts_at);
    const end = new Date(v.expires_at);
    if (end <= start) {
      errs.expires_at = "Ngày hết hạn phải sau ngày bắt đầu";
    }
  }

  if (v.applicable_to !== "all" && (!v.applicable_ids || v.applicable_ids.length === 0)) {
    errs.applicable_ids = "Vui lòng chọn ít nhất một đối tượng áp dụng";
  }

  return errs;
}

function buildPayload(v) {
  const payload = {
    code: String(v.code || "").trim(),
    name: String(v.name || "").trim(),
    description: String(v.description || "").trim() || null,
    type: v.type || "percentage",
    value: Number(v.value || 0),
    is_active: v.is_active ? 1 : 0,
    applicable_to: v.applicable_to || "all",
  };

  if (v.max_discount) payload.max_discount = Number(v.max_discount);
  if (v.min_order_amount) payload.min_order_amount = Number(v.min_order_amount);
  if (v.usage_limit) payload.usage_limit = Number(v.usage_limit);
  if (v.per_user_limit) payload.per_user_limit = Number(v.per_user_limit);
  if (v.starts_at) payload.starts_at = v.starts_at;
  if (v.expires_at) payload.expires_at = v.expires_at;

  if (v.applicable_to === "all") {
    payload.applicable_ids = [];
  } else {
    payload.applicable_ids = (v.applicable_ids || []).map((id) => Number(id));
  }

  return payload;
}

async function onSubmit(payload) {
  try {
    await couponAdminService.createCoupon(payload);

    notify.success("Tạo mã giảm giá thành công.", {
      title: "Thành công",
      duration: 2500,
    });

    router.push("/admin/coupons");
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Tạo mã giảm giá thất bại.";
    notify.error(msg, { title: "Lưu thất bại", duration: 3500 });
    throw e;
  }
}
</script>

<template>
  <AdminEntityForm
    mode="create"
    title="Tạo mã giảm giá"
    subtitle="Mã • tên • loại giảm giá • thời hạn"
    v-model:values="values"
    :schema="schema"
    :validate="validate"
    :transform-before-submit="buildPayload"
    :on-submit="onSubmit"
    :on-cancel="() => router.push('/admin/coupons')"
    submit-text="Tạo mã giảm giá"
    cancel-text="Huỷ"
  >
    <template #field:applicable_ids_slot="{ values: v, errors }">
      <div class="field full">
        <BaseMultiSelect
          v-if="v.applicable_to === 'specific_products'"
          v-model="v.applicable_ids"
          :options="productOptions"
          label="Chọn sản phẩm"
          placeholder="Tìm và chọn sản phẩm..."
          :max-display="5"
          :searchable="true"
        />
        <BaseMultiSelect
          v-else-if="v.applicable_to === 'specific_categories'"
          v-model="v.applicable_ids"
          :options="categoryOptions"
          label="Chọn danh mục"
          placeholder="Tìm và chọn danh mục..."
          :max-display="5"
          :searchable="true"
        />
        <BaseMultiSelect
          v-else-if="v.applicable_to === 'specific_brands'"
          v-model="v.applicable_ids"
          :options="brandOptions"
          label="Chọn thương hiệu"
          placeholder="Tìm và chọn thương hiệu..."
          :max-display="5"
          :searchable="true"
        />
        <div v-else class="slot-hint">
          Mã giảm giá này sẽ áp dụng cho tất cả sản phẩm
        </div>
        <div v-if="errors.applicable_ids" class="err">{{ errors.applicable_ids }}</div>
      </div>
    </template>
  </AdminEntityForm>
</template>

<style scoped>
.slot-hint {
  padding: 12px 16px;
  background: #f0f7ff;
  border: 1.5px solid #bfdbfe;
  border-radius: 10px;
  font-size: 13px;
  color: #2563eb;
  font-weight: 500;
}

.err {
  font-size: 11.5px;
  color: #ef4444;
  font-weight: 600;
  margin-top: 4px;
}
</style>
