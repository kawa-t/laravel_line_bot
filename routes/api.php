<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['api']], function () {
  Route::get('get_access', 'GettingController@gettoeken');
});

Route::group(['namespace' => 'Api'], function() {
  // LineからのWebhookを受信する
  Route::post('/line/webhook', 'LineController@webhook')->name('line.webhook');
});