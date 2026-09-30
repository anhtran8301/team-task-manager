<script setup lang="ts">
import { RouteName } from "@/common/enums";
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useTaskStore } from "@/stores/task";
import { apiError } from "@/helpers/apiError";

const auth = useAuthStore();
const router = useRouter();

const busy = ref(false);
const error = ref("");

async function logout() {
  busy.value = true;
  try {
    await auth.logout();
    useTaskStore().reset();
    await router.replace({ name: RouteName.Login });
  } catch (cause) {
    error.value = apiError(cause).message;
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <v-app-bar flat border="b" class="px-3 px-sm-6">
    <v-icon
      icon="mdi-checkbox-multiple-marked-outline"
      color="primary"
      class="mr-3"
    />
    <span class="font-weight-bold">Team Task Manager</span><v-spacer />
    <div class="text-right mr-4">
      <div class="text-body-2 font-weight-medium">{{ auth.user?.name }}</div>
      <div class="text-caption muted text-capitalize">
        {{ auth.user?.role }}
      </div>
    </div>
    <v-btn
      icon="mdi-logout"
      aria-label="Log out"
      :loading="busy"
      @click="logout"
    />
  </v-app-bar>
  <v-main>
    <div class="workspace"><slot /></div>
  </v-main>
  <v-snackbar
    :model-value="!!error"
    color="error"
    @update:model-value="error = ''"
  >
    {{ error }}
  </v-snackbar>
</template>
