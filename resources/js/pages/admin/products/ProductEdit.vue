<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import AdminEntityForm from "../../../components/AdminEntityForm.vue";

import productAdminService from "../../../services/admin/productAdminService";
import uploadAdminService from "../../../services/admin/uploadAdminService";
import brandAdminService from "../../../services/admin/brandAdminService";
import categoryAdminService from "../../../services/admin/categoryAdminService";
import { useAlert } from "../../../composables/useAlert";

/** ========= helpers ========= */
function slugify(str) {
  return (str || "")
    .toString()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)+/g, "");
}

function skuify(str) {
  return (str || "")
    .toString()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toUpperCase()
    .trim()
    .replace(/[^A-Z0-9]+/g, "-")
    .replace(/(^-|-$)+/g, "");
}

function makeProductSkuFromName(name) {
  return skuify(name) || "";
}

function makeVariantSku(productSku, color, size) {
  const p = skuify(productSku);
  const c = skuify(color);
  const s = skuify(size);
  return [p, c, s].filter(Boolean).join("-");
}

function makeKey() {
  return `${Date.now()}_${Math.random().toString(16).slice(2)}`;
}

function normalizeNumber(v) {
  if (v === "" || v === undefined || v === null) return null;
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
}

function extractPathOrUrl(resp) {
  const root = resp?.data ?? resp;

  if (root?.path) return root.path;
  if (root?.url) return root.url;

  if (root?.data?.path) return root.data.path;
  if (root?.data?.url) return root.data.url;

  return "";
}

const router = useRouter();
const route = useRoute();
const notify = useAlert();
const id = computed(() => route.params.id);

/** ========= touched flags ========= */
const touchedSlug = ref(false);
const touchedProductSku = ref(false);
const touchedVariantSku = ref({});

/** ========= last auto snapshots ========= */
const lastAutoSlug = ref("");
const lastAutoProductSku = ref("");
const lastAutoVariantSku = ref({});

/** ========= form values ========= */
const values = ref({
  brand_id: null,
  category_ids: [],

  name: "",
  slug: "",
  sku: "",
  short_description: "",
  description: "",

  thumbnail: "",
  status: true,
  is_featured: false,

  variants: [
    {
      id: null,
      _key: makeKey(),
      color: "",
      size: "",
      sku: "",
      price: 0,
      sale_price: null,
      stock: 0,
      is_active: true,
      images: [{ url: "", sort_order: 0 }],
    },
  ],
});

/** ========= ensure _key & maps exist ========= */
watch(
  () => values.value.variants,
  (arr) => {
    (arr || []).forEach((it) => {
      if (!it._key) it._key = makeKey();

      if (touchedVariantSku.value[it._key] === undefined) {
        touchedVariantSku.value = {
          ...touchedVariantSku.value,
          [it._key]: false,
        };
      }

      if (lastAutoVariantSku.value[it._key] === undefined) {
        lastAutoVariantSku.value = {
          ...lastAutoVariantSku.value,
          [it._key]: "",
        };
      }
    });
  },
  { deep: true, immediate: true }
);

/** ========= AUTO slug + product sku by NAME ========= */
watch(
  () => values.value.name,
  (name) => {
    const autoSlug = slugify(name);
    const curSlug = String(values.value.slug || "").trim();

    if (!touchedSlug.value && (!curSlug || curSlug === lastAutoSlug.value)) {
      values.value.slug = autoSlug;
      lastAutoSlug.value = autoSlug;
    }

    const autoSku = makeProductSkuFromName(name);
    const curSku = String(values.value.sku || "").trim();

    if (!touchedProductSku.value && (!curSku || curSku === lastAutoProductSku.value)) {
      values.value.sku = autoSku;
      lastAutoProductSku.value = autoSku;
    }
  },
  { immediate: true }
);

watch(
  () => values.value.slug,
  (val) => {
    const cur = String(val || "").trim();

    if (!cur) {
      touchedSlug.value = false;
      return;
    }

    if (lastAutoSlug.value && cur !== lastAutoSlug.value) {
      touchedSlug.value = true;
    }
  }
);

