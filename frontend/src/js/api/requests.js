import { fetchApi } from "./api.js";

export const RequestsService = {
  async list(params = {}) {
    const query = new URLSearchParams(
      Object.entries(params).filter(
        ([, value]) => value !== undefined && value !== null && value !== "",
      ),
    ).toString();
    const endpoint = query
      ? `/api/requests/list.php?${query}`
      : "/api/requests/list.php";
    return fetchApi(endpoint);
  },

  async create(formData) {
    return fetchApi("/api/requests/create.php", {
      method: "POST",
      body: formData,
    });
  },

  async assign(requestId, payload) {
    return fetchApi(
      `/api/requests/assign.php?id=${encodeURIComponent(requestId)}`,
      {
        method: "POST",
        body: JSON.stringify(payload),
      },
    );
  },
};
