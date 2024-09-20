<script setup lang="ts">
  // VueJS deps
  import { ref, onMounted, onBeforeUnmount, watch, computed, reactive } from 'vue';

  // Child components
  import Trade from './Trade.vue';

  // internal deps
  import { getBinancePrice } from '@/api/binanceApi';
  import { formatNumber, getTickerInfoCurrencyFromTicker } from '@/utils/helpers';
  import { TickerType, BalanceType, TradeOrderType } from '@/types/ticker';

  import { getUserBalances } from '@/api/binanceApi';
  import { getOptions, saveOptions } from '@/utils/localStorage-CRUD';

  // PROPS coming from the parent
  // ============
  const props = defineProps<{
    selectedTicker: string,
    allTickers: TickerType[] | null,
    balances: BalanceType[] | null,
    binancePublicKey: string,
    updateAllBalances: () => void
  }>();

  
  // Reactive data.
  // =============
  const price = ref<number>(0);
  const loadingPrice = ref<number>(0);

  // balance
  const selectedTickerInfo = ref<TickerType | null>();

  // Trade options
  const percentages = reactive<{ gain: number, gainPrice: number, loss: number, lossPrice: number }>({
    gain: getOptions( 'tradePercentages' )?.gain?? 0, // % of gain
    gainPrice: 0,
    loss: getOptions( 'tradePercentages' )?.loss?? 0,
    lossPrice: 0
  });
  const theTrade = reactive<TradeOrderType>({
    symbol: '',
    amount: getOptions( 'tradeAmount') ?? 0,
    quantity: 0,
    price: 0,
    type: 'LIMIT',
    side: 'BUY',
  })

  // METHODS and functions
  // ============

  const updateBinancePrice = async function() {
    return; // todelete
    
  }

  const updateTradeOrder = function( newTradeSettings : TradeOrderType ) {
    Object.assign(theTrade, newTradeSettings);
  }
  
  const updateBalanceSelectedTicker = async function() {
    getUserBalances().then((response) => {
      console.log('TODELETE: For the current Ticker Asset currency: Retrieve balances from backend', response);
      // get current asset 
      const tickerInfo = getTickerInfoCurrencyFromTicker( props.selectedTicker, props.allTickers );
      selectedTickerInfo.value = tickerInfo;
      if (tickerInfo && selectedTickerInfo && selectedTickerInfo.value) {
        // response is an Array of { symbol, amount }
        const balance = {
          amount: response[selectedTickerInfo.value.asset]['available'],
          symbol: selectedTickerInfo.value.symbol
        };
        tickerInfo.balanceAsset = balance?.amount ?? 0;
        selectedTickerInfo.value = tickerInfo;
        
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
          tickerInfo, props.selectedTicker, props.allTickers)
      }

    }).finally(() => {
      // stuff
    });
    
  }
  
  function handleClickedPartial(percent: number | string) {
    console.log(`partial clicked`, percent, theTrade.amount, (selectedTickerInfo.value?.balanceAsset? selectedTickerInfo.value.balanceAsset : ''));
    // percentage
    if ( typeof percent === 'number') {
      theTrade.amount = parseInt( formatNumber((selectedTickerInfo.value?.balanceAsset? selectedTickerInfo.value.balanceAsset : 0) * percent / 100, 0, false) );
    } else {
      //units (eg 100 USDT)
      const units = parseInt(percent);
      theTrade.amount = units;
    }

  }
  

  // websockets fun @TODO: add to the BinanceAPI.

  // Function to open WebSocket connection
  let ws: WebSocket | null = null;
  const openWebSocket = (ticker: string, callback: (arg0: number) => void) => {
    if (!ticker || !ticker.length) {
      console.error('error trying to open socket, ' , ticker);
      return;
    }
    const wsUrl = `wss://stream.binance.com:9443/ws/${ticker.toLowerCase()}@ticker`;
    ws?.close();
    ws = new WebSocket(wsUrl);
    console.log('TODELET: connecting to socket, ' , wsUrl, ws);

    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      callback(parseFloat(data.c));
    };

    ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };
  };

  // Logic for updating the price.
  // @TODO: ideally we'll use websockers, with an external server like Pusher and a Laravel event.
  
  
  onMounted(() => {
    updateBinancePrice();
    openWebSocket(props.selectedTicker, (newPrice: number) => price.value = newPrice );
    updateBalanceSelectedTicker();
  });

  onBeforeUnmount(() => {
    ws?.close();
  });

  // Watch for changes in percentages.gain or percentages.loss
  watch(
    () => [percentages.gain, percentages.loss, price.value],
    ([newGain, newLoss, newPrice]) => {
      percentages.gainPrice = newPrice * (1 + newGain / 100); // Calculate gainPrice
      percentages.lossPrice = newPrice * (1 - newLoss / 100); // Calculate lossPrice
    }
  );

  // watch every change in  props.selectedTicker
  watch(
    () => props.selectedTicker,
    (newTicker) => {
      // update the price
      if (newTicker) {
        price.value = 0;
        openWebSocket(newTicker.toLowerCase(), (newPrice: number) => {
          price.value = newPrice;
        });
        // updateBinancePrice();
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
    
    <div class="the-columns-container flex flex-row items-start gap-4">
      <div class="the-ticker flex flex-col items-start">
        <h3 class="text-2xl text-secondary">{{props.selectedTicker}}</h3>
        <p class="text-3xl text-accent transition-opacity"
          :class="{ 'opacity-20': loadingPrice }"
          @click="price = 100"
          @dblclick="updateBinancePrice()"
          >{{
            formatNumber(price,4)
        }}</p>

        
        <div @click="theTrade.amount = selectedTickerInfo?.balanceAsset ?? 0 " class="text-gray-500 mt-3">
          <p>
            balance: 
          </p>
          <div class="text-accent hover:text-secondary hover:underline cursor-pointer">
            {{ formatNumber(selectedTickerInfo?.balanceAsset,2) }} <span class="text-gray-400">{{selectedTickerInfo?.asset}}</span>
          </div>
            
        </div>

      </div>
      <div class="the-trade-percentages flex flex-col items-start">
        <div class="space-y-4">
          <div>
            <label for="gain" class="block text-sm font-medium text-gray-700">Porcentaje de Ganancia</label>
            <div class="flex">
              <input 
                id="gain" 
                type="number" 
                step="0.05" 
                v-model="percentages.gain"
                @change="saveOptions({ tradePercentages: percentages })" 
                class="percentage-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-center w-[100px]"
                placeholder="Introduce el % de ganancia"
              />
              <div class="flex flex-col items-end justify-center pl-2 text-gray-600 text-sm text-right">
                <span>{{ formatNumber(percentages.gainPrice,2) }}</span>
                <span  class="text-xs text-accent">+{{ formatNumber(theTrade.amount * (percentages.gain / 100), 2) }}</span>
              </div>

            </div>
          </div>

          <div>
            <label for="loss" class="block text-sm font-medium text-gray-700">Porcentaje de Pérdida</label>
              <div class="flex">
                <input 
                  id="loss" 
                  type="number" 
                  step="0.05" 
                  v-model="percentages.loss" 
                  @change="saveOptions({ tradePercentages: percentages })" 
                  class="percentage-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-center w-[100px]"
                  placeholder="Introduce el % de pérdida"
                />
                <div class="flex flex-col items-end justify-center pl-2 text-gray-600 text-sm ">
                    <span>{{ formatNumber(percentages.lossPrice,2) }}</span>
                    <span  class="text-xs text-red-600">-{{ formatNumber((theTrade.amount * (percentages.loss / 100)), 2) }}</span>
                </div>
              </div>
          </div>
        </div>
      </div>



      <div class="the-trade-values flex flex-col items-start">
        <label for="trade-amount" class="block text-sm font-medium text-gray-700">Amount of {{ selectedTickerInfo?.asset }} to trade</label>
        <div class="flex">
          <input 
              id="trade-amount" 
              type="number" 
              step="1" 
              v-model="theTrade.amount" 
              class="mt-1 block w-full border-t border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm rounded-md rounded-tr-none rounded-br-none"
              :placeholder="selectedTickerInfo?.asset?? 'set the amount'"
            />
            <span class="mt-1 inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 border border-gray-300 dark:bg-gray-200 dark:text-gray-500 dark:border-gray-100 rounded-md rounded-tl-none rounded-bl-none">
              {{selectedTickerInfo?.asset?? ''}}
            </span>
        </div>
        
        <ul class="flex flex-row justify-between w-full text-xs text-red-500 cursor-pointer">
          <li
            v-for="partial in [100, 50, 25, 10]"
            class="hover:text-secondary"
            @click="handleClickedPartial(partial)"
            >
            {{ partial }}%
          </li>
          <li class="hover:text-secondary"
              @click="handleClickedPartial('100units')"
          >
            100 units
          </li>
        </ul>
        

      </div>
    </div>
    <div class="the-trade-action w-full flex flex-col items-center flex-1">
      <Trade 
        :theTrade="theTrade"
        :percentages="percentages"
        :updateTradeOrder="updateTradeOrder"
        :selectedTickerInfo="selectedTickerInfo"
        :allTickers="allTickers"
        :price="price"
        :binancePublicKey="props.binancePublicKey"
        />
    </div>
  </div>
</template>

<style scoped>
.the-columns-container > div {
  min-width: 200px;
}
.percentage-input {
  max-width: 100px;
}
</style>
