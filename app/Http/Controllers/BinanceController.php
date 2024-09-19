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

    public static function getBinanceApi() {
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
    public function getTickerPrice( string $symbol )
    {
        $api    = self::getBinanceApi();
        return $api->price($symbol);
    } // end fn

    /**
     * Retrieves the user's balances from the Binance API.
     *
     * @throws \Illuminate\Http\Client\RequestException if the request to the Binance API fails
     * @return \Illuminate\Http\JsonResponse a JSON response containing the user's balances
     */
    public static function getUserBalances() {
        $api = self::getBinanceApi();
        $balances = $api->balances();
        $balances = array_filter($balances, function ($balance) {
            return 0 !== intval( floatval( $balance['available'] ) + floatval( $balance['onOrder'] ) );
        });
        return $balances;
    }

    // Función para crear una orden en Binance
    public function placeOrder(\Illuminate\Http\Request $request)
    {
        $user = Auth::user(); // Obtener el usuario autenticado

        // Assuming you're using Laravel and have set up HTTP requests
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

}


