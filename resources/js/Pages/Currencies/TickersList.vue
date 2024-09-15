<script setup lang="ts">
  import { TickerPriceType } from '@/types/ticker';
  
  // propd coming from the parent
  const props = defineProps<{
    tickersWithPrice: TickerPriceType[] | null,
    deleteTicker: (arg0: string) => void,
    btnFunction: () => void
  }>();
</script>
<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div v-if="props.tickersWithPrice && !props.tickersWithPrice.length"
        class="p-4 bg-gray-100 rounded-lg shadow-md flex justify-center items-center">
        <span class="font-semibold text-xl text-dark">You don't have any favourite tickers selected yet.</span>
    </div>
    <div v-if="props.tickersWithPrice === null">
        <p>Loading...</p>
    </div>

    <div
        v-if="props.tickersWithPrice !== null"
        v-for="currency in props.tickersWithPrice"
        :key="currency.symbol"
        class="p-4 bg-gray-100 rounded-lg shadow-md flex justify-between items-center cursor-pointer hover:bg-red-100"
        @click="props.deleteTicker(currency.symbol)"
        
    >
        <!-- @TODO: Create this as a separated component -->
        <span class="font-semibold text-xl text-dark">{{ currency.symbol }}</span>
        <span class="text-success font-bold text-xl">{{ currency.price }}</span>
    </div>

    <div class="col-span-2 p-4 flex justify-center items-center">
        <button @click="props.btnFunction" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <!-- This can be deleted. It was created for debug purposes -->
            Update fav tickers list
        </button>
    </div>
  </div>
</template>

<style scoped>

</style>
