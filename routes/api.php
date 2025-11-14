<?php

use App\Http\Controllers\AddToController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\OfferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SellerCustomerController;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| API Routesg
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
Route::post('/register-phone', [UserController::class, 'registerWithPhone']);
Route::post('login', [UserController::class, 'Login'])->name('login');
Route::post('/login-phone', [UserController::class, 'loginWithPhone']);
Route::post('/security-question', [UserController::class, 'getSecurityQuestion']);
Route::post('/resetpassword', [UserController::class, 'resetPassword']);
Route::post('/reset-password', [UserController::class, 'resetPasswordWithSecurity']);
Route::get('pro', [ProductController::class, 'index']);
Route::get('settings', [SettingController::class, 'index']);
Route::get('/search/cate', [ProductController::class, 'search']);
Route::get('/search/category', [ProductController::class, 'searchByCategory']);
Route::get('/search', [PageController::class, 'search']);
Route::get('pageProducts/show', [PageController::class, 'showPageProduct']);
Route::get('categorie/show', [CategorieController::class, 'showCateProduct']);
Route::get('/offers/active', [OfferController::class, 'activeOffers']);
Route::get('/offers', [OfferController::class, 'index']);
Route::get('/offers/{id}', [OfferController::class, 'show']);
Route::get('show/{id}', [ProductController::class, 'show']);
Route::get('categorie/proshow', [CategorieController::class, 'showCateProduct']);
Route::get('show/reviwe/{id}', [ReviewController::class, 'showProReviwes']);


// ✅ Product Image
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

// ✅ User  imageid
Route::get('/imageid/{filename}', function ($filename) {
    $filename = urldecode($filename);
    $path = 'imageid/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'الصورة غير موجودة', 'path' => $path], 404);
    }

    $mime = Storage::disk('public')->mimeType($path);
    $file = Storage::disk('public')->get($path);

    return response($file, 200)->header('Content-Type', $mime);
})->where('filename', '.*');

// categories imge
Route::get('/categories/{filename}', function ($filename) {
    $filename = urldecode($filename);
    $path = 'categories/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'الصورة غير موجودة', 'path' => $path], 404);
    }

    $mime = Storage::disk('public')->mimeType($path);
    $file = Storage::disk('public')->get($path);

    return response($file, 200)->header('Content-Type', $mime);
})->where('filename', '.*');
//  imge   categorebanner
Route::get('/categorebanner/{filename}', function ($filename) {
    $filename = urldecode($filename);
    $path = 'categorebanner/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'الصورة غير موجودة', 'path' => $path], 404);
    }

    $mime = Storage::disk('public')->mimeType($path);
    $file = Storage::disk('public')->get($path);

    return response($file, 200)->header('Content-Type', $mime);
})->where('filename', '.*');
//  offers imge  storebanners pages
Route::get('/offersbanner/{filename}', function ($filename) {
    $filename = urldecode($filename);
    $path = 'offersbanner/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'الصورة غير موجودة', 'path' => $path], 404);
    }

    $mime = Storage::disk('public')->mimeType($path);
    $file = Storage::disk('public')->get($path);

    return response($file, 200)->header('Content-Type', $mime);
})->where('filename', '.*');
//  pages imge   
Route::get('/pages/{filename}', function ($filename) {
    $filename = urldecode($filename);
    $path = 'pages/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'الصورة غير موجودة', 'path' => $path], 404);
    }

    $mime = Storage::disk('public')->mimeType($path);
    $file = Storage::disk('public')->get($path);

    return response($file, 200)->header('Content-Type', $mime);
})->where('filename', '.*');

//  imge  storebanners categorebanner
Route::get('/storebanners/{filename}', function ($filename) {
    $filename = urldecode($filename);
    $path = 'storebanners/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'الصورة غير موجودة', 'path' => $path], 404);
    }

    $mime = Storage::disk('public')->mimeType($path);
    $file = Storage::disk('public')->get($path);

    return response($file, 200)->header('Content-Type', $mime);
})->where('filename', '.*');

