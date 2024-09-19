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
  console.log('TODELET:all info for account user: ', response);
  return response.data;
}

// place orders functions
export const placeBinanceOrder = async( symbol: string, quantity: number, price: number, side: 'BUY' | 'SELL', type: 'LIMIT' | 'MARKET' = 'LIMIT') => {
  try {
    const response = await axios.post('/binance/order', {
      symbol,
      quantity,
      price,
      side,
      type: type ?? 'LIMIT'
    });
    console.log('Order placed:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error placing order:', error);
    return null;
  }
}



export const placeBinanceOCOOrder = async function (
  symbol: string,
  side: 'BUY' | 'SELL',
  quantity: number,
  price: number,
  stopPrice: number,
  stopLimitPrice: number
) {
    try {
        // Enviar los parámetros de la orden OCO al backend
        const response = await axios.post('/binance/order/oco', {
            symbol: symbol,            // Ticker del par (ej: BTCUSDT)
            side: side,                // 'BUY' o 'SELL'
            quantity: quantity,        // Cantidad que se quiere operar
            price: price,              // Precio límite - vende y recibe ganancia
            stopPrice: stopPrice,      // Precio de activación del Stop-Limit a stopLimitPrice
            stopLimitPrice: stopLimitPrice // Precio límite del Stop-Limit: vender y aceptar perdidas
        });

        // Si la orden es exitosa, imprime los resultados
        console.log('OCO Order placed successfully:', response.data);
    } catch (error) {
        // Manejar errores
        console.error('Error placing OCO order:', error);
        alert('Error placing order. Please try again.');
    }
}

export const getUserOrders = async function( tickerSymbol: string) {
  const response = await axios.put(`/binance/list-orders?symbol=${tickerSymbol}`);
  return response.data; 
}