watch(
  () => values.value.sku,
  (val) => {
    const cur = String(val || "").trim();

    if (!cur) {
      touchedProductSku.value = false;
      return;
    }

    if (lastAutoProductSku.value && cur !== lastAutoProductSku.value) {
      touchedProductSku.value = true;
    }
  }
);

/** ========= AUTO variant sku by productSku + color + size ========= */
watch(
  () => [values.value.sku, values.value.variants],
  () => {
    const productSku = values.value.sku;

    (values.value.variants || []).forEach((it) => {
      const key = it._key;
      const touched = !!touchedVariantSku.value[key];
      const lastAuto = lastAutoVariantSku.value[key] ?? "";
      const cur = String(it.sku || "").trim();

      const auto = makeVariantSku(productSku, it.color, it.size);

      if (!touched && (!cur || cur === lastAuto)) {
        it.sku = auto;
        lastAutoVariantSku.value = {
          ...lastAutoVariantSku.value,
          [key]: auto,
        };
      }
    });
  },
  { deep: true, immediate: true }
);

watch(
  () => values.value.variants.map((v) => ({ key: v._key, sku: v.sku })),
  (rows) => {
    rows.forEach((r) => {
      const key = r.key;
      const cur = String(r.sku || "").trim();
      const lastAuto = lastAutoVariantSku.value[key] ?? "";

      if (!cur) {
        touchedVariantSku.value = {
          ...touchedVariantSku.value,
          [key]: false,
        };
        return;
      }

      if (lastAuto && cur !== lastAuto) {
        touchedVariantSku.value = {
          ...touchedVariantSku.value,
          [key]: true,
        };
      }
    });
  },
  { deep: true }
);

/** ========= options ========= */
const brandOptions = ref([]);
const categoryOptions = ref([]);

async function fetchBrands() {
  try {
    const { data } = await brandAdminService.list({
      page: 1,
      per_page: 200,
      status: 1,
    });
    brandOptions.value = data?.data || [];
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Không tải được danh sách thương hiệu.";
    notify.error(msg, {
      title: "Lỗi tải dữ liệu",
      duration: 3500,
    });
    throw e;
  }
}

async function fetchCategories() {
  try {
    const { data } = await categoryAdminService.list({
      page: 1,
      per_page: 500,
      status: 1,
    });
    categoryOptions.value = data?.data || [];
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Không tải được danh sách danh mục.";
    notify.error(msg, {
      title: "Lỗi tải dữ liệu",
      duration: 3500,
    });
    throw e;
  }
}

async function uploadThumbnailWithAlert(file) {
  try {
    return await uploadAdminService.uploadProductImage(file);
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Upload ảnh đại diện thất bại.";

    notify.error(msg, {
      title: "Upload thất bại",
      duration: 3500,
    });

    throw e;
  }
}

const schema = computed(() => [
  {
    group: "general",
    groupTitle: "Thông tin cơ bản",
    name: "name",
    label: "Tên sản phẩm",
    type: "text",
    required: true,
    placeholder: "Nhập tên sản phẩm",
  },
  {
    group: "general",
    name: "slug",
    label: "Slug",
    type: "text",
    required: true,
    placeholder: "Nhập slug (để trống sẽ tự tạo)",
    help: "Xóa trống để hệ thống auto lại theo tên.",
  },
  {
    group: "general",
    name: "sku",
    label: "SKU (Auto từ tên - có thể sửa)",
    type: "text",
    placeholder: "VD: NIKE-AIR-FORCE-1",
    help: "Nếu sửa tay thì hệ thống không tự đổi nữa (xóa trống để auto lại).",
  },
  {
    group: "general",
    name: "short_description",
    label: "Mô tả ngắn",
    type: "text",
    full: true,
    placeholder: "Mô tả ngắn...",
  },
  {
    group: "general",
    name: "description",
    label: "Mô tả chi tiết",
    type: "textarea",
    full: true,
    rows: 6,
    placeholder: "Nhập mô tả...",
  },

  {
    group: "settings",
    groupTitle: "Cài đặt",
    name: "brand_id",
    label: "Thương hiệu",
    type: "select",
    required: true,
    options: brandOptions.value.map((b) => ({
      label: b.name,
      value: b.id,
    })),
  },
  {
    group: "settings",
    name: "category_ids",
    label: "Danh mục",
    type: "checkboxes",
    required: true,
    checkboxColumns: 2,
    options: categoryOptions.value.map((c) => ({
      label: c.name,
      value: c.id,
    })),
  },
  {
    group: "settings",
    name: "status",
    label: "Trạng thái",
    type: "switch",
    onText: "Hoạt động",
    offText: "Tạm tắt",
  },
  {
    group: "settings",
    name: "is_featured",
    label: "Nổi bật",
    type: "switch",
    onText: "Bật",
    offText: "Tắt",
  },

  {
    group: "media",
    groupTitle: "Media",
    name: "thumbnail",
    label: "Hình ảnh",
    type: "file",
    full: true,
    upload: uploadThumbnailWithAlert,
    uploadHint: "PNG, JPG, WEBP (Max 5MB)",
    placeholder: "path/url...",
  },
]);

