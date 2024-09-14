<!--
Input with lookup, given a list of possible results
@TODO: we can include a lazy load of the list of items, on focus.
@TODO: add keys up and down to select between the results
-->
<template>
  <div class="relative">
    <!-- Input de búsqueda -->
    <input
      type="text"
      v-model="inputValue"
      @keydown="debounceFilterItems"
      @blur="hideResults"
      class="border rounded-md p-2 w-full focus:outline-none focus:ring-2 focus:ring-indigo-500"
      placeholder="Search..."
    />

    <!-- Spinner mientras no haya ítems -->
    <div v-if="loading" class="absolute top-full left-0 w-full bg-white border rounded-md p-2 mt-2 flex justify-center">
      <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
      </svg>
    </div>

    <!-- Lista de resultados -->
    <ul v-if="filteredItems.length > 0" class="absolute w-full bg-white border rounded-md mt-2 max-h-40 overflow-y-auto z-10">
      <li
        v-for="(item, index) in filteredItems.slice(0, 10)"
        :key="item.value"
        @click="selectItem(item)"
        class="p-2 cursor-pointer hover:bg-indigo-100"
      >
        {{ item.label }}
      </li>
    </ul>
  </div>
</template>

<script lang="ts">
import { ref, watch } from 'vue';

export type LookupItemType = {
  label: string;
  value: string | number | Record<any, any> | Array<any>;
  // hidden label allows to make it searchable by other names 
  // (eg. label 'Leon' will be found by 'León', if we add it as hiddenLabel)
  hiddenLabel?: string;
};
export default {
  props: {
    items: {
      type: Array as () => LookupItemType[],
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
      required: false,
    },
    debounce: {
      type: Number,
      default: 200,
      required: false
    },
    selectItem: {
      type: Function,
      required: true,
    },
  },
  setup(props) {
    const inputValue = ref('');
    const filteredItems = ref<LookupItemType[]>([]);
    let debounceTimeout: number | undefined = undefined;

  /**
   * Filters the items based on the inputValue
   */
    const debounceFilterItems = (event: KeyboardEvent) => {

      // cliking return chooses the first elment
      if (event.key === 'Enter') {
        if (filteredItems.value.length) {
          selectItem(filteredItems.value[0]);
        }
        hideResults();
      }

      // Otherwise let's filter the list. 
      clearTimeout(debounceTimeout);

      debounceTimeout = setTimeout(() => {

        if (inputValue.value === '') {
          filteredItems.value = [];
        } else {
          let notStartByItems: LookupItemType[] = [];
          let startByItems: LookupItemType[] = [];
          if (props.items && props.items.length) {
            props.items.forEach( item => {
              // From the result of the filtered items, prioritize the ones which START by the inputValue.
              const hasMatch = item.label.toLowerCase().includes(inputValue.value.toLowerCase()) ||
              (item.hiddenLabel && item.hiddenLabel.toLowerCase().includes(inputValue.value.toLowerCase()));
              if (hasMatch) {
                if ( item.label.toLowerCase().indexOf(inputValue.value.toLowerCase()) === 0 ) {
                  startByItems.push(item);
                } else {
                  notStartByItems.push(item) ;
                }
              }
            });
          }
          // resort the result placing the ones that start by the input first.
          filteredItems.value = props.items === null ? [] : startByItems.concat(notStartByItems);
        
        }
      }, props.debounce);
    };

    const hideResults = () => {
      setTimeout(() => {
        filteredItems.value = [];
      }, 200); // Delay para evitar cerrar antes de hacer click
    };

    const selectItem = (selectedItem: LookupItemType) => {
      props.selectItem(selectedItem);
      inputValue.value = ''; // Limpiar el input
      hideResults();
    };

    return {
      inputValue,
      filteredItems,
      debounceFilterItems,
      hideResults,
      selectItem,
    };
  },
};
</script>

<style scoped>
/* Añade cualquier estilo personalizado adicional aquí si lo necesitas */
</style>

