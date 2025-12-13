import axios from "axios";
import { store } from "../app/store";
import { logout } from "../features/auth/authSlice";
import { notificationService } from "./notificationService";

const api = axios.create({
  // Use /api to go through Vite proxy (Dev) or Nginx proxy (Prod)
  baseURL: import.meta.env.VITE_API_URL || "/api",
});

// Request interceptor - attach token
api.interceptors.request.use((config) => {
  const state = store.getState();
  const token = state.auth.token;
  if (token && !config.url?.includes('login')) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor - auto logout on 401 (token expired)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const state = store.getState();
      // Only logout if user was logged in (has token)
      if (state.auth.token) {
        console.warn("[API] Token expired, logging out...");
        store.dispatch(logout());

        // Show notification using toast
        notificationService.warning(
          "Phiên đăng nhập đã hết hạn! Vui lòng đăng nhập lại."
        );

        // Redirect to login page
        window.location.href = "/login";
      }
    }
    return Promise.reject(error);
  }
);

export default api;