function validate(v) {
  const errs = {};

  if (!v.brand_id) errs.brand_id = "Vui lòng chọn thương hiệu";
  if (!v.category_ids || v.category_ids.length === 0) {
    errs.category_ids = "Vui lòng chọn ít nhất 1 danh mục";
  }
  if (!String(v.name || "").trim()) errs.name = "Tên sản phẩm là bắt buộc";
  if (!String(v.slug || "").trim()) errs.slug = "Slug là bắt buộc";

  if (!Array.isArray(v.variants) || v.variants.length === 0) {
    return { ...errs, _form: "Cần ít nhất 1 biến thể" };
  }

  const seen = new Set();

  for (let i = 0; i < v.variants.length; i++) {
    const it = v.variants[i];

    if (!it.color || !it.size) {
      return { ...errs, _form: `Biến thể #${i + 1}: cần màu & size` };
    }

    const key = `${String(it.color).trim().toLowerCase()}__${String(it.size).trim().toLowerCase()}`;
    if (seen.has(key)) {
      return { ...errs, _form: `Trùng biến thể: ${it.color} - ${it.size}` };
    }
    seen.add(key);

    if (normalizeNumber(it.price) === null || Number(it.price) < 0) {
      return { ...errs, _form: `Biến thể #${i + 1}: giá không hợp lệ` };
    }

    if (normalizeNumber(it.stock) === null || Number(it.stock) < 0) {
      return { ...errs, _form: `Biến thể #${i + 1}: tồn kho không hợp lệ` };
    }

    const sp = normalizeNumber(it.sale_price);
    if (sp !== null && sp < 0) {
      return { ...errs, _form: `Biến thể #${i + 1}: giá KM không hợp lệ` };
    }
  }

  return errs;
}

function buildPayload(v) {
  return {
    brand_id: v.brand_id ? Number(v.brand_id) : null,
    category_ids: (v.category_ids || []).map((x) => Number(x)),

    name: String(v.name || "").trim(),
    slug: String(v.slug || "").trim(),
    sku: String(v.sku || "").trim() || null,
    short_description: String(v.short_description || "").trim() || null,
    description: v.description || null,

    thumbnail: String(v.thumbnail || "").trim() || null,
    status: v.status ? 1 : 0,
    is_featured: !!v.is_featured,

    variants: (v.variants || []).map((it) => ({
      id: it.id ?? null,
      color: String(it.color).trim(),
      size: String(it.size).trim(),
      sku: String(it.sku || "").trim() || null,
      price: Number(it.price),
      sale_price: normalizeNumber(it.sale_price),
      stock: Number(it.stock),
      is_active: !!it.is_active,
      images: (it.images || [])
        .filter((img) => (img.url || "").trim() !== "")
        .map((img) => ({
          url: (img.url || "").trim(),
          sort_order: Number(img.sort_order || 0),
        })),
    })),
  };
}

