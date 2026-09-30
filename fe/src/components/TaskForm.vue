<script setup lang="ts">
import { computed, ref, watch } from "vue";
import MasterForm from "./forms/MasterForm.vue";
import taskService from "@/services/taskService";
import { apiError } from "@/helpers/apiError";
import type { Task, TaskPayload } from "@/typings/task";
import { taskStatuses, APP_CONFIG } from "@/common/constants";
import { TaskStatus } from "@/common/enums";
import { isAdmin } from "@/helpers/role";
import type { User } from "@/typings/user";
import type { FieldErrors } from "@/typings/api";
import type { FormField, FormValues } from "@/typings/form";

const open = defineModel<boolean>({ required: true });
const props = defineProps<{ task: Task | null; users: User[]; actor: User }>();
const emit = defineEmits<{ saved: [] }>();
const values = ref<FormValues>({});
const busy = ref(false);
const errors = ref<FieldErrors>({});
const message = ref("");

const fields = computed<FormField[]>(() => [
  {
    key: "title",
    label: "Title",
    type: "text",
    required: true,
    maxlength: APP_CONFIG.titleLimit,
  },
  {
    key: "description",
    label: "Description (optional)",
    type: "textarea",
    maxlength: APP_CONFIG.descriptionLimit,
  },
  {
    key: "status",
    label: "Status",
    type: "select",
    items: taskStatuses,
    required: true,
  },
  {
    key: "assigned_to",
    label: "Assignee",
    type: "select",
    required: true,
    disabled: !isAdmin(props.actor),
    items: props.users.map((u) => ({ title: u.name, value: u.id })),
  },
  { key: "due_date", label: "Due date (optional)", type: "date" },
]);

watch(open, (value) => {
  if (!value) return;
  const task = props.task;
  values.value = {
    title: task?.title ?? "",
    description: task?.description ?? "",
    status: task?.status ?? TaskStatus.Todo,
    assigned_to: task?.assigned_to ?? props.actor.id,
    due_date: task?.due_date ?? null,
  };
  errors.value = {};
  message.value = "";
});

async function save() {
  if (busy.value) return;
  busy.value = true;
  errors.value = {};
  message.value = "";
  const payload: TaskPayload = {
    title: String(values.value.title).trim(),
    description: String(values.value.description ?? "").trim() || null,
    status: values.value.status as TaskPayload["status"],
    assigned_to: Number(values.value.assigned_to),
    due_date: values.value.due_date as string | null,
  };
  try {
    if (props.task) await taskService.update(props.task.id, payload);
    else await taskService.create(payload);
    open.value = false;
    emit("saved");
  } catch (error) {
    const failure = apiError(error);
    errors.value = failure.fields;
    message.value = failure.message;
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <v-dialog v-model="open" max-width="580" :persistent="busy" scrollable>
    <v-card>
      <v-card-title class="px-7 pt-6">
        {{ task ? "Edit task" : "Create task" }}
      </v-card-title>
      <v-card-text class="px-7 pb-7 pt-4">
        <v-alert v-if="message" type="error" variant="tonal" class="mb-5">
          {{ message }}
        </v-alert>
        <MasterForm
          v-model="values"
          :fields="fields"
          :errors="errors"
          :busy="busy"
          @submit="save"
        >
          <template #actions>
            <v-btn variant="text" :disabled="busy" @click="open = false">
              Cancel </v-btn
            ><v-btn type="submit" color="primary" :loading="busy">
              {{ task ? "Save changes" : "Create task" }}
            </v-btn>
          </template>
        </MasterForm>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>
