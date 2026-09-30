import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { RouteName } from "@/common/enums";
import authRoutes from "./auth";
import taskRoutes from "./task";
export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: "/", redirect: { name: RouteName.Tasks } },
    ...authRoutes,
    ...taskRoutes,
    { path: "/:pathMatch(.*)*", redirect: { name: RouteName.Tasks } },
  ],
});
router.beforeEach(async (to) => {
  const auth = useAuthStore();
  try {
    await auth.restore();
  } catch {
    if (to.meta.requiresAuth)
      return { name: RouteName.Login, query: { reason: "restore" } };
  }
  if (to.meta.requiresAuth && !auth.user) return { name: RouteName.Login };
  if (to.name === RouteName.Login && auth.user)
    return { name: RouteName.Tasks };
});

export default router;
