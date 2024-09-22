import { TripleOrderType } from '@/types/ticker';
import axios from 'axios';

export const orderIsSelectedForCreatingATrade 
  : (orderId: string|number, currentTradeCreating: TripleOrderType) => string = 
  (orderId, currentTradeCreating) => {

  const orderIdString = orderId.toString();
  if ( orderIdString === currentTradeCreating.originalEntryOrder ) {
    return 'originalEntryOrder';
  }
  if ( orderIdString === currentTradeCreating.closingGainOrder ) {
    return 'closingGainOrder';
  }
  if ( orderIdString === currentTradeCreating.closingLossOrder ) {
    return 'closingLossOrder';
  }
  return '';
}
export const numberOrdersMatchingSelected = function(currentTradeCreating : TripleOrderType) : number {
  let count = 0;
  count += currentTradeCreating.originalEntryOrder ? 1 : 0;
  count += currentTradeCreating.closingGainOrder ? 1 : 0;
  count += currentTradeCreating.closingLossOrder ? 1 : 0;
  return count;
}

export const indexMatchingOrder = function (
    orderId: string,
    tradesTripleOrdersArray: TripleOrderType[]
  ) : number {
  if (!orderId.length) return 0;
  
  if ( tradesTripleOrdersArray.length === 0 ) return 0;
  
  let indexFound = tradesTripleOrdersArray.findIndex( tripleOrder => (
      tripleOrder.originalEntryOrder === orderId
      || tripleOrder.closingGainOrder === orderId
      || tripleOrder.closingLossOrder === orderId
  ))
  const indexFoundNullable = indexFound > -1 ? indexFound + 1 : 0;
  
  return indexFoundNullable;
}


// All functions to cooperate with backend, loading info from DB.

// Function to load trade groups for a specific symbol
export const loadTradeGroupsForSymbol = async (symbol: string, callbackOnSuccess: (data: any) => void) => {
  try {
    const response = await axios.get(`/profile/trade-groups/${symbol}`);
    if (response.status === 200) {
      callbackOnSuccess(response.data);
    }
  } catch (error) {
    console.error('Error loading trade groups:', error);
  }
};

export const saveTradeGroupsForSymbol = async (symbol: string | undefined, valueArray: TripleOrderType[]) : Promise<any> => {
  
  if (!symbol) return;
  return new Promise( (resolve, reject) => {
    try {
      axios.post(`/profile/trade-groups/${symbol}`, {
        trade_groups: valueArray,
      }).then( response => {
        if (response.status === 200) {
          resolve(response.data);
        }
      }).catch( error => {
        reject(error);
      })
    } catch (error) {
      reject(error);
    }
  });
};