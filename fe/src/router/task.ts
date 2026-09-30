import { RouteName } from "@/common/enums";
import type { RouteRecordRaw } from "vue-router";

export default [
  {
    path: "/tasks",
    name: RouteName.Tasks,
    component: () => import("@/views/pages/TasksPage.vue"),
    meta: { requiresAuth: true },
  },
] satisfies RouteRecordRaw[];
