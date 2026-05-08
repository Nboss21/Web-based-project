import { fetchApi } from "./api.js";

const mapPath = (path) => {
  // Accept legacy paths like /users/technicians and map to PHP endpoint.
  if (path.startsWith("/api/")) {
    return path.endsWith(".php") ? path : `${path}.php`;
  }
  return `/api${path.endsWith(".php") ? path : `${path}.php`}`;
};

export async function apiFetch(path, options = {}) {
  return fetchApi(mapPath(path), options);
}
