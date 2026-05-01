// src/js/api/auth.js
import { apiFetch } from "./client.js";

export const AuthService = {
  async login(email, password) {
    try {
      const data = await apiFetch("/auth/login", {
        method: "POST",
        body: JSON.stringify({ email, password }),
      });
      // Accept multiple backend response shapes. Some responses don't include a
      // `success` boolean and instead return { message, token, user }.
      const token =
        (data && (data.token || (data.data && data.data.token))) || null;
      const user =
        (data && (data.user || (data.data && data.data.user))) || null;

      if (token) localStorage.setItem("jwt_token", token);
      if (user) localStorage.setItem("user", JSON.stringify(user));

      if (token || user) return user || null;
      // If no token/user returned, treat as a failed login
      throw new Error("Login failed: invalid response");
    } catch (error) {
      console.error("Login failed", error);
      throw error;
    }
  },

  logout() {
    localStorage.removeItem("jwt_token");
    localStorage.removeItem("user");
    // Trigger global event if needed
    document.dispatchEvent(new Event("auth:logout"));
  },

  getCurrentUser() {
    const userStr = localStorage.getItem("user");
    if (!userStr) return null;
    try {
      return JSON.parse(userStr);
    } catch (e) {
      return null;
    }
  },

  isAuthenticated() {
    return !!localStorage.getItem("jwt_token");
  },
};
