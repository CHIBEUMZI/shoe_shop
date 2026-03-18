<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  watch,
  toRaw,
  useSlots,
} from "vue";
import { useRouter } from "vue-router";

function clonePlain(input) {
  const raw = toRaw(input);
  try {
    return structuredClone(raw);
  } catch (e) {
    return JSON.parse(JSON.stringify(raw ?? {}));
  }
}

const props = defineProps({
  mode: { type: String, required: true }, // "create" | "edit"
  entityId: { type: [String, Number], default: null },

  title: { type: String, required: true },
  subtitle: { type: String, default: "" },

  values: { type: Object, required: true }, // v-model:values
  schema: { type: Array, default: () => [] },

  load: { type: Function, default: null }, // (id) => object
  onSubmit: { type: Function, required: true }, // (payload, ctx) => Promise
  onCancel: { type: Function, default: null },

  transformBeforeSubmit: { type: Function, default: null }, // (values) => payload
  validate: { type: Function, default: null }, // (values) => { field: msg, _form: msg }

  storageBaseUrl: { type: String, default: "/storage/" },

  submitText: { type: String, default: null },
  cancelText: { type: String, default: null },
});

const emit = defineEmits(["update:values", "submitted", "cancelled", "dirty-change"]);

const router = useRouter();
const slots = useSlots();

const loading = ref(false);
const submitting = ref(false);
const submitAttempted = ref(false);

const serverError = ref("");
const errors = ref({});

const localValues = reactive(clonePlain(props.values || {}));

watch(
  () => props.values,
  (v) => Object.assign(localValues, clonePlain(v || {})),
  { deep: true }
);

watch(
  localValues,
  () => emit("update:values", clonePlain(localValues)),
  { deep: true }
);

const initialSnapshot = ref(JSON.stringify(clonePlain(localValues)));
const isDirty = computed(
  () => JSON.stringify(clonePlain(localValues)) !== initialSnapshot.value
);

watch(isDirty, (v) => emit("dirty-change", v), { immediate: true });

function beforeUnload(e) {
  if (!isDirty.value) return;
  e.preventDefault();
  e.returnValue = "";
}

onMounted(() => window.addEventListener("beforeunload", beforeUnload));
onBeforeUnmount(() => window.removeEventListener("beforeunload", beforeUnload));

const groups = computed(() => {
  const map = new Map();

  for (const f of props.schema || []) {
    const g = f.group || "general";
    if (!map.has(g)) {
      map.set(g, {
        key: g,
        title:
          f.groupTitle ||
          (g === "general"
            ? "Thông tin cơ bản"
            : g === "settings"
              ? "Cài đặt"
              : g === "media"
                ? "Media"
                : "Mục"),
        subtitle: f.groupSubtitle || "",
        fields: [],
      });
    }
    map.get(g).fields.push(f);
  }

  return Array.from(map.values());
});

const hasRight = computed(() => !!slots.right);

function fieldDisabled(field) {
  if (loading.value || submitting.value) return true;
  if (typeof field.disabledWhen === "function") return !!field.disabledWhen(localValues);
  return false;
}

function previewSrc(value) {
  const v = (value || "").toString().trim();
  if (!v) return "";
  if (v.startsWith("http://") || v.startsWith("https://")) return v;
  const base = props.storageBaseUrl.endsWith("/")
    ? props.storageBaseUrl
    : props.storageBaseUrl + "/";
  return base + v.replace(/^\/+/, "");
}

function runValidate() {
  serverError.value = "";
  errors.value = {};

  if (typeof props.validate !== "function") return true;

  const res = props.validate(localValues) || {};
  const { _form, ...fieldErrs } = res;

  if (_form) serverError.value = _form;
  errors.value = fieldErrs;

  return Object.keys(fieldErrs).length === 0 && !_form;
}

