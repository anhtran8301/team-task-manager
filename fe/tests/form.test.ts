import { Role } from "@/common/enums";
import { flushPromises, mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import TaskForm from "@/components/TaskForm.vue";
import MasterForm from "@/components/forms/MasterForm.vue";
import taskService from "@/services/taskService";
vi.mock("@/services/taskService", () => ({
  default: { create: vi.fn(), update: vi.fn() },
}));
const actor = {
  id: 1,
  name: "Alex",
  email: "alex@example.com",
  role: Role.User,
};
const stubs = {
  VDialog: { template: "<div><slot /></div>" },
  VCard: { template: "<div><slot /></div>" },
  VCardTitle: { template: "<div><slot /></div>" },
  VCardText: { template: "<div><slot /></div>" },
  VAlert: { template: "<div><slot /></div>" },
  VBtn: true,
  MasterForm: true,
};
describe("task form", () => {
  it("locks assignee for users and allows admins to choose", async () => {
    const wrapper = mount(TaskForm, {
      props: { modelValue: false, task: null, actor, users: [actor] },
      global: { stubs },
    });
    const form = wrapper.findComponent(MasterForm);
    const assignee = () =>
      form
        .props("fields")
        .find((field: { key: string }) => field.key === "assigned_to");
    expect(assignee()?.disabled).toBe(true);
    await wrapper.setProps({ actor: { ...actor, role: Role.Admin } });
    expect(assignee()?.disabled).toBe(false);
  });
  it("sends create data, prevents duplicate submits, and exposes server field errors", async () => {
    let reject!: (reason: unknown) => void;
    vi.mocked(taskService.create).mockReturnValueOnce(
      new Promise((_, r) => {
        reject = r;
      }),
    );
    const wrapper = mount(TaskForm, {
      props: { modelValue: false, task: null, actor, users: [actor] },
      global: { stubs },
    });
    await wrapper.setProps({ modelValue: true });
    const form = wrapper.findComponent(MasterForm);
    form.vm.$emit("update:modelValue", {
      title: "New task",
      description: "",
      status: "todo",
      assigned_to: 1,
      due_date: null,
    });
    await flushPromises();
    form.vm.$emit("submit");
    form.vm.$emit("submit");
    expect(taskService.create).toHaveBeenCalledTimes(1);
    reject({
      isAxiosError: true,
      response: {
        data: {
          message: { "11000": "Invalid title" },
          errors: { title: ["Title is invalid"] },
        },
      },
    });
    await flushPromises();
    expect(form.props("errors")).toEqual({ title: ["Title is invalid"] });
    expect(wrapper.text()).toContain("Invalid title");
  });
  it("updates an existing task using its id", async () => {
    const task = {
      id: 42,
      title: "Edit me",
      description: null,
      status: "todo" as const,
      assigned_to: 1,
      due_date: null,
      assignee: actor,
      created_at: "",
      updated_at: "",
    };
    vi.mocked(taskService.update).mockResolvedValueOnce(task);
    const wrapper = mount(TaskForm, {
      props: { modelValue: false, task, actor, users: [actor] },
      global: { stubs },
    });
    await wrapper.setProps({ modelValue: true });
    wrapper.findComponent(MasterForm).vm.$emit("submit");
    await flushPromises();
    expect(taskService.update).toHaveBeenCalledWith(42, {
      title: "Edit me",
      description: null,
      status: "todo",
      assigned_to: 1,
      due_date: null,
    });
    expect(wrapper.emitted("saved")).toHaveLength(1);
  });
});
