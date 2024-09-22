import { TripleOrderType } from '@/types/ticker';

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

