<script setup lang="ts">
import { ref } from "vue";
import FormManager from "./FormManager.vue";
import type { FormField, FormValues } from "@/typings/form";
import type { FieldErrors } from "@/typings/api";

const values = defineModel<FormValues>({ required: true });

const props = defineProps<{
  fields: FormField[];
  errors?: FieldErrors;
  busy?: boolean;
}>();

const emit = defineEmits<{ submit: [] }>();
const form = ref<{ validate: () => Promise<{ valid: boolean }> }>();

async function submit() {
  if (!props.busy && (await form.value?.validate())?.valid) emit("submit");
}
</script>

<template>
  <v-form ref="form" @submit.prevent="submit">
    <FormManager
      v-model="values"
      :fields="fields"
      :errors="errors"
      :disabled="busy"
    />
    <div class="d-flex justify-end ga-3 mt-7"><slot name="actions" /></div>
  </v-form>
</template>
