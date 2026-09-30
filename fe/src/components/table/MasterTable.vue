<script setup lang="ts">
import { PAGE_SIZE_OPTIONS } from "@/common/constants";
import { displayDueDate } from "@/helpers/date";
import type { Task, TableSort } from "@/typings/task";
defineProps<{
  items: Task[];
  total: number;
  loading: boolean;
  page: number;
  perPage: number;
  sortBy: TableSort[];
}>();
defineEmits<{
  "update:sortBy": [value: TableSort[]];
  "update:page": [value: number];
  "update:perPage": [value: number];
}>();
const headers = [
  { title: "Title", key: "title", sortable: true },
  { title: "Assignee", key: "assignee.name", sortable: true },
  { title: "Status", key: "status", sortable: false },
  { title: "Due date", key: "due_date", sortable: true },
  { title: "Actions", key: "actions", sortable: false, align: "end" as const },
];
</script>
<template>
  <v-data-table-server
    :headers="headers"
    :sort-by="sortBy"
    multi-sort
    :items="items"
    :items-length="total"
    :loading="loading"
    :page="page"
    :items-per-page="perPage"
    :items-per-page-options="PAGE_SIZE_OPTIONS"
    @update:sort-by="$emit('update:sortBy', $event)"
    @update:page="$emit('update:page', $event)"
    @update:items-per-page="$emit('update:perPage', $event)"
  >
    <template #item.title="{ item }">
      <div class="task-title py-3">{{ item.title }}</div>
    </template>
    <template #item.status="{ item }">
      <slot name="status" :item="item" />
    </template>
    <template #item.due_date="{ item }">
      {{ displayDueDate(item.due_date) }}
    </template>
    <template #item.actions="{ item }">
      <slot name="actions" :item="item" />
    </template>
    <template #no-data>
      <div class="py-12 muted">
        <v-icon
          icon="mdi-checkbox-marked-circle-outline"
          size="36"
          class="mb-3"
        />
        <p>No tasks found. Try a different filter or create a task.</p>
      </div>
    </template>
  </v-data-table-server>
</template>
