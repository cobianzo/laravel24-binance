<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;

class BinanceController extends Controller
{
    /**
     * Related Endpoint: /binance/prices/BTCUSDT
     * Retrieves the current ticker price from the Binance API.
     *
     * @param string $ticker The ticker symbol to retrieve the price for.
     * @return array|\Illuminate\Http\JsonResponse The response from the Binance API.
     */
    public function getTickerPrice( string $symbol ) : array|\Illuminate\Http\JsonResponse
    {
        // Instanciar el cliente de Guzzle
        $client = new Client();

        try {

            $response = $client->get("https://api.binance.com/api/v3/ticker/price?symbol={$symbol}");
            return json_decode($response->getBody(), true);

        } catch (\Exception $e) {
            // Manejar errores y devolver una respuesta adecuada
            return response()->json([
                'error' => 'No se pudo obtener el precio del ticker',
                'message' => $e->getMessage(),
            ], 500);
        }
    } // end fn
}
