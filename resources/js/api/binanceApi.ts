import axios from 'axios';
import { TickerPriceType } from '@/types/ticker';

// Función para obtener el precio de una moneda específica
// returns { symbol: BTCUSTD, price: 56452.0004344}
export const getBinancePrice = async (symbol: string): Promise<TickerPriceType> => {
  try {
    const response = await axios.get<TickerPriceType>(`/binance/prices/${symbol}`);
    return response.data;
  } catch (error) {
    console.error(`Error fetching Binance price for ${symbol}:`, error);
    throw error;
  }
};

export const getUserBalances = async () => {
  const response = await axios.put(`/binance/balances`);
  return response.data;
  console.log(response);
}
