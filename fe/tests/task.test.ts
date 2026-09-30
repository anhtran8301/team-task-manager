import { beforeEach, describe, expect, it, vi } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import { useTaskStore } from "@/stores/task";
import taskService from "@/services/taskService";
import type { Paginated } from "@/typings/api";
import type { Task } from "@/typings/task";
vi.mock("@/services/taskService", () => ({ default: { list: vi.fn() } }));
const result = (total: number): Paginated<Task> => ({
  data: [],
  total,
  current_page: 1,
  last_page: 1,
  per_page: 20,
});
describe("task requests", () => {
  beforeEach(() => setActivePinia(createPinia()));
  it("ignores stale results when filters change", async () => {
    let resolveOld!: (value: Paginated<Task>) => void;
    vi.mocked(taskService.list).mockReturnValueOnce(
      new Promise((resolve) => {
        resolveOld = resolve;
      }),
    );
    vi.mocked(taskService.list).mockResolvedValueOnce(result(2));
    const store = useTaskStore();
    const old = store.fetch({ page: 1, per_page: 20 });
    await store.fetch({
      page: 1,
      per_page: 20,
      status: ["done", "in_progress"],
      assigned_to: [2, 3],
      sort: [{ field: "title", direction: "desc" }],
      search: "release",
    });
    resolveOld(result(99));
    await old;
    expect(store.total).toBe(2);
    expect(store.loading).toBe(false);
    expect(taskService.list).toHaveBeenLastCalledWith({
      page: 1,
      per_page: 20,
      status: ["done", "in_progress"],
      assigned_to: [2, 3],
      sort: [{ field: "title", direction: "desc" }],
      search: "release",
    });
  });
  it("reports errors and clears inaccessible results", async () => {
    vi.mocked(taskService.list).mockRejectedValueOnce(new Error("offline"));
    const store = useTaskStore();
    await store.fetch({ page: 1, per_page: 20 });
    expect(store.error).not.toBe("");
    expect(store.tasks).toEqual([]);
    expect(store.loading).toBe(false);
  });
});
