<script setup lang="ts">
import type { Task } from "@/typings/task";

const open = defineModel<boolean>();

defineProps<{ task: Task | null; busy: boolean; error: string }>();
defineEmits<{ confirm: [] }>();
</script>

<template>
  <v-dialog v-model="open" max-width="440" :persistent="busy">
    <v-card class="pa-6">
      <h2 class="text-h6 mb-3">Delete task?</h2>
      <p class="muted mb-4">“{{ task?.title }}” will be permanently deleted.</p>
      <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
        {{ error }}
      </v-alert>
      <div class="d-flex justify-end ga-2">
        <v-btn :disabled="busy" variant="text" @click="open = false">
          Cancel </v-btn
        ><v-btn color="error" :loading="busy" @click="$emit('confirm')">
          Delete task
        </v-btn>
      </div>
    </v-card>
  </v-dialog>
</template>
