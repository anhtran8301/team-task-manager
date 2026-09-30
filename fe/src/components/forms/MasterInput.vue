<script setup lang="ts">
const model = defineModel<string | number | null>();
</script>

<template><v-text-field v-model="model" /></template>
