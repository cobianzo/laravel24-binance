<script setup lang="ts">
  import { ref } from 'vue';
  // propd coming from the parent
  const props = defineProps<{
    selectedTicker: string
  }>();

  // state. For every currency in the portfolio, we ask to biance the price in USDT
  const mivar = ref<boolean>(false);


</script>
<template>
  <div class="w-full p-4 bg-gray-100 text-dark font-bold rounded-lg shadow-md flex-col justify-between items-center">

    
    <p>{{props.selectedTicker}}</p>
  </div>
</template>

<style scoped>

</style>
