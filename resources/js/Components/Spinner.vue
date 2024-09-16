<template>
  <div :id="props.id" :class="`flex items-center justify-center ${extraClass}`">
    <!-- Spinning Wheel -->
    <div class="w-24 h-24 border-4 border-t-transparent border-blue-500 rounded-full animate-spin"></div>
  </div>
</template>

<script setup lang="ts">
  const props = withDefaults(
    defineProps<{
      id?: string;
      extraClass?: string;
    }>(),
    {
      id: "lar24-spinner",
      extraClass: ""
    }
  );
</script>

<style>
</style>
