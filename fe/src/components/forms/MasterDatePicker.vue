<script setup lang="ts">
import { computed, ref } from "vue";

const model = defineModel<string | null>();

defineProps<{ label: string; errorMessages?: string[]; disabled?: boolean }>();

const open = ref(false);
const selected = computed(() =>
  model.value ? new Date(`${model.value}T00:00:00`) : undefined,
);

function select(value: unknown) {
  if (!(value instanceof Date) || Number.isNaN(value.getTime())) return;
  model.value = `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, "0")}-${String(value.getDate()).padStart(2, "0")}`;
  open.value = false;
}
</script>

<template>
  <v-menu v-model="open" :close-on-content-click="false" :disabled="disabled">
    <template #activator="{ props }">
      <v-text-field
        v-bind="props"
        :model-value="model"
        :label="label"
        :error-messages="errorMessages"
        :disabled="disabled"
        prepend-inner-icon="mdi-calendar-outline"
        readonly
        clearable
        @click:clear="model = null"
      />
    </template>
    <v-date-picker
      :model-value="selected"
      color="primary"
      @update:model-value="select"
    />
  </v-menu>
</template>
