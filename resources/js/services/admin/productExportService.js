import api from "../../api";

const baseUrl = "/api/v1/admin/products";

const productExportService = {
  async exportExcel(params = {}) {
    const queryParams = new URLSearchParams();
    if (params.search) queryParams.append("search", params.search);
    if (params.status !== undefined && params.status !== "") {
      queryParams.append("status", params.status);
    }
    const query = queryParams.toString();
    const url = `${baseUrl}/export/excel${query ? `?${query}` : ""}`;
    const response = await api.get(url, {
      responseType: "blob",
    });
    return response;
  },

  async exportPdf(params = {}) {
    const queryParams = new URLSearchParams();
    if (params.search) queryParams.append("search", params.search);
    if (params.status !== undefined && params.status !== "") {
      queryParams.append("status", params.status);
    }
    const query = queryParams.toString();
    const url = `${baseUrl}/export/pdf${query ? `?${query}` : ""}`;
    const response = await api.get(url, {
      responseType: "blob",
    });
    return response;
  },
};

export default productExportService;
