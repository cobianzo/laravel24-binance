<script setup lang="ts">
  // vue
  import { watch, Ref, ref, onMounted, onBeforeUnmount } from 'vue';
  
  // Child components
  import Orders from './Orders.vue';

  // types, api, localstorage, helpers
  import { OrderBinanceType, TickerType, TradeOrderType, TripleOrderType, TripleOrdersAPIType } from '@/types/ticker';  

  import { getUserOrders, placeBinanceOrder, apiCallTest, getUserBalances } from '@/api/binanceApi';
  import { saveOptions } from '@/utils/localStorage-CRUD';
  // import { startWebSocket, closeWebSocket } from '@/utils/websocket-orders';
  import { formatNumber, stepSizeDecimalsForTicker } from '@/utils/helpers';
  import { saveTradeGroupsForSymbol, loadTradeGroupsForSymbol } from '@/utils/tradeTripleOrder-utils';
  

  // Props sent from parent
  const props = defineProps<{
    // sent from parent
    theTrade: TradeOrderType,
    price: number,
    updateTradeOrder: (arg0: TradeOrderType) => void,
    allTickers: TickerType[] | null,
    percentages: { gain: number, gainPrice: number, loss: number, lossPrice: number },
    selectedTickerInfo: TickerType | null | undefined,
    binancePublicKey: string
  }>();

  // Reactive vars
  const orders = ref<OrderBinanceType[]|null>(null);
  
  // not in use. If deleted, delete all references
  const ordersInfoInDB = ref<{ order_id: string, order_data: Object, parent_order_id: string|null }[]>([])

  
  const tradesGroupedInTripleOrders = ref<TripleOrderType[]>([]);
  const currentTripleOrder = ref<TripleOrderType>({ originalEntryOrder: null, closingGainOrder: null, closingLossOrder: null });
  const clearCurrentTripleOrder = () => currentTripleOrder.value = { originalEntryOrder: null, closingGainOrder: null, closingLossOrder: null };
  
  const selectCurrentTripleOrder = function(orderId:string, orderType: string, toggle: boolean = true) {
    const isGain = ['gain', 'LIMIT_MAKER'].includes(orderType);
    const isLoss = ['loss', 'STOP_LOSS_LIMIT'].includes(orderType);
    // const isOpen = !isGain && !isLoss; // ['NEW', 'PARTIALLY_FILLED']
    let property : 'originalEntryOrder' | 'closingGainOrder' | 'closingLossOrder';
    property = 'originalEntryOrder'; 
    if ( isGain ) {
      property = 'closingGainOrder';
    } else 
    if ( isLoss ) {
      property = 'closingLossOrder';
    }
    if ( toggle && currentTripleOrder.value[property] === orderId ) {
      currentTripleOrder.value[property] = null;
    } else {
      currentTripleOrder.value[property] = orderId;
    }
  }
  const saveCurrentTripleOrder = function() {
    // validation. If there are other in the matchedorders, we need to clear them.
    // covert proxy type into a clean array:
    let cleanMatchedOrders = [... JSON.parse(JSON.stringify(tradesGroupedInTripleOrders.value)) ];
    const currentMatchingArConcatAsArray = Object.values(currentTripleOrder.value).filter( vals => vals !== null )
    cleanMatchedOrders = cleanMatchedOrders.filter( matchedSingle => {
      // if any of the three orders is in any of the three orders of currentTripleOrder.value, we remove it
      if ( currentMatchingArConcatAsArray.includes(matchedSingle.originalEntryOrder) || currentMatchingArConcatAsArray.includes(matchedSingle.closingGainOrder) || currentMatchingArConcatAsArray.includes(matchedSingle.closingLossOrder) ) {
        return false;
      } else {
        return true;  
      }
    });
    const newTripla = JSON.parse(JSON.stringify(currentTripleOrder.value));
    cleanMatchedOrders.push(newTripla);
    tradesGroupedInTripleOrders.value = cleanMatchedOrders;

    // save into the DB and clear the temporary current linking
    saveTradeGroupsForSymbol( props.selectedTickerInfo?.symbol, cleanMatchedOrders);
    clearCurrentTripleOrder();
  }
  const deleteTradeContainingOrder = function(orderId: string) {
    const newArray: TripleOrderType[] = tradesGroupedInTripleOrders.value.filter( matchedSingle => {
      return ! Object.values(matchedSingle).includes(orderId);
    });
    tradesGroupedInTripleOrders.value = newArray;
    saveTradeGroupsForSymbol( props.selectedTickerInfo?.symbol, newArray);
  }

  const tripleOrdersAPI: TripleOrdersAPIType = {
    tradesGroupedInTripleOrders,
    currentTripleOrder,
    clearCurrentTripleOrder,
    selectCurrentTripleOrder,
    saveCurrentTripleOrder,
    deleteTradeContainingOrder
  }

  function handleUpdateTradeOrder() {
    console.log('TODELETE: updating trade order');
    let quantity = props.theTrade.amount? props.theTrade.amount / props.price : 0;

    const quantityDecimals = stepSizeDecimalsForTicker(props.selectedTickerInfo?.symbol, props.allTickers);
    quantity = Number(quantity.toFixed(quantityDecimals));
    console.log('calculated quantity: '+quantity+' for ',props.selectedTickerInfo?.symbol, quantityDecimals);
    const setupTradeData: TradeOrderType = {
      symbol: props.selectedTickerInfo?.symbol ?? '',
      quantity,
      price: props.price,
      side: 'BUY',
      type: 'LIMIT',
    }
    console.log('TODELETE: preparing trading at: ', setupTradeData);
    props.updateTradeOrder(setupTradeData);
    
  }
  function handlePlaceOrder() {
    const { symbol, quantity, price, side, type } = props.theTrade;
    if (!props.theTrade.symbol)
    props.theTrade.symbol = props.selectedTickerInfo?.symbol ?? '';
    console.log('>>>>>>>>>>>>>>', props.selectedTickerInfo?.symbol ?? '', props.theTrade);
    placeBinanceOrder( symbol, quantity, price, side, type)
      .then(response => {
        syncOrdersForSelectedTicker( true )
      })

    // console.log('TODELETE: placing a stop loss GAIN ', props.percentages.gainPrice);
    // console.log('TODELETE: placing a stop loss LOSS ', props.percentages.lossPrice);
  }



  

  // Methods
  const syncOrdersForSelectedTicker = async( reset:boolean = false) => {
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
    const response = await getUserOrders( props.selectedTickerInfo.symbol, 20 ); 
    if (response) {
      // some more validation?
      // @TODO: can we ask only for recent orders in the endpoint already?
      console.info('%c ORders: ', 'font-size: 2rem; background:black;color:white', response);

      orders.value = response;

      // cargar los links de orders para crear un Trade.
      loadTradeGroupsForSymbol(props.selectedTickerInfo.symbol, (listTripleOrders : TripleOrderType[]) => {
        console.log('TODELETE: list of triple orders, loading: ', listTripleOrders);
        tradesGroupedInTripleOrders.value = listTripleOrders;
      });

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

  // Watchers

  // calculate quantity (asset currency) based on amount (of base currency)
  watch(
    () => props.theTrade.amount,
    (newAmount: number | undefined) => {
      props.theTrade.quantity = newAmount? newAmount / props.price : 0;
      saveOptions( { tradeAmount: newAmount } );
      return newAmount;
    }
  );

  // @TODO: This could be moved to the component of MatchTradesColumn, for cleaniness.
  watch(
    () => tradesGroupedInTripleOrders,
    (newTradesGroupedInTripleOrders) => {
      console.log('>>>>>> Watching tradesGroupedInTripleOrders', newTradesGroupedInTripleOrders);
    },
    { deep: true }
  );

  watch(
    () => props.selectedTickerInfo as TickerType,
    (newTicker: TickerType) => {
      console.log('TODELELEEE >>>>>  We should replace the orders.', newTicker)
      if ( newTicker?.symbol ) {
        syncOrdersForSelectedTicker(true);
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
        <button @click="updateMiTest">TEST TODELTEE</button>
        <button class="inline-flexitems-center px-4 py-2 border border-transparent text-center shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex-none"
          @click="handleUpdateTradeOrder">update</button>
        
        <button class="inline-flex items-center px-4 py-2 border border-transparent text-center shadow-sm 
           rounded-md font-medium text-sm justify-center text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          @click="handlePlaceOrder">
          ➽ Place order
        </button>

        <button class="inline-flex items-center px-4 py-2 border border-transparent text-center shadow-sm text-sm
           font-medium rounded-md text-dark bg-red-200 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
           @click="()=> { const cosa = 'cosas'; console.log('cpsas', cosa); syncOrdersForSelectedTicker( true ) ; }"
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

    <div class="trade-orders w-full flex flex-col items-start justify-center text-xsgap-3">
      <Orders
        :orders="orders"
        :ordersInfoInDB="ordersInfoInDB"
        :tripleOrdersAPI="tripleOrdersAPI"
        :allTickers="props.allTickers"
        :percentages="props.percentages"
        :price="props.price"
        :syncOrdersForSelectedTicker="syncOrdersForSelectedTicker"
      />
    </div>
  </div>
</template>

<style scoped>
</style>