/** ========= load product ========= */
async function loadProduct(productId) {
  try {
    const { data } = await productAdminService.show(productId);
    const p = data?.data ?? data;

    const loaded = {
      brand_id: p?.brand_id ?? null,
      category_ids: (p?.categories || p?.category_ids || []).map((x) =>
        Number(x.id ?? x)
      ),

      name: p?.name ?? "",
      slug: p?.slug ?? "",
      sku: p?.sku ?? "",
      short_description: p?.short_description ?? "",
      description: p?.description ?? "",

      thumbnail: p?.thumbnail ?? "",
      status: Number(p?.status ?? 1) === 1,
      is_featured: !!p?.is_featured,

      variants:
        Array.isArray(p?.variants) && p.variants.length
          ? p.variants.map((it) => ({
              id: it.id ?? null,
              _key: makeKey(),
              color: it.color ?? "",
              size: it.size ?? "",
              sku: it.sku ?? "",
              price: Number(it.price ?? 0),
              sale_price: it.sale_price ?? null,
              stock: Number(it.stock ?? 0),
              is_active: !!it.is_active,
              images:
                Array.isArray(it.images) && it.images.length
                  ? it.images.map((img) => ({
                      url: img.url ?? img.path ?? "",
                      sort_order: Number(img.sort_order ?? 0),
                    }))
                  : [{ url: "", sort_order: 0 }],
            }))
          : [
              {
                id: null,
                _key: makeKey(),
                color: "",
                size: "",
                sku: "",
                price: 0,
                sale_price: null,
                stock: 0,
                is_active: true,
                images: [{ url: "", sort_order: 0 }],
              },
            ],
    };

    touchedSlug.value = false;
    touchedProductSku.value = false;

    lastAutoSlug.value = String(loaded.slug || "").trim();
    lastAutoProductSku.value = String(loaded.sku || "").trim();

    const vAuto = {};
    const vTouched = {};

    (loaded.variants || []).forEach((it) => {
      vAuto[it._key] = String(it.sku || "").trim();
      vTouched[it._key] = false;
    });

    lastAutoVariantSku.value = vAuto;
    touchedVariantSku.value = vTouched;

    return loaded;
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Không tải được thông tin sản phẩm.";

    notify.error(msg, {
      title: "Lỗi tải dữ liệu",
      duration: 3500,
    });

    throw e;
  }
}

async function onSubmit(payload) {
  try {
    await productAdminService.update(id.value, payload);

    notify.success("Cập nhật sản phẩm thành công.", {
      title: "Lưu thành công",
      duration: 2500,
    });

    router.push("/admin/products");
  } catch (e) {
    const msg =
      e?.response?.data?.message || e?.message || "Cập nhật sản phẩm thất bại.";

    notify.error(msg, {
      title: "Lưu thất bại",
      duration: 3500,
    });

    throw e;
  }
}

/** ===== Variants UI actions ===== */
function addVariant() {
  const next = {
    id: null,
    _key: makeKey(),
    color: "",
    size: "",
    sku: "",
    price: 0,
    sale_price: null,
    stock: 0,
    is_active: true,
    images: [{ url: "", sort_order: 0 }],
  };

  values.value = {
    ...values.value,
    variants: [...(values.value.variants || []), next],
  };

  notify.info("Đã thêm một biến thể mới.", {
    title: "Thêm biến thể",
    duration: 1600,
  });
}

function removeVariant(i) {
  if ((values.value.variants || []).length <= 1) {
    notify.warning("Sản phẩm cần ít nhất 1 biến thể.", {
      title: "Không thể xóa",
      duration: 2500,
    });
    return;
  }

  const arr = [...values.value.variants];
  const removed = arr[i];
  arr.splice(i, 1);

  values.value = { ...values.value, variants: arr };

  const key = removed?._key;
  if (key) {
    const t = { ...touchedVariantSku.value };
    delete t[key];
    touchedVariantSku.value = t;

    const a = { ...lastAutoVariantSku.value };
    delete a[key];
    lastAutoVariantSku.value = a;
  }

  notify.info("Đã xóa biến thể.", {
    title: "Đã xóa",
    duration: 1600,
  });
}

