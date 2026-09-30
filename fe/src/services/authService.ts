import { BaseService } from "./baseService";
import { initializeCsrf } from "@/api/http";
import { API_ENDPOINTS } from "@/common/constants";
import type { LoginPayload, LoginResult, User } from "@/typings/user";

class AuthService extends BaseService {
  /** Bootstrap CSRF before credential submission; no tokens are exposed to JS. */
  async login(data: LoginPayload): Promise<LoginResult> {
    await initializeCsrf();
    return this.post<LoginResult>(API_ENDPOINTS.login, data);
  }

  /** Bootstrap CSRF before restoring the current account. */
  async restore(): Promise<User> {
    await initializeCsrf();
    return this.me();
  }

  /** Resolve identity exclusively from the server-managed cookie. */
  me(): Promise<User> {
    return this.get<User>(API_ENDPOINTS.me);
  }

  /** Revoke the current cookie token on the server. */
  async logout(): Promise<void> {
    await this.post(API_ENDPOINTS.logout);
  }
}

export default new AuthService();
