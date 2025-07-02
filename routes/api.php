<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(DashboardController::class)->group(function () {
    Route::get('home', 'home');

    Route::get('products-by-brand/{brand}', 'ProductsByBrand');

    Route::get('show-product/{product}', 'showProduct');

    Route::get('products/{filter}', 'filter');
    Route::get('products/{brand}/{filter}', 'filterByBrand');

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('add-to-favorites/{product}', 'addToFav');
        Route::get('favorites', 'fav');
        Route::get('remove-favorite/{product}', 'removeFav');

        Route::post('add-cart', 'addToCart');
        Route::get('show-cart', 'showCart');
        Route::delete('delete-cart/{cart}', 'deleteCart');
        Route::get('delete-all-cart', 'deleteAllCart');

        Route::post('make-order', 'createOrder');
        Route::get('my-orders', 'showOrders');

        Route::any('live-chat', 'live_chat');

    });
});

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});