function applyLaravelErrors(e) {
  const errs = e?.response?.data?.errors;
  if (errs && typeof errs === "object") {
    const flat = {};
    for (const k of Object.keys(errs)) {
      flat[k] = Array.isArray(errs[k]) ? errs[k][0] : String(errs[k]);
    }
    errors.value = flat;
    const firstKey = Object.keys(flat)[0];
    serverError.value = flat[firstKey] || e?.response?.data?.message || "Validation error";
  } else {
    serverError.value = e?.response?.data?.message || e?.message || "Save failed";
  }
}

/** ============ FILE UPLOAD FIELD ============ */
const uploading = ref({});
const localPreview = ref({});

function cleanupPreview(name) {
  const u = localPreview.value[name];
  if (u) URL.revokeObjectURL(u);
  localPreview.value = { ...localPreview.value, [name]: "" };
}

function extractPathOrUrl(resp) {
  const root = resp?.data ?? resp;

  if (root?.path) return root.path;
  if (root?.url) return root.url;

  if (root?.data?.path) return root.data.path;
  if (root?.data?.url) return root.data.url;

  return "";
}

async function onPickFile(field, e) {
  const file = e.target.files?.[0];
  if (!file) return;

  const name = field.name;

  cleanupPreview(name);
  localPreview.value = { ...localPreview.value, [name]: URL.createObjectURL(file) };

  if (!field.upload) {
    e.target.value = "";
    return;
  }

  uploading.value = { ...uploading.value, [name]: true };
  serverError.value = "";

  try {
    const resp = await field.upload(file);
    const path = extractPathOrUrl(resp);
    if (!path) throw new Error("Upload API did not return path/url");
    localValues[name] = path;
    if (submitAttempted.value) runValidate();
  } catch (err) {
    serverError.value = err?.response?.data?.message || err?.message || "Upload failed";
  } finally {
    uploading.value = { ...uploading.value, [name]: false };
    e.target.value = "";
  }
}

/** ============ CHECKBOX LIST ============ */
function toggleCheckbox(fieldName, optionValue) {
  const cur = Array.isArray(localValues[fieldName])
    ? localValues[fieldName].slice()
    : [];
  const exists = cur.some((x) => String(x) === String(optionValue));
  localValues[fieldName] = exists
    ? cur.filter((x) => String(x) !== String(optionValue))
    : [...cur, optionValue];

  if (submitAttempted.value) runValidate();
}

/** ============ LOAD / SUBMIT / CANCEL ============ */
async function loadIfNeeded() {
  if (
    props.mode !== "edit" ||
    !props.load ||
    props.entityId === null ||
    props.entityId === undefined
  ) {
    return;
  }

  loading.value = true;
  serverError.value = "";
  errors.value = {};

  try {
    const loaded = await props.load(props.entityId);
    if (loaded && typeof loaded === "object") {
      Object.assign(localValues, loaded);
      initialSnapshot.value = JSON.stringify(clonePlain(localValues));
    }
  } catch (e) {
    serverError.value = e?.response?.data?.message || e?.message || "Load failed";
  } finally {
    loading.value = false;
  }
}

async function submit() {
  submitAttempted.value = true;
  if (!runValidate()) return;

  submitting.value = true;
  serverError.value = "";
  errors.value = {};

  try {
    const payload = props.transformBeforeSubmit
      ? props.transformBeforeSubmit(localValues)
      : clonePlain(localValues);

    await props.onSubmit(payload, {
      mode: props.mode,
      entityId: props.entityId,
      values: localValues,
    });

    initialSnapshot.value = JSON.stringify(clonePlain(localValues));
    emit("submitted", payload);
  } catch (e) {
    applyLaravelErrors(e);
  } finally {
    submitting.value = false;
  }
}

function cancel() {
  if (isDirty.value) {
    const ok = window.confirm("Bạn có thay đổi chưa lưu. Bạn chắc chắn muốn thoát?");
    if (!ok) return;
  }

  if (props.onCancel) props.onCancel();
  else router.back();

  emit("cancelled");
}

