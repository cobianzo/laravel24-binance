import axios from 'axios';
import { TickerPriceType } from '@/types/ticker';

// Función para obtener el precio de una moneda específica
// returns { symbol: BTCUSTD, price: 56452.0004344}
export const getBinancePrice = async (symbol: string): Promise<number> => {
  const price = await axios.get<number>(`/binance/prices/${symbol}`);
  return price.data ?? 0;
};

export const getUserBalances = async () => {
  console.log('TODELET:all ANTES: ');
  const response = await axios.put(`/binance/balances`);
  console.log('%cTODELET:all DESPUES: ', 'color:white;background:red;font-size:2rem',response.data);
  return response.data;
}

// place orders functions
export const placeBinanceOrder = async( symbol: string, quantity: number, price: number, side: 'BUY' | 'SELL', type: 'LIMIT' | 'MARKET' = 'LIMIT') => {
  try {
    console.info('TODELETE: placing a buying order for ', symbol, quantity, price, side, type);
    const response = await axios.post(`/binance/place-order`, {
      symbol, quantity, price, side, type
    });
    console.info('TODELETE respuesta de place order: ',response.data);
  } catch (error) {
    console.error('Error placing order:', error);
    return null;
  }
}



export const placeBinanceOCOOrder = async function (
  symbol: string,
  side: 'BUY' | 'SELL',
  quantity: number,
  price: number,        // price of sell to gain
  stopPrice: number,    // trigger to set the next line
  stopLimitPrice: number// price to seel to lose
) {
    try {
      console.log('OCO Order placeing... BEFORE successfully:', symbol, side, quantity, price, stopPrice, stopLimitPrice);
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

export const getUserOrders = async function( tickerSymbol: string, limit: number = 0) {
  const response = await axios.get(`/binance/list-orders?symbol=${tickerSymbol}&limit=${limit}`);
  return response.data; 
}

export const cancelOrder = async function( symbol: string, orderId: string) {
  const response = await axios.delete(`/binance/cancel-order`, { 
    data: { symbol: symbol, orderId: orderId } 
  });
  return response;
}

// JUST FOR TESTING @TODELETE:
export const apiCallTest = async function( justOneParam: string) {
  console.log('%cBEFORE CALL /test-data', 'background:red;color:white;font-size:2rem;', justOneParam);
  const response = await axios.post('/binance/create-listen-key');
  console.log('%cAFTER CALL', 'background:red;color:white;font-size:2rem;', response.data);
  return response.data; 
}
