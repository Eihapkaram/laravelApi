<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\WelcomeUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'last_name' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($request->hasFile('img')) {
            $imge = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('users', $imge, 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role ?? 'customer',
            'img' => $path ?? 'null',
            'latitude' => $request->latitude ?? null,
            'longitude' => $request->longitude ?? null,
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
        if (!$user) {
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

        if (!$user) {
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
            'img' => 'image|mimes:jpeg,png,jpg,gif,webp'
        ]);

        if ($request->hasFile('img')) {
            $imge = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('users', $imge, 'public');
        }
        $user = auth()->user();
        if (!$user) {
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
            return response()->json(['token' => $token], 200);
        } else {
            return response()->json(['error' => 'field login'], 401);
        }
    }

    public function userinfo()
    {
        $userdata = User::select('id', 'name', 'last_name', 'email', 'phone', 'role', 'img', 'latitude', 'longitude', 'created_at')->get();
        return response()->json(['user' => $userdata], 200);
    }
    // جلب المستخدمين الاقرب للموقع الي هتبعته لي url GET /api/users-nearby?latitude=30.0444&longitude=31.2357&distance=10

    public function usersNearby(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'distance' => 'nullable|numeric'
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $distance = $request->distance ?? 10; // افتراضي 10 كم هيجيب كل المستخدمين ضمن 15 كم من النقطة المحددة.

        $users = User::nearby($latitude, $longitude, $distance)->get();

        return response()->json([
            'count' => $users->count(),
            'users' => $users
        ], 200);
    }


    public function OneUserinfo($id)
    {
        $userdata = User::select('id', 'name', 'last_name', 'email', 'phone', 'role', 'img', 'latitude', 'longitude', 'created_at')->find($id);
        if (!$userdata) {
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
                "created_at" => $userdata->created_at
            ]
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
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'phone' => [
                'required',
                'regex:/^(011|012|015|010)[0-9]{8}$/'
            ],
        ], [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex' => 'رقم الهاتف يجب أن يتكون من 11 رقم ويبدأ بـ 010او  011 أو 012 أو 015',
        ]);

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            $user = User::create([
                'phone' => $request->phone,
                'name' => $request->name,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
            $user->notify(new WelcomeUser($user));
        }

        $token = $user->createToken('API Token')->accessToken;
        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function loginWithPhone(Request $request)
    {
        $request->validate([
            'phone' => [
                'required',
                'regex:/^(011|012|015|010)[0-9]{8}$/'
            ],
        ], [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex' => 'رقم الهاتف يجب أن يتكون من 11 رقم ويبدأ بـ 010او  011 أو 012 أو 015',
        ]);

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'user' => 'الرقم غير مسجل او تاكد من',
            ], 401);
        }

        $token = $user->createToken('API Token')->accessToken;
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

    // ✅ استيراد المستخدمين من ملف Excel
    public function importUsers(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
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
            $fileName = 'users_export_' . date('Y_m_d_His') . '.xlsx';
            $tempPath = storage_path('app/' . $fileName);

            $spreadsheet = new Spreadsheet();
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
                    $sheet->setCellValueExplicit('A' . $row, $user->id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue('B' . $row, $user->name ?? '');
                    $sheet->setCellValue('C' . $row, $user->last_name ?? '');
                    $sheet->setCellValue('D' . $row, $user->email ?? '');
                    $sheet->setCellValue('E' . $row, $user->phone ?? '');
                    $sheet->setCellValue('F' . $row, $user->role ?? '');
                    $sheet->setCellValue('G' . $row, '********');

                    // إضافة الصورة في الخلية H إذا موجودة وصالحة
                    try {
                        if ($user->img && file_exists(public_path($user->img))) {
                            $drawing = new Drawing();
                            $drawing->setPath(public_path($user->img));
                            $drawing->setCoordinates('H' . $row);
                            $drawing->setHeight(50); // تقليل ارتفاع الصورة لتقليل استهلاك الذاكرة
                            $drawing->setWorksheet($sheet);
                        } else {
                            $sheet->setCellValue('H' . $row, '');
                        }
                    } catch (\Exception $imgEx) {
                        $sheet->setCellValue('H' . $row, 'Image error');
                    }

                    $sheet->setCellValue('I' . $row, $user->latitude ?? '');
                    $sheet->setCellValue('J' . $row, $user->longitude ?? '');
                    $row++;
                }
            });

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            // تحقق من وجود الملف قبل محاولة تحميله
            if (!file_exists($tempPath)) {
                return response()->json([
                    'error' => true,
                    'message' => 'ملف Excel لم يتم إنشاؤه'
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
