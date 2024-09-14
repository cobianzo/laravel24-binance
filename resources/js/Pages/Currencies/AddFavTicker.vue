<template>

  <h2>Add a favourite ticker</h2>

  <p>test: {{ $props.test }}</p>

  <p>Select a ticker. </p>

  <Lookupinput :loading="tickers === null" :items="tickers !== null ? items : []" :selectItem="mySelect" />

</template>

<script setup lang="ts">
import { TickerType } from '@/types/ticker';
import { computed } from 'vue';
import Lookupinput from '@/Components/LookupInput.vue';

const props = defineProps<{
  tickers: TickerType[] | null,
  test: string
}>();

const items = computed(() => 
  props.tickers === null ? [] : props.tickers.map(t => ({ label: t.symbol, value: t.symbol }))
);

function mySelect(item: any): void {
  console.log('ITEMMM' , item);
}
</script>
