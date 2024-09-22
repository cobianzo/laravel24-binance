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
Route::post('/binance/order/oco', [BinanceController::class, 'placeOCOOrderCurl'])->middleware('auth')->name('binance.placeOCOOrder');
Route::get('/binance/list-orders', fn() => BinanceController::getUserOrders(request()->all()))->middleware('auth')->name('binance.listOrders');
Route::delete('/binance/cancel-order', [BinanceController::class, 'cancelOrder'])->middleware('auth')->name('binance.cancelOrder');


// JUST FOR TESTING @TODELETE
Route::get('/test-data', [BinanceController::class, 'handleTestData'])->middleware('auth');


// Internal routes, not connecting to Binance
Route::put(   '/user/fav-tickers',          [ProfileController::class, 'getFavTicker'])   ->middleware('auth');
Route::post(  '/user/fav-tickers',          [ProfileController::class, 'addFavTicker'])   ->middleware('auth');
Route::delete('/user/fav-tickers/{ticker}', [ProfileController::class, 'deleteFavTicker'])->middleware('auth');


Route::middleware('auth')->group(function () {
  Route::get('/profile/trade-groups/{symbol}', [ProfileController::class, 'loadTradeGroupsForSymbol']);
  Route::post('/profile/trade-groups/{symbol}', [ProfileController::class, 'saveTradeGroupsForSymbol']);
});

// Websockets functions for orders
// http://127.0.0.1:8000/binance/test-endpoint
Route::get('/binance/test-endpoint', function() {
  
  $body = [
    'symbol' => 'BTCUSDT',
    'side' => 'SELL', 
    'quantity' => '0.0015', 
    'price' => '70000.01', 
    'stopPrice' => '55000.01', 
    'stopLimitPrice' => '54000.01', 
    'stopLimitTimeInForce' => 'GTC', 
    'recvWindow' => '10000',
  ];

  $resp = \App\Http\Controllers\BinanceController::sendBinanceRequestCurl(
    'POST', 
    'v3/order/oco', 
    $body);
  echo 'La respo es';
  dd($resp);

  return;
  $opening_orderId = '30594603910'; // optional
  $curl = curl_init();
  $timestamp = round(microtime(true) * 1000);
  $body = [
    'symbol' => 'BTCUSDT',
    'side' => 'SELL', 
    'quantity' => '0.001', 
    'price' => '70000', 
    'stopPrice' => '55000', 
    'stopLimitPrice' => '54000', 
    'stopLimitTimeInForce' => 'GTC', 
    'timestamp' => (string) $timestamp,
    'recvWindow' => '10000',
  ];
  $queryString = http_build_query($body);
  $signature = hash_hmac('sha256', $queryString, \App\Http\Controllers\BinanceController::get_secret_key());

  $url = 'https://api.binance.com/api/v3/order/oco?' . $queryString . '';
  $url .= '&signature=' . $signature;
  
  curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "",
    CURLOPT_HTTPHEADER => [
      "X-MBX-APIKEY: " . \App\Http\Controllers\BinanceController::get_public_key()
    ],
  ]);
  
  $response = curl_exec($curl);
  $err = curl_error($curl);
  
  curl_close($curl);
  
  if ($err) {
    echo "cURL Error #:" . $err;
  } else {
    $data = json_decode($response, true);
    if ( !empty($data['orderListId'])) {
      $useful_data = [
        'entryOrderId' => $opening_orderId,
        'exitOrderIdGain' => $data['orderReports'][1]['orderId'],
        'exitOrderIdLoss' => $data['orderReports'][0]['orderId'],
        'data' => $data,
      ];
      dd($useful_data);
    } else {
      echo "cURL Error, not found  #data['orderListId']:  " . (!empty( $data['msg'] ) ? $data['msg'] : '');
    }
  }









  return;
  $body = [
    'symbol' => 'BTCUSDT',
    'side' => 'SELL', 
    'quantity' => '0.001', 
    'price' => '70000', 
    'stopPrice' => '55000', 
    'stopLimitPrice' => '54000', 
    'stopLimitTimeInForce' => 'GTC', 
    'recvWindow' => '10000',
  ];
  echo '<pre>';
  echo json_encode($body, JSON_PRETTY_PRINT);
  echo '</pre>';
  echo '<h1>timestamp</h1>';
  $timestamp = round(microtime(true) * 1000);
  echo $timestamp;
  echo '<h1>signature</h1>';
  $queryString = http_build_query(array_merge($body, ['timestamp' => $timestamp]));
  $signature = hash_hmac('sha256', $queryString, \App\Http\Controllers\BinanceController::get_secret_key());
  echo $signature;
  // Completar la URL con la firma
  $url = \App\Http\Controllers\BinanceController::get_api_base() . 'v3/order/oco';
  $fullUrl = $url . '?' . $queryString . '&signature=' . $signature;
  $response = [
    \App\Http\Controllers\BinanceController::get_public_key(),
    $fullUrl
  ];
  dd($response);
})->middleware('auth')->name('binance.testEndpoint');
