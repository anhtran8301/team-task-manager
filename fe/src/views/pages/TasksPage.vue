<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import MainLayout from "@/views/layouts/MainLayout.vue";
import MasterTable from "@/components/table/MasterTable.vue";
import TaskForm from "@/components/TaskForm.vue";
import DialogConfirm from "@/components/DialogConfirm.vue";
import { useAuthStore } from "@/stores/auth";
import { useTaskStore } from "@/stores/task";
import taskService from "@/services/taskService";
import { taskSort } from "@/helpers/taskQuery";
import { apiError } from "@/helpers/apiError";
import type { Task, TaskStatus, TableSort } from "@/typings/task";
import { taskStatuses, APP_CONFIG } from "@/common/constants";
import { Role } from "@/common/enums";
import { isAdmin, hasRole, canManageTask } from "@/helpers/role";
import type { User } from "@/typings/user";

const auth = useAuthStore();
const store = useTaskStore();

const users = ref<User[]>([]);
const userError = ref("");
const usersLoading = ref(true);
const page = ref(1);
const perPage = ref<number>(APP_CONFIG.pageSize);
const search = ref("");
const settledSearch = ref("");
const status = ref<TaskStatus[]>([]);
const sortBy = ref<TableSort[]>([]);
const assignedTo = ref<number[]>([]);
const formOpen = ref(false);
const editing = ref<Task | null>(null);
const deleting = ref<Task | null>(null);
const deleteOpen = ref(false);
const deleteBusy = ref(false);
const deleteError = ref("");
const notification = ref("");

const filters = computed(() => ({
  page: page.value,
  per_page: perPage.value,
  search: settledSearch.value || undefined,
  status: status.value ?? [],
  assigned_to: assignedTo.value ?? [],
  sort: taskSort(sortBy.value),
}));

let timer: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    settledSearch.value = (value ?? "").trim();
  }, APP_CONFIG.searchDebounce);
});

watch(
  [settledSearch, status, assignedTo, perPage, sortBy],
  () => {
    page.value = 1;
  },
  { flush: "sync" },
);
watch(
  filters,
  () => {
    void store.fetch(filters.value);
  },
  { immediate: true },
);

async function loadUsers() {
  usersLoading.value = true;
  userError.value = "";
  try {
    users.value = await taskService.users();
  } catch (error) {
    userError.value = apiError(error).message;
  } finally {
    usersLoading.value = false;
  }
}

onMounted(loadUsers);

onBeforeUnmount(() => {
  clearTimeout(timer);
  store.reset();
});

function edit(task: Task | null) {
  editing.value = task;
  formOpen.value = true;
}

function confirmDelete(task: Task) {
  deleting.value = task;
  deleteError.value = "";
  deleteOpen.value = true;
}

async function saved() {
  notification.value = editing.value ? "Task updated." : "Task created.";
  await refresh();
}

async function refresh() {
  await store.fetch(filters.value);
  if (!store.error && page.value > store.lastPage) page.value = store.lastPage;
}

async function remove() {
  if (!deleting.value || deleteBusy.value) return;
  deleteBusy.value = true;
  try {
    await taskService.remove(deleting.value.id);
    deleteOpen.value = false;
    notification.value = "Task deleted.";
    await refresh();
  } catch (error) {
    deleteError.value = apiError(error).message;
  } finally {
    deleteBusy.value = false;
  }
}
</script>

<template>
  <MainLayout>
    <div class="d-flex flex-wrap align-center justify-space-between ga-4 mb-8">
      <div>
        <p class="eyebrow mb-3">Workspace / Tasks</p>
        <h1 class="page-title">
          {{ isAdmin(auth.user) ? "Team tasks" : "My tasks" }}
        </h1>
        <p class="muted mt-3">
          A clear view of what needs doing, and who's doing it.
        </p>
      </div>
      <v-btn
        v-if="isAdmin(auth.user) || hasRole(auth.user, Role.User)"
        color="primary"
        size="large"
        prepend-icon="mdi-plus"
        :disabled="usersLoading || !!userError"
        @click="edit(null)"
      >
        New task
      </v-btn>
    </div>
    <v-alert v-if="userError" type="error" variant="tonal" class="mb-4">
      {{ userError }}
      <v-btn variant="text" @click="loadUsers"> Retry loading assignees </v-btn>
    </v-alert>
    <v-alert v-if="store.error" type="error" variant="tonal" class="mb-4">
      {{ store.error }}
      <v-btn variant="text" @click="refresh">Retry</v-btn>
    </v-alert>
    <v-card class="panel">
      <div class="pa-5 d-flex flex-wrap align-center ga-4">
        <v-text-field
          v-model="search"
          label="Search by title"
          persistent-hint
          prepend-inner-icon="mdi-magnify"
          clearable
          style="min-width: 210px; flex: 2"
          @click:clear="search = ''"
        />
        <v-select
          v-model="status"
          multiple
          chips
          closable-chips
          :items="taskStatuses"
          label="Status"
          clearable
          hide-details
          style="min-width: 160px; flex: 1"
        />
        <v-select
          v-if="isAdmin(auth.user)"
          v-model="assignedTo"
          multiple
          chips
          closable-chips
          :items="users"
          item-title="name"
          item-value="id"
          label="Assignee"
          clearable
          hide-details
          style="min-width: 180px; flex: 1"
        />
        <v-btn
          icon="mdi-refresh"
          variant="text"
          aria-label="Refresh tasks"
          :loading="store.loading"
          @click="refresh"
        />
      </div>
      <v-divider />
      <MasterTable
        v-model:sort-by="sortBy"
        v-model:page="page"
        v-model:per-page="perPage"
        :items="store.tasks"
        :total="store.total"
        :loading="store.loading"
      >
        <template #status="{ item }">
          <v-chip
            size="small"
            :color="taskStatuses.find((s) => s.value === item.status)?.color"
            variant="tonal"
          >
            {{ taskStatuses.find((s) => s.value === item.status)?.title }}
          </v-chip>
        </template>
        <template #actions="{ item }">
          <div class="d-flex justify-end">
            <v-btn
              v-if="canManageTask(auth.user, item)"
              icon="mdi-pencil-outline"
              size="small"
              variant="text"
              :aria-label="`Edit ${item.title}`"
              :disabled="usersLoading || !!userError"
              @click="edit(item)"
            /><v-btn
              v-if="canManageTask(auth.user, item)"
              icon="mdi-trash-can-outline"
              size="small"
              variant="text"
              color="error"
              :aria-label="`Delete ${item.title}`"
              @click="confirmDelete(item)"
            />
          </div>
        </template>
      </MasterTable>
    </v-card>
    <p class="text-caption muted mt-4">
      {{
        isAdmin(auth.user)
          ? "You can manage tasks across the entire team."
          : "Only tasks assigned to you appear in this workspace."
      }}
    </p>
    <TaskForm
      v-if="auth.user"
      v-model="formOpen"
      :task="editing"
      :users="users"
      :actor="auth.user"
      @saved="saved"
    />
    <DialogConfirm
      v-model="deleteOpen"
      :task="deleting"
      :busy="deleteBusy"
      :error="deleteError"
      @confirm="remove"
    />
    <v-snackbar
      :model-value="!!notification"
      color="primary"
      :timeout="APP_CONFIG.notificationDuration"
      @update:model-value="notification = ''"
    >
      {{ notification }}
    </v-snackbar>
  </MainLayout>
</template>
