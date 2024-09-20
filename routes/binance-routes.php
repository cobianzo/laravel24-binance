<?php

use App\Http\Controllers\BinanceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// External requests to Binance.
Route::get('/binance/prices/{symbol}', [ BinanceController::class, 'getTickerPrice' ]);
// > Usage: /binance/prices/BTCUSDT  > returns { "symbol": "BTCUSDT", "price": "58071.21000000" }
Route::get('/binance/alltickers', [ BinanceController::class, 'getAllTickers' ]);
Route::put('/binance/balances', [BinanceController::class, 'getUserBalances'])->middleware('auth');

// orders:
Route::post('/binance/place-order', [BinanceController::class, 'placeOrder'])->middleware('auth');
Route::post('/binance/order/oco', [BinanceController::class, 'placeOCOOrder'])->middleware('auth')->name('binance.placeOCOOrder');
Route::get('/binance/list-orders', fn() => BinanceController::getUserOrders(request()->all()))->middleware('auth')->name('binance.listOrders');
Route::delete('/binance/cancel-order', [BinanceController::class, 'cancelOrder'])->middleware('auth')->name('binance.cancelOrder');


// JUST FOR TESTING @TODELETE
Route::get('/test-data', [BinanceController::class, 'handleTestData'])->middleware('auth');


// Internal Binance routes
Route::put(   '/user/fav-tickers',          [ProfileController::class, 'getFavTicker'])   ->middleware('auth');
Route::post(  '/user/fav-tickers',          [ProfileController::class, 'addFavTicker'])   ->middleware('auth');
Route::delete('/user/fav-tickers/{ticker}', [ProfileController::class, 'deleteFavTicker'])->middleware('auth');


// Websockets functions for orders
http://127.0.0.1:8000/binance/test-endpoint
Route::get('/binance/test-endpoint', function() {
  $api = \App\Http\Controllers\BinanceController::getBinanceApi();
  $response = $api->cancel(
      'BTCUSDT',
      '30593458536'
  );
  dd($response);
})->middleware('auth')->name('binance.testEndpoint');
