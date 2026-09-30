import { API_ENDPOINTS } from "@/common/constants";
import { http } from "@/api/http";
import { taskQuery } from "@/helpers/taskQuery";
import { BaseService } from "./baseService";
import type { Task, TaskFilters, TaskPayload } from "@/typings/task";
import type { Paginated } from "@/typings/api";
import type { User } from "@/typings/user";

class TaskService extends BaseService {
  /** Fetch one authorized server page with the supplied filters. */
  list(filters: TaskFilters) {
    return this.get<Paginated<Task>>(API_ENDPOINTS.tasks, taskQuery(filters));
  }

  /** Load only assignees the current account may view. */
  users() {
    return this.get<User[]>(API_ENDPOINTS.users);
  }

  /** Persist a new task through backend validation and Policies. */
  create(data: TaskPayload) {
    return this.post<Task>(API_ENDPOINTS.tasks, data);
  }

  /** Submit editable fields for an existing task. */
  update(id: number, data: TaskPayload) {
    return this.put<Task>(`${API_ENDPOINTS.tasks}/${id}`, data);
  }
  /** Hard-delete a task; errors remain available to the caller. */
  async remove(id: number) {
    await http.delete(`${API_ENDPOINTS.tasks}/${id}`);
  }
}

export default new TaskService();
