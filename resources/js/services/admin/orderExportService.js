import api from "../../api";

const baseUrl = "/api/v1/admin/orders";

const orderExportService = {
  async exportExcel(params = {}) {
    const queryParams = new URLSearchParams();
    if (params.search) queryParams.append("search", params.search);
    if (params.status) queryParams.append("status", params.status);
    if (params.payment_status) queryParams.append("payment_status", params.payment_status);
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
    if (params.status) queryParams.append("status", params.status);
    if (params.payment_status) queryParams.append("payment_status", params.payment_status);
    const query = queryParams.toString();
    const url = `${baseUrl}/export/pdf${query ? `?${query}` : ""}`;
    const response = await api.get(url, {
      responseType: "blob",
    });
    return response;
  },
};

export default orderExportService;
