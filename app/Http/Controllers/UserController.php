<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\WelcomeUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:8',
            'last_name' => 'required|string|max:255',
            'role' => 'required|in:customer,supplier,seller', // تقييد الأدوار لمنع التلاعب بالصلاحيات
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'security_question' => 'required|string|max:255',
            'security_answer' => 'required|string',
            'wallet_number' => 'nullable|numeric',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096', // إضافة فحص لصورة الحساب المفقودة فوق
            'front_id_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'back_id_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'terms_accepted' => 'required|accepted',
        ]);

        if ($request->hasFile('img')) {
            // توليد اسم عشوائي آمن لمنع تخطي الامتدادات أو استبدال ملفات النظام
            $imgName = uniqid('img_', true).'.'.$request->file('img')->getClientOriginalExtension();
            $path = $request->file('img')->storeAs('users', $imgName, 'public');
        }
        if ($request->hasFile('front_id_image')) {
            $path1 = $request->file('front_id_image')->store('imageid', 'public');
        }
        if ($request->hasFile('back_id_image')) {
            $path2 = $request->file('back_id_image')->store('imageid', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // استخدام Hash::make بدلاً من bcrypt القديمة
            'role' => $request->role ?? 'customer',
            'img' => $path ?? 'null',
            'latitude' => $request->latitude ?? 'null',
            'longitude' => $request->longitude ?? 'null',
            'security_question' => $request->security_question ?? 'null',
            'security_answer' => Hash::make(trim($request->security_answer)) ?? 'null', // تشفير الإجابة الأمنية لضمان عدم قراءتها من قاعدة البيانات
            'wallet_number' => $request->wallet_number ?? 'null',
            'front_id_image' => $path1 ?? 'null',
            'back_id_image' => $path2 ?? 'null',
            'terms_accepted' => $request->terms_accepted,
        ]);

        $token = $user->createToken('eihapkaramvuejs')->accessToken;
        $user->notify(new WelcomeUser($user));

        return response()->json([
            'message' => 'تم التسجيل بنجاح، برجاء التحقق من بريدك الإلكتروني',
            'token' => $token,
        ], 200);
    }

    public function userUpdate(Request $request, $id)
    {
        // حماية ID: التأكد أن المستخدم يعدل حسابه الشخصي فقط ولا يحق له تعديل حساب مستخدم آخر عبر الـ ID
        if (auth()->id() != $id) {
            return response()->json(['message' => 'غير مصرح لك بتعديل هذا الحساب'], 403);
        }

        $this->validate($request, [
            'name' => 'required|string|max:255',
            'password' => 'required|min:8',
            'last_name' => 'required|string|max:255',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'role' => 'nullable|in:customer,supplier,seller',
        ]);

        $user = User::find($id);
        if (! $user) {
            return response()->json([
                'message' => 'المستخدم غير موجود',
            ], 404);
        }

        if ($request->hasFile('img')) {
            $imgName = uniqid('img_', true).'.'.$request->file('img')->getClientOriginalExtension();
            $path = $request->file('img')->storeAs('users', $imgName, 'public');
        }

        $user->update([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $user->email, // جلب البيانات مباشرة من الموديل المستدعى وليس بعمل Query متكرر غير آمن
            'phone' => $user->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? $user->role, // حماية ضد التلاعب بالصلاحيات عبر الـ Request
            'img' => $path ?? $user->img,
            'latitude' => $request->latitude ?? $user->latitude,
            'longitude' => $request->longitude ?? $user->longitude,
            'security_question' => $request->security_question ?? $user->security_question,
            'security_answer' => $request->has('security_answer') ? Hash::make(trim($request->security_answer)) : $user->security_answer,
            'wallet_number' => $request->wallet_number ?? $user->wallet_number,
        ]);

        return response()->json([
            'message' => 'تم تعديل بنجاح',
            'user' => $user,
        ], 200);
    }

    public function updateLocation(Request $request)
    {
        $this->validate($request, [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'message' => 'المستخدم غير موجود',
            ], 404);
        }

        $user->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'message' => 'تم تحديث الموقع بنجاح',
            'user' => $user,
        ], 200);
    }

    public function addimg(Request $request)
    {
        $this->validate($request, [
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $user = auth()->user();
        if (! $user) {
            return response()->json([
                'message' => 'المستخدم غير موجود',
            ], 404);
        }

        if ($request->hasFile('img')) {
            $imgName = uniqid('img_', true).'.'.$request->file('img')->getClientOriginalExtension();
            $path = $request->file('img')->storeAs('users', $imgName, 'public');
        }

        $user->update([
            'img' => $path ?? $user->img,
        ]);

        return response()->json([
            'message' => 'تم اضافه صورة بنجاح',
            'user' => $user,
        ], 200);
    }

    public function Login(Request $Request)
    {
        // إضافة التحقق من الحقول المدخلة لمنع الـ SQL Injection المحتمل في بعض فريموركات الـ Auth وإيقاف العمليات العشوائية
        $Request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $data = [
            'email' => $Request->email,
            'password' => $Request->password,
        ];

        if (auth()->attempt($data)) {
            $user = auth()->user();
            $token = $user->createToken('eihapkaramvuejs')->accessToken;
            $user->last_seen = now();
            $user->save();

            return response()->json(['token' => $token], 200);
        } else {
            // رسالة خطأ عامة وموحدة لحماية النظام من الـ Enumeration Attacks (معرفة هل الإيميل مسجل أم لا)
            return response()->json(['error' => 'بيانات الدخول غير صحيحة'], 401);
        }
    }

    public function userinfo()
    {
        // تقييد الوصول لهذه الداتا (مثلاً الأدمن فقط)، حظر الوصول العام لكل حسابات المستخدمين الحساسة كـ IDs المرفوعة.
        if (! auth()->user() || ! in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'غير مصرح لك بالوصول لهذه البيانات'], 403);
        }

        $userdata = User::select(
            'id',
            'name',
            'last_name',
            'email',
            'phone',
            'role',
            'img',
            'latitude',
            'longitude',
            'created_at',
            'front_id_image',
            'back_id_image',
        )->get();

        return response()->json(['user' => $userdata], 200);
    }

    public function usersNearby(Request $request)
    {
        $user = auth()->user();

        if (! $user->latitude || ! $user->longitude) {
            return response()->json([
                'message' => '⚠️ لا يوجد إحداثيات محفوظة للمستخدم الحالي.',
            ], 400);
        }

        // فلترة الـ distance القادمة من الـ request
        $request->validate([
            'distance' => 'nullable|numeric|min:1|max:500',
        ]);

        $latitude = $user->latitude;
        $longitude = $user->longitude;
        $distance = $request->distance ?? 10;

        $users = User::where('role', 'customer')
            ->where('id', '!=', $user->id)
            ->nearby($latitude, $longitude, $distance)
            ->get();

        return response()->json([
            'count' => $users->count(),
            'users' => $users,
        ], 200);
    }

    public function OneUserinfo($id)
    {
        // التحقق من الصلاحيات: يحق للمستخدم رؤية حسابه فقط، أو للأدمن رؤية أي حساب
        if (auth()->id() != $id && ! in_array(auth()->user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'غير مصرح لك باستعراض بيانات هذا المستخدم'], 403);
        }

        $userdata = User::select('id', 'name', 'last_name', 'email', 'phone', 'role', 'img', 'latitude', 'longitude', 'created_at')->find($id);
        if (! $userdata) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        return response()->json(['user' => $userdata], 200);
    }

    public function info()
    {
        $userdata = auth()->user();

        return response()->json([
            'user' => [
                'id' => $userdata->id,
                'name' => $userdata->name,
                'last_name' => $userdata->last_name,
                'email' => $userdata->email,
                'phone' => $userdata->phone,
                'role' => $userdata->role,
                'img' => $userdata->img,
                'latitude' => $userdata->latitude,
                'longitude' => $userdata->longitude,
                'created_at' => $userdata->created_at,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->token()) {
            $request->user()->token()->revoke();
        }

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    public function logoutAll(Request $request)
    {
        if ($request->user() && $request->user()->token()) {
            $request->user()->token()->revoke();
        }

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }

    public function UserDelete($id)
    {
        // حظر الحذف العشوائي بدون صلاحية أدمن أو أن يكون صاحب الحساب نفسه
        if (auth()->id() != $id && ! in_array(auth()->user()->role, ['admin'])) {
            return response()->json(['message' => 'غير مصرح لك بحذف هذا الحساب'], 403);
        }

        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'لم يتم ايجاد حساب المستخدم'], 404);
        }
        $user->delete();

        return response()->json(['message' => 'تم ازاله حساب المستخدم']);
    }

    public function registerWithPhone(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|min:8',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'role' => 'required|in:customer,supplier,seller',
            'security_question' => 'required|string|max:255',
            'security_answer' => 'required|string',
            'wallet_number' => 'nullable|numeric',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'front_id_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'back_id_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'terms_accepted' => 'required|accepted',
            'phone' => [
                'required',
                'unique:users',
                'regex:/^(011|012|015|010)[0-9]{8}$/',
            ],
        ], [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex' => 'رقم الهاتف يجب أن يتكون من 11 رقم ويبدأ بـ 010 أو 011 أو 012 أو 015',
        ]);

        if ($request->hasFile('img')) {
            $imgName = uniqid('img_', true).'.'.$request->file('img')->getClientOriginalExtension();
            $path = $request->file('img')->storeAs('users', $imgName, 'public');
        }
        if ($request->hasFile('front_id_image')) {
            $path3 = $request->file('front_id_image')->store('imageid', 'public');
        }
        if ($request->hasFile('back_id_image')) {
            $path4 = $request->file('back_id_image')->store('imageid', 'public');
        }

        $user = User::create([
            'phone' => $request->phone,
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'latitude' => $request->latitude,
            'role' => $request->role ?? 'customer',
            'img' => $path ?? 'null',
            'longitude' => $request->longitude,
            'security_question' => $request->security_question ?? 'null',
            'security_answer' => Hash::make(trim($request->security_answer)) ?? 'null',
            'wallet_number' => $request->wallet_number ?? 'null',
            'front_id_image' => $path3 ?? 'null',
            'back_id_image' => $path4 ?? 'null',
            'terms_accepted' => $request->terms_accepted,
        ]);

        $user->notify(new WelcomeUser($user));

        $token = $user->createToken('eihapkaramvuejs')->accessToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function getSuppliers()
    {
        $suppliers = User::with('suppliedProducts')
            ->where('role', 'supplier')
            ->get();

        return response()->json([
            'count' => $suppliers->count(),
            'suppliers' => $suppliers,
        ], 200);
    }

    public function loginWithPhone(Request $request)
    {
        $request->validate([
            'phone' => [
                'required',
                'regex:/^(011|012|015|010)[0-9]{8}$/',
            ],
            'password' => 'required|string|min:8',
        ], [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex' => 'رقم الهاتف يجب أن يتكون من 11 رقم ويبدأ بـ 010 أو 011 أو 012 أو 015',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
        ]);

        $user = User::where('phone', $request->phone)->first();

        // حماية هجمات الـ Enumeration: توحيد ردود الخطأ عند الفشل
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة التلفون أو كلمة المرور',
            ], 401);
        }

        $token = $user->createToken('eihapkaramvuejs')->accessToken;

        $user->last_seen = now();
        $user->save();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'role' => $user->role,
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
            ],
            'token' => $token,
        ], 200);
    }

    public function logoutphone(Request $request)
    {
        if ($request->user() && $request->user()->token()) {
            $request->user()->token()->revoke();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    public function getSecurityQuestion(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifier = $request->identifier;

        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'لا يوجد مستخدم بهذا البريد أو رقم الهاتف.',
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            'question' => $user->security_question,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function resetPasswordWithSecurity(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'security_answer' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $identifier = $request->identifier;

        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'لا يوجد مستخدم بهذا البريد أو رقم الهاتف.',
            ], 404);
        }

        if (! $user->security_answer || ! Hash::check(trim($request->security_answer), $user->security_answer)) {
            return response()->json([
                'message' => 'إجابة السؤال الأمني غير صحيحة.',
            ], 403);
        }

        $user->password = Hash::make($request->new_password);
        $user->last_seen = now();
        $user->save();

        $token = $user->createToken('Personal Access Token')->accessToken;

        return response()->json([
            'message' => 'تم تغيير كلمة المرور وتسجيل الدخول بنجاح ✅',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'token' => 'required',
            'security_question' => 'required|string|max:255',
            'security_answer' => 'required|string|max:255',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $record = DB::table('password_resets')
            ->where('phone', $request->phone)
            ->where('token', $request->token)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'الرابط غير صالح أو منتهي الصلاحية.'], 400);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_resets')->where('phone', $request->phone)->delete();

            return response()->json(['message' => 'انتهت صلاحية رابط إعادة التعيين.'], 400);
        }

        $user = User::where('phone', $request->phone)->first();
        if (! $user) {
            return response()->json(['message' => 'المستخدم غير موجود.'], 404);
        }

        if ($user->role === 'seller') {
            return response()->json([
                'message' => 'غير مسموح للبائعين باستخدام رابط إعادة تعيين كلمة المرور.',
            ], 403);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'security_question' => $request->security_question,
            'security_answer' => Hash::make(trim($request->security_answer)),
            'last_seen' => now(),
        ]);

        DB::table('password_resets')->where('phone', $request->phone)->delete();

        // أمان: يفضل عدم استخدام تسجيل الدخول التلقائي بالـ Session في الـ APIs هنا وتوليد Token فقط
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->accessToken;
        $expiresAt = $tokenResult->token->expires_at;

        return response()->json([
            'message' => 'تم تغيير كلمة المرور وتسجيل الدخول بنجاح ✅',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt,
        ]);
    }

    public function importUsers(Request $request)
    {
        // حماية الصلاحية للأدمن فقط
        if (! auth()->user() || auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مسموح لك برفع واستيراد ملفات المستخدمين'], 403);
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240', // تحديد حد أقصى لحجم الملف
        ]);

        $filePath = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        foreach (array_slice($rows, 1) as $row) {
            if (empty($row[3])) { // التحقق أن الإيميل متواجد وأنه صالح قبل الإدخال لمنع الـ Null Constraints
                continue;
            }

            // فلترة وتأكيد صحة الإيميل المدخل من ملف الإكسيل لعدم تخريب قاعدة البيانات
            if (! filter_var($row[3], FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            User::updateOrCreate(
                ['email' => $row[3]],
                [
                    'name' => sanitize_string($row[1] ?? 'User'),
                    'last_name' => sanitize_string($row[2] ?? null),
                    'phone' => $row[4] ?? null,
                    'role' => in_array($row[5] ?? '', ['customer', 'supplier', 'seller']) ? $row[5] : 'customer',
                    'password' => isset($row[6]) && $row[6] != '********' ? Hash::make($row[6]) : Hash::make('12345678'),
                    'img' => $row[7] ?? null,
                    'latitude' => is_numeric($row[8] ?? null) ? $row[8] : null,
                    'longitude' => is_numeric($row[9] ?? null) ? $row[9] : null,
                ]
            );
        }

        return response()->json(['message' => 'تم استيراد المستخدمين بنجاح']);
    }

    public function exportUsers()
    {
        // حماية الصلاحية للأدمن فقط لحظر تسريب بيانات المستخدمين بالكامل
        if (! auth()->user() || auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مسموح لك بتصدير بيانات المستخدمين'], 403);
        }

        try {
            $fileName = 'users_export_'.date('Y_m_d_His').'.xlsx';
            $tempPath = storage_path('app/'.$fileName);

            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->getDefaultStyle()->getFont()->setName('Arial');
            $sheet->getDefaultStyle()->getFont()->setSize(12);

            $headers = ['ID', 'Name', 'Last Name', 'Email', 'Phone', 'Role', 'Password', 'Img', 'Latitude', 'Longitude'];
            $sheet->fromArray([$headers], null, 'A1');

            $row = 2;

            User::chunk(500, function ($usersChunk) use ($sheet, &$row) {
                foreach ($usersChunk as $user) {
                    $sheet->setCellValueExplicit('A'.$row, $user->id, DataType::TYPE_STRING);
                    // استخدام e() أو strip_tags لمنع ثغرات الـ XSS في حال فتح الملف داخل المتصفح
                    $sheet->setCellValue('B'.$row, strip_tags($user->name ?? ''));
                    $sheet->setCellValue('C'.$row, strip_tags($user->last_name ?? ''));
                    $sheet->setCellValue('D'.$row, strip_tags($user->email ?? ''));
                    $sheet->setCellValue('E'.$row, strip_tags($user->phone ?? ''));
                    $sheet->setCellValue('F'.$row, $user->role ?? '');
                    $sheet->setCellValue('G'.$row, '********'); // عدم تصدير الـ Hashes الحقيقية لزيادة الأمان

                    try {
                        // أمان: فحص المسار لمنع الـ Path Traversal وإضافة صور خارج مجلد العمل المسموح
                        if ($user->img && file_exists(public_path($user->img)) && ! str_contains($user->img, '..')) {
                            $drawing = new Drawing;
                            $drawing->setPath(public_path($user->img));
                            $drawing->setCoordinates('H'.$row);
                            $drawing->setHeight(50);
                            $drawing->setWorksheet($sheet);
                        } else {
                            $sheet->setCellValue('H'.$row, '');
                        }
                    } catch (\Exception $imgEx) {
                        $sheet->setCellValue('H'.$row, 'Image error');
                    }

                    $sheet->setCellValue('I'.$row, $user->latitude ?? '');
                    $sheet->setCellValue('J'.$row, $user->longitude ?? '');
                    $row++;
                }
            });

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            if (! file_exists($tempPath)) {
                return response()->json([
                    'error' => true,
                    'message' => 'ملف Excel لم يتم إنشاؤه',
                ], 500);
            }

            return response()->download($tempPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'حدث خطأ أثناء تصدير الملف الحساس.',
            ], 500);
        }
    }
}

// دالة مساعدة لتطهير النصوص البرمجية المدخلة من ملفات الرفع الخارجية
function sanitize_string($value)
{
    return $value ? htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8') : null;
}
