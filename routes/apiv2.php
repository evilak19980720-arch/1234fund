<?php

use Illuminate\Support\Facades\Route;


Route::prefix('v2')->name('api.v2.')->middleware('change-locale')->namespace('Api\v2')->group(function () {


    //通过IP进行国家限制
    Route::middleware(['ip'])->group(function () {

        //需要登录后才能访问的接口
        Route::middleware('auth:api')->group(function () {
            //获取提现手续费
            //Route::get('configurations/withdrawal_fee','ConfigurationController@withdrawalFee');
        });
    });
});
