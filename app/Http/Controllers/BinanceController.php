<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;


class BinanceController extends Controller
{
    public static function test() { return "this is a test"; }
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
    public function getTickerPrice( string $symbol ) : \Illuminate\Http\JsonResponse
    {
        // @TODO: save the API base endpoint as a const.
        $response = Http::get("https://api.binance.com/api/v3/ticker/price", [
            'symbol' => $symbol
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Error fetching data from Binance'], 500);

    } // end fn

    /**
     * Retrieves the user's balances from the Binance API.
     *
     * @throws \Illuminate\Http\Client\RequestException if the request to the Binance API fails
     * @return \Illuminate\Http\JsonResponse a JSON response containing the user's balances
     */
    public static function getUserBalances() {
        $user = Auth::user();

        $response = self::sendBinanceRequest('GET', 'v3/account', [], $user);

        if ($response->successful()) {
            $balances = $response->json()['balances'];
            // return  $balances;
            $result = collect($balances)
                ->filter(fn ($balance) => $balance['free'] > 0)
                ->values()
                ->map(fn ($balance) => [
                    'symbol' => $balance['asset'],
                    'amount' => $balance['free'],
            ])->toArray();

            return response()->json($result);
        }

        return response()->json(['error' => 'Error fetching data from Binance'], 500);
    }

    // Función para crear una orden en Binance
    public function placeOrder(Request $request)
    {
        $user = Auth::user(); // Obtener el usuario autenticado

        // Parámetros de la orden (puedes añadir validaciones)
        $symbol      = $request->input('symbol');
        $quantity    = $request->input('quantity');
        $price       = $request->input('price');
        $side        = $request->input('side'); // 'BUY' o 'SELL'
        $type        = $request->input('type', 'LIMIT'); // 'LIMIT' o 'MARKET'
        $timeInForce = 'GTC'; // Good Till Canceled

        // Construcción del cuerpo de la solicitud
        $body = [
            'symbol'      => $symbol,
            'side'        => $side,
            'type'        => $type,
            'timeInForce' => $timeInForce,
            'quantity'    => $quantity,
            'price'       => $price,
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

    // Endpoint: PUT /binance/list-orders
    public static function getUserOrders( $args )
    {
        $args = array_merge(['symbol' => 'BTCUSDT'], $args);
        $response = self::sendBinanceRequest( 'GET', 'v3/allOrders', $args, Auth::user());
        
        if ($response->successful()) {
            return response()->json($response->json());
        }
        return $response->json(['error' => 'orders could not be retrievend']); // Devuelve las órdenes como JSON
    }


    // HELPER: Función estática que gestiona las solicitudes a la API de Binance
    public static function sendBinanceRequest($method, $url, $body = [], $user)
    {
        $timestamp = round(microtime(true) * 1000);
        $queryString = http_build_query(array_merge($body, ['timestamp' => $timestamp]));

        // Crear la firma para autenticar la solicitud
        $signature = hash_hmac('sha256', $queryString, $user->binance_secret_key);

        // Completar la URL con la firma
        $fullUrl = $url . '?' . $queryString . '&signature=' . $signature;

        // debug:
        // return $fullUrl;

        // Realizar la solicitud HTTP a Binance
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $user->binance_public_key,
        ])->$method('https://api.binance.com/api/' . $fullUrl);

        return $response;
    }


}


