import { TaskStatus } from "./enums";

export const API_ENDPOINTS = {
  csrf: "/csrf-cookie",
  login: "/login",
  logout: "/logout",
  me: "/me",
  tasks: "/tasks",
  users: "/users",
} as const;

export const APP_CONFIG = {
  requestTimeout: 15000,
  pageSize: 20,
  searchDebounce: 300,
  notificationDuration: 3000,
  titleLimit: 255,
  descriptionLimit: 10000,
} as const;

export const taskStatuses = [
  { title: "To do", value: TaskStatus.Todo, color: "grey" },
  { title: "In progress", value: TaskStatus.InProgress, color: "blue" },
  { title: "Done", value: TaskStatus.Done, color: "success" },
];

export const PAGE_SIZE_OPTIONS = [10, 20, 50, 100];
