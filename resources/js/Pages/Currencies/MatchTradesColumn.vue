<script setup lang="ts">
import { Ref, computed } from 'vue';

import { orderIsSelectedForCreatingATrade, indexMatchingOrder } from '@/utils/tradeTripleOrder-utils';
import { OrderBinanceType, TripleOrderType } from '@/types/ticker';  

const props = defineProps<{
  order: OrderBinanceType,
  matchingOrdersAPI: { 
    // the model (reactive data)
    tradesGroupedInTripleOrders: TripleOrderType[], 
    // methods
    currentTripleOrder: Ref<TripleOrderType>,
    clearCurrentTripleOrder: any,
    selectCurrentTripleOrder: any,
    saveCurrentTripleOrder: any,
    deleteTradeContainingOrder: any
  },
}>();

function handleMatchOrders(orderData: OrderBinanceType) {
  props.matchingOrdersAPI.selectCurrentTripleOrder(orderData.orderId.toString(), orderData.type);
}

const labelOnColumn = computed(() => {
  const isSelected = orderIsSelectedForCreatingATrade(props.order.orderId, props.matchingOrdersAPI.currentTripleOrder.value );
  if (isSelected) {
    return 'selected';
  }
  const indexOfTrade = indexMatchingOrder(props.order.orderId.toString(), props.matchingOrdersAPI.tradesGroupedInTripleOrders.value )
  return ( indexOfTrade ) ? `Trade ${indexOfTrade}` : '🔗'
});
</script>

<template>
  <button 
    v-if="props.order.status !== 'CANCELED'"
    title="Match order open-closing trade"
    class="text-accent ml-5 text-lg"
    :class="{'animate-pulse': Object.values(props.matchingOrdersAPI.currentTripleOrder.value).some( (value) => value === order.orderId ) }"
    @click="handleMatchOrders(order)">
      {{  labelOnColumn }}
  </button>
  <button 
    v-if="labelOnColumn.includes('Trade')"
    title="Match order open-closing trade"
    class="text-accent ml-5 text-lg"
    @click="matchingOrdersAPI.deleteTradeContainingOrder(order.orderId.toString())"
    >
    ⛔️ 
  </button>
</template>


<style scoped>
</style>
