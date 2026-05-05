export const router = {
  routes: [],

  addRoute(path, handler, roles = ["*"]) {
    this.routes.push({ path, handler, roles });
  },

  async handleLocation() {
    const path = window.location.hash.slice(1) || "/login";
    const route = this.routes.find((r) => r.path === path);
    const appRoot = document.getElementById("app-root");

    if (!route) {
      appRoot.innerHTML = "<h2>404 - Page Not Found</h2>";
      return;
    }

    const userStr = localStorage.getItem("user");
    const user = userStr ? JSON.parse(userStr) : null;

    // Authorization check
    if (route.roles && !route.roles.includes("*")) {
      if (!user || !route.roles.includes(user.role)) {
        this.navigateTo("/login");
        return;
      }
    }

    document.dispatchEvent(
      new CustomEvent("route:changed", { detail: { path } }),
    );

    try {
      await route.handler(appRoot);
    } catch (e) {
      console.error("Route evaluation error", e);
      appRoot.innerHTML = "<h2>Error loading page</h2>";
    }
  },

  navigateTo(path) {
    window.location.hash = path;
  },
};

window.addEventListener("hashchange", () => router.handleLocation());
