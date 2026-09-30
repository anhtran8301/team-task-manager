import { describe, expect, it } from "vitest";
import { Role } from "@/common/enums";
import { hasRole, isAdmin, canManageTask } from "@/helpers/role";
import type { User } from "@/typings/user";
import type { Task } from "@/typings/task";

const user: User = {
  id: 1,
  name: "User",
  email: "user@example.com",
  role: Role.User,
};
const task: Task = {
  id: 1,
  title: "Assigned",
  description: null,
  status: "todo",
  assigned_to: 1,
  assignee: user,
  due_date: null,
  created_at: "",
  updated_at: "",
};

describe("role access", () => {
  it("allows admin all tasks and regular users only their assignments", () => {
    expect(hasRole(user, Role.User)).toBe(true);
    expect(isAdmin(user)).toBe(false);
    expect(canManageTask(user, task)).toBe(true);
    expect(canManageTask({ ...user, id: 2 }, task)).toBe(false);
    expect(canManageTask({ ...user, id: 2, role: Role.Admin }, task)).toBe(
      true,
    );
  });
  it("denies missing and unsupported identities", () => {
    expect(isAdmin(null)).toBe(false);
    expect(canManageTask(null, task)).toBe(false);
    expect(canManageTask({ ...user, role: "reviewer" as Role }, task)).toBe(
      false,
    );
  });
});