function addImage(variantIndex) {
  const arr = [...(values.value.variants || [])];
  const v = { ...(arr[variantIndex] || {}) };
  const imgs = Array.isArray(v.images) ? [...v.images] : [];
  const nextOrder = imgs.length
    ? Math.max(...imgs.map((x) => Number(x.sort_order || 0))) + 1
    : 0;

  imgs.push({ url: "", sort_order: nextOrder });
  v.images = imgs;
  arr[variantIndex] = v;
  values.value = { ...values.value, variants: arr };

  notify.info("Đã thêm ô ảnh cho biến thể.", {
    title: "Thêm ảnh",
    duration: 1500,
  });
}

function removeImage(variantIndex, imageIndex) {
  const arr = [...(values.value.variants || [])];
  const v = { ...(arr[variantIndex] || {}) };
  const imgs = Array.isArray(v.images) ? [...v.images] : [];

  if (imgs.length <= 1) {
    v.images = [{ url: "", sort_order: 0 }];
  } else {
    imgs.splice(imageIndex, 1);
    v.images = imgs;
  }

  arr[variantIndex] = v;
  values.value = { ...values.value, variants: arr };

  notify.info("Đã xóa ảnh của biến thể.", {
    title: "Đã xóa",
    duration: 1500,
  });
}

const uploadingVariantImage = ref({});

async function onPickVariantImageFile(e, variantIndex, imageIndex) {
  const file = e.target.files?.[0];
  if (!file) return;

  const key = `${variantIndex}-${imageIndex}`;
  uploadingVariantImage.value = {
    ...uploadingVariantImage.value,
    [key]: true,
  };

  try {
    const resp = await uploadAdminService.uploadProductImage(file);
    const pathOrUrl = extractPathOrUrl(resp);
    if (!pathOrUrl) throw new Error("Upload API did not return path/url");

    const arr = [...(values.value.variants || [])];
    const v = { ...(arr[variantIndex] || {}) };
    const imgs = Array.isArray(v.images) ? [...v.images] : [];

    imgs[imageIndex] = {
      ...(imgs[imageIndex] || { url: "", sort_order: 0 }),
      url: pathOrUrl,
    };

    v.images = imgs;
    arr[variantIndex] = v;
    values.value = { ...values.value, variants: arr };

    notify.success("Upload ảnh biến thể thành công.", {
      title: "Upload thành công",
      duration: 2200,
    });
  } catch (err) {
    const msg =
      err?.response?.data?.message || err?.message || "Upload ảnh thất bại";

    notify.error(msg, {
      title: "Upload thất bại",
      duration: 3500,
    });
  } finally {
    uploadingVariantImage.value = {
      ...uploadingVariantImage.value,
      [key]: false,
    };
    e.target.value = "";
  }
}

onMounted(async () => {
  await Promise.all([fetchBrands(), fetchCategories()]);
});
</script>

