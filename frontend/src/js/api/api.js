export const API_BASE_URL = "http://localhost:8000";

export async function fetchApi(endpoint, options = {}) {
  const token = localStorage.getItem("auth_token");

  const headers = {
    "Content-Type": "application/json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  };

  const config = {
    ...options,
    headers,
  };

  try {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, config);
    const data = await response.json();

    if (!response.ok) {
      if (response.status === 401) {
        // Trigger an event or call a method to handle unauthorized access
        document.dispatchEvent(new CustomEvent("auth:unauthorized"));
      }
      throw new Error(data.message || "API request failed");
    }

    return data;
  } catch (error) {
    console.error("API Fetch Error:", error);
    throw error;
  }
}
