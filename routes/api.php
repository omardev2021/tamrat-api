<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\MoyasarController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\MyFatoorahController;
use App\Http\Controllers\HelpersController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatwootBotController;
use App\Http\Controllers\FulfillmentController;


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
// RETIRED 2026-06-13: unverified confirm replaced by /payments/verify (Moyasar)
// Route::post('/orders/confirm',[OrdersController::class,'confirm']);

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
Route::get('/merchant-feed',[FeedController::class,'google']);
Route::post('/social/upload-image',[SocialController::class,'uploadImage']);

// Chatwoot Agent Bot webhook (Tamrat WhatsApp CS) — secret in the path; no auth middleware.
Route::post('/chatwoot/webhook/{secret}',[ChatwootBotController::class,'webhook']);

Route::post('/users/sms',[AuthController::class,'send_sms']);

Route::post('/users/email',[AuthController::class,'send_email']);

Route::post('/users/login-user',[AuthController::class,'login_user']);

Route::post('/coupons/check',[CouponController::class,'check']);

Route::post('/upload-image', [OrdersController::class, 'upload']);
Route::post('/users/contact',[HelpersController::class,'contact']);
Route::post('/users/letter',[HelpersController::class,'letter']);


// Admin-only data endpoints — were UNAUTHENTICATED (exposed all customer PII).
// Now require an authenticated admin (type 13). EnsureAdmin resolves the Sanctum
// bearer token itself and returns clean 401/403 JSON (we deliberately do NOT add
// the auth:sanctum middleware — its unauthenticated path redirects to a missing
// 'login' route and 500s on a JSON API). The admin frontend sends the logged-in
// user's token via apiSlice prepareHeaders.
Route::middleware(['admin'])->group(function () {
    Route::get('/orders', [OrdersController::class, 'all']);
    Route::get('/orders/admin/contacts', [AdminController::class, 'contacts']);
    Route::get('/orders/admin/receipts', [AdminController::class, 'receipts']);
    Route::get('/orders/admin/newsletters', [AdminController::class, 'newsletters']);
});



Route::post('/users/update-user-data',[AuthController::class,'update_user_data']);
Route::post('/orders/guest',[OrdersController::class,'storeGuest']);
Route::post('/payments/verify',[MoyasarController::class,'verify']);
// Moyasar server-to-server webhook (payment_paid) — authenticated by secret_token in the body.
Route::post('/moyasar/webhook',[MoyasarController::class,'webhook']);

// Fulfillment ops — gated by a shared secret (X-Admin-Secret), enforced inside
// the controller (fails closed if ADMIN_SECRET is unset).
Route::get('/fulfillment/queue',[FulfillmentController::class,'queue']);
Route::post('/fulfillment/ship',[FulfillmentController::class,'markShipped']);
Route::post('/fulfillment/delivered',[FulfillmentController::class,'markDelivered']);

Route::group(['middleware' => ['auth:sanctum']],function(){

Route::post('/orders',[OrdersController::class,'store']);

Route::get('/orders/profile/mine',[OrdersController::class,'my_orders']);





});

});



