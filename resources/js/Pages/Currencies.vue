<template>
    <Head title="Currencies" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Currencies</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h1 class="text-3xl font-bold mb-4">Currencies</h1>
                        <div class="p-4 bg-gray-100 text-dark font-bold rounded-lg shadow-md flex-col justify-between items-center">
                            Add
                            <AddFavTicker :tickers="allTickers" :test="test" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="currency in currencies"
                                :key="currency.symbol"
                                class="p-4 bg-gray-100 rounded-lg shadow-md flex justify-between items-center"
                            >
                                <span class="font-semibold text-xl text-dark">{{ currency.symbol }}</span>
                                <span class="text-success font-bold text-xl">{{ currency.price }}</span>
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
import { onMounted, ref } from 'vue';

// Internal dependencies
import { getBinancePrice } from '@/api/binanceApi'; // Importa la función del módulo binanceApi

// This comes from the PHP.
const props = defineProps<{
    favTickers: string[],
    allTickers: TickerType[] | null,
    test: string
}>();

// fav currencies with all the info for current user.
const currencies = ref<TickerPriceType[]>([]);

onMounted(async () => {
    
    console.log('all TEST: ', props.test );
    console.log('all tickers: ', props.allTickers );

    // async filling of the price for every fav currency.
    // @TODO: get current amount in user's account.
    props.favTickers.forEach( async function(s) {
        const price = await getBinancePrice(s);
        if (price) {
            currencies.value.push(price as TickerPriceType);
        }
    });

    // not warranty that currencies.value is completed at this line.

    
}); // end Mount
</script>

<style scoped>

</style>
