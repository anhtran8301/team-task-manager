import type { User } from "./user";
import type { TaskStatus } from "@/common/enums";
export type { TaskStatus } from "@/common/enums";
export interface TaskPayload {
  title: string;
  description: string | null;
  status: TaskStatus;
  assigned_to: number;
  due_date: string | null;
}
export interface Task extends TaskPayload {
  id: number;
  assignee: User;
  created_at: string;
  updated_at: string;
}

export interface TaskFilters {
  page: number;
  per_page: number;
  search?: string;
  status?: TaskStatus[];
  assigned_to?: number[];
  sort?: TaskSort[];
}

export type TaskSortField = "title" | "assignee" | "due_date";
export type SortDirection = "asc" | "desc";
export interface TaskSort {
  field: TaskSortField;
  direction: SortDirection;
}

/** Matches the native Vuetify sort event; unsupported keys are ignored at the API boundary. */
export interface TableSort {
  key: string;
  order?: boolean | SortDirection;
}
