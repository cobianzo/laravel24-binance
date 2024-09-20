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