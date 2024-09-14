<template>

  <h2>Add a favourite ticker</h2>
  <div class="lookup-wrapper max-w-md">
    <Lookupinput :loading="tickers === null" :items="tickers !== null ? items : []" :selectItem="mySelect" />
  </div>

</template>

<script setup lang="ts">
// types TS
import { TickerType } from '@/types/ticker';

// Vue e intertia 
import { computed } from 'vue';
import axios from 'axios';


// Internal Components dependencies
import Lookupinput, {LookupItemType} from '@/Components/LookupInput.vue';

const props = defineProps<{
  tickers: TickerType[] | null,
  test: string
}>();

const items = computed(() => {
  return props.tickers === null ? [] : props.tickers.map(t => ({ label: t.symbol, value: t.symbol }));
});

function mySelect(item: LookupItemType): void {
  const ticker: string = String(item.value);
  
  
  console.log('TODELETE. selected: ', item);
  // Enviar una petición POST al backend para añadir el ticker favorito
  axios.post('/user/fav-tickers', { ticker })
    .then((response) => {
      console.log('Ticker añadido con éxito', response.data); // TODELETE
    })
    .catch((error) => {
      console.error('Error al añadir el ticker:', error);
    });

}
</script>
