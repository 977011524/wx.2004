<?php

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
    // echo phpinfo();
    return view('welcome');
});
Route::prefix('/wx')->group(function(){
    //  
    Route::get('/','WxController@checkwx');//接受事件推送
    Route::get('/token','WxController@token');
    Route::get('/guzzle2','WxController@guzzle2');//获取access_token
    Route::get('/create_menu','WxController@createMenu');
    Route::get('/weather','WxController@weather');//天气
});

//TEST路由分组
Route::prefix('/test')->group(function(){
    Route::get('/guzzle1','TestController@guzzle1');//text/guzzle1
    Route::get('/guzzle2','TestController@guzzle2');//text/guzzle2
    Route::get('/guzzle3','TestController@guzzle3');//text/guzzle3
    Route::get('/json','TestController@json');
});

//Test路由分组

