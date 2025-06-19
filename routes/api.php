<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(DashboardController::class)->group(function(){
    Route::get('home', 'home');

    Route::get('products-by-brand/{brand}', 'ProductsByBrand');

    Route::get('show-product/{product}', 'showProduct');
    
    Route::get('products/{filter}', 'filter');
    Route::get('products/{brand}/{filter}', 'filterByBrand');

    Route::get('add-to-favorites/{product}', 'addToFav')->middleware('auth:sanctum');
    Route::get('favorites', 'fav')->middleware('auth:sanctum');
    Route::get('remove-favorite/{product}', 'removeFav')->middleware('auth:sanctum');

    Route::post('add-cart', 'addToCart')->middleware('auth:sanctum');
});

Route::controller(AuthController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
});