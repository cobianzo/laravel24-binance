import { TickerType } from '@/types/ticker';

/**
   * Converts 23.3400000 into '23.34'
   *
   * @param {number|null} value - The number or null value to be formatted.
   * @return {string} The formatted string value.
   */
export function formatNumber(value: number | string | null, maxDecimals: number = 0, locale: boolean = true ): string {
  if (!value) return '';
  const formatted = typeof value === 'string' ? parseFloat(value) : value;
  let formattedString: string = maxDecimals && typeof formatted === 'number'? formatted.toFixed(maxDecimals) : formatted.toString();
  if (maxDecimals === 0) {
    formattedString = parseInt(formattedString).toString();
  }
  return locale ? parseFloat(formattedString).toLocaleString('en-US') : parseFloat(formattedString).toString();
}

export function getTickerInfoCurrencyFromTicker( symbol: string, allTickers : TickerType[] | null ): TickerType | null {
  const tickerInfo = allTickers !== null ? 
        allTickers.find( ticker => ticker.symbol === symbol ) 
        : null;;
  return tickerInfo ?? null;
}