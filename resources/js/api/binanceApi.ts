import axios from 'axios';

export interface BinancePriceResponse {
  symbol: string;
  price: string;
}

// Función para obtener el precio de una moneda específica
export const getBinancePrice = async (symbol: string): Promise<BinancePriceResponse> => {
  try {
    const response = await axios.get<BinancePriceResponse>(`/binance/prices/${symbol}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching Binance price:', error);
    throw error;
  }
};
