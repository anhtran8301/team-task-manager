<script setup lang="ts">
const model = defineModel<string | number | null>();
</script>

<template><v-textarea v-model="model" /></template>
