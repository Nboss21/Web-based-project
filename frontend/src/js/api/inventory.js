import { fetchApi } from "./api.js";

export const InventoryService = {
  async list(params = {}) {
    const query = new URLSearchParams(
      Object.entries(params).filter(
        ([, value]) => value !== undefined && value !== null && value !== "",
      ),
    ).toString();
    const endpoint = query
      ? `/api/inventory/list.php?${query}`
      : "/api/inventory/list.php";
    return fetchApi(endpoint);
  },

  async adjust(itemId, payload) {
    return fetchApi(
      `/api/inventory/adjust.php?id=${encodeURIComponent(itemId)}`,
      {
        method: "POST",
        body: JSON.stringify(payload),
      },
    );
  },

  async create(payload) {
    return fetchApi("/api/inventory/create.php", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },
};
