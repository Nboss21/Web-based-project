import { fetchApi } from "./api.js";

export const NotificationsService = {
  async list(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await fetchApi(`/api/notifications/list.php?${query}`);
  },

  async markAsRead(id) {
    return await fetchApi(`/api/notifications/read.php?id=${id}`, {
      method: "GET",
    });
  },

  async getPreferences() {
    return await fetchApi("/api/users/me/preferences.php");
  },

  async updatePreferences(preferences) {
    return await fetchApi("/api/users/me/preferences.php", {
      method: "POST",
      body: JSON.stringify(preferences),
    });
  },
};
