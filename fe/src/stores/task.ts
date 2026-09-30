import { defineStore } from "pinia";
import { ref } from "vue";
import taskService from "@/services/taskService";
import { apiError } from "@/helpers/apiError";
import type { Task, TaskFilters } from "@/typings/task";

export const useTaskStore = defineStore("task", () => {
  const tasks = ref<Task[]>([]);
  const total = ref(0);
  const lastPage = ref(1);
  const loading = ref(false);
  const error = ref("");
  let requestId = 0;

  /** Discard obsolete requests so slow responses cannot overwrite a newer page. */
  async function fetch(filters: TaskFilters) {
    const current = ++requestId;
    loading.value = true;
    error.value = "";

    try {
      const result = await taskService.list(filters);
      if (current !== requestId) return;
      tasks.value = result.data;
      total.value = result.total;
      lastPage.value = result.last_page;
    } catch (cause) {
      if (current === requestId) {
        tasks.value = [];
        total.value = 0;
        error.value = apiError(cause).message;
      }
    } finally {
      if (current === requestId) loading.value = false;
    }
  }

  /** Invalidate in-flight work and clear task state when leaving the workspace. */
  function reset() {
    requestId++;
    tasks.value = [];
    total.value = 0;
    lastPage.value = 1;
    error.value = "";
    loading.value = false;
  }

  return { tasks, total, lastPage, loading, error, fetch, reset };
});
