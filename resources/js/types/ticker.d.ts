export interface TickerType {
  symbol: string;
  base: string;
  precisionBase: number;
  asset: string;
  precisionAsset: number;
}

export interface TickerPriceType {
  symbol: string;
  price: number;
}