<template>
    <!-- @TODO: add a loading status when on the watch, when the list is being calculated. -->
    <Head title="Currencies" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Currencies</h2>
        </template>
        
        <div id="currencies-container" class="py-12" :class="{ 'loading': loading === 'page' }">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex flex-row w-full gap-4">
                            <div class="flex-grow items-center justify-center border-accent border-left-16 flex">
                                <TradingPanel :selectedTicker="selectedTicker" 
                                              @selectCurrentTicker="handleSelectCurrentTicker"
                                              :allTickers="allTickers"
                                              :balances="balances"
                                              :updateAllBalances="updateAllBalances"
                                              :binancePublicKey="binancePublicKey"
                                />
                            </div>
                        </div> 
                        

                        <Spinner v-if="loading === 'portfolio-loading'" :extraClass="'absolute left-1/2'" />
                        <p>{{ `DEBUG TODELETE ${loading}`  }}</p>

                        <nav class="flex justify-start mb-4" aria-label="Tabs">
                            <button @click="selectedTab = 'tab-favourites'; saveOptions({ selectedTab: 'tab-favourites' });" 
                                :class="{ 'dark:bg-white-700 text-gray-900 hover:text-gray-900 dark:text-white hover:dark:text-white border-b border-accent': selectedTab === 'tab-favourites' }"
                                class="py-2 px-4 text-gray-500 hover:text-gray-800 dark:text-gray-100 hover:dark:text-accent">Favourites</button>
                            <button @click="activateBalancesTab" 
                                :class="{ 'dark:bg-white-700 text-gray-900 hover:text-gray-900 dark:text-white hover:dark:text-white border-b border-accent': selectedTab === 'tab-portfolio' }"
                                class="py-2 px-4 text-gray-500 hover:text-gray-800 dark:text-gray-100 hover:dark:text-accent">Portfolio</button>
                        </nav>
                        <!-- Paneles de contenido para desktop -->
                        <div class="mt-4">
                            <div v-show="selectedTab === 'tab-favourites'" class="p-4 mb-2 bg-white rounded-lg shadow-md">
                                <div class="p-4 bg-gray-100 text-dark font-bold rounded-lg shadow-md flex-col justify-between items-center mb-4">
                                    <AddFavTicker 
                                        :allTickers="allTickers" 
                                        :updateFavTickersFrom="updateFavTickersFrom" 
                                        :exclude="favTickersReactive"
                                        :test="test" />
                                </div>
                                <TickersList 
                                    :tickersWithPrice="favTickersWithPrice"
                                    :deleteTicker="deleteFavTicker" 
                                    :selectedTicker="selectedTicker"
                                    :dragEndFunction="saveSortedTickers"
                                    :containerId="favourite-tickers"
                                    @selectCurrentTicker="handleSelectCurrentTicker"
                                    :btnFunction="updateFavTickersFrom"
                                    />
                            </div>
                            <div v-show="selectedTab === 'tab-portfolio'" 
                                class="p-4 mb-2 bg-white rounded-lg shadow-md text-dark"
                                :class="{ 'opacity-25': loading === 'portfolio-loading' }"
                                >
                                
                                <BalancesList 
                                    :balances="balances"
                                    :selectBalance="selectBalance"
                                    :updateLoading="updateLoading" 
                                    :allTickers="allTickers"
                                />
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
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { TickerType, TickerPriceType, BalanceType } from '@/types/ticker';

// Vue dependencies
import { onMounted, watchEffect, ref, nextTick } from 'vue';

// Libraries
import axios from 'axios';

// Internal dependencies (binanceApi and Localstorage options for the selected tab)
import { getBinancePrice, getUserBalances } from '@/api/binanceApi';
import { getOptions, saveOptions } from '@/utils/localStorage-CRUD';
import Spinner from '@/Components/Spinner.vue';
import TradingPanel from './Currencies/TradingPanel.vue';


// This comes from the PHP. It's updated over favTickersReactive with axios calls too as we operate with them.
const props = defineProps<{
    favTickers: string[],
    binancePublicKey: string
}>();

// reactive states (data)
// ======================
// => Fav tickers tab
const allTickers = ref<TickerType[]>([]);
const favTickersReactive = ref<string[]>(props.favTickers);
const favTickersWithPrice = ref<TickerPriceType[]|null>(null);
// => Balances (portfolio tab)
const balances = ref<{ [symbol: string]: BalanceType } |null>(null);
// => Trading panel
const selectedTicker = ref<string>(getOptions('selectedTicker')?? '');

// for ui:
const selectedTab = ref<string>(getOptions('selectedTab')?? 'tab-favourites');
const loading = ref<string>('');
const updateLoading = (val: string) => { loading.value = val };

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
                results.push({
                  symbol: s,
                  price: price  
                } as TickerPriceType);
            }
        }
    }
  // Once all prices are fetched, update the reactive array
  favTickersWithPrice.value = results;
});

