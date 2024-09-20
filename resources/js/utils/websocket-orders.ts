/**
 * @TODO: I'm not using this file
 * 
 * I can't create the ListenKey from PHP. I get an error
 *   "msg" => "Too many parameters; expected '0' and received '3'."
 * I should be using the Binance PHP API. But I gave up.
 * 
 */

import axios from 'axios';

window.wsOrders = null;
export const generateListenKey = async (): Promise<string> => {
  console.log('>>>> Before retrievign listen key');
  const response = await axios.post('/binance/create-listen-key');
  console.log('>>>> After retrievign listen key', response.data);
  return response.data.listenKey;
};

// this endpoint doesnt exist yet. It should retrieve the user->binance_listen_key
// export const getListenKey = async (): Promise<string | null> => {
//   const response = await axios.get('/binance/get-listen-key');
//   return response.data.listenKey;
// };

export const startWebSocket = async (): Promise<WebSocket | null> => {
  const listenKey = await generateListenKey();
  if (listenKey) {
    closeWebSocket();
    window.wsOrders = new WebSocket(`wss://stream.binance.com:9443/ws/${listenKey}`);
    console.log('>>>> started websocket', window.wsOrders);

    window.wsOrders.onopen = () => {
      console.log('>>>> open websocket. Subscribing', window.wsOrders);
      window.wsOrders.send(JSON.stringify({
        method: 'SUBSCRIBE',
        params: [`${props.selectedTickerInfo.symbol.toLowerCase()}@trade`],
        id: 1
      }));
    };

    return window.wsOrders;
  }
  return null;
};

export const closeWebSocket = () => {
  if (window.wsOrders) {
    console.log('>>>> closing websocket.', window.wsOrders);
    window.wsOrders.close();
    window.wsOrders = null;
  }
};
