import axios from "axios";
import { API_ENDPOINTS, APP_CONFIG } from "@/common/constants";
import { HttpStatus } from "@/common/enums";

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? "/api",
  timeout: APP_CONFIG.requestTimeout,
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: "application/json", "Content-Type": "application/json" },
});

let unauthorizedHandler: (() => void) | undefined;

let csrfRequest: Promise<void> | null = null;

/** Share CSRF bootstrap across concurrent consumers, without replaying mutations. */
export function initializeCsrf(): Promise<void> {
  csrfRequest ??= http
    .get(API_ENDPOINTS.csrf)
    .then(() => undefined)
    .finally(() => {
      csrfRequest = null;
    });

  return csrfRequest;
}

export function onUnauthorized(handler: () => void): void {
  unauthorizedHandler = handler;
}

http.interceptors.response.use(
  (response) => response,
  async (error: unknown) => {
    if (axios.isAxiosError(error)) {
      if (
        error.response?.status === HttpStatus.Unauthorized &&
        error.config?.url !== API_ENDPOINTS.login
      )
        unauthorizedHandler?.();
      if (
        error.response?.status === HttpStatus.CsrfExpired &&
        error.config?.url !== API_ENDPOINTS.csrf
      ) {
        // Recover the CSRF cookie for a deliberate retry, preserving the original failure.
        await initializeCsrf().catch(() => undefined);
      }
    }

    return Promise.reject(error);
  },
);
