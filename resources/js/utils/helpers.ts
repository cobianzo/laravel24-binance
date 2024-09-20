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

// given 0.001, returns 3. For 0.00001, returns 5.
export function countDecimals(number: number) {
  const str = number.toString();
  
  // Check if the number has a decimal point and count digits after it
  if (str.includes('.')) {
    return str.split('.')[1].length;
  } else {
    return 0; // If there's no decimal point, it has 0 decimal places
  }
}


export function stepSizeDecimalsForTicker( symbol: string, allTickers: TickerType[] ) {
  const find = allTickers.find( t => t.symbol === symbol );
  if (find?.stepSize) {
    return countDecimals(find.stepSize);
  }
  return 0;
}