onMounted(loadIfNeeded);
watch(() => [props.mode, props.entityId], loadIfNeeded);

/** ============ LAYOUT HELPERS ============ */
function sectionGridClass(sectionKey) {
  if (sectionKey === "media") return "grid-1";
  return "grid-2";
}
</script>

<template>
  <div class="page">
    <!-- header -->
    <div class="topbar">
      <div class="topbar-left">
        <div>
          <div class="page-title">{{ title }}</div>
          <div class="page-subtitle" v-if="subtitle">{{ subtitle }}</div>
        </div>
      </div>

      <div class="topbar-actions">
        <button
          class="btn btn-ghost"
          type="button"
          :disabled="loading || submitting"
          @click="cancel"
        >
          {{ cancelText }}
        </button>

        <button
          class="btn btn-primary"
          type="button"
          :disabled="loading || submitting"
          @click="submit"
        >
          <span v-if="submitting" class="spinner" />
          {{ submitting ? "Đang lưu..." : submitText }}
        </button>
      </div>
    </div>

    <!-- server error -->
    <div v-if="serverError" class="alert">
      <div>
        <div class="alert-title">Lỗi</div>
        <div class="alert-body">{{ serverError }}</div>
      </div>
    </div>

    <div v-if="loading" class="loading">Đang tải...</div>

    <div v-else class="wrap">
      <div class="layout" :class="hasRight ? 'two-col' : 'one-col'">
        <!-- LEFT -->
        <div class="left">
          <div v-for="g in groups" :key="g.key" class="section">
            <div class="section-head">
              <div class="section-icon">
                {{
                  g.key === "general"
                    ? "📝"
                    : g.key === "settings"
                      ? "⚙️"
                      : g.key === "media"
                        ? "🖼️"
                        : "📋"
                }}
              </div>
              <div class="section-title">{{ g.title }}</div>
            </div>

            <div class="section-body">
              <div class="form-grid" :class="sectionGridClass(g.key)">
                <template v-for="field in g.fields" :key="field.name">
                  <div v-if="field.type === 'slot'" class="field full">
                    <slot
                      :name="`field:${field.name}`"
                      :field="field"
                      :values="localValues"
                      :errors="errors"
                      :serverError="serverError"
                      :loading="loading"
                      :submitting="submitting"
                      :submit="submit"
                      :cancel="cancel"
                      :previewSrc="previewSrc"
                    />
                  </div>

                  <!-- TEXT -->
                  <div
                    v-else-if="field.type === 'text'"
                    class="field"
                    :class="field.full ? 'full' : ''"
                  >
                    <label class="label">
                      {{ field.label }}
                      <span v-if="field.required" class="req">*</span>
                    </label>

                    <input
                      v-model="localValues[field.name]"
                      class="control"
                      :placeholder="field.placeholder"
                      :disabled="fieldDisabled(field)"
                      @input="submitAttempted && runValidate()"
                    />

                    <div v-if="field.help" class="hint">{{ field.help }}</div>
                    <div v-if="errors[field.name]" class="err">{{ errors[field.name] }}</div>
                  </div>

                  <!-- NUMBER -->
                  <div
                    v-else-if="field.type === 'number'"
                    class="field"
                    :class="field.full ? 'full' : ''"
                  >
                    <label class="label">
                      {{ field.label }}
                      <span v-if="field.required" class="req">*</span>
                    </label>

                    <input
                      v-model="localValues[field.name]"
                      type="number"
                      class="control"
                      :min="field.min"
                      :max="field.max"
                      :step="field.step ?? 1"
                      :placeholder="field.placeholder"
                      :disabled="fieldDisabled(field)"
                      @input="submitAttempted && runValidate()"
                    />

                    <div v-if="field.help" class="hint">{{ field.help }}</div>
                    <div v-if="errors[field.name]" class="err">{{ errors[field.name] }}</div>
                  </div>

                  <!-- SELECT -->
                  <div
                    v-else-if="field.type === 'select'"
                    class="field"
                    :class="field.full ? 'full' : ''"
                  >
                    <label class="label">
                      {{ field.label }}
                      <span v-if="field.required" class="req">*</span>
                    </label>

                    <select
                      v-model="localValues[field.name]"
                      class="control"
                      :disabled="fieldDisabled(field)"
                      @change="submitAttempted && runValidate()"
                    >
                      <option :value="null">-- Chọn --</option>
                      <option
                        v-for="opt in field.options || []"
                        :key="String(opt.value)"
                        :value="opt.value"
                      >
                        {{ opt.label }}
                      </option>
                    </select>

                    <div v-if="field.help" class="hint">{{ field.help }}</div>
                    <div v-if="errors[field.name]" class="err">{{ errors[field.name] }}</div>
                  </div>

                  <!-- DATE / TIME / DATETIME -->
                  <div
                    v-else-if="
                      field.type === 'date' ||
                      field.type === 'time' ||
                      field.type === 'datetime-local'
                    "
                    class="field"
                    :class="field.full ? 'full' : ''"
                  >
                    <label class="label">
                      {{ field.label }}
                      <span v-if="field.required" class="req">*</span>
                    </label>

                    <div class="datetime-wrap">
                      <span class="datetime-icon">
                        {{
                          field.type === "date"
                            ? "📅"
                            : field.type === "time"
                              ? "⏰"
                              : "🗓️"
                        }}
                      </span>

                      <input
                        v-model="localValues[field.name]"
                        :type="field.type"
                        class="control datetime-control"
                        :min="field.min"
                        :max="field.max"
                        :step="field.step"
                        :disabled="fieldDisabled(field)"
                        @input="submitAttempted && runValidate()"
                        @change="submitAttempted && runValidate()"
                      />
                    </div>

                    <div v-if="field.help" class="hint">{{ field.help }}</div>
                    <div v-if="errors[field.name]" class="err">{{ errors[field.name] }}</div>
                  </div>

                  <!-- CHECKBOX LIST -->
                  <div v-else-if="field.type === 'checkboxes'" class="field full">
                    <div class="row">
                      <label class="label">
                        {{ field.label }}
                        <span v-if="field.required" class="req">*</span>
                      </label>

                      <button
                        v-if="Array.isArray(localValues[field.name]) && localValues[field.name].length"
                        class="mini"
                        type="button"
                        :disabled="fieldDisabled(field)"
                        @click="localValues[field.name] = []"
                      >
                        Clear
                      </button>
                    </div>

                    <div
                      class="checkbox-grid"
                      :style="{
                        gridTemplateColumns: `repeat(${field.checkboxColumns || 2}, 1fr)`,
                      }"
                    >
                      <label
                        v-for="opt in field.options || []"
                        :key="String(opt.value)"
                        class="chk"
                      >
                        <input
                          type="checkbox"
                          :disabled="fieldDisabled(field)"
                          :checked="
                            (localValues[field.name] || []).some(
                              (x) => String(x) === String(opt.value)
                            )
                          "
                          @change="toggleCheckbox(field.name, opt.value)"
                        />
                        <span>{{ opt.label }}</span>
                      </label>
                    </div>

                    <div v-if="field.help" class="hint">{{ field.help }}</div>
                    <div v-if="errors[field.name]" class="err">{{ errors[field.name] }}</div>
                  </div>

                  <!-- TEXTAREA -->
                  <div v-else-if="field.type === 'textarea'" class="field full">
                    <label v-if="field.label" class="label">
                      {{ field.label }}
                      <span v-if="field.required" class="req">*</span>
                    </label>

                    <textarea
                      v-model="localValues[field.name]"
                      class="control textarea"
                      :rows="field.rows ?? 7"
                      :placeholder="field.placeholder"
                      :disabled="fieldDisabled(field)"
                      @input="submitAttempted && runValidate()"
                    />

                    <div v-if="field.help" class="hint">{{ field.help }}</div>
                    <div v-if="errors[field.name]" class="err">{{ errors[field.name] }}</div>
                  </div>

                  <!-- SWITCH -->
                  <div
                    v-else-if="field.type === 'switch'"
                    class="field"
                    :class="field.full ? 'full' : ''"
                  >
                    <label class="label">{{ field.label }}</label>

                    <div class="switch-row">
                      <label class="switch">
                        <input
                          v-model="localValues[field.name]"
                          type="checkbox"
                          :disabled="fieldDisabled(field)"
                          @change="submitAttempted && runValidate()"
                        />
                        <span class="track"><span class="thumb" /></span>
                      </label>

                      <span class="switch-text" :class="localValues[field.name] ? 'on' : 'off'">
                        {{
                          localValues[field.name]
                            ? field.onText || field.switchText || "Hoạt động"
                            : field.offText || "Tạm tắt"
                        }}
                      </span>
                    </div>

                    <div v-if="field.help" class="hint">{{ field.help }}</div>
                    <div v-if="errors[field.name]" class="err">{{ errors[field.name] }}</div>
                  </div>

                  <!-- FILE -->
                  <div v-else-if="field.type === 'file'" class="field full">
                    <label class="label">
                      {{ field.label }}
                      <span v-if="field.required" class="req">*</span>
                    </label>

                    <div class="upload-box">
                      <input
                        class="file-input"
                        type="file"
                        :accept="field.accept || 'image/*'"
                        :disabled="fieldDisabled(field)"
                        @change="(e) => onPickFile(field, e)"
                      />

                      <div class="upload-inner">
                        <div class="upload-icon">🖼️</div>
                        <div class="upload-text">{{ field.uploadText || "Nhấn để tải lên" }}</div>
                        <div v-if="uploading[field.name]" class="upload-sub">Uploading...</div>
                        <div
                          v-else-if="field.uploadHint"
                          class="upload-sub muted"
                        >
                          {{ field.uploadHint }}
                        </div>
                      </div>

                      <div class="preview" v-if="localPreview[field.name] || localValues[field.name]">
                        <img
                          :src="localPreview[field.name] || previewSrc(localValues[field.name])"
                        />
                      </div>
                    </div>

                    <input
                      v-model="localValues[field.name]"
                      class="control"
                      :placeholder="field.placeholder || 'path/url...'"
                      :disabled="fieldDisabled(field)"
                      @input="submitAttempted && runValidate()"
                    />

                    <div v-if="field.help" class="hint">{{ field.help }}</div>
                    <div v-if="errors[field.name]" class="err">{{ errors[field.name] }}</div>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- extra slot -->
          <slot
            name="extra"
            :values="localValues"
            :errors="errors"
            :serverError="serverError"
            :loading="loading"
            :submitting="submitting"
            :submit="submit"
            :cancel="cancel"
            :previewSrc="previewSrc"
          />
        </div>

        <!-- RIGHT (optional) -->
        <div class="right" v-if="hasRight">
          <slot
            name="right"
            :values="localValues"
            :errors="errors"
            :serverError="serverError"
            :loading="loading"
            :submitting="submitting"
            :submit="submit"
            :cancel="cancel"
            :previewSrc="previewSrc"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap");

