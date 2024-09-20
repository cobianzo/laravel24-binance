<script setup lang="ts">
// Vue
import { ref } from 'vue';
import { formatNumber } from '@/utils/helpers';
import { TickerType } from '@/types/ticker';  
import { getTickerInfoCurrencyFromTicker, getPercentage } from '@/utils/helpers'
import { cancelOrder, placeBinanceOCOOrder } from '@/api/binanceApi';

const props = defineProps<{
  orders: Object[]|null,
  allTickers: TickerType[] | null,
  price: number,
  percentages: { gain: number, gainPrice: number, loss: number, lossPrice: number },
  selectedTickerInfo: TickerType,
  syncOrdersForSelectedTicker: (arg0: boolean) => void,
}>()

// Filled orders that we want to monitorize if we are winninng or losing
const followedUpOrders = ref<string[]>([]);

function handleCancelOrder(order: any){
    console.log('Deleting order ', order.symbol, String(order.orderId), order);
    cancelOrder( order.symbol, String(order.orderId)).then( response => {
      if (response.status === 200) console.log('All ok. Deleted', response);
    }).finally(()=> props.syncOrdersForSelectedTicker(true) );
}

function handlePlaceOCOOrderToExitOrder(originalFilledOrder: any) {

  if (originalFilledOrder.status !== 'FILLED') {
    console.log('can\'t create the exit orders for a non filled order');
  }

  const symbol = originalFilledOrder.symbol;
  const side = 'SELL'; // Since you want to sell BTC for profit or loss
  const quantity = originalFilledOrder.origQty; // alsp:  100 / 63658; // USDT / Price per BTC -> This gives you the amount of BTC you're selling

  const entryPrice = originalFilledOrder.price; // Price you bought BTC at
  const gainPrice = entryPrice + entryPrice * props.percentages.gain/100; // % above entry price
  const stopPrice = entryPrice - entryPrice * props.percentages.loss/100;
  const gap = 0.5; // percentage of the price to separate stopLimit and stopLimitPrice.
  const stopLimitPrice = stopPrice - entryPrice * gap / 100;

  console.log(`From price ${entryPrice} (+${props.percentages.gain} -${props.percentages.loss}) Before placing the OCO order with , symbol: ${symbol}, side: ${side}, quantity: ${quantity}, price: ${gainPrice}, stopPrice: ${stopPrice}, stopLimitPrice: ${stopLimitPrice}`);
}
  // placeBinanceOCOOrder(symbol, side, quantity, entryPrice, stopPrice, stopLimitPrice)
  //   .then(response => {
  //     console.log('the response', response);
  //     // @TODO: Attach the new order to the origina order in our own Laravel DATABASE.
  //     // Update the orders list
  //   //   syncOrdersForSelectedTicker();
  //   //  @TODO: update the balance of a single ticker.
  // }) ;

  
const orderFollowed = (orderId:string|number) => followedUpOrders.value.includes(orderId.toString());
function handleFollowUpOrder(order: any) {
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
    <a class="todo flex-grow-1 justify-start text-sm text-accent underline hover:no-underline cursor-pointer">
      ignore cancelled orders
    </a>
  </div>
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
          <th class="text-left px-1 py-0 text-center">Matched Order</th>
        </tr>
      </thead>
      <tbody class="text-xs">
        <tr v-for="order in props.orders" 
            :id="'order-'+order.orderId"
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

          <td 
            v-if="!orderFollowed(order.orderId)"
            class="the-price px-1 py-0 text-right ">
            <span class="text-accent text-sm">{{ formatNumber(order.price, 2) }}</span>
            <span class="text-gray-400 text-xs">{{ getTickerInfoCurrencyFromTicker(order.symbol, props.allTickers ).asset }}
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
                'text-green-600': order.price < props.price,
                'text-red-600': order.price > props.price,
              }"
            >
              {{ formatNumber( (props.price - order.price) * parseFloat(getPercentage(order.price, props.price, false)/ 100), 2) }}
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
              v-if="'FILLED' === order.status && order.executedQty > 0"
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
        </tr>
      </tbody>
    </table>
  </div>

</template>

<style scoped>
</style>
