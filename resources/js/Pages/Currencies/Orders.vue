<script setup lang="ts">
// Vue
import { ref, Ref, computed, watch } from 'vue';
import { TickerType, OrderBinanceType, TripleOrdersAPIType } from '@/types/ticker';  
import { getTickerInfoCurrencyFromTicker, getPercentage, formatNumber, formatPriceToStepSize, formatPriceToPriceFilter } from '@/utils/helpers'
import { cancelOrder, placeBinanceOCOOrder } from '@/api/binanceApi';
import {numberOrdersMatchingSelected } from '@/utils/tradeTripleOrder-utils';
import { saveOptions, getOptions } from '@/utils/localStorage-CRUD';
import MatchTradesColumn from './MatchTradesColumn.vue';

/**
 * Logic in here:
 * 1. Get and show all orders for the selected ticker
 * 2. For every order, we can:
 *  - Check if we are winning or losing, checked as Follow (followedUpOrders), if active (NEW)
 *  - Cancel the order if active (NEW)
 * 
 */
 
const props = defineProps<{
  orders: OrderBinanceType[]|null,
  ordersInfoInDB: { order_id: string, order_data: Object, parent_order_id: string|null }[],
  tripleOrdersAPI: TripleOrdersAPIType,
  allTickers: TickerType[] | null,
  price: number,
  percentages: { gain: number, gainPrice: number, loss: number, lossPrice: number },
  selectedTickerInfo: TickerType | null | undefined,
  syncOrdersForSelectedTicker: (arg0: boolean) => void,
}>()

// Reactive variables
// Filled orders that we want to monitorize if we are winninng or losing
const followedUpOrders = ref<string[]>([]);
const options = ref<{
  hideCanceled: boolean
}>({
  hideCanceled: getOptions( 'hideCanceled' ) as boolean
});



function handleCancelOrder(order: OrderBinanceType){
    console.log('Deleting order ', order.symbol, String(order.orderId), order);
    cancelOrder( order.symbol, String(order.orderId)).then( response => {
      if (response.status === 200) console.log('All ok. Deleted', response);
    }).finally(()=> props.syncOrdersForSelectedTicker(true) );
}

function handlePlaceOCOOrderToExitOrder(originalFilledOrder: OrderBinanceType) {

  if (!props.allTickers) {
    return;
  }

  if (originalFilledOrder.status !== 'FILLED') {
    console.log('can\'t create the exit orders for a non filled order');
  }

  const symbol = originalFilledOrder.symbol;
  
  const side = 'SELL'; // Since you want to sell BTC for profit or loss
  let quantity = originalFilledOrder.executedQty; // or origQty;
  quantity = formatPriceToStepSize(quantity, symbol, props.allTickers).toString();

  let entryPrice = parseFloat(originalFilledOrder.price); // Price you bought BTC at
  entryPrice = formatPriceToPriceFilter(entryPrice, symbol, props.allTickers);
  let gainPrice = entryPrice + entryPrice * props.percentages.gain/100; // % above entry price
  gainPrice = formatPriceToPriceFilter(gainPrice, symbol, props.allTickers);
  
  let stopPrice = entryPrice - entryPrice * props.percentages.loss/100;
  stopPrice = formatPriceToPriceFilter(stopPrice, symbol, props.allTickers);
  const gap = 0.5; // percentage of the price to separate stopLimit and stopLimitPrice.
  let stopLimitPrice = stopPrice - entryPrice * gap / 100;
  stopLimitPrice = formatPriceToPriceFilter(stopLimitPrice, symbol, props.allTickers);

  console.log(`From price ${entryPrice} (+${props.percentages.gain} -${props.percentages.loss}) Before placing the OCO order with , symbol: ${symbol}, side: ${side}, quantity: ${quantity}, price: ${gainPrice}, stopPrice: ${stopPrice}, stopLimitPrice: ${stopLimitPrice}`, originalFilledOrder);

  placeBinanceOCOOrder(symbol, side, quantity, entryPrice.toString(), stopPrice.toString(), stopLimitPrice.toString())
    .then(response => {
      console.log('the response', response);
      // @TODO: Attach the new order to the origina order in our own Laravel DATABASE.
      // Update the orders list
    //   syncOrdersForSelectedTicker();
    //  @TODO: update the balance of a single ticker for currency base..
  }) ;
}

  
// checks if an order is on the followeup list.
const orderFollowed: (orderId: string|number) => boolean = (orderId) => followedUpOrders.value.includes(orderId.toString());

function handleFollowUpOrder(order: OrderBinanceType) {
  const {orderId}: {orderId: number|string} = order;
  if (!orderId) return;
  if (orderFollowed(orderId)) {
    // remove
    followedUpOrders.value = followedUpOrders.value.filter((oID: string) => oID !== orderId.toString());
  } else {
    // add
    followedUpOrders.value.push(orderId.toString());
  }
}


</script>

