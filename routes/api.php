<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AddToController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ReviewController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Storage;

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
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return response()->json(['message' => 'Email verified successfully']);
})->middleware(['auth:api', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification link sent!']);
})->middleware(['auth:api', 'throttle:6,1'])->name('verification.send');


Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'Login'])->name('login');
Route::get('pro',[ProductController::class, 'index']);
Route::get('show/{id}',[ProductController::class, 'show']);
Route::get('categorie/proshow', [CategorieController::class, 'showCateProduct']);
Route::get('show/reviwe/{id}', [ReviewController::class, 'showProReviwes']);
Route::get('/products/{filename}', function ($filename) {
    // فك الترميز من الرابط
    $filename = urldecode($filename);

    // منع الأحرف الغير مسموح بها
    $filename = preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', $filename);

    $path = 'products/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        return response()->json([
            'message' => 'الصورة غير موجودة'
        ], 404);
    }

    return response()->file(storage_path('app/public/' . $path));
})->where('filename', '.*');


Route::middleware('auth:api')->group(function () {
    Route::post('logout', [UserController::class, 'logout']);
    Route::post('logoutFromAll', [UserController::class, 'logoutAll']);});



    Route::middleware(['auth:api','UserRole'])->group(function () {
Route::prefix('dashboard')->group(function () {
    Route::post('create',[ProductController::class, 'create']);
     Route::post('categorie/add', [CategorieController::class, 'AddCate']);
      Route::get('usersinfo', [UserController::class, 'userinfo'])->name('userinfo');
    Route::post('update/{id}',[ProductController::class, 'update']);
    Route::delete('destroy/{id}',[ProductController::class, 'destroy']);
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
     Route::post('add/reviweForProdict/{id}', [ReviewController::class, 'AddReviwes']);
     Route::post('update/reviwe/{id}', [ReviewController::class, 'UpdateReviwes']);
     Route::delete('delete/reviwe/{id}/{reviweid}', [ReviewController::class, 'DeleteReviwes']);
Route::post('categorie/update/{id}', [CategorieController::class, 'UpdateCate']);
     Route::delete('categorie/delete/{id}', [CategorieController::class, 'DeleteCate']);
     Route::delete('user/delete/{id}', [UserController::class, 'UserDelete']);
});