//  imge  settings
Route::get('/settings/{filename}', function ($filename) {
    $filename = urldecode($filename);
    $path = 'settings/' . $filename;

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
    Route::post('/logout-phone', [UserController::class, 'logoutphone']);
    Route::get('user/info', [UserController::class, 'info']);
    Route::post('user/addPhoto', [UserController::class, 'addimg']);
    //seller 
    Route::get('/seller/customers', [SellerCustomerController::class, 'index']);
    Route::get('/seller/customers/{id}', [SellerCustomerController::class, 'show']);
    Route::get('/seller/customers/{id}/orders', [SellerCustomerController::class, 'customerOrders']);
    Route::get('/seller/customersHeAdd', [SellerCustomerController::class, 'myCustomers']);
    Route::post('seller/customers/new', [SellerCustomerController::class, 'createNewCustomer']);
    Route::post('/seller/customers', [SellerCustomerController::class, 'store']);
    Route::delete('/seller/customers/{id}', [SellerCustomerController::class, 'destroy']);

    // Cart
    Route::post('cart/add', [AddToController::class, 'addfun']);
    Route::get('cart/show', [AddToController::class, 'CartShow']);
    Route::delete('cart/delete/{id}', [AddToController::class, 'deleteCartItem']);
    Route::delete('cart/deleteAll', [AddToController::class, 'deleteAllCartItems']);

    // Order 
    Route::post('order/add', [OrderController::class, 'createOrder']);
    Route::get('order/show', [OrderController::class, 'showOrder']);

    //orders Seller 
    Route::get('sellerPosition', [OrderController::class, 'getpositionSellersByApprovedOrders']);
    Route::get('sellerApprovedOrdershow', [OrderController::class, 'showCurrentSellerApprovedOrders']);
    Route::get('ALLorder/show/forSeller', [OrderController::class, 'sellerOrdersForCustomers']);
    Route::get('order/count/seller', [OrderController::class, 'sellerOrdersCount']);
    Route::get('/seller/my-profits', [SellerCustomerController::class, 'myProfits']);
    Route::get('order/count', [OrderController::class, 'OrderCount']);
    Route::delete('order/delete/all', [OrderController::class, 'deleteAllOrder']);
    Route::get('order/show/latest', [OrderController::class, 'showlatestOrder']);
    Route::post('/orders/seller-create', [OrderController::class, 'createBySeller']);
    Route::get('/orders/export', [OrderController::class, 'export']);
    // عملاء (بدون seller_id)

    Route::get('/orders/export/customers', [OrderController::class, 'exportCustomerOrders']);
    // للكل مش لواحد  بائعون (approved فقط)

    Route::get('/orders/export/sellers/approved', [OrderController::class, 'exportApprovedSellerOrders']);
    Route::post('/orders/{id}/approve', [OrderController::class, 'approveOrder']);
    Route::post('/orders/{id}/reject', [OrderController::class, 'rejectOrder']);
    Route::put('order/update/{id}', [OrderController::class, 'updateOrderStatus']);
    Route::delete('order/delete/{id}', [OrderController::class, 'deleteOrder']);

    // inquiries
    Route::post('/inquiries', [InquiryController::class, 'store']);
    // Payment
    Route::post('/pay', [PaymentController::class, 'pay']);
    Route::post('/paymob/webhook', [PaymentController::class, 'webhook']);
    Route::get('/paymob/redirect', [PaymentController::class, 'redirect']);

    // Review
    Route::post('add/reviweForProdict/{id}', [ReviewController::class, 'AddReviwes']);
    Route::put('update/reviwe/{id}', [ReviewController::class, 'UpdateReviwes']);
    Route::delete('delete/reviwe/{id}/{reviweid}', [ReviewController::class, 'DeleteReviwes']);
    // notification
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    //طلب سحب ارباح 

    //  عرض ارباجحهة الحاليه المندوب
    Route::get('/seller/myProfits', [SellerCustomerController::class, 'myProfits']);
    //سحب كل 
    Route::post('/seller/withdraw', [SellerCustomerController::class, 'requestWithdraw']);
    //سحب جزء 
    Route::post('/seller/withdraw', [SellerCustomerController::class, 'requestWithdrawpart']);
    // عرض طلبا السحب
    Route::get('/seller/withdraws', [SellerCustomerController::class, 'myWithdraws']);
    //فاتورا 
    Route::get('/orders/{id}/invoice', [OrderController::class, 'generateInvoice']);
});

