<?php

use App\Http\Controllers\BinanceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// External requests to Binance.
Route::get('/binance/prices/{symbol}', [ BinanceController::class, 'getTickerPrice' ]);
// > Usage: /binance/prices/BTCUSDT  > returns { "symbol": "BTCUSDT", "price": "58071.21000000" }
Route::get('/binance/alltickers', [ BinanceController::class, 'getAllTickers' ]);
Route::put('/binance/balances', [BinanceController::class, 'getUserBalances'])->middleware('auth');


// Internal Binance routes
Route::put(   '/user/fav-tickers',          [ProfileController::class, 'getFavTicker'])   ->middleware('auth');
Route::post(  '/user/fav-tickers',          [ProfileController::class, 'addFavTicker'])   ->middleware('auth');
Route::delete('/user/fav-tickers/{ticker}', [ProfileController::class, 'deleteFavTicker'])->middleware('auth');

