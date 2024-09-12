<?php

use App\Http\Controllers\BinanceController;
use Illuminate\Support\Facades\Route;

// Binance routes

// > Usage: /binance/prices/BTCUSDT 
Route::get('/binance/prices/{symbol}', [ BinanceController::class, 'getTickerPrice' ]);
// > return { "symbol": "BTCUSDT", "price": "58071.21000000" }

