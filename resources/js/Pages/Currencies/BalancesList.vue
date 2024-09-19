<script setup lang="ts">
  import { AllBalancesType, BalanceType } from '@/types/ticker';
  import { watch, ref, Ref } from 'vue';
  import { getBinancePrice } from '@/api/binanceApi';
  import { formatNumber } from '@/utils/helpers';
  
  // propd coming from the parent
  const props = defineProps<{
    updateLoading?: (arg0: string) => void,
    balances: AllBalancesType | null,
    selectBalance: (arg0: string) => void
    updateBalanceSelectedTicker: (arg0: BalanceType) => void
  }>();

  // state. For every currency in the portfolio, we ask to binance the price in USDT
  const balancesWithPrice = ref<{ [symbol: string] : { price: number | null, balanceValue: number } }>({});

  /**
   * Update UI for one current ticker Card
   */
  const updateBalanceWithPrice = async ( symbol: string, amount: number) => {
    const price = await getUSDPriceForSymbol(symbol);
    if (price) {
      const currencyInfo = {
        price,
        balanceValue: amount * price
      }
      balancesWithPrice.value[symbol] = currencyInfo;
    }
    return price;
  }

  const updateBalancesWithPrice = async (newBalances : AllBalancesType | null) => {
    if (newBalances === null) {
        balancesWithPrice.value = {};
      } else {
        // these calculations take a long time, so we show a loading UI
        if (props.updateLoading) props.updateLoading('portfolio-loading');

        const setOfPromises = Object.keys(newBalances).map(async (symbol) => {
          const available = newBalances[symbol].available;
          const availableN: number = typeof  available === 'string' ? parseFloat(available) : available;
          
          return updateBalanceWithPrice(symbol, availableN);
        });
        
        Promise.all(setOfPromises).finally( () => {
          if (props.updateLoading) props.updateLoading('');
        });
        
        
        // @TODO: Now we could sort the currencies in order of balance in USDT.
        
      }
  };
  watch(
    () => props.balances,
    async (newBalances) => {
      updateBalancesWithPrice(newBalances);
    }
  );

  // methods
  const getUSDPriceForSymbol = async function (symbol: string, toSymbol : string = 'USDT'): Promise<number> {
    if ( toSymbol === symbol ) {
      return 1;
    }
    let price = 0;
    try {
      price = await getBinancePrice(`${symbol}${toSymbol}`);
    } catch (e) {
      console.warn(`No price for ${symbol}`);
    }
    console.log(`Calculated price for ${symbol} into ${toSymbol}:`, price);
    return price?? 0;
  }

  function myDebugBtnClick() {
    if (props.updateLoading) {
      console.log('clicked!!');  
      updateBalancesWithPrice(props.balances);
    }
  }

</script>
<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    <div
        v-if="props.balances !== null"
        v-for="(symbol, index) in Object.keys(props.balances)"
        :key="symbol"
        class="p-4 bg-gray-100 rounded-lg shadow-md flex flex-col justify-between items-center cursor-pointer hover:bg-green-100"
        @click="props.selectBalance(symbol); updateBalanceWithPrice(symbol, props.balances[symbol].available);"
    >
      
      <div class="w-full grid grid-cols-2 gap-2">
        <span class="font-semibold text-xl text-dark">{{ symbol }}</span>
        <span class="text-success font-bold text-xl text-right">
          {{ formatNumber(props.balances[symbol].available, 5) }}
        </span>
      </div>
      <div class="w-full grid grid-cols-2 gap-2">
        <span class="font-semibold text-sm text-gray-900">{{
          balancesWithPrice[ symbol ] ?
          formatNumber(balancesWithPrice[ symbol ].price)
          :
          ''
        }}</span>
        <span v-if="balancesWithPrice[ symbol ]?.balanceValue"
          class="font-semibold text-sm text-gray-900 text-right">
          <b class="text-gray-500 pr-2">$</b>{{ Math.round(balancesWithPrice[ symbol ].balanceValue) }}
          <b class="text-red-500 text-xs" v-if="parseFloat( formatNumber(props.balances[symbol].onOrder) )">({{ 
           formatNumber(props.balances[symbol].onOrder, 2)
          }})</b>
          
        </span>
      </div>
    </div>
  </div>
  <div class="col-span-2 p-4 flex justify-center items-center">
    <button @click="myDebugBtnClick" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        <!-- This can be deleted. It was created for debug purposes -->
        Update
    </button>
  </div>
</template>

<style scoped>

</style>
