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
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'last_name' => 'required',
            'role' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'security_question' => 'required',
            'security_answer' => 'required',
            'wallet_number' => 'nullable|numeric',
            'front_id_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'back_id_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'terms_accepted' => 'required|accepted',
        ]);

        if ($request->hasFile('img')) {
            $imge = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('users', $imge, 'public');
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
            'password' => bcrypt($request->password),
            'role' => $request->role ?? 'customer',
            'img' => $path ?? 'null',
            'latitude' => $request->latitude ?? 'null',
            'longitude' => $request->longitude ?? 'null',
            'security_question' => $request->security_question ?? 'null',
            'security_answer' => bcrypt($request->security_answer) ?? 'null',
            'wallet_number' => $request->wallet_number ?? 'null',
            'front_id_image' => $path1 ?? 'null',
            'back_id_image' => $path2 ?? 'null',
            'terms_accepted' => $request->terms_accepted, // ✅
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
        $this->validate($request, [
            'name' => 'required',
            'password' => 'required|min:8',
            'last_name' => 'required',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($request->hasFile('img')) {
            $imge = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('users', $imge, 'public');
        }

        $user = User::find($id);
        if (! $user) {
            return response()->json([
                'message' => 'المستخدم غير موجود',
            ], 404);
        }

        $user->update([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => auth()->user()->find($id)->email,
            'phone' => auth()->user()->find($id)->phone,
            'password' => bcrypt($request->password),
            'role' => $request->role ?? 'customer',
            'img' => $path ?? $user->img,
            'latitude' => $request->latitude ?? $user->latitude,
            'longitude' => $request->longitude ?? $user->longitude,
            'security_question' => $request->security_question ?? $user->security_question,
            'security_answer' => $request->security_answer ?? $user->security_answer,
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
            'img' => 'image|mimes:jpeg,png,jpg,gif,webp',
        ]);

        if ($request->hasFile('img')) {
            $imge = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('users', $imge, 'public');
        }
        $user = auth()->user();
        if (! $user) {
            return response()->json([
                'message' => 'المستخدم غير موجود',
            ], 404);
        }

        $user->update([
            'img' => $path ?? 'null',
        ]);

        return response()->json([
            'message' => 'تم اضافه صورة  بنجاح',
            'user' => $user,
        ], 200);
    }

    public function Login(Request $Request)
    {
        $data = [
            'email' => $Request->email,
            'password' => $Request->password,
        ];
        if (auth()->attempt($data)) {
            $token = auth()->user()->createToken('eihapkaramvuejs')->accessToken;
            $user = Auth::user();
            $user->last_seen = now();
            $user->save();

            return response()->json(['token' => $token], 200);
        } else {
            return response()->json(['error' => 'field login'], 401);
        }
    }

    public function userinfo()
    {
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
    // جلب العملاء القريبين من موقع المندوب

    public function usersNearby(Request $request)
    {
        $user = auth()->user(); // 🧍‍♂️ المستخدم الحالي

        // تأكد أن عنده إحداثيات
        if (! $user->latitude || ! $user->longitude) {
            return response()->json([
                'message' => '⚠️ لا يوجد إحداثيات محفوظة للمستخدم الحالي.',
            ], 400);
        }

        $latitude = $user->latitude;
        $longitude = $user->longitude;
        $distance = $request->distance ?? 10; // المسافة الافتراضية 10 كم

        // 🔍 جلب العملاء القريبين من المستخدم الحالي فقط
        $users = User::where('role', 'customer')
            ->where('id', '!=', $user->id) // استبعاد المستخدم نفسه
            ->nearby($latitude, $longitude, $distance)
            ->get();

        return response()->json([
            'count' => $users->count(),
            'users' => $users,
        ], 200);
    }

    public function OneUserinfo($id)
    {
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
        $request->user()->token()->revoke();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }

    public function UserDelete($id)
    {
        if (! User::find($id)) {
            return response()->json(['message' => 'لم يتم ايجاد حساب المستخدم']);
        }
        User::find($id)->delete();

        return response()->json(['message' => 'تم ازاله حساب  المستخدم']);
    }

    public function registerWithPhone(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'password' => 'required|min:8',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'role' => 'required',
            'security_question' => 'required',
            'security_answer' => 'required',
            'wallet_number' => 'nullable|numeric',
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

        $user = User::where('phone', $request->phone)->first();
        if ($request->hasFile('front_id_image')) {
            $path3 = $request->file('front_id_image')->store('imageid', 'public');
        }
        if ($request->hasFile('back_id_image')) {
            $path4 = $request->file('back_id_image')->store('imageid', 'public');
        }
        if (! $user) {
            $user = User::create([
                'phone' => $request->phone,
                'name' => $request->name,
                'password' => bcrypt($request->password),
                'latitude' => $request->latitude,
                'role' => $request->role ?? 'customer',
                'img' => $path ?? 'null',
                'longitude' => $request->longitude,
                'security_question' => $request->security_question ?? 'null',
                'security_answer' => bcrypt($request->security_answer) ?? 'null',
                'wallet_number' => $request->wallet_number ?? 'null',
                'front_id_image' => $path3 ?? 'null',
                'back_id_image' => $path4 ?? 'null',
                'terms_accepted' => $request->terms_accepted,

            ]);
            $user->notify(new WelcomeUser($user));
        }

        $token = $user->createToken('eihapkaramvuejs')->accessToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function getSuppliers()
    {
        $suppliers = User::with('suppliedProducts')  // تحميل العلاقة
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

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'الرقم غير مسجل',
            ], 401);
        }

        // التحقق من كلمة المرور
        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة',
            ], 401);
        }

        // إنشاء التوكن
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
        $request->user()->token()->revoke();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    // سوال التحقق
    public function getSecurityQuestion(Request $request)
    {
        $request->validate([
            'identifier' => 'required', // البريد أو رقم الهاتف
        ]);

        $identifier = $request->identifier;

        // البحث حسب الإيميل أو رقم الهاتف
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

    // اعاده تعين كلمه السر
    public function resetPasswordWithSecurity(Request $request)
    {
        $request->validate([
            'identifier' => 'required', // البريد أو رقم الهاتف
            'security_answer' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $identifier = $request->identifier;

        // البحث حسب البريد أو رقم الهاتف
        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'لا يوجد مستخدم بهذا البريد أو رقم الهاتف.',
            ], 404);
        }

        // التحقق من إجابة السؤال الأمني
        if (! $user->security_answer || ! Hash::check(trim($request->security_answer), $user->security_answer)) {
            return response()->json([
                'message' => 'إجابة السؤال الأمني غير صحيحة.',
            ], 403);
        }

        // تحديث كلمة المرور بشكل آمن
        $user->password = Hash::make($request->new_password);
        $user->last_seen = now(); // تحديث آخر ظهور (اختياري)
        $user->save();

        // إنشاء توكن جديد (Passport أو Sanctum حسب مشروعك)
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

    // اعاده تعين كلمه السر
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'token' => 'required',
            'security_question' => 'required|string|max:255',
            'security_answer' => 'required|string|max:255',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // 🔍 التحقق من وجود سجل إعادة التعيين
        $record = DB::table('password_resets')
            ->where('phone', $request->phone)
            ->where('token', $request->token)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'الرابط غير صالح أو منتهي الصلاحية.'], 400);
        }

        // 🕒 التحقق من صلاحية الرابط (ساعة واحدة)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_resets')->where('phone', $request->phone)->delete();

            return response()->json(['message' => 'انتهت صلاحية رابط إعادة التعيين.'], 400);
        }

        // 🔍 التحقق من المستخدم
        $user = User::where('phone', $request->phone)->first();
        if (! $user) {
            return response()->json(['message' => 'المستخدم غير موجود.'], 404);
        }

        // 🚫 منع البائع من استخدام رابط إعادة التعيين
        if ($user->role === 'seller') {
            return response()->json([
                'message' => 'غير مسموح للبائعين باستخدام رابط إعادة تعيين كلمة المرور.',
            ], 403);
        }

        // ✅ تحديث كلمة المرور + سؤال وإجابة الأمان
        $user->update([
            'password' => Hash::make($request->new_password),
            'security_question' => $request->security_question,
            'security_answer' => Hash::make($request->security_answer),
            'last_seen' => now(),
        ]);

        // 🗑️ حذف السجل من password_resets
        DB::table('password_resets')->where('phone', $request->phone)->delete();

        // ✅ تسجيل الدخول تلقائياً بعد التعيين
        Auth::login($user);

        // ✅ إنشاء توكن Passport
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

    // ✅ استيراد المستخدمين من ملف Excel
    public function importUsers(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $filePath = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // نفترض أول صف عناوين الأعمدة
        foreach (array_slice($rows, 1) as $row) {
            // مثال على ترتيب الأعمدة داخل الملف:
            // [0 => id, 1 => name, 2 => last_name, 3 => email, 4 => phone, 5 => role, 6 => password, 7 => img]

            if (empty($row[1]) && empty($row[3])) {
                continue; // تجاهل الصفوف الفارغة
            }
            User::updateOrCreate(
                ['email' => $row[3] ?? null],
                [
                    'name' => $row[1] ?? null,
                    'last_name' => $row[2] ?? null,
                    'phone' => $row[4] ?? null,
                    'role' => $row[5] ?? 'customer',
                    'password' => isset($row[6]) ? Hash::make($row[6]) : Hash::make('12345678'),
                    'img' => $row[7] ?? null,
                    'latitude' => $row[8] ?? null,
                    'longitude' => $row[9] ?? null,
                ]
            );
        }

        return response()->json(['message' => 'تم استيراد المستخدمين بنجاح']);
    }

    public function exportUsers()
    {
        try {
            $fileName = 'users_export_'.date('Y_m_d_His').'.xlsx';
            $tempPath = storage_path('app/'.$fileName);

            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            // دعم الحروف العربية
            $sheet->getDefaultStyle()->getFont()->setName('Arial');
            $sheet->getDefaultStyle()->getFont()->setSize(12);

            // العناوين
            $headers = ['ID', 'Name', 'Last Name', 'Email', 'Phone', 'Role', 'Password', 'Img', 'Latitude', 'Longitude'];
            $sheet->fromArray([$headers], null, 'A1');

            $row = 2;

            User::chunk(500, function ($usersChunk) use ($sheet, &$row) {
                foreach ($usersChunk as $user) {
                    $sheet->setCellValueExplicit('A'.$row, $user->id, DataType::TYPE_STRING);
                    $sheet->setCellValue('B'.$row, $user->name ?? '');
                    $sheet->setCellValue('C'.$row, $user->last_name ?? '');
                    $sheet->setCellValue('D'.$row, $user->email ?? '');
                    $sheet->setCellValue('E'.$row, $user->phone ?? '');
                    $sheet->setCellValue('F'.$row, $user->role ?? '');
                    $sheet->setCellValue('G'.$row, '********');

                    // إضافة الصورة في الخلية H إذا موجودة وصالحة
                    try {
                        if ($user->img && file_exists(public_path($user->img))) {
                            $drawing = new Drawing;
                            $drawing->setPath(public_path($user->img));
                            $drawing->setCoordinates('H'.$row);
                            $drawing->setHeight(50); // تقليل ارتفاع الصورة لتقليل استهلاك الذاكرة
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

            // تحقق من وجود الملف قبل محاولة تحميله
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
                'message' => $e->getMessage(),
                'file' => method_exists($e, 'getFile') ? $e->getFile() : null,
                'line' => method_exists($e, 'getLine') ? $e->getLine() : null,
            ], 500);
        }
    }
}
