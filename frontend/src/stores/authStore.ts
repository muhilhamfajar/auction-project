import { defineStore } from "pinia";
import axios, { AxiosError } from "axios";
import axiosInstance from "@/config/axios";

interface User {
  id: number;
  username: string;
  uuid: string;
  roles: string[];
}

interface LoginResult {
  success: boolean;
  error?: string;
}

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null as User | null,
    token: null as string | null,
    isAuthChecked: false,
    mercureToken: null as string | null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token && state.user,
    isAdmin(): boolean {
      return this.user?.roles.includes("ROLE_ADMIN") ?? false;
    },
  },
  actions: {
    async login(username: string, password: string): Promise<LoginResult> {
      try {
        const response = await axiosInstance.post("/login", {
          username,
          password,
        });
        this.token = response.data.token;
        localStorage.setItem("token", this.token);
        axiosInstance.defaults.headers.common["Authorization"] =
          `Bearer ${this.token}`;
        await this.fetchUserData();
        return { success: true };
      } catch (error) {
        console.error("Login failed:", error);
        if (axios.isAxiosError(error)) {
          const axiosError = error as AxiosError<{ message: string }>;
          if (axiosError.response) {
            switch (axiosError.response.status) {
              case 401:
                return { success: false, error: "INVALID_CREDENTIALS" };
              case 403:
                return { success: false, error: "ACCOUNT_DISABLED" };
              default:
                return { success: false, error: "UNKNOWN_ERROR" };
            }
          }
        }
        return { success: false, error: "UNKNOWN_ERROR" };
      }
    },

    async fetchUserData() {
      try {
        const response = await axiosInstance.get("/users/me");
        this.user = response.data;
      } catch (error) {
        console.error("Failed to fetch user data:", error);
        this.handleAuthError(error);
        throw error;
      }
    },

    async fetchMercureToken() {
      try {
        const response = await axiosInstance.get("/mercure-jwt", {
          headers: { Authorization: `Bearer ${this.token}` },
        });
        this.mercureToken = response.data.token;
      } catch (error) {
        console.error("Failed to fetch Mercure token:", error);
        throw error;
      }
    },

    logout() {
      this.user = null;
      this.token = null;
      localStorage.removeItem("token");
      delete axiosInstance.defaults.headers.common["Authorization"];
    },
    async checkAuth() {
      if (this.isAuthChecked) return;

      const token = localStorage.getItem("token");
      if (token) {
        this.token = token;
        axiosInstance.defaults.headers.common["Authorization"] =
          `Bearer ${token}`;
        try {
          await this.fetchUserData();
        } catch (error) {
          this.handleAuthError(error);
        }
      }
      this.isAuthChecked = true;
    },
    async register(name: string, email: string, password: string, confirmedPassword: string) {
      try {
        await axiosInstance.post("/users/register", {
          name,
          username: email,
          password: { first: password, second: confirmedPassword },
        });
        return true;
      } catch (error) {
        console.error("Registration failed:", error);
        throw error;
      }
    },
    handleAuthError(error: unknown) {
      if (axios.isAxiosError(error)) {
        const axiosError = error as AxiosError;
        if (axiosError.response && axiosError.response.status === 401) {
          this.logout();
          window.dispatchEvent(new CustomEvent("auth:required"));
        }
      }
    },
  },
});
