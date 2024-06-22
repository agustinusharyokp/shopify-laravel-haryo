<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\RedirController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });
// Route::get('/auth', [ShopifyController::class, 'auth']);
// Route::get('/auth/callback', [ShopifyController::class, 'callback']);

// Route::get('/products', [ShopifyController::class, 'showProducts']);
// Route::post('/create-product', [ShopifyController::class, 'createProduct']);
// Route::delete('/delete-product/{id}', [ShopifyController::class, 'deleteProducts']);
// Route::put('/update-product/{id}', [ShopifyController::class, 'updateProduct']);

Route::get('/', function () {
    return view('welcom_shopify');
});
Route::get('/install', [InstallController::class, 'index']);
Route::get('/redir', [RedirController::class, 'index']);
