<template>
    <!-- @TODO: add a loading status when on the watch, when the list is being calculated. -->
    <Head title="Currencies" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Currencies</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h1 class="text-3xl font-bold mb-4">Currencies</h1>
                        <div class="p-4 bg-gray-100 text-dark font-bold rounded-lg shadow-md flex-col justify-between items-center mb-4">
                            <AddFavTicker :allTickers="allTickers" :updateFavTickersFrom="updateFavTickersFrom" :test="test" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-if="!favTickersWithPrice.length" class="p-4 bg-gray-100 rounded-lg shadow-md flex justify-center items-center">
                                <span class="font-semibold text-xl text-dark">You don't have any favourite tickers selected yet.</span>
                            </div>

                            <div
                                v-for="currency in favTickersWithPrice"
                                :key="currency.symbol"
                                class="p-4 bg-gray-100 rounded-lg shadow-md flex justify-between items-center cursor-pointer hover:bg-red-100"
                                @click="deleteTicker(currency.symbol)"
                                
                            >
                                <!-- @TODO: Create this as a separated component -->
                                <span class="font-semibold text-xl text-dark">{{ currency.symbol }}</span>
                                <span class="text-success font-bold text-xl">{{ currency.price }}</span>
                            </div>

                            <div class="col-span-2 p-4 flex justify-center items-center">
                                <button @click="updateFavTickersFrom" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    <!-- This can be deleted. It was created for debug purposes -->
                                    Update fav tickers list
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">

// Partials
import AddFavTicker from './Currencies/AddFavTicker.vue';

// Inertia dependencies
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { TickerType, TickerPriceType } from '@/types/ticker';

// Vue dependencies
import { onMounted, watchEffect, ref } from 'vue';

// Libraries
import axios from 'axios';

// Internal dependencies (binanceApi)
import { getBinancePrice } from '@/api/binanceApi';

// This comes from the PHP.
const props = defineProps<{
    favTickers: string[],
    test: string
}>();

// reactive states (data)
const allTickers = ref<TickerType[]>([]);
const favTickersReactive = ref<string[]>(props.favTickers);
const favTickersWithPrice = ref<TickerPriceType[]>([]);

// computed states (add the price to favTickersReactive)
// Watch or trigger this effect when 'favTickersReactive' changes
watchEffect(async () => {
  const results: TickerPriceType[] = [];

  // Use a for-loop to await each async call before pushing to results
  if (favTickersReactive.value && favTickersReactive.value.length) {
    
      for (const s of favTickersReactive.value) {
            console.log('getting price for ', s);
            if (s) {
                const price = await getBinancePrice(s); // Assuming this is an async function
                results.push(price as TickerPriceType);
            }
        }
    }
  // Once all prices are fetched, update the reactive array
  favTickersWithPrice.value = results;
});

// functions 
function updateFavTickersFrom() {
    axios.put<string[]>(`/user/fav-tickers`).then(response => {
        console.log('Retrieve fav tickers from backend', response);
        favTickersReactive.value = response.data;
    } );;
}

function deleteTicker(tickerSymbol: string): void {
    console.log('CALLED deleteTuicker', tickerSymbol);
    axios.delete<string[]>(`/user/fav-tickers/${tickerSymbol}`).then(response => {
        console.log(`Deleted ${tickerSymbol} fav ticker from backend`, response);
        updateFavTickersFrom();
    } );;
}


onMounted(async () => {

    // initialize the fav currencies from bakckend
    favTickersReactive.value = props.favTickers;

    try {
        const allTickersResponse = await axios.get<TickerType[]>(`/binance/alltickers`);
        allTickers.value = allTickersResponse.data;
    } catch (error) {
        console.error('Error fetching Binance price:', error);
        throw error;
    }
    
    console.log('all tickers: ', allTickers.value );
    
}); // end Mount
</script>

<style scoped>

</style>

