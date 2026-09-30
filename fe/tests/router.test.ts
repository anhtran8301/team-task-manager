import { Role } from "@/common/enums";
import { beforeEach, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import router from "@/router";
import authService from "@/services/authService";
vi.mock("@/services/authService", () => ({ default: { restore: vi.fn() } }));
beforeEach(() => setActivePinia(createPinia()));
it("redirects unauthenticated task navigation to login", async () => {
  vi.mocked(authService.restore).mockResolvedValueOnce(null as never);
  await router.push("/tasks");
  expect(router.currentRoute.value.path).toBe("/login");
});
it("restores identity before entering a protected page", async () => {
  vi.mocked(authService.restore).mockResolvedValueOnce({
    id: 1,
    name: "Alex",
    email: "a@example.com",
    role: Role.User,
  });
  await router.push("/tasks");
  expect(router.currentRoute.value.path).toBe("/tasks");
});
