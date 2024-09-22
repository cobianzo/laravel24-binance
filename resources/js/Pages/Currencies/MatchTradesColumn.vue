<script setup lang="ts">
import { Ref, computed } from 'vue';

import { orderIsSelectedForCreatingATrade, indexMatchingOrder } from '@/utils/tradeTripleOrder-utils';
import { OrderBinanceType, TripleOrdersAPIType } from '@/types/ticker';  

const props = defineProps<{
  order: OrderBinanceType,
  tripleOrdersAPI: TripleOrdersAPIType
}>();

function handleMatchOrders(orderData: OrderBinanceType) {
  props.tripleOrdersAPI.selectCurrentTripleOrder(orderData.orderId.toString(), orderData.type);
}

const labelOnColumn = computed(() => {
  const isSelected = orderIsSelectedForCreatingATrade(props.order.orderId, props.tripleOrdersAPI.currentTripleOrder.value );
  if (isSelected) {
    return 'selected';
  }
  const indexOfTrade = indexMatchingOrder(props.order.orderId.toString(), props.tripleOrdersAPI.tradesGroupedInTripleOrders.value )
  return ( indexOfTrade ) ? `Trade ${indexOfTrade}` : '🔗'
});
</script>

<template>
  <button 
    v-if="props.order.status !== 'CANCELED'"
    title="Match order open-closing trade"
    class="text-accent ml-5 text-lg"
    :class="{'animate-pulse': Object.values(props.tripleOrdersAPI.currentTripleOrder.value).some( (value) => value === order.orderId ) }"
    @click="handleMatchOrders(order)">
      {{  labelOnColumn }}
  </button>
  <button 
    v-if="labelOnColumn.includes('Trade')"
    title="Match order open-closing trade"
    class="text-accent ml-5 text-lg"
    @click="tripleOrdersAPI.deleteTradeContainingOrder(order.orderId.toString())"
    >
    ⛔️ 
  </button>
</template>


<style scoped>
</style>
