<script setup lang="ts">
  import { computed, watch, ref, onMounted, onBeforeMount, onBeforeUnmount } from 'vue';
  import { TickerType, BalanceType, TradeOrderType } from '@/types/ticker';  
  import { getUserOrders, placeBinanceOrder, apiCallTest, getUserBalances, cancelOrder } from '@/api/binanceApi';
  import { saveOptions } from '@/utils/localStorage-CRUD';
  // import { startWebSocket, closeWebSocket } from '@/utils/websocket-orders';
  import { formatNumber, getTickerInfoCurrencyFromTicker } from '@/utils/helpers';

  // Props sent from parent
  const props = defineProps<{
    // sent from parent
    theTrade: TradeOrderType,
    price: number,
    updateTradeOrder: (arg0: TradeOrderType) => void,
    allTickers: TickerType[] | null,
    percentages: { gain: number, gainPrice: number, loss: number, lossPrice: number },
    selectedTickerInfo: TickerType,
    binancePublicKey: string
  }>();

  // Reactive vars
  const orders = ref<Object[]|null>(null);

  function handleUpdateTradeOrder() {
    console.log('TODELETE: updating trade order');
    let quantity = props.theTrade.amount / props.price;
    const {stepSize} = props.selectedTickerInfo;
    const quantityDecimals = stepSize.toString().split('.')[1].length;
    quantity = Number(quantity.toFixed(quantityDecimals));
    const setupTradeData: TradeOrderType = {
      symbol: props.selectedTickerInfo.symbol,
      quantity,
      price: props.price,
      side: 'BUY',
      type: 'LIMIT',
    }
    props.updateTradeOrder(setupTradeData);
    
  }
  function handlePlaceOrder() {
    const { symbol, quantity, price, side, type } = props.theTrade;
    if (!props.theTrade.symbol)
    props.theTrade.symbol = props.selectedTickerInfo.symbol;
    console.log('>>>>>>>>>>>>>>', props.selectedTickerInfo.symbol, props.theTrade);
    placeBinanceOrder( symbol, quantity, price, side, type);

    // console.log('TODELETE: placing a stop loss GAIN ', props.percentages.gainPrice);
    // console.log('TODELETE: placing a stop loss LOSS ', props.percentages.lossPrice);
  }

  function handleCancelOrder(order: any){
    console.log('Deleting order ', order.symbol, String(order.orderId), order);
    cancelOrder( order.symbol, String(order.orderId)).then( response => {
      if (response.status === 200) console.log('All ok. Deleted', response);
    }).finally(()=> getOrdersForSelectedTicker() );
  }

  // Methods
  const getOrdersForSelectedTicker = async( reset:boolean = false) => {
    console.log('%cTODEL Trying to retrieve orders for the first time for ', 'color:orange',props.selectedTickerInfo?.symbol);
    if ( orders.value !== null && reset === false ) {
      console.log( 'Orders are not null, It was loaded before. ');
      return;
    }
    if (  ! props.selectedTickerInfo ) {
      console.error( 'Error: can\'t retrieve orders because we don\'t have a ('+props.selectedTickerInfo+')selected ticker. ', props.selectedTickerInfo );
      return;
    }
    // @TODO: Aparently this is called several times on page LOAD. @TOFIX
    const response = await getUserOrders( props.selectedTickerInfo.symbol ); 
    if (response) {
      // some more validation?
      // @TODO: can we ask only for recent orders in the endpoint already?
      console.info('%c ORders: ', 'font-size: 2rem; background:black;color:white', response);
      const daysOld = 5;
      const recentOrders = response.filter((o) => o.time > Date.now() - 1000 * 60 * 60 * 24 * daysOld);
      orders.value = recentOrders;

      // now that we have initialized the orders, we want to start the websocket to update
      // it incase a new order comes in.
    
      // closeWebSocket();
      // const ws = await startWebSocket(); // websocket is also stored in window.wsOrders
      // console.log('>>>>>> This is the ws', ws);
      
      // ws.onmessage = (event) => {
      //   const data = JSON.parse(event.data);
      //   console.log('>>>>> WS. ', data);
      //   if (data.stream === `${props.selectedTickerInfo.symbol.toLowerCase()}@trade`) {
      //     updateOrders(data.data);
      //   }
      // };
      
    }
  
  }

  // WIP: for the websockets
  function updateOrders(newOrder) {
    console.log('>>>>> in theory a new order has arrived!! ', newOrder);
    // orders.value = [...orders.value, newOrder];
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

  watch(
    () => props.selectedTickerInfo,
    (newTicker: TickerType) => {
      if ( newTicker?.symbol ) {
        getOrdersForSelectedTicker();
      }
    }
  );
  
  // Lifecycle
  onMounted(() => {
    
  });

  onBeforeUnmount(() => {
    // closeWebSocket();
  });


</script>
<template>
  <div class="w-full flex flex-col items-center">

    <div class="trade-data mt-5 flex flex-col items-start justify-center w-full text-xs gap-3">
      <div class="flex flex-row gap-3 justify-between w-full">
        <button class="inline-flexitems-center px-4 py-2 border border-transparent text-center shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex-none"
          @click="handleUpdateTradeOrder">update</button>
        
        <button class="inline-flex items-center px-4 py-2 border border-transparent text-center shadow-sm 
           rounded-md font-medium text-sm justify-center text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          @click="handlePlaceOrder">
          ➽ Place order
        </button>

        <button class="inline-flex items-center px-4 py-2 border border-transparent text-center shadow-sm text-sm
           font-medium rounded-md text-dark bg-red-200 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
           @click="()=> { const cosa = 'cosas'; console.log('cpsas', cosa); getOrdersForSelectedTicker( true ) ; }"
           >
           Debug: show orders
          </button>
        <button @click="() => apiCallTest( 'param here ')">Other test</button>
        <button @click="() => getUserBalances()">Another test</button>
      </div>
      <div class="flex flex-1 flex-row justify-between gap-4">
        <span class="text-center">type<br/> {{ props.theTrade.type }}</span>
        <span class="text-center">quantity<br/> {{ props.theTrade.quantity }}</span>
          
        <span class="text-center flex flex-col">
          <b>price</b>
          <input class="text-xs w-[90px] px-2 py-1 border rounded-md" type="number" step="0.01" v-model="props.theTrade.price" />
          <em>{{ formatNumber(props.theTrade.price - props.price, 2) }}</em>  
        </span>
        
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
            <tr class="bg-gray-200 text-sm">
              <th class="text-left px-1 py-0">Date</th>
              <th class="text-left py-0 overflow-hidden max-w-[25px]">Type</th>
              <th class="text-left px-1 py-0 text-center">Side</th>
              <th class="text-left px-1 py-0 text-center">Status</th>
              <th class="text-left px-1 py-0 text-center">Quantity</th>
              <th class="text-left px-1 py-0 text-center">Price</th>
              <th class="text-left px-1 py-0 text-center">Action</th>
            </tr>
          </thead>
          <tbody class="text-xs">
            <tr v-for="order in orders?.slice().slice(0, 5)" 
                :id="'order'+order.orderId"
                :key="order.orderId" class="border-t"
                :class="{
                  'bg-green-100 from-green-100 to-green-300 animate-pulse': order.status === 'NEW',
                  'text-gray-400': order.status === 'CANCELED'
                }"
            >
              <td class="px-1 py-0">{{ new Date(order.time).toLocaleString() }}</td>
              <td class="py-0 overflow-hidden max-w-[25px] text-center">
                {{ order.type === 'MARKET' ? 'MRK' : (
                    order.type === 'LIMIT' ? 'LMT' : order.type
                ) }}
              </td>
              <td class="px-1 py-0 text-center" 
                :class="{
                  'text-red-600': order.side === 'SELL',
                  'text-green-600': order.side === 'BUY' && order.status !== 'CANCELED',
                  'text-gray-600': order.status === 'CANCELED',
                }">
                {{ order.side?.slice(0, 4) }}
              </td>
              <td class="px-1 py-0 text-center"
                :class="{
                  'text-red-600': order.status === 'CANCELED',
                }"
              >{{ order.status.slice(0,4) }}</td>
              <td class="px-1 py-0 text-center">{{ order.origQty }} ({{ (order.executedQty / order.origQty * 100).toFixed(2) }}%)</td>
              <td class="px-1 py-0 text-right ">
                <span class="text-accent text-sm">{{ formatNumber(order.price) }}</span>
                <span class="text-gray-400 text-xs">{{ getTickerInfoCurrencyFromTicker(order.symbol, props.allTickers ).asset }}
                </span>
              </td>
              <td class="px-1 py-0 text-right ">
                <button @click="handleCancelOrder(order)" 
                  class="inline-flex items-center px-2 py-1 border rounded-md font-semibold text-xs text-white dark:text-gray-300 uppercase tracking-widest shadow-sm bg-red-100 hover:bg-gray-500 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                  v-if="['NEW'].includes(order.status)"
                >
                  ❌
                </button>
                
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
