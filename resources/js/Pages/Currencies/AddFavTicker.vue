<template>

  <h2>Add a favourite ticker</h2>
  <div class="lookup-wrapper max-w-md">
    <Lookupinput :loading="allTickers === null" :items="allTickers !== null ? items : []" :selectItem="addFavWhenItemSelected" />
  </div>

</template>

<script setup lang="ts">
// types TS
import { TickerType } from '@/types/ticker';

// Vue e intertia 
import { ref, computed } from 'vue';
import axios from 'axios';


// Internal Components dependencies
import Lookupinput, {LookupItemType} from '@/Components/LookupInput.vue';

// props sent by parent.
const props = defineProps<{
  allTickers: TickerType[] | null,
  exclude?: string[],
  updateFavTickersFrom: () => void
  test: string
}>();

const items = computed(() => {
  let mappedTickers = props.allTickers === null ? [] : props.allTickers.map(t => ({ label: t.symbol, value: t.symbol }));
  if (props.exclude !== undefined && props.exclude.length) {
    mappedTickers = mappedTickers.filter( (t) => ! props.exclude?.includes(t.value) )
  }
  return mappedTickers;
});

// @TODO: to make this component universal, we should move this function outside the component and pass as prop.
function addFavWhenItemSelected(item: LookupItemType): void {
  const ticker: string = String(item.value);
  
  
  console.log('TODELETE. selected: ', item);
  // Enviar una petición POST al backend para añadir el ticker favorito
  axios.post('/user/fav-tickers', { ticker })
    .then((response) => {
      props.updateFavTickersFrom();
      console.log('Ticker añadido con éxito', response.data); // TODELETE
    })
    .catch((error) => {
      console.error('Error al añadir el ticker:', error);
    });

}
</script>
