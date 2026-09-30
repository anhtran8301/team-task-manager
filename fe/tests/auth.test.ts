import { Role } from "@/common/enums";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { useAuthStore } from "@/stores/auth";
import authService from "@/services/authService";
import type { User } from "@/typings/user";
vi.mock("@/services/authService", () => ({
  default: { login: vi.fn(), restore: vi.fn(), logout: vi.fn() },
}));
const user: User = {
  id: 1,
  name: "Alex",
  email: "alex@example.com",
  role: Role.User,
};
describe("cookie authentication", () => {
  beforeEach(() => setActivePinia(createPinia()));
  it("restores identity once without exposing credentials in browser storage", async () => {
    vi.mocked(authService.restore).mockResolvedValue(user);
    const auth = useAuthStore();
    await Promise.all([auth.restore(), auth.restore()]);
    expect(authService.restore).toHaveBeenCalledTimes(1);
    expect(auth.user).toEqual(user);
    expect(sessionStorage.length).toBe(0);
    expect(localStorage.length).toBe(0);
    await auth.logout();
    expect(auth.user).toBeNull();
  });
  it("keeps identity when logout fails so revocation can be retried", async () => {
    const auth = useAuthStore();
    auth.user = user;
    vi.mocked(authService.logout).mockRejectedValueOnce(new Error("offline"));
    await expect(auth.logout()).rejects.toThrow("offline");
    expect(auth.user).toEqual(user);
  });
  it("does not let a pending restore resurrect cleared identity", async () => {
    let resolve!: (user: User) => void;
    vi.mocked(authService.restore).mockReturnValueOnce(
      new Promise((r) => {
        resolve = r;
      }),
    );
    const auth = useAuthStore();
    const pending = auth.restore();
    auth.clear();
    resolve(user);
    await pending;
    expect(auth.user).toBeNull();
  });
});
