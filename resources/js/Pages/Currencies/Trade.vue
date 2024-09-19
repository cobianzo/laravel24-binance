<script setup lang="ts">
  import { computed, watch, ref, onMounted } from 'vue';
  import { TickerType, BalanceType, TradeOrderType } from '@/types/ticker';  
  import { getUserOrders } from '@/api/binanceApi';
  import { saveOptions } from '@/utils/localStorage-CRUD';

  // Props sent from parent
  const props = defineProps<{
    // sent from parent
    theTrade: TradeOrderType,
    price: number,
    percentages: { gain: number, gainPrice: number, loss: number, lossPrice: number },
    selectedTickerInfo: TickerType,
    userOrders: Object[], // the initial orders when page was loaded (not in use)
    binancePublicKey: string
  }>();

  // Reactive vars
  const orders = ref<Object[]|null>(null);

  function handleUpdateTradeOrder() {
    console.log('TODELETE: updating trade order');
    props.theTrade.symbol = props.selectedTickerInfo.symbol;
    props.theTrade.quantity = props.theTrade.amount / props.price;
    props.theTrade.price = props.price;
    props.theTrade.side = 'BUY'
    props.theTrade.type = 'LIMIT';
  }
  function handlePlaceOrder() {
    console.log('TODELETE: placing a buying order for ', props.theTrade.amount, props.selectedTickerInfo?.asset);

    console.log('TODELETE: placing a stop loss GAIN ', props.percentages.gainPrice);
    console.log('TODELETE: placing a stop loss LOSS ', props.percentages.lossPrice);
  }

  // Methods
  const updateOrdersForSelectedTicker = async() => {
    console.info('letseee');
    if ( orders.value !== null ) {
      return;
    }
    if (  props.selectedTickerInfo ) {
      const response = await getUserOrders( props.selectedTickerInfo.symbol ); 
      if (response) {
        // some more validation?
        console.info('%c ORders: ', 'font-size: 2rem; backgroun:black;color:white', orders);
        const daysOld = 5;
        const recentOrders = response.filter(o => o.time > Date.now() - 1000 * 60 * 60 * 24 * daysOld);
        orders.value = recentOrders;
      }
    }
  }
  function updateOrders(newOrder) {
    orders.value = [...orders.value, newOrder];
  }

  // Watchers

  // calculate quantity (asset currency) based on amount (of base currency)
  watch(
    () => props.theTrade.amount,
    (newAmount: number) => {
      props.theTrade.quantity = newAmount / props.price;
      saveOptions( { tradeAmount: newAmount } );
    }
  );
  
  // Lifecycle
  onMounted(() => {
    // init the orders. 
    updateOrdersForSelectedTicker();

  })

</script>
<template>
  <div class="w-full flex flex-col items-center">

    <div class="trade-data  flex flex-row items-start justify-center w-full text-xs gap-3">
      <div class="flex flex-1 flex-col">
        <div class="flex flex-row justify-between">
          <span class="text-center">symbol<br/>{{ props.theTrade.symbol }}</span>
          <span class="text-center">quantity<br/> {{ props.theTrade.quantity }}</span>
          <span class="text-center">side<br/> {{ props.theTrade.side }}</span>
        </div>
        <div class="flex flex-row justify-between">
          <span class="text-center">type<br/> {{ props.theTrade.type }}</span>
          <span class="text-center">price<br/>
            <input class="text-xs w-[90px] px-2 py-1 border rounded-md" type="number" step="0.01" v-model="props.theTrade.price" />
            {{ props.theTrade.price }}
          </span>
        </div>
      </div>
      <div class="flex flex-col gap-3">
        <button class="inline-flexitems-center px-4 py-2 border border-transparent text-center shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex-none"
          @click="handleUpdateTradeOrder">update</button>
        
        <button class="inline-flex items-center px-4 py-2 border border-transparent text-center shadow-sm text-sm
           font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          @click="handlePlaceOrder">
          Place order
        </button>
      </div>
    </div>

    <div class="trade-orders w-full flex flex-col items-center justify-center text-xsgap-3">
      <h3 class="text-left w-full">Current Order:</h3>
      <div class="flex flex-row justify-between ">
      </div>
      <h3 class="text-left w-full">Binance Orders:</h3>
      <div class="w-full border border-gray-400">
        <table class="table-auto w-full border-collapse">
          <thead>
            <tr class="bg-gray-200">
              <th class="text-left px-1 py-0">Date</th>
              <th class="text-left py-0 overflow-hidden max-w-[25px]">Type</th>
              <th class="text-left px-1 py-0">Side</th>
              <th class="text-left px-1 py-0">Status</th>
              <th class="text-left px-1 py-0">Quantity</th>
            </tr>
          </thead>
          <tbody class="text-xs">
            <tr v-for="order in orders" :key="order.id" class="border-t">
              <td class="px-1 py-0">{{ new Date(order.time).toLocaleString() }}</td>
              <td class="py-0 overflow-hidden max-w-[25px]">
                {{ order.type === 'MARKET' ? 'MRK' : (
                    order.type === 'LIMIT' ? 'LMT' : order.type
                ) }}
              </td>
              <td class="px-1 py-0" :class="{'text-red-600': order.side === 'SELL', 'text-green-600': order.side === 'BUY'}">
                {{ order.side }}
              </td>
              <td class="px-1 py-0">{{ order.status }}</td>
              <td class="px-1 py-0">{{ order.origQty }} ({{ (order.executedQty / order.origQty * 100).toFixed(2) }}%)</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
