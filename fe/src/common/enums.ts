export enum Role {
  Admin = "admin",
  User = "user",
}

export const TaskStatus = {
  Todo: "todo",
  InProgress: "in_progress",
  Done: "done",
} as const;
export type TaskStatus = (typeof TaskStatus)[keyof typeof TaskStatus];

export enum RouteName {
  Login = "login",
  Tasks = "tasks",
}

export enum HttpStatus {
  Unauthorized = 401,
  CsrfExpired = 419,
}
