import { fetchApi } from "./api.js";

export const AuthService = {
  async login(email, password) {
    const data = await fetchApi("/api/auth/login.php", {
      method: "POST",
      body: JSON.stringify({ email, password }),
    });

    if (data && data.token) {
      localStorage.setItem("auth_token", data.token);
      localStorage.setItem("user", JSON.stringify(data.user));
      return data.user;
    }
    throw new Error("Invalid response from server");
  },

  logout() {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user");
  },

  isAuthenticated() {
    return !!localStorage.getItem("auth_token");
  },

  getCurrentUser() {
    const user = localStorage.getItem("user");
    return user ? JSON.parse(user) : null;
  },
};
