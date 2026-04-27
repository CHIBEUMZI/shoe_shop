import { ref, computed, watch } from "vue";

const BASE = "https://provinces.open-api.vn/api/v2";

export function useAddress(form) {
  const provinces = ref([]);
  const wards = ref([]);

  const loadingProvinces = ref(false);
  const loadingWards = ref(false);

  // Search filters
  const provinceSearch = ref("");
  const wardSearch = ref("");

  // Filtered lists for search
  const filteredProvinces = computed(() => {
    if (!provinceSearch.value) return provinces.value;
    const search = provinceSearch.value.toLowerCase();
    return provinces.value.filter(p => p.label.toLowerCase().includes(search));
  });

  async function fetchProvinces() {
    loadingProvinces.value = true;
    try {
      const res = await fetch(`${BASE}/`);
      const data = await res.json();
      provinces.value = data.map((p) => ({
        label: p.name,
        value: p.code,
        display: p.name.replace(/^(Thành phố|Tỉnh)\s+/i, "")
      }));
    } finally {
      loadingProvinces.value = false;
    }
  }

  async function fetchWards() {
    wards.value = [];
    wardSearch.value = "";
    loadingWards.value = true;
    try {
      const res = await fetch(`${BASE}/w/`);
      const data = await res.json();
      wards.value = data.map((w) => ({
        label: w.name,
        value: w.code,
        display: w.name.replace(/^(Phường|Xã)\s+/i, ""),
        province_code: w.province_code
      }));
    } finally {
      loadingWards.value = false;
    }
  }

  watch(
    () => form.province_obj,
    (val) => {
      form.province = val?.label ?? "";
      form.ward_obj = null;
      form.ward = "";
      wardSearch.value = "";
    }
  );

  // Filter wards by selected province
  const filteredWards = computed(() => {
    let list = wards.value;
    if (form.province_obj?.value) {
      list = list.filter(w => w.province_code === form.province_obj.value);
    }
    if (wardSearch.value) {
      const search = wardSearch.value.toLowerCase();
      list = list.filter(w => w.label.toLowerCase().includes(search));
    }
    return list;
  });

  watch(
    () => form.ward_obj,
    (val) => {
      form.ward = val?.label ?? "";
    }
  );

  fetchProvinces();
  fetchWards();

  return {
    provinces,
    wards,
    filteredProvinces,
    filteredWards,
    loadingProvinces,
    loadingWards,
    provinceSearch,
    wardSearch,
  };
}
