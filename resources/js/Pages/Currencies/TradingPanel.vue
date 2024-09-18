<script setup lang="ts">
  // VueJS deps
  import { ref, onMounted, onBeforeUnmount, watch, computed, reactive } from 'vue';

  // internal deps
  import { TickerPriceType } from '@/types/ticker';
  import { getBinancePrice } from '@/api/binanceApi';
  import { formatNumber, getAssetCurrencyFromTicker } from '@/utils/helpers';
  import { TickerType, BalanceType } from '@/types/ticker';

  import { getUserBalances } from '@/api/binanceApi';
  import { getOptions, saveOptions } from '@/utils/localStorage-CRUD';

  // PROPS coming from the parent
  // ============
  const props = defineProps<{
    selectedTicker: string,
    allTickers: TickerType[] | null,
    balances: BalanceType[] | null,
    updateAllBalances: () => void
  }>();

  
  // Reactive data.
  // =============
  const price = ref<number>(0);
  const loadingPrice = ref<number>(0);
  const intervalId = ref<number>(0);
  const intervalIncrement = ref<number>(0);
  const speedInterval = ref<number>(5); // 0: stop, 1, slow(every 10 secs), 5: medium, 10: fast ( every 1 sec )
  const timeInterval  = computed<number>(() => speedInterval? (10000 - ((speedInterval.value / 10) * (10000 - 1000))) : 1000000 );

  // balance
  const selectedTickerInfo = ref<TickerType | null>();

  // Trade options
  const percentages = reactive<{ gain: number,  loss: number }>({
    gain: getOptions( 'tradePercentages' )?.gain?? 0, // % of gain
    loss: getOptions( 'tradePercentages' )?.loss?? 0
  });
  const theTrade = reactive<{ amount: number }>({
    amount: 0
  })

  // METHODS and functions
  // ============

  // setInterval to retrieve the up to date price of the selectedTicker every x seconds.
  const updateBinancePrice = async function() {
    if (! props.selectedTicker) {
      price.value = 0;
      return;
    }
    loadingPrice.value = intervalIncrement.value;
    getBinancePrice(props.selectedTicker).then((tickerAndPrice: TickerPriceType) => {
      if (loadingPrice.value === intervalIncrement.value) {
        price.value = tickerAndPrice.price;  
        loadingPrice.value = 0;
        intervalIncrement.value++;
        console.log('TODELET.Updated price on interval', tickerAndPrice.price, intervalIncrement.value);
      }
    })
  }

  
  const updateBalanceSelectedTicker = async function() {
    getUserBalances().then((response: BalanceType[]) => {
      console.log('TODELETE: For the current Ticker Asset currency: Retrieve balances from backend', response);
      // get current asset 
      const buyingCurrency = getAssetCurrencyFromTicker( props.selectedTicker, props.allTickers );
      selectedTickerInfo.value = buyingCurrency;
      if (buyingCurrency && selectedTickerInfo && selectedTickerInfo.value) {
        // response is an Array of { symbol, amount }
        const balance = response.find( (balance: BalanceType) => balance.symbol === buyingCurrency?.asset);
        buyingCurrency.balance = balance?.amount ?? 0;
        selectedTickerInfo.value = buyingCurrency;
        
        // we can update also the balance in the global balance reactive var:
        // @TODO: we shouldnt update it directly, but using `emit` or an update prop function
        if (props.balances) {
          const index = props.balances?.findIndex( (balance: BalanceType) => balance.symbol === selectedTickerInfo.value?.asset );
          if (index !== -1) {
            props.balances[index].amount = balance?.amount ?? 0;
          }
        }
      } else {
        console.error('could not calculate balance for current buying currency, because we don\'t have ', 
          buyingCurrency, props.selectedTicker, props.allTickers)
      }

    }).finally(() => {
      // stuff
    });
    
  }
  
  // Logic for updating the price.
  // @TODO: ideally we'll use websockers, with an external server like Pusher and a Laravel event.
  
  onMounted(() => {
    updateBinancePrice();
    updateBalanceSelectedTicker();
    intervalId.value = setInterval(() => updateBinancePrice(), timeInterval.value);
  });

  onBeforeUnmount(() => {
    clearInterval(intervalId.value);
  });

  // watch every change in  props.selectedTicker
  watch(
    () => props.selectedTicker,
    (newTicker) => {
      // update the price
      if (newTicker) {
        price.value = 0;
        clearInterval(intervalId.value);
        intervalIncrement.value = 0;
        updateBinancePrice();
      }

      // update the selectedTickerInfo
      updateBalanceSelectedTicker();
      
    }
  );

  
  // watch every change in  props.allTickers
  watch(
    () => props.allTickers,
    (newAllTickers) => {
      // update the selectedTickerInfo
      updateBalanceSelectedTicker();
    }
  );

</script>
<template>
  <div class="flex flex-col w-full p-4 bg-gray-100 text-dark font-bold rounded-lg shadow-md">
    <h2 class="m-0 py-0 px-3 inline-block translate-y-[-100%] bg-gray-400 rounded dark:bg-gray-800 text-white dark:text-white shadow">Trade panel</h2>
    
    <div class="flex flex-row items-start gap-4">
      <div class="the-ticker flex flex-col items-start">
        <h3 class="text-2xl">{{props.selectedTicker}}</h3>
        <p class="text-3xl text-accent transition-opacity"
          :class="{ 'opacity-20': loadingPrice }"
          @click="" >{{
            formatNumber(price)
        }}</p>

        <p>{{selectedTickerInfo?.base + '/' + selectedTickerInfo?.asset}}</p>
        <p>balance for {{selectedTickerInfo?.asset}}:<br/> {{ selectedTickerInfo?.balance }}</p>
        <p>speed: {{ `${speedInterval} (${timeInterval}seg)` }}</p>

      </div>
      <div class="the-trade-percentages flex flex-col items-start">
        <div class="space-y-4">
          <div>
            <label for="gain" class="block text-sm font-medium text-gray-700">Porcentaje de Ganancia</label>
            <input 
              id="gain" 
              type="number" 
              step="0.05" 
              v-model="percentages.gain"
              @change="saveOptions({ tradePercentages: percentages })" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Introduce el % de ganancia"
            />
          </div>

          <div>
            <label for="loss" class="block text-sm font-medium text-gray-700">Porcentaje de Pérdida</label>
            <input 
              id="loss" 
              type="number" 
              step="0.05" 
              v-model="percentages.loss" 
              @change="saveOptions({ tradePercentages: percentages })" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Introduce el % de pérdida"
            />
          </div>

          <div class="mt-4">
            <p><strong>Ganancia Aspirada:</strong> {{ percentages.gain }}%</p>
            <p><strong>Pérdida Permitida:</strong> {{ percentages.loss }}%</p>
          </div>
        </div>
      </div>



      <div class="the-trade-values flex flex-col items-start">
        <label for="trade-amount" class="block text-sm font-medium text-gray-700">Amount of {{ selectedTickerInfo?.asset }} to trade</label>
        <input 
              id="trade-amount" 
              type="number" 
              step="1" 
              v-model="theTrade.amount" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :placeholder="selectedTickerInfo?.asset?? 'set the amount'"
            />

      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
