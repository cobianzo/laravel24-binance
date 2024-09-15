<script setup lang="ts">
  import { BalanceType } from '@/types/ticker';
  import { watch, ref } from 'vue';
  import { getBinancePrice } from '@/api/binanceApi';

  // propd coming from the parent
  const props = defineProps<{
    balances: BalanceType[] | null,
    selectBalance: (arg0: BalanceType) => void
  }>();

  // state. For every currency in the portfolio, we ask to biance the price in USDT
  const balancesWithPrice = ref<{ [symbol: string] : { price: number | null, balanceValue: number } }>({});

  const updateBalancesWithPrice = async (newBalances : BalanceType[] | null) => {
    if (newBalances === null) {
        balancesWithPrice.value = {};
      } else {
        // let newBalancesWithPrice: { [ticker: string]: number } = {};
        
        const setOfPromises = newBalances.map(async (balance) => {
            const price = await getUSDPriceForSymbol(balance.symbol);
            if (price) {
              const currencyInfo = {
                price,
                balanceValue: balance.amount * price
              }
              balancesWithPrice.value[balance.symbol] = currencyInfo;
            }
        });
        
        await Promise.all(setOfPromises);
        console.log('Finished CALCULATED all balances in USDT: ', balancesWithPrice.value );
        // @TODO: Now we could sort the currencies in order of balance in USDT.
        
      }
  };
  watch(
    () => props.balances,
    async (newBalances) => {
      updateBalancesWithPrice(newBalances);
    },
    { immediate: true }
  );

  // methods
  const getUSDPriceForSymbol = async function (symbol: string, toSymbol : string = 'USDT'): Promise<number> {
    if ( toSymbol === symbol ) {
      return 1;
    }
    let price = { price: 0, symbol: ''};
    try {
      price = await getBinancePrice(`${symbol}${toSymbol}`);
    } catch (e) {
      console.warn(`No price for ${symbol}`);
    }
    console.log(`Calculated price for ${symbol} into ${toSymbol}:`, price);
    if (price?.price) {
      return price.price;
    }
    return 0;
  }

  function myDebugBtnClick() {
    console.log('clicked!!');
    updateBalancesWithPrice(props.balances);
  }

  function formatNumber(value: number | null ): string {
    if (!value) return '';
    return parseFloat(value.toString()).toString();
  }

</script>
<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div
        v-if="props.balances !== null"
        v-for="currencyAndBalance in props.balances"
        :key="currencyAndBalance.symbol"
        class="p-4 bg-gray-100 rounded-lg shadow-md flex flex-col justify-between items-center cursor-pointer hover:bg-red-100"
        @click="props.selectBalance(currencyAndBalance)"
    >
        <!-- @TODO: Create this as a separated component -->
        <div class="w-full grid grid-cols-2 gap-2">
          <span class="font-semibold text-xl text-dark">{{ currencyAndBalance.symbol }}</span>
          <span class="text-success font-bold text-xl text-right">
            {{ formatNumber(currencyAndBalance.amount) }}
          </span>
        </div>
        <div class="w-full grid grid-cols-2 gap-2">
          <span class="font-semibold text-sm text-gray-900">{{
            balancesWithPrice[ currencyAndBalance.symbol ] ?
            formatNumber(balancesWithPrice[ currencyAndBalance.symbol ].price)
            :
            ''
          }}</span>
          <span v-if="balancesWithPrice[ currencyAndBalance.symbol ]?.balanceValue"
            class="font-semibold text-sm text-gray-900 text-right">
            <b class="text-gray-500 pr-2">$</b>{{ Math.round(balancesWithPrice[ currencyAndBalance.symbol ].balanceValue) }}
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
