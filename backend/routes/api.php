<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

use App\Http\Controllers\Api\AuthController;

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated Routes (require a valid Sanctum token)
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\ProducerProfileController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\CartController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']); // General user details (already includes profiles)

    // User Profile Management
    Route::get('/user/profile', [UserProfileController::class, 'show']); // Duplicate of /user, but can be specialized
    Route::put('/user/profile', [UserProfileController::class, 'update']);
    Route::put('/user/password', [UserProfileController::class, 'updatePassword']);
    Route::get('/user/settings', [UserProfileController::class, 'getSettings']);
    Route::put('/user/settings', [UserProfileController::class, 'updateSettings']);

    // Producer Profile Management (for authenticated producer)
    Route::get('/producer/profile', [ProducerProfileController::class, 'show']);
    Route::put('/producer/profile', [ProducerProfileController::class, 'update']); // Using PUT, but client must send POST with _method=PUT for files

    // Customer Profile Management (for authenticated customer)
    Route::get('/customer/profile', [CustomerProfileController::class, 'show']);
    Route::put('/customer/profile', [CustomerProfileController::class, 'update']);

    // Product Management (Authenticated actions)
    Route::post('/products', [ProductController::class, 'store']); // Create
    Route::post('/products/{product}', [ProductController::class, 'update']); // Update (using POST for FormData/file uploads)
    Route::delete('/products/{product}', [ProductController::class, 'destroy']); // Delete

    // Order Management
    Route::post('/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']); // Create a new order (customer)
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']); // List orders (customer, producer, admin)
    Route::get('/orders/{order}', [\App\Http\Controllers\Api\OrderController::class, 'show']); // Show specific order
    Route::put('/orders/{order}/status', [\App\Http\Controllers\Api\OrderController::class, 'updateStatus']); // Update order status (producer, admin)

    // Delivery Management (Producer/Admin for store/update, Customer for show on their order)
    Route::get('/deliveries', [\App\Http\Controllers\Api\DeliveryController::class, 'index']); // List deliveries (admin/producer)
    Route::post('/deliveries', [\App\Http\Controllers\Api\DeliveryController::class, 'store']); // Create delivery (admin/producer)
    Route::get('/deliveries/{delivery}', [\App\Http\Controllers\Api\DeliveryController::class, 'show']); // Show delivery (customer, admin, producer)
    Route::put('/deliveries/{delivery}', [\App\Http\Controllers\Api\DeliveryController::class, 'update']); // Update delivery (admin/producer)

    // Wallet (for authenticated user)
    Route::get('/wallet', [\App\Http\Controllers\Api\WalletController::class, 'show']);


    // Future Agriflow API routes will be defined here
    // ... other authenticated routes
});

// Public Product Routes
Route::get('/products', [ProductController::class, 'index']); // List products
Route::get('/products/{product}', [ProductController::class, 'show']); // Show a single product

// Cart Routes (accessible by guests using session, and authenticated users)
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/items', [CartController::class, 'store']); // Add or update item quantity
Route::post('/cart/items/{productId}/increment', [CartController::class, 'increment']);
Route::post('/cart/items/{productId}/decrement', [CartController::class, 'decrement']);
Route::delete('/cart/items/{productId}', [CartController::class, 'destroy']); // Remove item
Route::delete('/cart', [CartController::class, 'clear']); // Clear entire cart

// Payment Webhook Routes (Server-to-Server, should be CSRF exempt if not by default in api.php)
Route::post('/webhooks/payment/paygate', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'handlePaygateWebhook'])->name('webhook.paygate');
Route::post('/webhooks/payment/tmoney', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'handleTMoneyWebhook'])->name('webhook.tmoney');
Route::post('/webhooks/payment/moov', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'handleMoovWebhook'])->name('webhook.moov');

// Payment Redirect Routes (Client browser redirects)
// These might redirect to frontend routes that then query the order status.
Route::get('/checkout/payment/success/{orderId}', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'paymentSuccessRedirect'])->name('checkout.payment.success');
Route::get('/checkout/payment/failure/{orderId}', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'paymentFailureRedirect'])->name('checkout.payment.failure');
Route::get('/checkout/payment/cancelled/{orderId}', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'paymentCancelledRedirect'])->name('checkout.payment.cancelled');
