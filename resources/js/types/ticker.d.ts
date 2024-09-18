export interface TickerType {
  symbol: string;
  base: string;
  precisionBase: number;
  asset: string;
  precisionAsset: number;
  balance?: number; 
}

export interface TickerPriceType {
  symbol: string;
  price: number;
  isDeleting?: boolean; // Prescindible: just to add an animation when we delete it.
}

export interface BalanceType {
  amount: number;
  symbol: string;
}