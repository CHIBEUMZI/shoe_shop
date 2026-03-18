import api from "../../api";

function buildParamsSerializer() {
  return (params) => {
    const sp = new URLSearchParams();

    Object.entries(params || {}).forEach(([key, val]) => {
      if (val === undefined || val === null || val === "") return;

      if (Array.isArray(val)) {
        val.forEach((v) => {
          if (v === undefined || v === null || v === "") return;
          sp.append(`${key}[]`, String(v));
        });
      } else {
        sp.append(key, String(val));
      }
    });

    return sp.toString();
  };
}

export default {
  facets(params) {
    return api.get("/api/v1/products/facets", {
      params,
      paramsSerializer: buildParamsSerializer(),
    });
  },
};