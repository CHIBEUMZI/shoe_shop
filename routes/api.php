<?php

use App\Mail\OrderStatusMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// TEST
// Route::get('/test', function () {
//     return response()->json([
//         'message' => 'API OK',
//     ]);
// });

// Route::get('/test-mail', function () {
//     $order = Order::with(['items', 'payments', 'items.variant'])->latest()->first();

//     if (!$order) {
//         return response()->json([
//             'message' => 'Không có đơn hàng để test.',
//         ], 404);
//     }

//     if (empty($order->customer_email)) {
//         return response()->json([
//             'message' => 'Đơn hàng chưa có customer_email.',
//         ], 422);
//     }

//     Mail::to($order->customer_email)->send(
//         new OrderStatusMail($order, 'paid')
//     );

//     return response()->json([
//         'message' => 'Đã gửi mail thành công tới ' . $order->customer_email,
//     ]);
// });

// AUTH
Route::prefix('auth')->group(function () {
    Route::post('/register/init', [App\Http\Controllers\Api\Auth\AuthController::class, 'initRegister']);
    Route::post('/register/verify', [App\Http\Controllers\Api\Auth\AuthController::class, 'verifyEmail']);
    Route::post('/register/resend', [App\Http\Controllers\Api\Auth\AuthController::class, 'resendVerificationCode']);
    Route::post('/login', [App\Http\Controllers\Api\Auth\AuthController::class, 'login']);
    Route::post('/logout', [App\Http\Controllers\Api\Auth\AuthController::class, 'logout']);
    Route::post('/forgot-password', [App\Http\Controllers\Api\Auth\AuthController::class, 'forgotPassword']);
    Route::post('/verify-reset-code', [App\Http\Controllers\Api\Auth\AuthController::class, 'verifyResetCode']);
    Route::post('/reset-password', [App\Http\Controllers\Api\Auth\AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [App\Http\Controllers\Api\Auth\AuthController::class, 'me']);
        Route::put('/profile', [App\Http\Controllers\Api\Auth\AuthController::class, 'updateProfile']);
        Route::post('/profile/change-password', [App\Http\Controllers\Api\Auth\AuthController::class, 'changePassword']);
        Route::post('/avatar', [App\Http\Controllers\Api\Auth\AvatarUploadController::class, 'store']);
        Route::delete('/avatar', [App\Http\Controllers\Api\Auth\AvatarUploadController::class, 'destroy']);
    });
});

// PUBLIC STORE
Route::prefix('v1')->group(function () {
    Route::get('products/facets', [App\Http\Controllers\Api\Public\ProductFacetController::class, 'index']);
    Route::get('products', [App\Http\Controllers\Api\Public\ProductController::class, 'index']);
    Route::get('products/{product:slug}', [App\Http\Controllers\Api\Public\ProductController::class, 'show']);

    Route::get('categories', [App\Http\Controllers\Api\Public\CategoryController::class, 'index']);
    Route::get('categories/{slug}', [App\Http\Controllers\Api\Public\CategoryController::class, 'show']);
    Route::get('/banners', [App\Http\Controllers\Api\Public\BannerController::class, 'index']);
    Route::get('/banners/position/{position}', [App\Http\Controllers\Api\Public\BannerController::class, 'showByPosition']);

    Route::post('/chatbot', [App\Http\Controllers\Api\Public\ChatbotController::class, 'message']);

    // REVIEWS - Public endpoints
    Route::get('products/{product}/reviews', [App\Http\Controllers\Api\Public\ReviewController::class, 'index']);
    Route::get('products/{product}/reviews/stats', [App\Http\Controllers\Api\Public\ReviewController::class, 'productStats']);
});

