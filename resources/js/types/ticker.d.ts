export interface TickerType {
  symbol: string;
  base: string;
  precisionBase: number;
  asset: string;
  precisionAsset: number;
  balanceAsset?: number; 
  stepSize: number;
}

export interface TickerPriceType {
  symbol: string;
  price: number;
  isDeleting?: boolean; // Prescindible: just to add an animation when we delete it.
}

export interface BalanceType {
  available: string | number,
  btcTotal?: string | number,
  btcValue?:string | number,
  onOrder?: string | number,
};

export interface AllBalancesType {
  [string] : BalanceType
};

export interface TradeOrderType {
  symbol: string;
  amount?: number;
  quantity: number; // quantity depends on amount
  price: number;
  side: 'BUY' | 'SELL';
  type: 'LIMIT' | 'MARKET';
}

// @TODO: not ticker anymore, rename the file or create a new one
export interface OrderBinanceType {
  clientOrderId: string,
  cummulativeQuoteQty: string,
  executedQty: string,
  icebergQty: string,
  isWorking: boolean,
  orderId: number
  orderListId: number
  origQty: string,
  origQuoteOrderQty: string,
  price: string
  selfTradePreventionMode: string
  side: string,
  status: string,
  stopPrice: string,
  symbol: string,
  time: number,
  timeInForce: string,
  type: string,
  updateTime: number,
  workingTime: number,

  // extending binance fields with my own date
  tradeStatus?: string, // if the trade has been closed with an OCO - 'gain' / 'loss' . If is filled but not closed: 'active'
  GainOrLoss?: number, // the amount in asset currecny (USDT) won or lost.
}

export interface TripleOrderType {
  originalEntryOrder: string|null,
  closingGainOrder: string|null,
  closingLossOrder: string|null,
}

export interface TripleOrdersAPIType {
  // the model (reactive data)
  tradesGroupedInTripleOrders: Ref<TripleOrderType[]>, 
  // methods
  currentTripleOrder: Ref<TripleOrderType>,
  clearCurrentTripleOrder: () => TripleOrderType,
  selectCurrentTripleOrder: (arg0:string, arg1: string, arg2?: boolean = true) => void,
  saveCurrentTripleOrder: () => void,
  deleteTradeContainingOrder: (arg0: string) => void
}
