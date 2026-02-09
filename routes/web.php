<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/loaderio-c0cab98f0304852a2a162793dd240c05.txt', function () {
    return response('loaderio-c0cab98f0304852a2a162793dd240c05', 200)
        ->header('Content-Type', 'text/plain');
});

Route::get('/paymob/redirect', [PaymentController::class, 'redirect']);