// USER: CART + ORDER
Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function () {
        // CART
        Route::get('cart', [App\Http\Controllers\Api\Public\CartController::class, 'show']);
        Route::post('cart/items', [App\Http\Controllers\Api\Public\CartController::class, 'addItem']);
        Route::patch('cart/items/{item}', [App\Http\Controllers\Api\Public\CartController::class, 'updateItem']);
        Route::delete('cart/items/{item}', [App\Http\Controllers\Api\Public\CartController::class, 'removeItem']);
        Route::delete('cart/clear', [App\Http\Controllers\Api\Public\CartController::class, 'clear']);

        // ORDERS
        Route::post('orders', [App\Http\Controllers\Api\Public\OrderController::class, 'store']);
        Route::get('orders', [App\Http\Controllers\Api\Public\OrderController::class, 'index']);
        Route::get('orders/{order}', [App\Http\Controllers\Api\Public\OrderController::class, 'show']);
        Route::post('orders/{order}/payment', [App\Http\Controllers\Api\Public\OrderController::class, 'createPayment']);
        Route::post('orders/{order}/cancellation', [App\Http\Controllers\Api\Public\OrderController::class, 'requestCancellation']);

        // REVIEWS - Authenticated endpoints
        Route::post('reviews', [App\Http\Controllers\Api\Public\ReviewController::class, 'store']);
        Route::get('reviews/my', [App\Http\Controllers\Api\Public\ReviewController::class, 'myReviews']);
        Route::get('reviews/{review}', [App\Http\Controllers\Api\Public\ReviewController::class, 'show']);
        Route::patch('reviews/{review}', [App\Http\Controllers\Api\Public\ReviewController::class, 'update']);
        Route::delete('reviews/{review}', [App\Http\Controllers\Api\Public\ReviewController::class, 'destroy']);

        // COUPONS - User validation
        Route::post('coupons/validate', [App\Http\Controllers\Api\Public\CouponController::class, 'validate']);
        Route::get('coupons/available', [App\Http\Controllers\Api\Public\CouponController::class, 'available']);
        Route::post('coupons/claim', [App\Http\Controllers\Api\Public\CouponController::class, 'claim']);
        Route::get('coupons/my', [App\Http\Controllers\Api\Public\CouponController::class, 'myCoupons']);
    });

// VNPAY CALLBACK
Route::prefix('v1/payments/vnpay')->group(function () {
    Route::get('return', [App\Http\Controllers\Api\Public\OrderController::class, 'vnpayReturn']);
    Route::get('ipn', [App\Http\Controllers\Api\Public\OrderController::class, 'vnpayIpn']);
});

// MOMO CALLBACK
Route::prefix('v1/payments/momo')->group(function () {
    Route::match(['GET', 'POST'], 'return', [App\Http\Controllers\Api\Public\OrderController::class, 'momoReturn']);
    Route::match(['GET', 'POST'], 'ipn', [App\Http\Controllers\Api\Public\OrderController::class, 'momoIpn']);
});

// ADMIN
Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);
        
        Route::apiResource('products', App\Http\Controllers\Api\Admin\ProductController::class);
        Route::apiResource('categories', App\Http\Controllers\Api\Admin\CategoryController::class);
        Route::apiResource('brands', App\Http\Controllers\Api\Admin\BrandController::class);
        
        Route::post('uploads/images', [App\Http\Controllers\Api\Admin\UploadController::class, 'store']);
        Route::delete('uploads/images', [App\Http\Controllers\Api\Admin\UploadController::class, 'destroy']);

        // USERS
        Route::get('users', [App\Http\Controllers\Api\Admin\UserController::class, 'index']);
        Route::get('users/{user}', [App\Http\Controllers\Api\Admin\UserController::class, 'show']);
        Route::put('users/{user}', [App\Http\Controllers\Api\Admin\UserController::class, 'update']);

        // ORDERS
        Route::get('orders', [App\Http\Controllers\Api\Admin\OrderController::class, 'index']);
        Route::get('orders/{order}', [App\Http\Controllers\Api\Admin\OrderController::class, 'show']);
        Route::patch('orders/{order}/status', [App\Http\Controllers\Api\Admin\OrderController::class, 'updateStatus']);
        Route::patch('orders/{order}/confirm-cancellation', [App\Http\Controllers\Api\Admin\OrderController::class, 'confirmCancellation']);
        Route::patch('orders/{order}/reject-cancellation', [App\Http\Controllers\Api\Admin\OrderController::class, 'rejectCancellation']);
        
        // REVIEWS
        Route::get('reviews/stats', [App\Http\Controllers\Api\Admin\ReviewAdminController::class, 'stats']);
        Route::get('reviews', [App\Http\Controllers\Api\Admin\ReviewAdminController::class, 'index']);
        // Route::post('reviews/bulk-action', [App\Http\Controllers\Api\Admin\ReviewAdminController::class, 'bulkAction']);
        Route::get('reviews/{review}', [App\Http\Controllers\Api\Admin\ReviewAdminController::class, 'show']);
        Route::patch('reviews/{review}/approve', [App\Http\Controllers\Api\Admin\ReviewAdminController::class, 'approve']);
        Route::patch('reviews/{review}/reject', [App\Http\Controllers\Api\Admin\ReviewAdminController::class, 'reject']);
        Route::delete('reviews/{review}', [App\Http\Controllers\Api\Admin\ReviewAdminController::class, 'delete']);
        
        // BANNER 
        Route::apiResource('banners', App\Http\Controllers\Api\Admin\BannerController::class);

        // COUPONS
        Route::apiResource('coupons', App\Http\Controllers\Api\Admin\CouponController::class);
        Route::patch('coupons/{id}/toggle-status', [App\Http\Controllers\Api\Admin\CouponController::class, 'toggleStatus']);
    });