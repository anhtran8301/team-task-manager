<script setup lang="ts">
import MasterInput from "./MasterInput.vue";
import MasterTextarea from "./MasterTextarea.vue";
import MasterSelect from "./MasterSelect.vue";
import MasterDatePicker from "./MasterDatePicker.vue";
import type { FormField, FormValues } from "@/typings/form";
import type { FieldErrors } from "@/typings/api";

const values = defineModel<FormValues>({ required: true });

defineProps<{
  fields: FormField[];
  errors?: FieldErrors;
  disabled?: boolean;
}>();

const dateValue = (key: string): string | null =>
  typeof values.value[key] === "string" ? values.value[key] : null;

const required = (value: unknown) =>
  (value !== null && value !== undefined && String(value).trim().length > 0) ||
  "This field is required.";
</script>

<template>
  <div class="d-flex flex-column ga-5">
    <template v-for="field in fields" :key="field.key">
      <MasterDatePicker
        v-if="field.type === 'date'"
        :model-value="dateValue(field.key)"
        :label="field.label"
        :error-messages="errors?.[field.key]"
        :disabled="disabled || field.disabled"
        @update:model-value="values[field.key] = $event ?? null"
      />
      <component
        :is="
          field.type === 'select'
            ? MasterSelect
            : field.type === 'textarea'
              ? MasterTextarea
              : MasterInput
        "
        v-else
        v-model="values[field.key]"
        :label="field.label"
        :items="field.items"
        :type="field.type === 'password' ? 'password' : 'text'"
        :rules="field.required ? [required] : []"
        :error-messages="errors?.[field.key]"
        :disabled="disabled || field.disabled"
        :maxlength="field.maxlength"
        :rows="field.type === 'textarea' ? 3 : undefined"
      />
    </template>
  </div>
</template>
