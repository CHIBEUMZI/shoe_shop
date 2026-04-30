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

  const filteredWards = computed(() => {
    let list = wards.value;
    if (form.province_code) {
      list = list.filter(w => w.province_code === Number(form.province_code));
    }
    if (wardSearch.value) {
      const search = wardSearch.value.toLowerCase();
      list = list.filter(w => w.label.toLowerCase().includes(search));
    }
    return list;
  });

  async function fetchProvinces() {
    loadingProvinces.value = true;
    try {
      const res = await fetch(`${BASE}/p/`);
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

  async function fetchWardsByProvince(provinceCode) {
    if (!provinceCode) {
      wards.value = [];
      return;
    }

    loadingWards.value = true;
    try {
      // Use depth=2 to get wards nested in province
      const res = await fetch(`${BASE}/p/${provinceCode}?depth=2`);
      const data = await res.json();

      if (data.wards && data.wards.length > 0) {
        wards.value = data.wards.map((w) => ({
          label: w.name,
          value: w.code,
          display: w.name.replace(/^(Phường|Xã|Thị trấn)\s+/i, ""),
          province_code: provinceCode
        }));
      } else {
        wards.value = [];
      }
    } catch (error) {
      console.error("Error fetching wards:", error);
      wards.value = [];
    } finally {
      loadingWards.value = false;
    }
  }

  // Watch province changes - reset ward when province changes
  watch(
    () => form.province_code,
    (newVal, oldVal) => {
      if (newVal !== oldVal) {
        form.ward_code = "";
        form.ward = "";
        wards.value = [];
      }
    }
  );

  fetchProvinces();

  return {
    provinces,
    wards,
    filteredProvinces,
    filteredWards,
    loadingProvinces,
    loadingWards,
    provinceSearch,
    wardSearch,
    fetchWardsByProvince,
  };
}
