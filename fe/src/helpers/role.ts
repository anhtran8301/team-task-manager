import { Role } from "@/common/enums";
import type { User } from "@/typings/user";
import type { Task } from "@/typings/task";

/** UI convenience only; the backend remains authoritative. */
export function hasRole(user: User | null, role: Role): boolean {
  return user?.role === role;
}

/** Admins may manage every task and select any assignee. */
export function isAdmin(user: User | null): boolean {
  return hasRole(user, Role.Admin);
}

/** Ownership follows assignment, not task creation. */
export function canManageTask(user: User | null, task: Task): boolean {
  return (
    isAdmin(user) || (hasRole(user, Role.User) && user?.id === task.assigned_to)
  );
}