* {
  box-sizing: border-box;
}

.page {
  min-height: 100vh;
  padding: 24px 28px 40px;
  color: #0f172a;
  font-family: "DM Sans", sans-serif;
}

.wrap {
  max-width: 1360px;
  margin: 0 auto;
}

/* ── Topbar ── */
.topbar {
  max-width: 1360px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 14px;
  padding: 16px 20px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.topbar-left::before {
  content: "";
  display: block;
  width: 4px;
  height: 36px;
  background: linear-gradient(180deg, #3b82f6, #6366f1);
  border-radius: 99px;
  flex-shrink: 0;
}

.page-title {
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: #0d1117;
  line-height: 1.2;
}

.page-subtitle {
  margin-top: 3px;
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.topbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* ── Alert ── */
.alert {
  max-width: 1360px;
  margin: 0 auto 16px;
  border: 1px solid rgba(239, 68, 68, 0.2);
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.07), rgba(239, 68, 68, 0.03));
  border-radius: 12px;
  padding: 12px 16px;
  color: #991b1b;
  display: flex;
  gap: 10px;
  align-items: flex-start;
}

.alert::before {
  content: "⚠";
  font-size: 15px;
  flex-shrink: 0;
  margin-top: 1px;
}

.alert-title {
  font-weight: 700;
  font-size: 13px;
}

.alert-body {
  margin-top: 2px;
  font-size: 13px;
  color: #b91c1c;
}

/* ── Loading ── */
.loading {
  max-width: 1360px;
  margin: 0 auto;
  padding: 40px 0;
  color: #94a3b8;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
}

.loading::before {
  content: "";
  width: 18px;
  height: 18px;
  border: 2px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* ── Layout ── */
.layout.one-col {
  display: block;
}

.layout.two-col {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 18px;
}

@media (max-width: 980px) {
  .layout.two-col {
    grid-template-columns: 1fr;
  }
}

/* ── Section ── */
.section {
  background: #fff;
  border: 1px solid #e8ecf4;
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 16px;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
  transition: box-shadow 0.2s;
}

.section:hover {
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.07);
}

.section-head {
  padding: 16px 20px 14px;
  border-bottom: 1px solid #f1f4fa;
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-icon {
  width: 34px;
  height: 34px;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border-radius: 10px;
  display: grid;
  place-items: center;
  font-size: 15px;
  flex-shrink: 0;
}

.section-title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.01em;
}

.section-body {
  padding: 18px 20px 20px;
  border-bottom: none;
}

/* ── Form Grid ── */
.form-grid {
  display: grid;
  gap: 16px 20px;
}

.grid-2 {
  grid-template-columns: 1fr 1fr;
}

.grid-1 {
  grid-template-columns: 1fr;
}

@media (max-width: 980px) {
  .grid-2 {
    grid-template-columns: 1fr;
  }
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.full {
  grid-column: 1 / -1;
}

/* ── Labels ── */
.label {
  font-size: 12.5px;
  font-weight: 600;
  color: #374151;
  letter-spacing: 0.01em;
}

.req {
  color: #ef4444;
  margin-left: 3px;
}

/* ── Controls ── */
.control {
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 9px 13px;
  outline: none;
  background: #fafbfd;
  color: #0f172a;
  font-family: "DM Sans", sans-serif;
  font-size: 14px;
  transition:
    border-color 0.15s,
    box-shadow 0.15s,
    background 0.15s;
}

.control:hover {
  border-color: #c7d4e8;
  background: #fff;
}

.control:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
  background: #fff;
}

.control:disabled {
  background: #f1f4fa;
  color: #94a3b8;
  cursor: not-allowed;
}

select.control {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 36px;
}

.textarea {
  resize: vertical;
  min-height: 120px;
}

/* ── Datetime ── */
.datetime-wrap {
  position: relative;
}

.datetime-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 15px;
  z-index: 1;
  pointer-events: none;
  opacity: 0.8;
}

