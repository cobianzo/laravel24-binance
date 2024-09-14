<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;

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
        
        $response = Http::get("https://api.binance.com/api/v3/ticker/price", [
            'symbol' => $symbol
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Error fetching data from Binance'], 500);

    } // end fn
}
