import { ref, watch } from "vue";

const BASE = "https://provinces.open-api.vn/api";

export function useAddress(form) {
  const provinces = ref([]);
  const districts = ref([]);
  const wards = ref([]);

  const loadingProvinces = ref(false);
  const loadingDistricts = ref(false);
  const loadingWards = ref(false);

  async function fetchProvinces() {
    loadingProvinces.value = true;
    try {
      const res = await fetch(`${BASE}/?depth=1`);
      const data = await res.json();
      provinces.value = data.map((p) => ({ label: p.name, value: p.code }));
    } finally {
      loadingProvinces.value = false;
    }
  }

  async function fetchDistricts(provinceCode) {
    districts.value = [];
    wards.value = [];
    if (!provinceCode) return;
    loadingDistricts.value = true;
    try {
      const res = await fetch(`${BASE}/p/${provinceCode}?depth=2`);
      const data = await res.json();
      districts.value = (data.districts || []).map((d) => ({ label: d.name, value: d.code }));
    } finally {
      loadingDistricts.value = false;
    }
  }

  async function fetchWards(districtCode) {
    wards.value = [];
    if (!districtCode) return;
    loadingWards.value = true;
    try {
      const res = await fetch(`${BASE}/d/${districtCode}?depth=2`);
      const data = await res.json();
      wards.value = (data.wards || []).map((w) => ({ label: w.name, value: w.code }));
    } finally {
      loadingWards.value = false;
    }
  }

  watch(
    () => form.province_obj,
    (val) => {
      form.province = val?.label ?? "";
      form.district_obj = null;
      form.district = "";
      form.ward_obj = null;
      form.ward = "";
      fetchDistricts(val?.value);
    }
  );

  watch(
    () => form.district_obj,
    (val) => {
      form.district = val?.label ?? "";
      form.ward_obj = null;
      form.ward = "";
      fetchWards(val?.value);
    }
  );

  watch(
    () => form.ward_obj,
    (val) => {
      form.ward = val?.label ?? "";
    }
  );

  fetchProvinces();

  return { provinces, districts, wards, loadingProvinces, loadingDistricts, loadingWards };
}