// 🧑‍💻 Admin Routes
Route::middleware(['auth:api', 'UserRole'])->prefix('dashboard')->group(function () {
    // Product
    Route::post('create', [ProductController::class, 'create']);
    Route::get('/products/export', [ProductController::class, 'export']);
    Route::post('/products/import', [ProductController::class, 'import']);
    Route::post('update/{id}', [ProductController::class, 'update']);
    Route::delete('destroy/{id}', [ProductController::class, 'destroy']);

    // Category
    Route::post('categorie/add', [CategorieController::class, 'AddCate']);
    Route::post('categories/import', [CategorieController::class, 'import']);
    Route::get('categories/export', [CategorieController::class, 'export']);
    Route::post('categorie/update/{id}', [CategorieController::class, 'UpdateCate']);
    Route::delete('categorie/{id}', [CategorieController::class, 'DeleteCate']);
    Route::delete('categorie/delete/{id}', [CategorieController::class, 'DeleteCate']);

    // Page
    Route::post('page/add', [PageController::class, 'AddPage']);
    Route::get('/pages/export', [PageController::class, 'export']);
    Route::post('/pages/import', [PageController::class, 'import']);
    Route::post('page/Update/{id}', [PageController::class, 'UpdatePage']);
    Route::delete('page/Delete/{id}', [PageController::class, 'DeletePage']);

    // User

    Route::get('usersinfo', [UserController::class, 'userinfo'])->name('userinfo');
    Route::get('user/info/{id}', [UserController::class, 'OneUserinfo']);
    Route::post('/import/users', [UserController::class, 'importUsers']);
    Route::get('/export/users', [UserController::class, 'export']);
    Route::post('user/update/{id}', [UserController::class, 'userUpdate']);
    Route::delete('user/delete/{id}', [UserController::class, 'UserDelete']);
    Route::put('orders/{id}/status', [OrderController::class, 'updateOrderStatus']);

    // 🔥 مسار إرسال إشعار من الأدمن
    Route::post('/notifications/send', [NotificationController::class, 'sendByAdmin']);
    //offers
    Route::post('/offers', [OfferController::class, 'store']);
    Route::post('/offers/{id}', [OfferController::class, 'update']);
    Route::delete('/offers/{id}', [OfferController::class, 'destroy']);
    //inquiries
    Route::get('/inquiries', [InquiryController::class, 'index']);
    Route::patch('/inquiries/{id}/status', [InquiryController::class, 'updateStatus']);
    //settings
    Route::post('settings/create', [SettingController::class, 'create']);
    Route::post('settings/update', [SettingController::class, 'update']);
    //all orders// and //by seller
    Route::get('allorderbyseller', [OrderController::class, 'showAllOrdersBySellers']);
    Route::get('allorderbyseller/ApprovedOrders', [OrderController::class, 'showApprovedOrdersBySellers']);
    Route::get('orders/customers', [OrderController::class, 'showAllOrdersWithoutSeller']);
    Route::get('orders/show/all', [OrderController::class, 'showAllOrders']);

    // عملاء (بدون seller_id)
    Route::post('/orders/import/customers', [OrderController::class, 'importCustomerOrders']);
    //اضافه ارباح لي orders لي seller
    Route::post('/orders/{id}/add-profit', [OrderController::class, 'addSellerProfit']);
    // بائعون (approved فقط)
    Route::post('/orders/import/sellers/approved', [OrderController::class, 'importApprovedSellerOrders']);
    //عرض ارباح المنديب
    Route::get('/sellersProfits', [SellerCustomerController::class, 'sellersProfits']);
    //موافقه او رفض طلب سحب الارباح 
    // الإدمن
    Route::get('/withdraw-requests', [SellerCustomerController::class, 'allWithdrawRequests']);
    //موافقه او رفض طلب سحب الارباح 
    Route::patch('/withdraw-requests/{id}', [SellerCustomerController::class, 'updateWithdrawStatus']);
    Route::post('/withdraws/{id}/approve', [SellerCustomerController::class, 'approveWithdraw']);
});