<template>
  <AdminEntityForm
    mode="edit"
    :entity-id="id"
    :load="loadProduct"
    title="Product Details"
    subtitle="Edit your footwear inventory item."
    v-model:values="values"
    :schema="schema"
    :validate="validate"
    :transform-before-submit="buildPayload"
    :on-submit="onSubmit"
    :on-cancel="() => router.push('/admin/products')"
    submit-text="Lưu"
    cancel-text="Hủy"
  >
    <template #extra="{ values: v }">
      <div class="card">
        <div class="card-head variants-head">
          <div class="card-head-left">
            <div class="card-head-icon">🎨</div>
            <div>
              <div class="card-title">Variants</div>
              <div class="card-subtitle">Color / Size / SKU / Price / Stock / Images</div>
            </div>
          </div>

          <button class="btn-outline" type="button" @click="addVariant">
            <span class="material-symbols-outlined">add</span>
            Add Variant
          </button>
        </div>

        <div class="card-body">
          <div class="table-wrap">
            <table class="variants">
              <thead>
                <tr>
                  <th>Color *</th>
                  <th>Size *</th>
                  <th>SKU</th>
                  <th class="num">Price *</th>
                  <th class="num">Sale</th>
                  <th class="num">Stock *</th>
                  <th class="center">Active</th>
                  <th class="center">Action</th>
                </tr>
              </thead>

              <tbody>
                <template v-for="(it, i) in v.variants" :key="it._key || i">
                  <tr>
                    <td><input v-model="it.color" class="tcontrol" placeholder="White" /></td>
                    <td><input v-model="it.size" class="tcontrol" placeholder="42" /></td>

                    <td>
                      <input v-model="it.sku" class="tcontrol" placeholder="AUTO" />
                      <div class="sku-hint" v-if="touchedVariantSku[it._key]">
                        (đã sửa tay — xóa trống để auto lại)
                      </div>
                    </td>

                    <td class="num">
                      <input v-model.number="it.price" type="number" min="0" class="tcontrol" />
                    </td>
                    <td class="num">
                      <input v-model.number="it.sale_price" type="number" min="0" class="tcontrol" />
                    </td>
                    <td class="num">
                      <input v-model.number="it.stock" type="number" min="0" class="tcontrol" />
                    </td>
                    <td class="center"><input v-model="it.is_active" type="checkbox" /></td>

                    <td class="center">
                      <button
                        class="icon-btn danger"
                        type="button"
                        title="Remove variant"
                        @click="removeVariant(i)"
                        :disabled="v.variants.length <= 1"
                      >
                        <span class="material-symbols-outlined">delete</span>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td colspan="8" class="imgs-cell">
                      <div class="imgs-head">
                        <div class="imgs-title">Images for #{{ i + 1 }} ({{ it.color }} / {{ it.size }})</div>

                        <button class="btn-outline sm" type="button" @click="addImage(i)">
                          <span class="material-symbols-outlined">add_photo_alternate</span>
                          Add Image
                        </button>
                      </div>

                      <div class="imgs-grid">
                        <div class="img-row" v-for="(img, j) in it.images" :key="j">
                          <div class="img-preview" v-if="img.url">
                            <img :src="img.url.startsWith('http') ? img.url : '/storage/' + img.url" />
                          </div>
                          <div class="img-preview placeholder" v-else>Preview</div>

                          <div class="fieldx">
                            <div class="labelx">Upload</div>

                            <label class="filebtn">
                              <span class="material-symbols-outlined">upload</span>
                              Chọn ảnh
                              <input
                                class="file-hidden"
                                type="file"
                                accept="image/*"
                                @change="(e) => onPickVariantImageFile(e, i, j)"
                              />
                            </label>

                            <div class="hintx" v-if="uploadingVariantImage[`${i}-${j}`]">Uploading...</div>
                          </div>

                          <div class="fieldx">
                            <div class="labelx">URL / Path</div>
                            <input v-model="img.url" class="tcontrol" />
                          </div>

                          <div class="fieldx sortx">
                            <div class="labelx">Sort</div>
                            <input v-model.number="img.sort_order" type="number" class="tcontrol" />
                          </div>

                          <div class="img-actions">
                            <button class="icon-btn danger" type="button" title="Xóa ảnh" @click="removeImage(i, j)">
                              <span class="material-symbols-outlined">delete</span>
                            </button>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

          <div class="product-sku-hint" v-if="touchedProductSku">
            SKU sản phẩm đã sửa tay — xóa trống để tự tạo lại theo tên.
          </div>
        </div>
      </div>
    </template>
  </AdminEntityForm>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap");

.card {
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 16px;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
  overflow: hidden;
  margin-top: 16px;
  transition: box-shadow 0.2s;
  font-family: "DM Sans", sans-serif;
}
.card:hover {
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.07);
}

.card-head {
  padding: 16px 20px;
  border-bottom: 1px solid #f1f4fa;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  background: #fafbfd;
}
.card-head-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.card-head-icon {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border-radius: 10px;
  display: grid;
  place-items: center;
  font-size: 16px;
  flex-shrink: 0;
}
.card-title {
  font-size: 14px;
  font-weight: 700;
  color: #0d1117;
  letter-spacing: -0.01em;
}
.card-subtitle {
  margin-top: 2px;
  font-size: 12px;
  color: #94a3b8;
  font-weight: 500;
}

.card-body {
  padding: 20px;
}

.product-sku-hint {
  margin-top: 10px;
  font-size: 12px;
  color: #64748b;
  font-weight: 600;
}
.sku-hint {
  margin-top: 4px;
  font-size: 11px;
  color: #94a3b8;
  font-weight: 600;
}

