<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\WelcomeUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
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
            'img' => 'image|mimes:jpeg,png,jpg,gif,webp',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
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
            'img' => $path ?? 'null',
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
            'name' => auth()->user()->name,
            'last_name' => auth()->user()->last_name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone,
            'password' => bcrypt(auth()->user()->password),
            'role' => auth()->user()->role ?? 'customer',
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
        $userdata = User::select('id', 'name', 'last_name', 'email', 'phone', 'role', 'img', 'latitude', 'longitude')->get();
        return response()->json(['user' => $userdata], 200);
    }

    public function OneUserinfo($id)
    {
        $userdata = User::select('id', 'name', 'last_name', 'email', 'phone', 'role', 'img', 'latitude', 'longitude')->find($id);
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

    public function importUsers(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);
        $filePath = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        foreach (array_slice($rows, 1) as $row) {
            if (empty($row[1]) && empty($row[3])) continue;
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
        $users = User::select('id', 'name', 'last_name', 'email', 'phone', 'role', 'password', 'img', 'latitude', 'longitude')->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['ID', 'Name', 'Last Name', 'Email', 'Phone', 'Role', 'Password', 'Img', 'Latitude', 'Longitude'];
        $sheet->fromArray([$headers], null, 'A1');

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                $user->id,
                $user->name,
                $user->last_name,
                $user->email,
                $user->phone,
                $user->role,
                '********',
                $user->img,
                $user->latitude,
                $user->longitude,
            ];
        }
        $sheet->fromArray($data, null, 'A2');

        $fileName = 'users_export_' . date('Y_m_d_His') . '.xlsx';
        $tempPath = storage_path('app/' . $fileName);
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }
}
