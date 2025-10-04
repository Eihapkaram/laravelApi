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

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return response()->json(['message' => 'Email verified successfully']);
})->middleware(['auth:api', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification link sent!']);
})->middleware(['auth:api', 'throttle:6,1'])->name('verification.send');


// 🔹 Authentication & Public Routes
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'Login'])->name('login');
Route::get('pro', [ProductController::class, 'index']);
Route::get('usersinfo', [UserController::class, 'userinfo'])->name('userinfo');
Route::get('pageProducts/show', [PageController::class, 'showPageProduct']);
Route::get('categorie/show', [CategorieController::class, 'showCateProduct']);
Route::get('show/{id}', [ProductController::class, 'show']);
Route::get('categorie/proshow', [CategorieController::class, 'showCateProduct']);
Route::get('show/reviwe/{id}', [ReviewController::class, 'showProReviwes']);


// ✅ عرض صور المنتجات من storage
Route::get('/products/{filename}', function ($filename) {
    $filename = urldecode($filename);
    $path = 'products/' . $filename;

    // لو الصورة مش موجودة
    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'الصورة غير موجودة', 'path' => $path], 404);
    }

    // إرجاع الصورة مع نوع الميديا الصحيح
    $mime = Storage::disk('public')->mimeType($path);
    $file = Storage::disk('public')->get($path);

    return response($file, 200)->header('Content-Type', $mime);
})->where('filename', '.*');


// ✅ عرض صور المستخدمين من storage
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
    Route::post('logout', [UserController::class, 'logout']);
    Route::post('logoutFromAll', [UserController::class, 'logoutAll']);
    Route::post('/pay', [PaymentController::class, 'pay']);
    Route::get('user/info/{id}', [UserController::class, 'OneUserinfo']);
    Route::post('add/reviweForProdict/{id}', [ReviewController::class, 'AddReviwes']);
});


// 🧑‍💻 Admin Routes
Route::middleware(['auth:api', 'UserRole'])->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::post('create', [ProductController::class, 'create']);
        Route::post('categorie/add', [CategorieController::class, 'AddCate']);
        Route::post('page/add', [PageController::class, 'AddPage']);
        Route::post('update/{id}', [ProductController::class, 'update']);
        Route::delete('destroy/{id}', [ProductController::class, 'destroy']);
        Route::delete('categorie/{id}', [CategorieController::class, 'DeleteCate']);
        Route::post('page/Update/{id}', [PageController::class, 'UpdatePage']);
        Route::post('categorie/update/{id}', [CategorieController::class, 'UpdateCate']);
        Route::delete('categorie/delete/{id}', [CategorieController::class, 'DeleteCate']);
        Route::delete('user/delete/{id}', [UserController::class, 'UserDelete']);
        Route::post('user/update/{id}', [UserController::class, 'userUpdate']);
        Route::delete('page/Delete/{id}', [PageController::class, 'DeletePage']);
    });

    Route::post('cart/add', [AddToController::class, 'addfun']);
    Route::get('cart/show', [AddToController::class, 'CartShow']);
    Route::delete('cart/deleteAll', [AddToController::class, 'deleteAllCartItems']);
    Route::post('order/add', [OrderController::class, 'createOrder']);
    Route::post('order/show', [OrderController::class, 'showOrder']);
    Route::post('order/show/latest', [OrderController::class, 'showlatestOrder']);
    Route::delete('order/delete/all', [OrderController::class, 'deleteAllOrder']);
    Route::delete('order/delete/{id}', [OrderController::class, 'deleteOrder']);
    Route::delete('cart/delete/{id}', [AddToController::class, 'deleteCartItem']);
    Route::post('update/reviwe/{id}', [ReviewController::class, 'UpdateReviwes']);
    Route::delete('delete/reviwe/{id}/{reviweid}', [ReviewController::class, 'DeleteReviwes']);
});
