<template>
    <Head title="Welcome" />
    <div class="container mx-auto bg-gray-50 min-h-screen p-4">
        <h1 class="text-3xl font-bold">Currencies</h1>
        <h2>{{ test }}</h2>
        <div class="container mx-auto p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
                v-for="currency in currencies"
                :key="currency.symbol"
                class="p-4 bg-gray-100 rounded-lg shadow-md flex justify-between items-center"
            >
                <span class="font-semibold text-xl">{{ currency.symbol }}</span>
                <span class="text-success font-bold text-xl">{{ currency.price }}</span>
            </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
// Inertia dependencies
import { Head, Link } from '@inertiajs/vue3';

// Vue dependencies
import { onMounted, ref } from 'vue';

// Internal libraries
import axios from 'axios';

// Internal dependencies
import { getBinancePrice } from '@/api/binanceApi'; // Importa la función del módulo binanceApi


const props = defineProps<{
    favCurrencies: string[],
}>();


const currencies = ref([]);

onMounted(async () => {
    // retrieve currencies from Binance API using axios
    const fetchPrice = async (symbol) => {
            try {
                const data = await getBinancePrice(symbol);
                return data;
            } catch (error) {
                console.error('Failed to fetch price:', error);
                return null;
            }
        };

    const mapRequests = props.favCurrencies?.map(async (pair) => {
        const [origin, destination] = pair.split('/');
        const binanceReponse = await fetchPrice(origin+destination);
        console.log('todelete: ', pair, binanceReponse);
        return {
            origin,
            destination,
            ...binanceReponse,
        }
    });

    const arrayCurrencies = await Promise.all(mapRequests);
    currencies.value = arrayCurrencies;
    console.log('todelete2: ', arrayCurrencies);
}); // end Mount
</script>

<style scoped>

</style>
