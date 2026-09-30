<script setup lang="ts">
const model = defineModel<string | number | null>();
</script>

<template><v-select v-model="model" /></template>