.btn-outline {
  border: 1.5px solid #e2e8f0;
  background: #fff;
  padding: 8px 12px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  color: #374151;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.15s;
  font-family: "DM Sans", sans-serif;
}
.btn-outline:hover {
  background: #f0f7ff;
  border-color: #93c5fd;
  color: #2563eb;
}
.btn-outline.sm {
  padding: 6px 10px;
  font-size: 12px;
  border-radius: 8px;
}

.icon-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1.5px solid #e2e8f0;
  background: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: 0.15s;
}
.icon-btn .material-symbols-outlined {
  font-size: 20px;
}
.icon-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}
.icon-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.icon-btn.danger {
  border-color: rgba(239, 68, 68, 0.25);
  background: rgba(239, 68, 68, 0.06);
  color: #dc2626;
}
.icon-btn.danger:hover {
  background: rgba(239, 68, 68, 0.12);
  border-color: rgba(239, 68, 68, 0.45);
}

.table-wrap {
  overflow: auto;
  border: 1.5px solid #e8ecf4;
  border-radius: 12px;
}
.variants {
  width: 100%;
  border-collapse: collapse;
  min-width: 1100px;
}
.variants thead th {
  position: sticky;
  top: 0;
  background: #f5f7ff;
  border-bottom: 1.5px solid #e8ecf4;
  padding: 11px 12px;
  text-align: left;
  font-size: 11px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-weight: 700;
}
.variants tr:hover td {
  background: #fafbff;
}
.variants td {
  border-top: 1px solid #f1f4fa;
  padding: 10px 12px;
  vertical-align: middle;
}
.num {
  text-align: right;
}
.center {
  text-align: center;
}

.tcontrol {
  width: 100%;
  height: 40px;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  padding: 0 10px;
  outline: none;
  font-size: 13px;
  font-family: "DM Sans", sans-serif;
  background: #fafbfd;
  transition: border-color 0.15s, box-shadow 0.15s;
  color: #0f172a;
}
.tcontrol:hover {
  border-color: #c7d4e8;
  background: #fff;
}
.tcontrol:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  background: #fff;
}

input[type="checkbox"] {
  accent-color: #3b82f6;
  width: 15px;
  height: 15px;
  cursor: pointer;
}

.imgs-cell {
  background: #f9fbff;
  padding: 14px 16px !important;
}
.imgs-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}
.imgs-title {
  font-weight: 700;
  color: #374151;
  font-size: 12.5px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.imgs-title::before {
  content: "🖼";
  font-size: 13px;
}
.imgs-grid {
  display: grid;
  gap: 12px;
}

.img-row {
  display: grid;
  grid-template-columns: 80px 220px 1fr 120px 56px;
  gap: 12px;
  align-items: start;
  padding: 12px;
  border: 1.5px solid #e8ecf4;
  border-radius: 12px;
  background: #fff;
  transition: border-color 0.15s;
}
.img-row:hover {
  border-color: #c7d4e8;
}

.img-preview {
  width: 80px;
  height: 80px;
  border-radius: 10px;
  overflow: hidden;
  border: 1.5px solid #e8ecf4;
  display: grid;
  place-items: center;
  background: #f5f7ff;
  color: #94a3b8;
  font-weight: 600;
  font-size: 11px;
}
.img-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.fieldx {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}
.labelx {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.filebtn {
  height: 40px;
  border: 1.5px dashed #bfdbfe;
  background: #f0f7ff;
  border-radius: 8px;
  padding: 0 12px;
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  user-select: none;
  white-space: nowrap;
}
.filebtn:hover {
  border-color: #3b82f6;
}
.filebtn .material-symbols-outlined {
  font-size: 18px;
}
.file-hidden {
  display: none;
}

.sortx .tcontrol {
  text-align: right;
}

.hintx {
  min-height: 16px;
  font-size: 11px;
  color: #3b82f6;
  font-weight: 600;
}
.img-actions {
  display: flex;
  justify-content: center;
  margin-top: 21px;
}
</style>