<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

use Binance\API;

class BinanceController extends Controller
{

    const TESTING       = 0;
    const API_BASE      = 'https://api.binance.com/api/';
    const API_BASE_TEST = 'https://testnet.binance.vision/api/';

    const WEBSOCKET_ORDERS = 'https://api.binance.com/api/v3/userDataStream';

    public static function getBinanceApi() : \Binance\API {
        return new API(self::get_public_key(), self::get_secret_key());
    }
    public static function get_api_base() {
        return self::TESTING ? self::API_BASE_TEST : self::API_BASE;
    }
    public static function get_public_key() {
        if ( self::TESTING ) {
            $test_public = 'WCXbqk9MuCQ5NahmiTVmizTG3lBzug0NBx28rKJdwwUDlQvK3CSH0xpLhTrMp11I';
            return $test_public;
        }
        return Auth::user()->binance_public_key;
    }
    public static function get_secret_key() {
        if ( self::TESTING ) {
            $test_secret = 'kK0467IKkywRwJFopD52WKuPESx80ZebVQdxBn8H6cetam1uh0CRE19eZHQva0um';
            return $test_secret;
        }
        return Auth::user()->binance_secret_key;
    }



    public static function handleTestData() { 


        // Get exchange information, including lot size details for each symbol
        $api = self::getBinanceApi();
        $exchangeInfo = $api->exchangeInfo();
        $symbols = $exchangeInfo['symbols'];
        $tickerInfo = [];
        foreach($symbols as $symbol => $data) {
            $filter = array_filter($data['filters'], fn($f) => ($f['filterType'] === 'LOT_SIZE'));
            if (!empty($filter)) {
                $filter = array_values($filter)[0];
                $stepSize = isset($filter['stepSize'])? $filter['stepSize'] : 'N/A';
            }
            $tickerInfo[$symbol] = $stepSize;
            
        }
        $tickers = json_decode(
            file_get_contents(storage_path('app/public/tickers.json')),
            true
        );
        echo '[';
        foreach ($tickers as $ticker) {
            $symbol = $ticker[0];
            $stepSize = !empty( $tickerInfo[$symbol] ) ? $tickerInfo[$symbol]: 'Not';
            echo '[';
            echo '"'.$ticker[0].'",';
            echo '"'.$ticker[1].'",';
            echo ''.$ticker[2].',';
            echo '"'.$ticker[3].'",';
            echo ''.$ticker[4].',';
            echo ",$stepSize]<br>";
        }
        echo ']';
        
        


     }
    /**
     * Retrieves and formats all available tickers from the storage file.
     * 
     * @return array A list of arrays containing ticker information.
     */
    public static function getAllTickers() : array
    {
        $tickers = json_decode(
            file_get_contents(storage_path('app/public/tickers.json')),
            true
        );
        $tickers = array_map(fn ($ticker) => [
            'symbol'         => $ticker[0],
            'base'           => $ticker[1],
            'precisionBase'  => $ticker[2],
            'asset'          => $ticker[3],
            'precisionAsset' => $ticker[4],
            'stepSize'       => $ticker[5],
        ], $tickers);
        
        return $tickers;
    }

    /**
     * Related Endpoint: /binance/prices/BTCUSDT
     * Retrieves the current ticker price from the Binance API.
     *
     * @param string $ticker The ticker symbol to retrieve the price for.
     * @return \Illuminate\Http\JsonResponse The response from the Binance API.
     */
    public function getTickerPrice( string $symbol )
    {
        $api    = self::getBinanceApi();
        return $api->price($symbol);
    } // end fn

    /**
     * Retrieves the user's balances from the Binance API.
     *
     */
    public static function getUserBalances() {
        $api = self::getBinanceApi();
        $balances = $api->balances();
        $balances = array_filter($balances, function ($balance) {
            return 0 !== intval(ceil( floatval( $balance['available'] ) + floatval( $balance['onOrder'] ) ));
            // return 1;
        });
        return $balances;
        // { BTC => { available: '0.00000000', onOrder: '0.00000000', btcValue, btcTotal }
    }

