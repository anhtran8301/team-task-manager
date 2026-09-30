import { RouteName } from "@/common/enums";
import type { RouteRecordRaw } from "vue-router";

export default [
  {
    path: "/login",
    name: RouteName.Login,
    component: () => import("@/views/pages/LoginPage.vue"),
  },
] satisfies RouteRecordRaw[];
