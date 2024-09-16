<template>
  <div class="ticker-card p-4 bg-gray-100 rounded-lg shadow-md flex justify-between items-center cursor-pointer border transition-opacity hover:border-accent"
    :class="{
      'border-accent': props.isSelected,
      'border-transparent': !props.isSelected,
      'opacity-20': props.ticker.isDeleting
    }"
    :data-symbol="ticker.symbol"
    >
    <span class="ticker-text font-semibold text-xl text-dark">{{ ticker.symbol }}</span>
    <slot />
  </div>
</template>

<script setup lang="ts">
import { TickerPriceType, TickerType } from '@/types/ticker';
import { defineProps } from 'vue';

const props = defineProps<{
  ticker: TickerPriceType,
  isSelected: boolean,
}>();
  

</script>

<style scoped>
.ticker-card {
  /* your styles here */
}
</style>