.datetime-control {
  width: 100%;
  padding-left: 40px;
  min-height: 42px;
  appearance: none;
}

.datetime-control::-webkit-calendar-picker-indicator {
  cursor: pointer;
  opacity: 0.85;
}

.datetime-control::-webkit-datetime-edit,
.datetime-control::-webkit-date-and-time-value {
  color: #0f172a;
}

/* ── Hints / Errors ── */
.hint {
  font-size: 11.5px;
  color: #94a3b8;
  font-weight: 500;
}

.err {
  font-size: 11.5px;
  color: #ef4444;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 4px;
}

.err::before {
  content: "•";
  font-size: 16px;
  line-height: 0;
}

/* ── Switch ── */
.switch-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: #fafbfd;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  transition: border-color 0.15s;
}

.switch-row:has(input:checked) {
  border-color: #bfdbfe;
  background: #f0f7ff;
}

.switch {
  display: inline-flex;
  align-items: center;
  cursor: pointer;
}

.switch input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.track {
  width: 42px;
  height: 23px;
  border-radius: 999px;
  background: #d1d5db;
  position: relative;
  transition: background 0.2s;
}

.thumb {
  width: 17px;
  height: 17px;
  border-radius: 999px;
  background: #fff;
  position: absolute;
  top: 3px;
  left: 3px;
  transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
}