<template>
  <h3 class="text-left w-full">Current Order:</h3>
  <div class="flex flex-row justify-between text-sm">
    stuff
  </div>
  <div class="flex flex-row gap-4 items-baseline">
    <h3 class="text-left flex-0 flex-shrink-1 flex">Binance Orders for&nbsp;<span class="text-accent">{{ props.selectedTickerInfo?.symbol }}</span>:</h3>
    <a class="todo flex-grow-1 justify-start text-sm text-accent underline hover:no-underline cursor-pointer"
        @click="() => {
          const newVal = !options.hideCanceled; 
          options.hideCanceled = newVal; 
          saveOptions( { hideCanceled : newVal } );
        }"
    >
    {{  options.hideCanceled ? 'show also canceled orders' : 'ignore canceled orders' }}
    </a>
  </div>

  <div class="matching-buttons w-full flex flex-row justify-end gap-4">
    
    <button 
      v-if="numberOrdersMatchingSelected(props.tripleOrdersAPI.currentTripleOrder.value) >= 2"
      class="text-sm bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded" 
      @click="props.tripleOrdersAPI.saveCurrentTripleOrder()">
        Save matching orders as a Trade
    </button>
    <button 
      v-if="numberOrdersMatchingSelected(props.tripleOrdersAPI.currentTripleOrder.value) > 0"
      class="text-sm bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" 
      @click="props.tripleOrdersAPI.clearCurrentTripleOrder()">
        Cancel matching orders as a Trade
    </button>
  </div>
  <div class="w-full border border-gray-400">
    <table 
      class="table-auto w-full border-collapse"
      :class="{
        'hide-canceled' : options.hideCanceled
      }"
      >
      <thead>
        <tr class="bg-gray-200 text-sm">
          <th class="text-left px-1 py-0">Date</th>
          <th class="text-left py-0 overflow-hidden max-w-[25px]">Type</th>
          <th class="text-left px-1 py-0 text-center">Side</th>
          <th class="text-left px-1 py-0 text-center">Status</th>
          <th class="text-left px-1 py-0 text-center">Quantity</th>
          <th class="text-left px-1 py-0 text-center">Price</th>
          <th class="text-left px-1 py-0 text-center">Action</th>
          <th class="text-left px-1 py-0 text-center">Matched Order</th>
        </tr>
      </thead>
      <tbody class="text-xs">
        <tr v-for="order in props.orders" 
            :id="'order-'+order.orderId"
            :key="order.orderId" class="border-t"
            
            
            :class="{
              'bg-green-100 from-green-100 to-green-300 animate-pulse': order.status === 'NEW',
              'text-gray-400': order.status === 'CANCELED',
              [`status-${order.status.toLowerCase()}`]: true
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

          <td 
            v-if="!orderFollowed(order.orderId)"
            class="the-price px-1 py-0 text-right ">
            <span class="text-accent text-sm">{{ formatNumber(order.price, 2) }}</span>
            <span class="text-gray-400 text-xs">
              {{ getTickerInfoCurrencyFromTicker(order.symbol, props.allTickers )?.asset }}
            </span>
            <span v-if="order.type === 'LIMIT_MAKER' && order.status === 'NEW'"
              class="text-gray-400 text-xs">
              (+{{ formatNumber(parseFloat(order.price)-props.price, 1) }})
            </span>
          </td>
          <td 
            v-if="orderFollowed(order.orderId)"
            class="the-price px-1 py-0 text-right "
            >
            <span class="text-gray-400 text-xs">
              ({{ getPercentage(order.price, props.price, true) }} )
            </span>
            <span class="text-accent text-sm"
              :class="{
                'text-green-600': parseFloat(order.price) < props.price,
                'text-red-600': parseFloat(order.price) > props.price,
              }"
            >
              {{ formatNumber( (props.price - parseFloat(order.price)) * parseFloat(getPercentage(order.price, props.price, false).toString())/ 100, 2) }}
            </span>
          </td>

          <td class="the-action px-1 py-0 text-center ">
            <button @click="handleCancelOrder(order)" 
              class="inline-flex items-center px-2 py-1 border rounded-md font-semibold text-xs text-white dark:text-gray-300 uppercase tracking-widest shadow-sm bg-red-100 hover:bg-gray-500 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
              v-if="['NEW'].includes(order.status)"
            >
              ❌
            </button>

            <button 
              title="Place stop losses gain and loss (OCO order)"
              v-if="'FILLED' === order.status && ['LIMIT', 'MARKET'].includes(order.type)"
              class="text-green-500 text-lg"
              @click="handlePlaceOCOOrderToExitOrder(order)">
              ➽
            </button>
            <button 
              title="Follow up the state of this option as the price changes"
              v-if="'FILLED' === order.status && order.executedQty > 0"
              class="text-accent ml-5 text-lg"
              :class="{'animate-pulse': orderFollowed(order.orderId)}"
            
              @click="handleFollowUpOrder(order)">
              {{orderFollowed(order.orderId)? '🕵️' : '🔎' }}
            </button>
          </td>
          <td class="the-matching-order px-1 py-0 text-center ">
            <MatchTradesColumn 
              :order="order"
              :tripleOrdersAPI="tripleOrdersAPI"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>

</template>

<style scoped>
  table.hide-canceled tr.status-canceled {
    animation: hide 0.5s ease-out forwards;
  }
  @keyframes hide { 0% { opacity: 1; } 100% { opacity: 0; display: none; } }
</style>