// functions and methods
// =====================

// => Favourites Tab
function updateFavTickersFrom() {
    // get the list
    axios.put<string[]>(`/user/fav-tickers`).then(response => {
        console.log('TODELETE: Retrieve fav tickers from backend', response);
        favTickersReactive.value = response.data;
    } );;
    selectedTab.value = 'tab-favourites';
    saveOptions({ selectedTab: 'tab-favourites' });
}

function saveSortedTickers(event: CustomEvent) {
    // We need to srt the favourite ticket symbols in the order they are shown nos
    console.log('DRAG FINSIHED', event);

    // @TODO: hide list while reordering. this could be done with state vars ...
    loading.value = '#ticker-container';
    const domContainer = document.querySelector('#ticker-container');
    if (domContainer) domContainer.classList.add('loading');
    
    const { oldIndex, newIndex }: { oldIndex: number, newIndex: number } = event as any;

    // swap positions
    [favTickersReactive.value[oldIndex], favTickersReactive.value[newIndex]] =
      [favTickersReactive.value[newIndex], favTickersReactive.value[oldIndex]];
    
    const newListAsString: string = Object.values(favTickersReactive.value).join(',');
    console.log('Preparing to save new order', newListAsString );
    if (newListAsString || newListAsString === '') {
        axios.post('/user/fav-tickers', { list: newListAsString? newListAsString : '' })
            .then((response) => {
                updateFavTickersFrom();
                console.log('Salvado nuevo orden', response.data); // TODELETE
            })
            .catch((error) => {
                console.error('Error al añadir el ticker:', error);
            })
            .finally(() => {
                if (domContainer) setTimeout( () => { 
                    loading.value = '';
                    domContainer.classList.remove('loading');
                } , 1500 );
            })
            ;
    }
    
}

function deleteFavTicker(tickerSymbol: string): void {
    console.log('CALLED deleteTuicker to PHP (@todelete)', tickerSymbol);
    
    // UI: this will add a transition of opacity for the second that it takes to remove the card.
    const index: number|undefined = favTickersWithPrice.value?.findIndex(t => t.symbol === tickerSymbol);
    if (index !== undefined && index !== -1) favTickersWithPrice.value![index].isDeleting = true;

    axios.delete<string[]>(`/user/fav-tickers/${tickerSymbol}`).then(response => {
        console.log(`TODELETE: Deleted ${tickerSymbol} fav ticker from backend`, response);
        if (selectedTicker.value === tickerSymbol) selectedTicker.value = '';
        updateFavTickersFrom();
    } ).catch((error) => {
        console.error(`TODELETE: Error  Deleteing ${tickerSymbol} fav ticker from backend`, error);
        if (index !== undefined && index !== -1) favTickersWithPrice.value![index].isDeleting = false;
    });
    
}

// => Balances Tab (portfolio)
function selectBalance(balance: string) : void {
    console.log('TODELET> selected: ', balance, balances);
}

function activateBalancesTab() {
    selectedTab.value = 'tab-portfolio';
    saveOptions({ selectedTab: 'tab-portfolio' });

    updateAllBalances();
}

/**
 * @TODO: For some reason this fn is called 3 times instead of one
 * on page load. 
 */
const updateAllBalances = async () => {
    if (balances.value === null) {
        getUserBalances().then((response: { [symbol: string]: BalanceType }) => {
            console.log('TODELETE: Retrieve balances from backend', response);
            balances.value = response;
        }).finally(() => {
    });
    }
}

// => Trading Panel 
function handleSelectCurrentTicker( symbol: string ) : void {
    selectedTicker.value = symbol;
    saveOptions({ selectedTicker: symbol });
}


// Lifecycle hooks
// ================
onMounted(async () => {

    // initialize the fav currencies from bakckend
    favTickersReactive.value = props.favTickers;

    // and initialize all tickets for the lookup @TODO: they could be initialized on focus
    // we can also send them from PHP directly to this page.
    try {
        const allTickersResponse = await axios.get<TickerType[]>(`/binance/alltickers`);
        console.log('All tickers loaded on Mount instead of from PHP, ', allTickersResponse.data);
        allTickers.value = allTickersResponse.data;
    } catch (error) {
        throw error;
    }    
    
    if (selectedTab.value === 'tab-portfolio') {
        // if on page load the balances are selected, we need to initialize them with a binance call.
        activateBalancesTab()
    }
}); // end Mount
</script>

<style scoped>
    #currencies-container.loading {
        opacity: 0.5;
    }
    #ticker-container.loading {
        opacity: 0.5;
    }
</style>

