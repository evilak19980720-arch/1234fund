<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('agreement','HomeController@agreement');
Route::get('privacy','HomeController@privacy');
Route::get('payment','HomeController@payment');
Route::get('pay/{order}','PayController@index');
Route::get('otpay/{order}','PayController@otpay');
Route::get('yunpay/{order}','PayController@yunpay');
Route::get('yarlungpay/{order}','PayController@yarlungpay');
Route::get('serious/{order}','PayController@serious');
Route::get('api/customer', 'HomeController@customer');  //客服跳转
