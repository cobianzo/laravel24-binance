<?php

use App\Http\Controllers\BinanceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Binance routes

// > Usage: /binance/prices/BTCUSDT 
Route::get('/binance/prices/{symbol}', [ BinanceController::class, 'getTickerPrice' ]);
// > return { "symbol": "BTCUSDT", "price": "58071.21000000" }

Route::get('/binance/alltickers', [ BinanceController::class, 'getAllTickers' ]);
Route::post('/user/fav-tickers', [ProfileController::class, 'addFavTicker'])->middleware('auth');
