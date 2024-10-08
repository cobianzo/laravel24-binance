<script setup lang="ts">
  import { TickerPriceType } from '@/types/ticker';
  import draggable from 'vuedraggable';
  import TickerCard from './TickerCard.vue';
  
  // propd coming from the parent
  const props = defineProps<{
    tickersWithPrice: TickerPriceType[] | null,
    deleteTicker: (arg0: string) => void,
    containerId?: string | null | undefined,
    selectedTicker: string,
    dragEndFunction: (arg0: CustomEvent) => void
    btnFunction: () => void, // button on the bottom just for debugging purposes
  }>();

  // methods
  const emit = defineEmits(['selectCurrentTicker'])  // Define the emitted events

</script>
<template>
  <div :id="props.containerId || 'ticker-container'" class="ticker-container">
    <div v-if="props.tickersWithPrice && !props.tickersWithPrice.length"
        class="p-4 bg-gray-100 rounded-lg shadow-md flex justify-center items-center">
        <span class="font-semibold text-xl text-dark">You don't have any favourite tickers selected yet.</span>
    </div>
    <div v-if="props.tickersWithPrice === null">
        <p>Loading...</p>
    </div>
    <draggable
        v-model="props.tickersWithPrice"
        itemKey="id"
        @end="props.dragEndFunction"
        class="grid grid-cols-1 md:grid-cols-2 gap-4"
      >
      <template #item="{ element }">
        
        <TickerCard :ticker="element" :key="element.symbol" 
          @click="$emit('selectCurrentTicker', element.symbol)"
          :isSelected="props.selectedTicker === element.symbol"
        >
          <span class="text-success font-bold text-xl">{{ element.price }}</span>
          <button
            @click="deleteTicker(element.symbol);"
            class="inline-flex items-center px-4 py-2 bg-red-500 border rounded-md font-semibold text-xs text-white dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-500 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150" 
            >
            Remove
          </button>
        </TickerCard>
      
      </template>
    </draggable>
    <div class="col-span-2 p-4 flex justify-center items-center">
        <button @click="props.btnFunction" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <!-- This can be deleted. It was created for debug purposes -->
            Update fav tickers list
        </button>
    </div>
  </div>
</template>

<style scoped>
  .ticker-container.loading .ticker-text {
    opacity: 0.1;
  }
</style>
