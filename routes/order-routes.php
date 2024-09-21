<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;


Route::get('/orders', [OrderController::class, 'getLastOrders'])->middleware('auth')->name('orders');