<?php

namespace App\Http\Controllers;
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

        // Create signature with the secret key, unique for the current time
        $timestamp   = round(microtime(true) * 1000);
        $queryString = 'timestamp=' . $timestamp;
        $signature   = hash_hmac('sha256', $queryString, $user->binance_secret_key);
        $url = 'https://api.binance.com/api/v3/account'
            . '?' . $queryString
            . '&signature=' . $signature;

        $response = Http::withHeaders([
            'X-MBX-APIKEY'     => $user->binance_public_key
        ])->get($url);
        
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
}