.switch input:checked + .track {
  background: #3b82f6;
}

.switch input:checked + .track .thumb {
  transform: translateX(19px);
}

.switch-text {
  font-size: 13px;
  font-weight: 600;
}

.switch-text.on {
  color: #2563eb;
}

.switch-text.off {
  color: #94a3b8;
}

/* ── Upload Box ── */
.upload-box {
  position: relative;
  border: 2px dashed #c7d4e8;
  border-radius: 14px;
  height: 190px;
  background: #fafbfd;
  overflow: hidden;
  transition:
    border-color 0.2s,
    background 0.2s;
}

.upload-box:hover {
  border-color: #93c5fd;
  background: #f0f7ff;
}

.file-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  z-index: 2;
}

.upload-inner {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 6px;
  user-select: none;
}

.upload-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border-radius: 14px;
  display: grid;
  place-items: center;
  font-size: 22px;
  margin-bottom: 4px;
}

.upload-text {
  font-weight: 700;
  color: #374151;
  font-size: 13.5px;
}

.upload-sub {
  font-size: 11.5px;
  color: #3b82f6;
  font-weight: 600;
}

.upload-sub.muted {
  color: #94a3b8;
  font-weight: 500;
}

.preview {
  position: absolute;
  inset: 0;
  z-index: 1;
  background: #fff;
}

.preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* ── Buttons ── */
.btn {
  border-radius: 10px;
  padding: 9px 18px;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.15s;
  font-family: "DM Sans", sans-serif;
}

.btn-ghost {
  background: #fff;
  border: 1.5px solid #e2e8f0;
  color: #475569;
}

.btn-ghost:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #334155;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6, #4f46e5);
  color: #fff;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.35);
}

.btn-primary:hover {
  background: linear-gradient(135deg, #2563eb, #4338ca);
  box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
  transform: translateY(-1px);
}

.btn-primary:active {
  transform: translateY(0);
}

.btn-primary:disabled,
.btn-ghost:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* ── Spinner ── */
.spinner {
  width: 13px;
  height: 13px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff;
  border-radius: 50%;
  display: inline-block;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ── Checkbox Grid ── */
.checkbox-grid {
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px;
  display: grid;
  gap: 8px;
  background: #fafbfd;
}

.chk {
  display: flex;
  align-items: center;
  gap: 9px;
  font-size: 13px;
  color: #374151;
  font-weight: 500;
  cursor: pointer;
  padding: 5px 8px;
  border-radius: 8px;
  transition: background 0.12s;
}

.chk:hover {
  background: #f0f7ff;
}

.chk input {
  width: 15px;
  height: 15px;
  accent-color: #3b82f6;
  flex-shrink: 0;
}

.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.mini {
  border: 1.5px solid #e2e8f0;
  background: #fff;
  padding: 5px 10px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 11.5px;
  cursor: pointer;
  color: #64748b;
  transition: all 0.12s;
}

.mini:hover {
  border-color: #ef4444;
  color: #ef4444;
  background: rgba(239, 68, 68, 0.05);
}
</style>