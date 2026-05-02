// src/js/pages/login.js
import { AuthService } from "../api/auth.js";
import { router } from "../core/router.js";

export async function renderLogin(container) {
  // Hide sidebar and header on login page
  document.getElementById("sidebar").classList.add("hidden");
  document.getElementById("header").classList.add("hidden");

  container.innerHTML = `
        <div style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
            <div class="card" style="width: 100%; max-width: 400px;">
                <div class="text-center mb-4">
                    <h1 class="text-primary" style="margin-bottom: 0.5rem;">Campus Maintenance</h1>
                    <p>Sign in to your account</p>
                </div>
                
                <div id="login-error" style="color: var(--danger-color); font-size: 0.875rem; margin-bottom: 1rem; text-align: center; display: none;"></div>
                
                <form id="login-form">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" class="form-control" placeholder="admin@campus.edu" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" class="form-control" placeholder="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;" id="login-btn">
                        Sign In
                    </button>
                    
                    <div style="margin-top: 1.5rem; font-size: 0.875rem; color: var(--text-secondary); text-align: center;">
                        <p>Demo Credentials:</p>
                        <ul style="display: inline-block; text-align: left; background: var(--surface-hover); padding: 0.5rem 1rem; border-radius: var(--radius-sm); margin-top: 0.5rem;">
                            <li>admin@campus.edu / password</li>
                            <li>tech@campus.edu / password</li>
                            <li>student@campus.edu / password</li>
                        </ul>
                    </div>
                </form>
            </div>
        </div>
    `;

  const form = document.getElementById("login-form");
  const errorDiv = document.getElementById("login-error");
  const submitBtn = document.getElementById("login-btn");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();

    errorDiv.style.display = "none";
    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<span class="spinner" style="width:16px;height:16px;border-width:2px;margin:0"></span> Signing in...';

    try {
      const user = await AuthService.login(email, password);

      // Redirect based on role
      if (user.role === "Admin") {
        router.navigateTo("/dashboard/admin");
      } else if (user.role === "Technician") {
        router.navigateTo("/dashboard/tech");
      } else {
        router.navigateTo("/dashboard/student"); // Assuming student/staff have same dash for now
      }
    } catch (error) {
      // Prefer backend error message when available for debugging
      const msg =
        error && error.message
          ? error.message
          : "Invalid email or password. Please try again.";
      errorDiv.textContent = msg;
      errorDiv.style.display = "block";
      submitBtn.disabled = false;
      submitBtn.textContent = "Sign In";
    }
  });
}
