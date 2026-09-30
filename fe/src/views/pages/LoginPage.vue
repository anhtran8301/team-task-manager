<script setup lang="ts">
import { RouteName } from "@/common/enums";
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { apiError } from "@/helpers/apiError";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const email = ref("");
const password = ref("");
const busy = ref(false);
const error = ref(
  route.query.reason === "restore"
    ? "Your session could not be restored. Please sign in again."
    : "",
);
const fields = ref<Record<string, string[]>>({});

async function login() {
  if (busy.value) return;
  busy.value = true;
  error.value = "";
  fields.value = {};
  try {
    await auth.login({ email: email.value, password: password.value });
    await router.replace({ name: RouteName.Tasks });
  } catch (cause) {
    const failure = apiError(cause);
    error.value = failure.message;
    fields.value = failure.fields;
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <main class="login-shell">
    <section class="login-story">
      <div class="d-flex align-center ga-3 font-weight-bold">
        <v-icon icon="mdi-checkbox-multiple-marked-outline" />Team Task Manager
      </div>
      <div>
        <div class="text-overline mb-5" style="color: #bdcfbd">
          A little clarity goes a long way
        </div>
        <h1>Good work.<br />One task<br />at a time.</h1>
        <p class="mt-6" style="color: #cfdfd4; max-width: 350px">
          Keep your priorities clear, your team in sync, and your next step
          within reach.
        </p>
      </div>
      <div class="text-caption" style="color: #b5c9bb">
        Your team's everyday workspace.
      </div>
    </section>
    <section class="login-form">
      <p class="eyebrow mb-3">Welcome back</p>
      <h2 class="text-h4 font-weight-bold mb-3">Sign in to your workspace</h2>
      <p class="muted mb-8">Use your team account to continue.</p>
      <v-alert v-if="error" type="error" variant="tonal" class="mb-5">
        {{ error }}
      </v-alert>
      <form @submit.prevent="login">
        <v-text-field
          v-model="email"
          label="Email address"
          type="email"
          autocomplete="username"
          required
          :error-messages="fields.email"
          :disabled="busy"
          class="mb-5"
        />
        <v-text-field
          v-model="password"
          label="Password"
          type="password"
          autocomplete="current-password"
          required
          :error-messages="fields.password"
          :disabled="busy"
          class="mb-6"
        />
        <v-btn
          type="submit"
          color="primary"
          size="large"
          block
          :loading="busy"
          append-icon="mdi-arrow-right"
        >
          Sign in
        </v-btn>
      </form>
    </section>
  </main>
</template>
