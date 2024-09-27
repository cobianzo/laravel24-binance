<script setup lang="ts">
// vue
import { watch, Ref, ref, defineEmits, onMounted, onBeforeUnmount, computed } from 'vue';

// types
import { OrderBinanceType, TickerType, TradeOrderType, TripleOrderType, TripleOrdersAPIType } from '@/types/ticker';  
import { formatNumber } from '@/utils/helpers';

// Props sent from parent
const props = defineProps<{
  currentPrice: number,
  entryOrder: OrderBinanceType, // the current order
  percentages: { gain: number, gainPrice: number, loss: number, lossPrice: number },
  selectedTickerInfo: TickerType | null | undefined,
  updateCurrentyEditingOCOOrder: (arg0: string) => void,
  isEditing: boolean,
}>();

const theExitOCOOrder = ref<{
  gain: number, // percentages
  loss: number
}>({
  gain: 0,
  loss: 0,
});

const priceGain = computed(() => parseFloat(props.entryOrder.price) * ( 1 + theExitOCOOrder.value.gain / 100) );
const netGain = computed(() => priceGain.value * parseFloat(props.entryOrder.executedQty) - parseFloat(props.entryOrder.cummulativeQuoteQty) );
const priceLoss = computed(() => parseFloat(props.entryOrder.price) * ( 1 - theExitOCOOrder.value.loss / 100) );
const netLoss = computed(() => priceLoss.value * parseFloat(props.entryOrder.executedQty) - parseFloat(props.entryOrder.cummulativeQuoteQty) );





// handles and methods
const handleTogggleEditing = () => {
  
  props.updateCurrentyEditingOCOOrder(props.isEditing? '' : props.entryOrder.orderId.toString());
  
  if (props.isEditing) {
    theExitOCOOrder.value.gain = props.percentages.gain;
    theExitOCOOrder.value.loss = props.percentages.loss;
  }
}



onMounted(() => {
});
onBeforeUnmount(() => {
});

</script>
<template>
  <div
    v-if="['LIMIT', 'MARKET'].includes(entryOrder.type) && ['NEW', 'FILLED'].includes(entryOrder.status)"
    class="space-y-2">
    <button @click="handleTogggleEditing" 
      class="block w-full text-center">
      Toggle editing
    </button>
    <template v-if="isEditing">
      <div class="grid grid-cols-1 gap-2">
        <div class="flex">
          <input
            v-model="theExitOCOOrder.gain"
            type="number"
            step="0.05"
            class="w-[80px] text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Porcentaje de ganancia"
          />
          <div class="flex flex-col items-end justify-center pl-2 text-gray-600 text-sm text-green-600">
            <span class="text-gray-400">{{ formatNumber(priceGain, 2) }}</span>
            <span>+ {{ formatNumber(netGain, 2) }}</span>
          </div>
        </div>
        <div class="flex items-center justify-center">
          {{ formatNumber(props.entryOrder.cummulativeQuoteQty,2) }} {{selectedTickerInfo?.asset}} at <span class="font-bold ml-1">{{ parseFloat(props.entryOrder.price).toFixed(2) }}</span>
        </div>
        <div class="flex">
          <input
            v-model="theExitOCOOrder.loss"
            type="number"
            step="0.05"
            class="w-[80px] text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Porcentaje de pérdida"
          />
          <div class="flex flex-col items-end justify-center pl-2 text-gray-600 text-sm  text-red-400">
            <span class="text-gray-400">{{ formatNumber(priceGain, 2) }}</span>
            <span>{{ formatNumber(netLoss, 2) }}</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

