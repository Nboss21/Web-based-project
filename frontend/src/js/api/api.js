import { API_BASE_URL } from "../config.js";

export async function fetchApi(endpoint, options = {}) {
  const token = localStorage.getItem("auth_token");
  const isFormData = options.body instanceof FormData;

  const headers = {
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(isFormData ? {} : { "Content-Type": "application/json" }),
    ...options.headers,
  };

  const config = {
    ...options,
    headers,
  };

  try {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, config);
    const contentType = response.headers.get("content-type") || "";
    let data = contentType.includes("application/json")
      ? await response.json()
      : { message: await response.text() };

    // Normalize: ensure `.data` always exists for client code expectations
    if (Array.isArray(data)) {
      data = { message: null, data };
    } else if (
      data &&
      typeof data === "object" &&
      !Object.prototype.hasOwnProperty.call(data, "data")
    ) {
      // Keep existing keys but ensure `data` key exists (null when not provided)
      data.data = null;
    }

    if (!response.ok) {
      if (response.status === 401) {
        document.dispatchEvent(new CustomEvent("auth:unauthorized"));
      }
      throw new Error(data.error || data.message || "API request failed");
    }

    return data;
  } catch (error) {
    console.error("API Fetch Error:", error);
    throw error;
  }
}
