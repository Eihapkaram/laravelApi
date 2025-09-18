<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;


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

Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'Login']);
Route::get('pro',[ProductController::class, 'index']);
Route::post('create',[ProductController::class, 'create']);
Route::middleware(['auth:api'])->group(function () {
    Route::get('usersinfo', [UserController::class, 'userinfo'])->name('userinfo');
    Route::get('show/{id}',[ProductController::class, 'show']);

    Route::post('update/{id}',[ProductController::class, 'update']);
    Route::delete('destroy/{id}',[ProductController::class, 'destroy']);
});