    // Función para crear una orden en Binance
    public function placeOrder(\Illuminate\Http\Request $request)
    {
        $user = Auth::user(); // Obtener el usuario autenticado
        
        // Validate the incoming request parameters
        $validated = $request->validate([
            'symbol' => 'required|string',     // Trading pair, e.g., BTCUSDT
            'quantity' => 'required|numeric',  // Quantity to buy
            'price' => 'required|numeric',     // Price for the LIMIT order
        ]);

        // Extract validated parameters
        $symbol = $validated['symbol'];
        $quantity = $validated['quantity'];
        $price = $validated['price'];

        $side  = $request->input('side'); // 'BUY' o 'SELL'
        $type  = $request->input('type', 'LIMIT'); // 'LIMIT' o 'MARKET'
        $timeInForce = 'GTC'; // Good Till Canceled

        $api = self::getBinanceApi();
        if ( $side === 'BUY' ) {
            try {
                $response = $api->buy($symbol, $quantity, $price, $type);
                return $response;
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }

        // Construcción del cuerpo de la solicitud
        $body = [
            'symbol'      => $symbol,
            'side'        => $side,
            'type'        => $type,
            // 'stopPrice'   => $price + 1,
            'quantity'    => number_format($quantity, 0, '.', ''),
            'price'       => number_format($price, 0, '.', ''),
            'recvWindow'  => 60000,
        ];

        // Llamada a la función estática que interactúa con la API de Binance
        $response = self::sendBinanceRequest('POST', 'v3/order', $body, $user);

        return response()->json($response->json());
    }

    // Método para colocar una orden OCO
    public function placeOCOOrder(Request $request)
    {
        $user = Auth::user(); // Obtener el usuario autenticado

        // Parámetros de la orden OCO (puedes añadir validaciones)
        $symbol           = $request->input('symbol'); // eg BTCUSDT
        $side             = $request->input('side'); // 'BUY' o 'SELL'
        $quantity         = $request->input('quantity');  // eg 0.01 BTC
        $price            = $request->input('price'); // Límite . eg 58071.21000000
        $stopPrice        = $request->input('stopPrice'); // Precio de activación para la orden stop
        $stopLimitPrice   = $request->input('stopLimitPrice'); // Precio límite de la orden stop
        $stopLimitTimeInForce = 'GTC'; // Good Till Canceled, podría configurarse dinámicamente

        // Construcción del cuerpo de la solicitud
        $body = [
            'symbol'          => $symbol,
            'side'            => $side,
            'quantity'        => $quantity,
            'price'           => $price,
            'stopPrice'       => $stopPrice,
            'stopLimitPrice'  => $stopLimitPrice,
            'stopLimitTimeInForce' => $stopLimitTimeInForce,
        ];

        // Llamada a la función estática que interactúa con la API de Binance
        $response = self::sendBinanceRequest('POST', 'v3/order/oco', $body, $user);

        return $response;
    }

    
    // Endpoint: DELETE /binance/cancel-order
    public function cancelOrder(\Illuminate\Http\Request $request)
    {
        // return $request->input('symbol') . '/// ' . $request->input('orderId');
        $api = \App\Http\Controllers\BinanceController::getBinanceApi();
        $response = $api->cancel(
            $request->input('symbol'),
            $request->input('orderId')
        );
        return $response;
    }

    // Endpoint: PUT /binance/list-orders
    public static function getUserOrders( $args )
    {
        $args   = array_merge(['symbol' => 'BTCUSDT'], $args);
        $api    = self::getBinanceApi();
        $orders = $api->orders($args['symbol'], $args['limit'] ?? 500);
        return array_reverse($orders);
    }


    // HELPER: Función estática que gestiona las solicitudes a la API de Binance.
    // Works well, but with the "jaggedsoft/php-binance-api": we dont need it.
    // Usage: $response = self::sendBinanceRequest('GET', 'v3/allOrders', ['symbol' => 'BTCUSDT'], Auth::user());
    
    public static function sendBinanceRequest($method, $url, $body = [], $user)
    {
        $timestamp = round(microtime(true) * 1000);
        $queryString = http_build_query(array_merge($body, ['timestamp' => $timestamp]));

        // Crear la firma para autenticar la solicitud
        $signature = hash_hmac('sha256', $queryString, self::get_secret_key());

        // Completar la URL con la firma
        $fullUrl = $url . '?' . $queryString . '&signature=' . $signature;

        // debug:
        // return $fullUrl;

        // Realizar la solicitud HTTP a Binance
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => self::get_public_key(),
        ])->$method( self::get_api_base() . $fullUrl);

        return $response;
    }


    // websokets. I could not make it work
    /* public static function createListenKey() {
        

        $response = self::sendBinanceRequest('POST',
            'v1/userDataStream', [], Auth::user());

        return $response->json();
        $listenKey = $response['listenKey'];
        $user->binance_listen_key = $listenKey;
        $user->save();
        
        return $listenKey;;  // Esto devolverá el Listen Key al frontend
    }

    // Debes actualizar la listen key cada 60 minutos para que la conexión siga viva. 
    // @TODO: crear un job que se ejecute de manera periódica.
    public function keepAliveListenKey() {
        $user = auth()->user();
        
        if ($user->binance_listen_key) {
            // Actualizar la listen key
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $user->binance_public_key
            ])->put( self::WEBSOCKET_ORDERS, [
                'listenKey' => $user->binance_listen_key
            ]);
        }
    }

    public function closeListenKey() {
        $user = Auth::user();  // Usuario autenticado

        $listenKey = $user->binance_listen_key;  // Guarda el listenKey en tu modelo de usuario

        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $user->binance_public_key,
        ])->delete( self::WEBSOCKET_ORDERS, [
            'listenKey' => $listenKey,
        ]);

        // Limpiar la listen key en la base de datos
        $user->binance_listen_key = null;
        $user->save();

        return $response->json();
    } */
}


