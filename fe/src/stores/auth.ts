import axios from "axios";
import { defineStore } from "pinia";
import { ref } from "vue";
import authService from "@/services/authService";
import { HttpStatus } from "@/common/enums";
import type { LoginPayload, User } from "@/typings/user";

export const useAuthStore = defineStore("auth", () => {
  const user = ref<User | null>(null);
  const initialized = ref(false);
  let restoreRequest: Promise<void> | null = null;
  let generation = 0;

  /** Invalidates pending identity loads so a late response cannot undo logout. */
  function clear(): void {
    generation++;
    user.value = null;
    initialized.value = true;
  }

  /** Restore identity once, sharing concurrent navigation requests. */
  async function restore(): Promise<void> {
    if (initialized.value) return;

    const current = generation;
    restoreRequest ??= authService
      .restore()
      .then((value) => {
        if (current === generation) {
          user.value = value;
          initialized.value = true;
        }
      })
      .catch((error: unknown) => {
        if (current !== generation) return;
        if (
          axios.isAxiosError(error) &&
          error.response?.status === HttpStatus.Unauthorized
        ) {
          clear();
          return;
        }
        throw error;
      })
      .finally(() => {
        restoreRequest = null;
      });

    await restoreRequest;
  }

  /** Keep only public user state after the server sets the cookie. */
  async function login(payload: LoginPayload): Promise<void> {
    const result = await authService.login(payload);
    generation++;
    user.value = result.user;
    initialized.value = true;
  }

  /** Retain local state on transport failure so revocation remains retryable. */
  async function logout(): Promise<void> {
    await authService.logout();
    clear();
  }

  return { user, initialized, clear, restore, login, logout };
});
