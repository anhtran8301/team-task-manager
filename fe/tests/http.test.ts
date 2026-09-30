import { AxiosError, AxiosHeaders } from "axios";
import { expect, it, vi } from "vitest";
import { http, onUnauthorized } from "@/api/http";
import { apiError } from "@/helpers/apiError";
it("rejects network errors without trying to read a missing response", async () => {
  const error = new AxiosError("Network Error", "ERR_NETWORK");
  await expect(
    http.get("/tasks", {
      adapter: async () => {
        throw error;
      },
    }),
  ).rejects.toBe(error);
  expect(apiError(error).message).toContain("Unable to connect");
});
it("clears authentication on an expired protected request, but not a rejected login", async () => {
  const handler = vi.fn();
  onUnauthorized(handler);
  const adapter = async (
    config: Parameters<NonNullable<import("axios").AxiosAdapter>>[0],
  ) => {
    throw new AxiosError("Unauthorized", "ERR_BAD_REQUEST", config, null, {
      status: 401,
      statusText: "Unauthorized",
      headers: new AxiosHeaders(),
      data: {},
      config,
    });
  };
  await expect(http.get("/me", { adapter })).rejects.toThrow();
  expect(handler).toHaveBeenCalledTimes(1);
  await expect(http.post("/login", {}, { adapter })).rejects.toThrow();
  expect(handler).toHaveBeenCalledTimes(1);
});

it("refreshes CSRF after 419 without replaying the failed write", async () => {
  const get = vi.spyOn(http, "get").mockResolvedValueOnce({ data: {} });
  const mutation = vi.fn(
    async (
      config: Parameters<NonNullable<import("axios").AxiosAdapter>>[0],
    ) => {
      throw new AxiosError("CSRF expired", "ERR_BAD_REQUEST", config, null, {
        status: 419,
        statusText: "CSRF expired",
        headers: new AxiosHeaders(),
        data: {},
        config,
      });
    },
  );
  await expect(http.post("/tasks", {}, { adapter: mutation })).rejects.toThrow(
    "CSRF expired",
  );
  expect(mutation).toHaveBeenCalledTimes(1);
  expect(get).toHaveBeenCalledWith("/csrf-cookie");
  get.mockRestore();
});
