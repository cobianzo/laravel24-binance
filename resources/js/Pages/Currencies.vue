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

                        <nav class="flex justify-start mb-4" aria-label="Tabs">
                            <button @click="selectedTab = 'tab-favourites'" 
                                :class="{ 'bg-gray-100  dark:bg-white-700 text-accent hover:text-accent dark:text-dark': selectedTab === 'tab-favourites' }"
                                class="py-2 px-4 rounded-lg text-gray-500 hover:text-gray-800">Favourites</button>
                            <button @click="selectedTab = 'tab-portfolio'" 
                                :class="{ 'bg-gray-100  dark:bg-white-700 text-accent hover:text-accent dark:text-dark': selectedTab === 'tab-portfolio' }"
                                class="py-2 px-4 rounded-lg text-gray-500 hover:text-gray-800">Portfolio</button>
                        </nav>
                        <!-- Paneles de contenido para desktop -->
                        <div class="mt-4">
                            <div v-show="selectedTab === 'tab-favourites'" class="p-4 mb-2 bg-white rounded-lg shadow-md">
                                <TickersList :tickersWithPrice="favTickersWithPrice" :deleteTicker="deleteTicker" :btnFunction="updateFavTickersFrom" />
                            </div>
                            <div v-show="selectedTab === 'tab-portfolio'" class="p-4 mb-2 bg-white rounded-lg shadow-md text-dark">
                                Contenido del panel 2
                                <BalancesList :balances="balances" :selectBalance="selectBalance" />
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
import TickersList from './Currencies/TickersList.vue';
import BalancesList from './Currencies/BalancesList.vue';

// Inertia dependencies
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { TickerType, TickerPriceType, BalanceType } from '@/types/ticker';

// Vue dependencies
import { onMounted, watchEffect, ref } from 'vue';

// Libraries
import axios from 'axios';

// Internal dependencies (binanceApi)
import { getBinancePrice, getUserBalances } from '@/api/binanceApi';

// This comes from the PHP.
const props = defineProps<{
    favTickers: string[],
    test: string
}>();

// reactive states (data)
const allTickers = ref<TickerType[]>([]);
const favTickersReactive = ref<string[]>(props.favTickers);
const favTickersWithPrice = ref<TickerPriceType[]|null>(null);
const balances = ref<BalanceType[]|null>(null);

// for ui:
const selectedTab = ref<string>('tab-favourites');

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
        console.log('TODELETE: Retrieve fav tickers from backend', response);
        favTickersReactive.value = response.data;
    } );;
    selectedTab.value = 'tab-favourites';
}

function deleteTicker(tickerSymbol: string): void {
    console.log('CALLED deleteTuicker', tickerSymbol);
    axios.delete<string[]>(`/user/fav-tickers/${tickerSymbol}`).then(response => {
        console.log(`TODELETE: Deleted ${tickerSymbol} fav ticker from backend`, response);
        updateFavTickersFrom();
    } );;
}

function selectBalance(balance: BalanceType) : void {
    console.log('TODELET> selected: ', balance);
}

onMounted(async () => {

    getUserBalances().then((response: BalanceType[]) => {
        console.log('TODELETE: Retrieve balances from backend', response);
        balances.value = response;
    });

    // initialize the fav currencies from bakckend
    favTickersReactive.value = props.favTickers;

    // and initialize all tickets for the lookup @TODO: they could be initialized on focus
    try {
        const allTickersResponse = await axios.get<TickerType[]>(`/binance/alltickers`);
        allTickers.value = allTickersResponse.data;
    } catch (error) {
        throw error;
    }    
    
}); // end Mount
</script>

<style scoped>
</style>

