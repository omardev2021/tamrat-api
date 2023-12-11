<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\MyFatoorahController;
use App\Http\Controllers\HelpersController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware(['cors'])->group(function () {
    
    Route::post('/orders/payment',[MyFatoorahController::class,'index']);
Route::post('/orders/confirm',[OrdersController::class,'confirm']);

Route::get('/products/countries',[ProductsController::class,'countries']);

Route::get('/products',[ProductsController::class,'index']);
Route::get('/products/shopping',[ProductsController::class,'all']);
Route::get('/products/{slug}',[ProductsController::class,'show']);

Route::get('/products/shopping/ajwa',[ProductsController::class,'ajwa']);
Route::get('/products/shopping/sukari',[ProductsController::class,'sukari']);
Route::get('/products/shopping/mabroom',[ProductsController::class,'mabroom']);
Route::get('/products/shopping/majhool',[ProductsController::class,'majhool']);
Route::get('/products/shopping/sagie',[ProductsController::class,'sagie']);

Route::post('/products/shopping/search',[ProductsController::class,'search']);


Route::post('/users/login',[AuthController::class,'login']);
Route::post('/users/register',[AuthController::class,'register']);
Route::get('/orders/{id}',[OrdersController::class,'show']);

Route::post('/users/sms',[AuthController::class,'send_sms']);

Route::post('/users/email',[AuthController::class,'send_email']);

Route::post('/users/login-user',[AuthController::class,'login_user']);

Route::post('/coupons/check',[CouponController::class,'check']);

Route::post('/upload-image', [OrdersController::class, 'upload']);
Route::post('/users/contact',[HelpersController::class,'contact']);
Route::post('/users/letter',[HelpersController::class,'letter']);



Route::group(['middleware' => ['auth:sanctum']],function(){

Route::post('/orders',[OrdersController::class,'store']);

Route::get('/orders/profile/mine',[OrdersController::class,'my_orders']);





});

});



