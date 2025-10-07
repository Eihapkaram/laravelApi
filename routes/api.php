<?php

use App\Http\Controllers\AddToController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ✅ Email Verification
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return response()->json(['message' => 'Email verified successfully']);
})->middleware(['auth:api', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification link sent!']);
})->middleware(['auth:api', 'throttle:6,1'])->name('verification.send');

// 🔹 Public Routes
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'Login'])->name('login');
Route::post('/login-phone', [UserController::class, 'loginWithPhone']);
Route::get('pro', [ProductController::class, 'index']);
Route::get('usersinfo', [UserController::class, 'userinfo'])->name('userinfo');
Route::get('pageProducts/show', [PageController::class, 'showPageProduct']);
Route::get('categorie/show', [CategorieController::class, 'showCateProduct']);
Route::get('show/{id}', [ProductController::class, 'show']);
Route::get('categorie/proshow', [CategorieController::class, 'showCateProduct']);
Route::get('show/reviwe/{id}', [ReviewController::class, 'showProReviwes']);

// ✅ Product Images
Route::get('/products/{filename}', function ($filename) {
    $filename = urldecode($filename);
    $path = 'products/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'الصورة غير موجودة', 'path' => $path], 404);
    }

    $mime = Storage::disk('public')->mimeType($path);
    $file = Storage::disk('public')->get($path);

    return response($file, 200)->header('Content-Type', $mime);
})->where('filename', '.*');

// ✅ User Images
Route::get('/users/{filename}', function ($filename) {
    $filename = urldecode($filename);
    $path = 'users/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'الصورة غير موجودة', 'path' => $path], 404);
    }

    $mime = Storage::disk('public')->mimeType($path);
    $file = Storage::disk('public')->get($path);

    return response($file, 200)->header('Content-Type', $mime);
})->where('filename', '.*');

// 🔐 Authenticated Routes
Route::middleware('auth:api')->group(function () {
    // User
    Route::post('logout', [UserController::class, 'logout']);
    Route::post('logoutFromAll', [UserController::class, 'logoutAll']);
    Route::post('//logout-phone', [UserController::class, 'logoutphone']);
    Route::get('user/info/{id}', [UserController::class, 'OneUserinfo']);

    // Cart
    Route::post('cart/add', [AddToController::class, 'addfun']);
    Route::get('cart/show', [AddToController::class, 'CartShow']);
    Route::delete('cart/delete/{id}', [AddToController::class, 'deleteCartItem']);
    Route::delete('cart/deleteAll', [AddToController::class, 'deleteAllCartItems']);

    // Order
    Route::post('order/add', [OrderController::class, 'createOrder']);
    Route::get('order/show', [OrderController::class, 'showOrder']);
    Route::get('order/show/latest', [OrderController::class, 'showlatestOrder']);
    Route::delete('order/delete/{id}', [OrderController::class, 'deleteOrder']);
    Route::delete('order/delete/all', [OrderController::class, 'deleteAllOrder']);

    // Payment
    Route::post('/pay', [PaymentController::class, 'pay']);

    // Review
    Route::post('add/reviweForProdict/{id}', [ReviewController::class, 'AddReviwes']);
    Route::post('update/reviwe/{id}', [ReviewController::class, 'UpdateReviwes']);
    Route::delete('delete/reviwe/{id}/{reviweid}', [ReviewController::class, 'DeleteReviwes']);
});

// 🧑‍💻 Admin Routes
Route::middleware(['auth:api', 'UserRole'])->prefix('dashboard')->group(function () {
    // Product
    Route::post('create', [ProductController::class, 'create']);
    Route::post('update/{id}', [ProductController::class, 'update']);
    Route::delete('destroy/{id}', [ProductController::class, 'destroy']);

    // Category
    Route::post('categorie/add', [CategorieController::class, 'AddCate']);
    Route::post('categorie/update/{id}', [CategorieController::class, 'UpdateCate']);
    Route::delete('categorie/{id}', [CategorieController::class, 'DeleteCate']);
    Route::delete('categorie/delete/{id}', [CategorieController::class, 'DeleteCate']);

    // Page
    Route::post('page/add', [PageController::class, 'AddPage']);
    Route::post('page/Update/{id}', [PageController::class, 'UpdatePage']);
    Route::delete('page/Delete/{id}', [PageController::class, 'DeletePage']);

    // User
    Route::post('user/update/{id}', [UserController::class, 'userUpdate']);
    Route::delete('user/delete/{id}', [UserController::class, 'UserDelete']);
});
