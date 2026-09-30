import { RouteName } from "@/common/enums";
import { createApp } from "vue";
import App from "./App.vue";
import { registerPlugins } from "./plugins";
import { onUnauthorized } from "./api/http";
import { useAuthStore } from "./stores/auth";
import { useTaskStore } from "./stores/task";
import router from "./router";
import "./assets/style.css";

const app = createApp(App);

registerPlugins(app);

onUnauthorized(() => {
  useAuthStore().clear();
  useTaskStore().reset();
  void router.replace({ name: RouteName.Login });
});

app.mount("#app");
