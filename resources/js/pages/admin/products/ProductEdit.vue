<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import AdminEntityForm from "../../../components/AdminEntityForm.vue";

import productAdminService from "../../../services/admin/productAdminService";
import uploadAdminService from "../../../services/admin/uploadAdminService";
import brandAdminService from "../../../services/admin/brandAdminService";
import categoryAdminService from "../../../services/admin/categoryAdminService";
import { useAlert } from "../../../composables/useAlert";

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

function makeProductSkuFromName(name) { return skuify(name) || ""; }

function makeVariantSku(productSku, color, size) {
  return [skuify(productSku), skuify(color), skuify(size)].filter(Boolean).join("-");
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
const route  = useRoute();
const notify = useAlert();
const id     = computed(() => route.params.id);

const ALL_COLORS = ["White", "Black", "Red", "Blue", "Green", "Yellow", "Grey", "Brown", "Cyan"];
const ALL_SIZES  = ["36", "37", "38", "39", "40", "41", "42", "43", "44", "45"];

const COLOR_META = {
  White: "#ffffff", Black: "#1a1a1a", Red: "#ef4444", Blue: "#3b82f6",
  Green: "#22c55e", Yellow: "#eab308", Grey: "#94a3b8", Brown: "#92400e", Cyan: "#06b6d4",
};

const matrixChecked = ref(new Set());
const variantCache  = ref(new Map());

/** ================= BULK FILL ================= */
const bulk = ref({ price: null, sale_price: null, stock: null });

function applyBulk() {
  const { price, sale_price, stock } = bulk.value;
  const hasPrice     = price     !== null && price     !== "" && Number.isFinite(Number(price));
  const hasSalePrice = sale_price !== null && sale_price !== "" && Number.isFinite(Number(sale_price));
  const hasStock     = stock     !== null && stock     !== "" && Number.isFinite(Number(stock));

  if (!hasPrice && !hasSalePrice && !hasStock) {
    notify.warning("Vui lòng nhập ít nhất 1 giá trị muốn áp dụng.", { title: "Bulk fill", duration: 2000 });
    return;
  }

  values.value = {
    ...values.value,
    variants: values.value.variants.map((v) => ({
      ...v,
      ...(hasPrice     ? { price:      Number(price) }      : {}),
      ...(hasSalePrice ? { sale_price: Number(sale_price) } : {}),
      ...(hasStock     ? { stock:      Number(stock) }      : {}),
    })),
  };

  const applied = [
    hasPrice     && `Giá: ${Number(price).toLocaleString("vi-VN")}đ`,
    hasSalePrice && `Giá KM: ${Number(sale_price).toLocaleString("vi-VN")}đ`,
    hasStock     && `Tồn kho: ${stock}`,
  ].filter(Boolean).join(" · ");

  notify.success(`Đã áp dụng cho ${values.value.variants.length} biến thể — ${applied}`, {
    title: "Bulk fill thành công", duration: 2500,
  });
}

function matrixKey(color, size) { return `${color}__${size}`; }
function isChecked(color, size) { return matrixChecked.value.has(matrixKey(color, size)); }

function toggleCell(color, size) {
  const key  = matrixKey(color, size);
  const next = new Set(matrixChecked.value);
  next.has(key) ? next.delete(key) : next.add(key);
  matrixChecked.value = next;
}

function toggleRow(color) {
  const allOn = ALL_SIZES.every((s) => matrixChecked.value.has(matrixKey(color, s)));
  const next  = new Set(matrixChecked.value);
  ALL_SIZES.forEach((s) => allOn ? next.delete(matrixKey(color, s)) : next.add(matrixKey(color, s)));
  matrixChecked.value = next;
}

function toggleCol(size) {
  const allOn = ALL_COLORS.every((c) => matrixChecked.value.has(matrixKey(c, size)));
  const next  = new Set(matrixChecked.value);
  ALL_COLORS.forEach((c) => allOn ? next.delete(matrixKey(c, size)) : next.add(matrixKey(c, size)));
  matrixChecked.value = next;
}

function rowState(color) {
  const count = ALL_SIZES.filter((s) => matrixChecked.value.has(matrixKey(color, s))).length;
  return { checked: count === ALL_SIZES.length, indeterminate: count > 0 && count < ALL_SIZES.length };
}

function colState(size) {
  const count = ALL_COLORS.filter((c) => matrixChecked.value.has(matrixKey(c, size))).length;
  return { checked: count === ALL_COLORS.length, indeterminate: count > 0 && count < ALL_COLORS.length };
}

/** ================= touched flags ================= */
const touchedSlug       = ref(false);
const touchedProductSku = ref(false);
const touchedVariantSku = ref({});

const lastAutoSlug       = ref("");
const lastAutoProductSku = ref("");
const lastAutoVariantSku = ref({});

/** ================= form values ================= */
const values = ref({
  brand_id: null, category_ids: [],
  name: "", slug: "", sku: "", short_description: "", description: "",
  thumbnail: "", status: true, is_featured: false,
  variants: [],
});

/** ================= sync matrix → variants ================= */
watch(
  () => matrixChecked.value,
  (checked) => {
    const desired = [];
    for (const color of ALL_COLORS)
      for (const size of ALL_SIZES)
        if (checked.has(matrixKey(color, size))) desired.push({ color, size });

    const nextVariants = desired.map(({ color, size }) => {
      const key      = matrixKey(color, size);
      const existing = values.value.variants.find((v) => v.color === color && v.size === size);
      if (existing) return existing;
      if (variantCache.value.has(key)) return { ...variantCache.value.get(key), _key: makeKey() };
      return { _key: makeKey(), id: null, color, size, sku: "", price: 0, sale_price: null, stock: 0, is_active: true };
    });

    values.value.variants.forEach((v) => {
      const key = matrixKey(v.color, v.size);
      if (!checked.has(key)) variantCache.value.set(key, { ...v });
    });

    values.value = { ...values.value, variants: nextVariants };
  },
  { deep: true }
);

/** ensure maps exist */
watch(
  () => values.value.variants,
  (arr) => {
    (arr || []).forEach((it) => {
      if (!it._key) it._key = makeKey();
      if (lastAutoVariantSku.value[it._key] === undefined)
        lastAutoVariantSku.value = { ...lastAutoVariantSku.value, [it._key]: "" };
      if (touchedVariantSku.value[it._key] === undefined)
        touchedVariantSku.value = { ...touchedVariantSku.value, [it._key]: false };
    });
  },
  { deep: true, immediate: true }
);

/** auto slug + product sku by name */
watch(
  () => values.value.name,
  (name) => {
    const autoSlug = slugify(name);
    const curSlug  = String(values.value.slug || "").trim();
    if (!touchedSlug.value && (!curSlug || curSlug === lastAutoSlug.value)) {
      values.value.slug = autoSlug; lastAutoSlug.value = autoSlug;
    }
    const autoSku = makeProductSkuFromName(name);
    const curSku  = String(values.value.sku || "").trim();
    if (!touchedProductSku.value && (!curSku || curSku === lastAutoProductSku.value)) {
      values.value.sku = autoSku; lastAutoProductSku.value = autoSku;
    }
  },
  { immediate: true }
);

watch(() => values.value.slug, (val) => {
  const cur = String(val || "").trim();
  if (!cur) { touchedSlug.value = false; return; }
  if (lastAutoSlug.value && cur !== lastAutoSlug.value) touchedSlug.value = true;
});

watch(() => values.value.sku, (val) => {
  const cur = String(val || "").trim();
  if (!cur) { touchedProductSku.value = false; return; }
  if (lastAutoProductSku.value && cur !== lastAutoProductSku.value) touchedProductSku.value = true;
});

/** auto variant sku */
watch(
  () => [values.value.sku, values.value.variants],
  () => {
    const productSku = values.value.sku;
    (values.value.variants || []).forEach((it) => {
      const key      = it._key;
      const auto     = makeVariantSku(productSku, it.color, it.size);
      const cur      = String(it.sku || "").trim();
      const lastAuto = lastAutoVariantSku.value[key] ?? "";
      const touched  = !!touchedVariantSku.value[key];
      if (!touched && (!cur || cur === lastAuto)) {
        it.sku = auto;
        lastAutoVariantSku.value = { ...lastAutoVariantSku.value, [key]: auto };
      }
    });
  },
  { deep: true, immediate: true }
);

watch(
  () => values.value.variants.map((v) => ({ key: v._key, sku: v.sku })),
  (rows) => {
    rows.forEach((r) => {
      const key      = r.key;
      const cur      = String(r.sku || "").trim();
      const lastAuto = lastAutoVariantSku.value[key] ?? "";
      if (!cur) { touchedVariantSku.value = { ...touchedVariantSku.value, [key]: false }; return; }
      if (lastAuto && cur !== lastAuto) touchedVariantSku.value = { ...touchedVariantSku.value, [key]: true };
    });
  },
  { deep: true }
);

/** ================= COLOR IMAGES ================= */
const colorImages      = ref({});
const colorImagesCache = ref({});

const activeColors = computed(() =>
  ALL_COLORS.filter((c) => ALL_SIZES.some((s) => matrixChecked.value.has(matrixKey(c, s))))
);

watch(
  activeColors,
  (colors) => {
    const next = { ...colorImages.value };
    colors.forEach((c) => {
      if (!next[c])
        next[c] = colorImagesCache.value[c] ? [...colorImagesCache.value[c]] : [{ url: "", sort_order: 0 }];
    });
    Object.keys(next).forEach((c) => {
      if (!colors.includes(c)) { colorImagesCache.value[c] = next[c]; delete next[c]; }
    });
    colorImages.value = next;
  },
  { immediate: true }
);

function addColorImage(color) {
  const imgs      = colorImages.value[color] || [];
  const nextOrder = imgs.length ? Math.max(...imgs.map((x) => Number(x.sort_order || 0))) + 1 : 0;
  colorImages.value = { ...colorImages.value, [color]: [...imgs, { url: "", sort_order: nextOrder }] };
  notify.info(`Đã thêm ô ảnh cho màu ${color}.`, { title: "Thêm ảnh", duration: 1500 });
}

function removeColorImage(color, imgIndex) {
  const imgs = [...(colorImages.value[color] || [])];
  if (imgs.length <= 1) {
    colorImages.value = { ...colorImages.value, [color]: [{ url: "", sort_order: 0 }] };
  } else {
    imgs.splice(imgIndex, 1);
    colorImages.value = { ...colorImages.value, [color]: imgs };
  }
  notify.info(`Đã xóa ảnh của màu ${color}.`, { title: "Đã xóa", duration: 1500 });
}

const uploadingColorImage = ref({});

async function onPickColorImageFile(e, color, imgIndex) {
  const file = e.target.files?.[0];
  if (!file) return;
  const key = `${color}-${imgIndex}`;
  uploadingColorImage.value = { ...uploadingColorImage.value, [key]: true };
  try {
    const resp      = await uploadAdminService.uploadProductImage(file);
    const pathOrUrl = extractPathOrUrl(resp);
    if (!pathOrUrl) throw new Error("Upload API did not return path/url");
    const imgs = [...(colorImages.value[color] || [])];
    imgs[imgIndex] = { ...(imgs[imgIndex] || { url: "", sort_order: 0 }), url: pathOrUrl };
    colorImages.value = { ...colorImages.value, [color]: imgs };
    notify.success(`Upload ảnh màu ${color} thành công.`, { title: "Upload thành công", duration: 2200 });
  } catch (err) {
    notify.error(err?.response?.data?.message || err?.message || "Upload ảnh thất bại", { title: "Upload thất bại", duration: 3500 });
  } finally {
    uploadingColorImage.value = { ...uploadingColorImage.value, [key]: false };
    e.target.value = "";
  }
}

/** ================= options ================= */
const brandOptions    = ref([]);
const categoryOptions = ref([]);

async function fetchBrands() {
  try {
    const { data } = await brandAdminService.list({ page: 1, per_page: 200, status: 1 });
    brandOptions.value = data?.data || [];
  } catch (e) {
    notify.error(e?.response?.data?.message || e?.message || "Không tải được danh sách thương hiệu.", { title: "Lỗi tải dữ liệu", duration: 3500 });
    throw e;
  }
}

async function fetchCategories() {
  try {
    const { data } = await categoryAdminService.list({ page: 1, per_page: 500, status: 1 });
    categoryOptions.value = data?.data || [];
  } catch (e) {
    notify.error(e?.response?.data?.message || e?.message || "Không tải được danh sách danh mục.", { title: "Lỗi tải dữ liệu", duration: 3500 });
    throw e;
  }
}

async function uploadThumbnailWithAlert(file) {
  try {
    return await uploadAdminService.uploadProductImage(file);
  } catch (e) {
    notify.error(e?.response?.data?.message || e?.message || "Upload ảnh đại diện thất bại.", { title: "Upload thất bại", duration: 3500 });
    throw e;
  }
}

/** ================= schema ================= */
const schema = computed(() => [
  { group: "general", groupTitle: "Thông tin cơ bản", name: "name", label: "Tên sản phẩm", type: "text", required: true, placeholder: "Nhập tên sản phẩm" },
  { group: "general", name: "slug", label: "Slug", type: "text", required: true, placeholder: "Nhập slug (để trống sẽ tự tạo)", help: "Xóa trống để hệ thống auto lại theo tên." },
  { group: "general", name: "sku", label: "SKU (Auto từ tên - có thể sửa)", type: "text", placeholder: "VD: NIKE-AIR-FORCE-1", help: "Nếu sửa tay thì hệ thống không tự đổi nữa (xóa trống để auto lại)." },
  { group: "general", name: "short_description", label: "Mô tả ngắn", type: "text", full: true, placeholder: "Mô tả ngắn..." },
  { group: "general", name: "description", label: "Mô tả chi tiết", type: "textarea", full: true, rows: 6, placeholder: "Nhập mô tả..." },
  { group: "settings", groupTitle: "Cài đặt", name: "brand_id", label: "Thương hiệu", type: "select", required: true, options: brandOptions.value.map((b) => ({ label: b.name, value: b.id })) },
  { group: "settings", name: "category_ids", label: "Danh mục", type: "checkboxes", required: true, checkboxColumns: 2, options: categoryOptions.value.map((c) => ({ label: c.name, value: c.id })) },
  { group: "settings", name: "status", label: "Trạng thái", type: "switch", onText: "Hoạt động", offText: "Tạm tắt" },
  { group: "settings", name: "is_featured", label: "Nổi bật", type: "switch", onText: "Bật", offText: "Tắt" },
  { group: "media", groupTitle: "Media", name: "thumbnail", label: "Hình ảnh", type: "file", full: true, upload: uploadThumbnailWithAlert, uploadHint: "PNG, JPG, WEBP (Max 5MB)", placeholder: "path/url..." },
]);

/** ================= validate / payload ================= */
function validate(v) {
  const errs = {};
  if (!v.brand_id) errs.brand_id = "Vui lòng chọn thương hiệu";
  if (!v.category_ids || v.category_ids.length === 0) errs.category_ids = "Vui lòng chọn ít nhất 1 danh mục";
  if (!String(v.name || "").trim()) errs.name = "Tên sản phẩm là bắt buộc";
  if (!String(v.slug || "").trim()) errs.slug = "Slug là bắt buộc";
  if (!Array.isArray(v.variants) || v.variants.length === 0)
    return { ...errs, _form: "Cần chọn ít nhất 1 biến thể từ bảng màu × size" };
  const seen = new Set();
  for (let i = 0; i < v.variants.length; i++) {
    const it  = v.variants[i];
    const key = `${String(it.color).trim().toLowerCase()}__${String(it.size).trim().toLowerCase()}`;
    if (seen.has(key)) return { ...errs, _form: `Trùng biến thể: ${it.color} - ${it.size}` };
    seen.add(key);
    if (normalizeNumber(it.price) === null || Number(it.price) < 0) return { ...errs, _form: `Biến thể #${i + 1}: giá không hợp lệ` };
    if (normalizeNumber(it.stock) === null || Number(it.stock) < 0) return { ...errs, _form: `Biến thể #${i + 1}: tồn kho không hợp lệ` };
    const sp = normalizeNumber(it.sale_price);
    if (sp !== null && sp < 0) return { ...errs, _form: `Biến thể #${i + 1}: giá KM không hợp lệ` };
  }
  return errs;
}

function buildPayload(v) {
  return {
    brand_id:          v.brand_id ? Number(v.brand_id) : null,
    category_ids:      (v.category_ids || []).map((x) => Number(x)),
    name:              String(v.name || "").trim(),
    slug:              String(v.slug || "").trim(),
    sku:               String(v.sku || "").trim() || null,
    short_description: String(v.short_description || "").trim() || null,
    description:       v.description || null,
    thumbnail:         String(v.thumbnail || "").trim() || null,
    status:            v.status ? 1 : 0,
    is_featured:       !!v.is_featured,
    variants: (v.variants || []).map((it) => ({
      id:         it.id ?? null,
      color:      String(it.color).trim(),
      size:       String(it.size).trim(),
      sku:        String(it.sku || "").trim() || null,
      price:      Number(it.price),
      sale_price: normalizeNumber(it.sale_price),
      stock:      Number(it.stock),
      is_active:  !!it.is_active,
      // ảnh lấy từ colorImages theo màu — dùng chung cho cả màu
      images: (colorImages.value[it.color] || [])
        .filter((img) => (img.url || "").trim() !== "")
        .map((img) => ({ url: (img.url || "").trim(), sort_order: Number(img.sort_order || 0) })),
    })),
  };
}

/** ================= load product (edit-specific) ================= */
async function loadProduct(productId) {
  try {
    const { data } = await productAdminService.show(productId);
    const p = data?.data ?? data;

    // --- khôi phục matrix từ variants đã lưu ---
    const nextChecked = new Set();
    const nextColorImages = {};

    const loadedVariants = Array.isArray(p?.variants) && p.variants.length
      ? p.variants.map((it) => {
          const color = it.color ?? "";
          const size  = it.size  ?? "";

          // tick ô matrix
          if (color && size && ALL_COLORS.includes(color) && ALL_SIZES.includes(size)) {
            nextChecked.add(matrixKey(color, size));
          }

          // gom ảnh theo màu (lấy từ variant đầu tiên của mỗi màu)
          if (color && !nextColorImages[color]) {
            nextColorImages[color] =
              Array.isArray(it.images) && it.images.length
                ? it.images.map((img) => ({ url: img.url ?? img.path ?? "", sort_order: Number(img.sort_order ?? 0) }))
                : [{ url: "", sort_order: 0 }];
          }

          return {
            id:         it.id ?? null,
            _key:       makeKey(),
            color,
            size,
            sku:        it.sku  ?? "",
            price:      Number(it.price  ?? 0),
            sale_price: it.sale_price ?? null,
            stock:      Number(it.stock ?? 0),
            is_active:  !!it.is_active,
          };
        })
      : [];

    // apply matrix + ảnh trước khi set values để watch không ghi đè
    matrixChecked.value = nextChecked;
    colorImages.value   = nextColorImages;

    const loaded = {
      brand_id:          p?.brand_id ?? null,
      category_ids:      (p?.categories || p?.category_ids || []).map((x) => Number(x.id ?? x)),
      name:              p?.name              ?? "",
      slug:              p?.slug              ?? "",
      sku:               p?.sku               ?? "",
      short_description: p?.short_description ?? "",
      description:       p?.description       ?? "",
      thumbnail:         p?.thumbnail         ?? "",
      status:            Number(p?.status ?? 1) === 1,
      is_featured:       !!p?.is_featured,
      variants:          loadedVariants,
    };

    // reset touched flags — dữ liệu đã có sẵn, không auto-override
    touchedSlug.value       = true;
    touchedProductSku.value = true;
    lastAutoSlug.value      = String(loaded.slug || "").trim();
    lastAutoProductSku.value = String(loaded.sku  || "").trim();

    const vAuto    = {};
    const vTouched = {};
    (loaded.variants || []).forEach((it) => {
      vAuto[it._key]    = String(it.sku || "").trim();
      vTouched[it._key] = true; // đã có SKU từ server, không auto-override
    });
    lastAutoVariantSku.value = vAuto;
    touchedVariantSku.value  = vTouched;

    return loaded;
  } catch (e) {
    notify.error(
      e?.response?.data?.message || e?.message || "Không tải được thông tin sản phẩm.",
      { title: "Lỗi tải dữ liệu", duration: 3500 }
    );
    throw e;
  }
}

/** ================= submit ================= */
async function onSubmit(payload) {
  try {
    await productAdminService.update(id.value, payload);
    notify.success("Cập nhật sản phẩm thành công.", { title: "Lưu thành công", duration: 2500 });
    router.push("/admin/products");
  } catch (e) {
    notify.error(e?.response?.data?.message || e?.message || "Cập nhật sản phẩm thất bại.", { title: "Lưu thất bại", duration: 3500 });
    throw e;
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

      <!-- ==================== MATRIX PICKER ==================== -->
      <div class="card">
        <div class="card-head">
          <div class="card-head-left">
            <div class="card-head-icon">🎨</div>
            <div>
              <div class="card-title">Chọn biến thể (Màu × Size)</div>
              <div class="card-subtitle">
                Tick ô giao của màu và size — bảng variant bên dưới sẽ tự cập nhật.
                <span class="badge" v-if="matrixChecked.size">{{ matrixChecked.size }} tổ hợp đã chọn</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card-body">
          <div class="matrix-wrap">
            <table class="matrix">
              <thead>
                <tr>
                  <th class="matrix-corner">
                    <span class="axis-label color-axis">Màu ↓</span>
                    <span class="axis-label size-axis">Size →</span>
                  </th>
                  <th
                    v-for="size in ALL_SIZES" :key="size"
                    class="matrix-col-head"
                    @click="toggleCol(size)"
                    :title="`Toggle tất cả màu cho size ${size}`"
                  >
                    <div class="col-head-inner">
                      <input type="checkbox"
                        :checked="colState(size).checked"
                        :indeterminate="colState(size).indeterminate"
                        @click.stop @change="toggleCol(size)"
                        class="matrix-cb"
                      />
                      <span class="size-label">{{ size }}</span>
                    </div>
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="color in ALL_COLORS" :key="color">
                  <td class="matrix-row-head" @click="toggleRow(color)" :title="`Toggle tất cả size cho màu ${color}`">
                    <div class="row-head-inner">
                      <input type="checkbox"
                        :checked="rowState(color).checked"
                        :indeterminate="rowState(color).indeterminate"
                        @click.stop @change="toggleRow(color)"
                        class="matrix-cb"
                      />
                      <span class="color-swatch" :style="{ background: COLOR_META[color] }"></span>
                      <span class="color-name">{{ color }}</span>
                    </div>
                  </td>

                  <td
                    v-for="size in ALL_SIZES" :key="size"
                    class="matrix-cell" :class="{ checked: isChecked(color, size) }"
                    @click="toggleCell(color, size)" :title="`${color} / ${size}`"
                  >
                    <div class="cell-inner">
                      <span class="check-icon" v-if="isChecked(color, size)">✓</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="matrix-empty" v-if="matrixChecked.size === 0">
            Chưa chọn tổ hợp nào. Tick vào bảng phía trên để thêm biến thể.
          </div>
        </div>
      </div>

      <!-- ==================== VARIANT TABLE ==================== -->
      <div class="card" v-if="v.variants && v.variants.length > 0">
        <div class="card-head">
          <div class="card-head-left">
            <div class="card-head-icon">📦</div>
            <div>
              <div class="card-title">Chi tiết biến thể</div>
              <div class="card-subtitle">Điền giá và tồn kho cho từng biến thể đã chọn.</div>
            </div>
          </div>
          <div class="variant-count-badge">{{ v.variants.length }} biến thể</div>
        </div>

        <div class="card-body">

          <!-- BULK FILL BAR -->
          <div class="bulk-bar">
            <div class="bulk-label">
              <span class="material-symbols-outlined bulk-icon">bolt</span>
              Điền nhanh cho tất cả
            </div>
            <div class="bulk-fields">
              <div class="bulk-field">
                <label class="bulk-field-label">Giá</label>
                <input v-model.number="bulk.price" type="number" min="0" class="tcontrol bulk-input" placeholder="VD: 2.500.000" />
              </div>
              <div class="bulk-field">
                <label class="bulk-field-label">Giá KM</label>
                <input v-model.number="bulk.sale_price" type="number" min="0" class="tcontrol bulk-input" placeholder="Để trống = bỏ qua" />
              </div>
              <div class="bulk-field">
                <label class="bulk-field-label">Tồn kho</label>
                <input v-model.number="bulk.stock" type="number" min="0" class="tcontrol bulk-input" placeholder="VD: 10" />
              </div>
              <button class="btn-apply" type="button" @click="applyBulk">
                <span class="material-symbols-outlined">done_all</span>
                Áp dụng tất cả
              </button>
            </div>
          </div>

          <div class="table-wrap">
            <table class="variants">
              <thead>
                <tr>
                  <th>Màu</th>
                  <th>Size</th>
                  <th>SKU</th>
                  <th class="num">Giá *</th>
                  <th class="num">Giá KM</th>
                  <th class="num">Tồn kho *</th>
                  <th class="center">Kích hoạt</th>
                  <th class="center">Bỏ chọn</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(it, i) in v.variants" :key="it._key || i">
                  <td>
                    <div class="color-cell">
                      <span class="color-swatch sm" :style="{ background: COLOR_META[it.color] }"></span>
                      <span class="color-text">{{ it.color }}</span>
                    </div>
                  </td>
                  <td><span class="size-chip">{{ it.size }}</span></td>
                  <td>
                    <input v-model="it.sku" class="tcontrol" placeholder="AUTO" />
                    <div class="sku-hint" v-if="touchedVariantSku[it._key]">đã sửa tay — xóa trống để auto lại</div>
                  </td>
                  <td class="num"><input v-model.number="it.price" type="number" min="0" class="tcontrol" /></td>
                  <td class="num"><input v-model.number="it.sale_price" type="number" min="0" class="tcontrol" placeholder="—" /></td>
                  <td class="num"><input v-model.number="it.stock" type="number" min="0" class="tcontrol" /></td>
                  <td class="center"><input v-model="it.is_active" type="checkbox" /></td>
                  <td class="center">
                    <button class="icon-btn danger" type="button" title="Bỏ chọn biến thể này" @click="toggleCell(it.color, it.size)">
                      <span class="material-symbols-outlined">close</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="product-sku-hint" v-if="touchedProductSku">
            SKU sản phẩm đã sửa tay — xóa trống để tự tạo lại theo tên.
          </div>
        </div>
      </div>

      <!-- ==================== COLOR IMAGES ==================== -->
      <div class="card" v-if="activeColors.length > 0">
        <div class="card-head">
          <div class="card-head-left">
            <div class="card-head-icon">🖼️</div>
            <div>
              <div class="card-title">Ảnh theo màu</div>
              <div class="card-subtitle">
                Mỗi màu dùng chung 1 bộ ảnh cho tất cả size.
                <span class="badge">{{ activeColors.length }} màu</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card-body color-imgs-body">
          <div class="color-imgs-block" v-for="color in activeColors" :key="color">
            <div class="color-imgs-header">
              <div class="color-imgs-title">
                <span class="color-swatch lg" :style="{ background: COLOR_META[color] }"></span>
                <span class="color-name-lg">{{ color }}</span>
                <span class="color-size-chips">
                  <span class="size-chip xs" v-for="size in ALL_SIZES.filter(s => isChecked(color, s))" :key="size">{{ size }}</span>
                </span>
              </div>
              <button class="btn-outline sm" type="button" @click="addColorImage(color)">
                <span class="material-symbols-outlined">add_photo_alternate</span>
                Thêm ảnh
              </button>
            </div>

            <div class="imgs-grid">
              <div class="img-row" v-for="(img, j) in (colorImages[color] || [])" :key="j">
                <div class="img-preview" v-if="img.url">
                  <img :src="img.url.startsWith('http') ? img.url : '/storage/' + img.url" />
                </div>
                <div class="img-preview placeholder" v-else>Preview</div>

                <div class="fieldx">
                  <div class="labelx">Upload</div>
                  <label class="filebtn">
                    <span class="material-symbols-outlined">upload</span>
                    Chọn ảnh
                    <input class="file-hidden" type="file" accept="image/*" @change="(e) => onPickColorImageFile(e, color, j)" />
                  </label>
                  <div class="hintx" v-if="uploadingColorImage[`${color}-${j}`]">Uploading...</div>
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
                  <button class="icon-btn danger" type="button" title="Xóa ảnh" @click="removeColorImage(color, j)">
                    <span class="material-symbols-outlined">delete</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </template>
  </AdminEntityForm>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

/* ===== CARD ===== */
.card {
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 16px;
  box-shadow: 0 1px 4px rgba(15,23,42,.04);
  overflow: hidden;
  margin-top: 16px;
  font-family: 'DM Sans', sans-serif;
  transition: box-shadow .2s;
}
.card:hover { box-shadow: 0 4px 16px rgba(15,23,42,.07); }

.card-head {
  padding: 16px 20px;
  border-bottom: 1px solid #f1f4fa;
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  background: #fafbfd;
}
.card-head-left { display: flex; align-items: center; gap: 12px; }
.card-head-icon {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border-radius: 10px;
  display: grid; place-items: center;
  font-size: 16px; flex-shrink: 0;
}
.card-title { font-size: 14px; font-weight: 700; color: #0d1117; }
.card-subtitle { margin-top: 2px; font-size: 12px; color: #94a3b8; font-weight: 500; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.card-body { padding: 20px; }

.badge {
  display: inline-flex; align-items: center;
  background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;
  border-radius: 20px; padding: 1px 9px; font-size: 11px; font-weight: 700;
}
.variant-count-badge {
  background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;
  border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 700;
}

/* ===== MATRIX ===== */
.matrix-wrap { overflow: auto; border: 1.5px solid #e8ecf4; border-radius: 12px; }
.matrix { border-collapse: collapse; width: 100%; min-width: 720px; }

.matrix-corner {
  background: #f5f7ff;
  border-bottom: 1.5px solid #e8ecf4; border-right: 1.5px solid #e8ecf4;
  padding: 10px 14px; position: relative; min-width: 110px;
}
.axis-label { position: absolute; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; }
.color-axis { top: 10px; left: 10px; }
.size-axis  { bottom: 10px; right: 10px; }

.matrix-col-head {
  background: #f5f7ff;
  border-bottom: 1.5px solid #e8ecf4; border-right: 1px solid #eef0f6;
  padding: 8px 6px; cursor: pointer; user-select: none; transition: background .15s;
}
.matrix-col-head:hover { background: #eff6ff; }
.col-head-inner { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.size-label { font-size: 12px; font-weight: 700; color: #374151; }

.matrix-row-head {
  background: #fafbfd;
  border-right: 1.5px solid #e8ecf4; border-top: 1px solid #eef0f6;
  padding: 6px 12px; cursor: pointer; user-select: none; transition: background .15s;
}
.matrix-row-head:hover { background: #eff6ff; }
.row-head-inner { display: flex; align-items: center; gap: 8px; }

.matrix-cell {
  border-top: 1px solid #eef0f6; border-right: 1px solid #eef0f6;
  width: 48px; height: 44px; text-align: center;
  cursor: pointer; transition: background .12s; user-select: none;
}
.matrix-cell:hover { background: #eff6ff; }
.matrix-cell.checked { background: #eff6ff; }
.cell-inner { width: 100%; height: 100%; display: grid; place-items: center; }
.check-icon {
  width: 22px; height: 22px; background: #3b82f6; color: #fff;
  border-radius: 6px; display: grid; place-items: center;
  font-size: 13px; font-weight: 700; animation: pop .15s ease;
}
@keyframes pop { from { transform: scale(.6); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.matrix-cb { accent-color: #3b82f6; width: 14px; height: 14px; cursor: pointer; flex-shrink: 0; }
.color-swatch {
  width: 16px; height: 16px; border-radius: 50%;
  border: 1.5px solid rgba(0,0,0,.12); flex-shrink: 0; display: inline-block;
}
.color-swatch.sm { width: 12px; height: 12px; }
.color-name { font-size: 12.5px; font-weight: 600; color: #374151; }

.matrix-empty {
  margin-top: 14px; text-align: center; color: #94a3b8; font-size: 13px; font-weight: 600;
  padding: 12px; border: 1.5px dashed #e2e8f0; border-radius: 10px;
}

/* ===== VARIANT TABLE ===== */
.btn-outline {
  border: 1.5px solid #e2e8f0; background: #fff; padding: 8px 12px;
  border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer;
  color: #374151; display: inline-flex; align-items: center; gap: 8px;
  transition: .15s; font-family: 'DM Sans', sans-serif;
}
.btn-outline:hover { background: #f0f7ff; border-color: #93c5fd; color: #2563eb; }
.btn-outline.sm { padding: 6px 10px; font-size: 12px; border-radius: 8px; }

.table-wrap { overflow: auto; border: 1.5px solid #e8ecf4; border-radius: 12px; }
.variants { width: 100%; border-collapse: collapse; min-width: 900px; }
.variants thead th {
  position: sticky; top: 0; background: #f5f7ff;
  border-bottom: 1.5px solid #e8ecf4; padding: 11px 12px; text-align: left;
  font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .06em; font-weight: 700;
}
.variants tr:hover td { background: #fafbff; }
.variants td { border-top: 1px solid #f1f4fa; padding: 10px 12px; vertical-align: middle; }
.num { text-align: right; }
.center { text-align: center; }

.color-cell { display: flex; align-items: center; gap: 7px; }
.color-text { font-size: 13px; font-weight: 600; color: #374151; }
.size-chip {
  display: inline-flex; align-items: center; justify-content: center;
  background: #f1f5f9; color: #374151; border: 1px solid #e2e8f0;
  border-radius: 6px; padding: 2px 10px; font-size: 12px; font-weight: 700;
}

.tcontrol {
  width: 100%; height: 40px; border: 1.5px solid #e2e8f0; border-radius: 8px;
  padding: 0 10px; outline: none; font-size: 13px; font-family: 'DM Sans', sans-serif;
  background: #fafbfd; transition: .15s; color: #0f172a;
}
.tcontrol:hover { border-color: #c7d4e8; background: #fff; }
.tcontrol:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); background: #fff; }

input[type="checkbox"] { accent-color: #3b82f6; width: 15px; height: 15px; cursor: pointer; }

.icon-btn {
  width: 40px; height: 40px; border-radius: 12px; border: 1.5px solid #e2e8f0;
  background: #fff; display: inline-flex; align-items: center; justify-content: center;
  cursor: pointer; transition: .15s;
}
.icon-btn .material-symbols-outlined { font-size: 20px; }
.icon-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
.icon-btn.danger { border-color: rgba(239,68,68,.25); background: rgba(239,68,68,.06); color: #dc2626; }
.icon-btn.danger:hover { background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.45); }

.sku-hint { margin-top: 4px; font-size: 11px; color: #94a3b8; font-weight: 600; }
.product-sku-hint { margin-top: 10px; font-size: 12px; color: #64748b; font-weight: 600; }

/* ===== BULK FILL BAR ===== */
.bulk-bar {
  display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap;
  background: linear-gradient(135deg, #fffbeb, #fef3c7);
  border: 1.5px solid #fde68a; border-radius: 12px;
  padding: 14px 16px; margin-bottom: 16px;
}
.bulk-label { display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 700; color: #92400e; white-space: nowrap; padding-bottom: 2px; }
.bulk-icon { font-size: 18px; color: #f59e0b; }
.bulk-fields { display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; flex: 1; }
.bulk-field { display: flex; flex-direction: column; gap: 5px; min-width: 140px; flex: 1; }
.bulk-field-label { font-size: 11px; font-weight: 700; color: #78350f; text-transform: uppercase; letter-spacing: .04em; }
.bulk-input { height: 38px !important; background: #fff !important; border-color: #fcd34d !important; }
.bulk-input:focus { border-color: #f59e0b !important; box-shadow: 0 0 0 3px rgba(245,158,11,.15) !important; }
.btn-apply {
  height: 38px; background: linear-gradient(135deg, #f59e0b, #d97706);
  border: none; border-radius: 10px; padding: 0 16px; font-size: 13px; font-weight: 700;
  color: #fff; cursor: pointer; display: inline-flex; align-items: center; gap: 7px;
  white-space: nowrap; font-family: 'DM Sans', sans-serif; transition: .15s;
  box-shadow: 0 2px 6px rgba(217,119,6,.35); flex-shrink: 0;
}
.btn-apply:hover { background: linear-gradient(135deg, #d97706, #b45309); box-shadow: 0 4px 12px rgba(217,119,6,.45); }
.btn-apply .material-symbols-outlined { font-size: 18px; }

/* ===== COLOR IMAGES CARD ===== */
.color-imgs-body { display: flex; flex-direction: column; gap: 24px; }
.color-imgs-block { border: 1.5px solid #e8ecf4; border-radius: 12px; overflow: hidden; }
.color-imgs-header {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  padding: 12px 16px; background: #fafbfd; border-bottom: 1px solid #f1f4fa;
}
.color-imgs-title { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.color-swatch.lg {
  width: 22px; height: 22px; border-radius: 50%;
  border: 2px solid rgba(0,0,0,.12); flex-shrink: 0; display: inline-block;
}
.color-name-lg { font-size: 13.5px; font-weight: 700; color: #0d1117; }
.color-size-chips { display: flex; flex-wrap: wrap; gap: 4px; }
.size-chip.xs {
  padding: 1px 7px; font-size: 11px; border-radius: 5px;
  background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;
}
.imgs-grid { display: flex; flex-direction: column; gap: 10px; padding: 14px; }

.img-row {
  display: grid; grid-template-columns: 90px 220px 1fr 120px 56px;
  gap: 12px; align-items: start; padding: 12px;
  border: 1.5px solid #e8ecf4; border-radius: 12px; background: #fff; transition: .15s;
}
.img-row:hover { border-color: #c7d4e8; }
.img-preview {
  width: 90px; height: 90px; border-radius: 12px; overflow: hidden;
  border: 1.5px solid #e8ecf4; display: grid; place-items: center;
  background: #f5f7ff; color: #94a3b8; font-weight: 600; font-size: 11px;
}
.img-preview img { width: 100%; height: 100%; object-fit: cover; }
.fieldx { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
.labelx { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
.filebtn {
  height: 40px; border: 1.5px dashed #bfdbfe; background: #f0f7ff;
  border-radius: 8px; padding: 0 12px; font-size: 12px; font-weight: 700;
  color: #2563eb; cursor: pointer; display: inline-flex; align-items: center;
  gap: 8px; user-select: none; white-space: nowrap;
}
.filebtn:hover { border-color: #3b82f6; }
.filebtn .material-symbols-outlined { font-size: 18px; }
.file-hidden { display: none; }
.sortx .tcontrol { text-align: right; }
.hintx { font-size: 11px; color: #3b82f6; font-weight: 600; }
.img-actions { display: flex; justify-content: center; margin-top: 21px; }
</style>
