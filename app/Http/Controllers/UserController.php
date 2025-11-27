<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\WelcomeUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'       => 'required',
            'last_name'  => 'required',
            'email'      => 'required|email|unique:users',
            'password'   => 'required|min:8',
            'role'       => 'nullable',
            'img'        => 'nullable|image|mimes:jpg,jpeg,png,webp'
        ]);

        if ($request->hasFile('img')) {
            $filename = time() . '_' . $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('users', $filename, 'public');
        }

        $user = User::create([
            'name'      => $request->name,
            'last_name' => $request->last_name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role ?? 'customer',
            'img'       => $path ?? null,
        ]);

        $token = $user->createToken('eihapkaramvuejs')->accessToken;

        // 🔔 إرسال إشعار ترحيبي
        $user->notify(new WelcomeUser($user));

        return response()->json([
            'message' => 'تم التسجيل بنجاح',
            'token'   => $token,
        ], 200);
    }

    public function Login(Request $request)
    {
        $credentials = [
            'email'    => $request->email,
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'بيانات الدخول غير صحيحة'
            ], 401);
        }

        $user = Auth::user();
        $user->last_seen = now();
        $user->save();

        $token = $user->createToken('eihapkaramvuejs')->accessToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $token,
        ], 200);
    }

    public function userUpdate(Request $request, $id)
    {
        $request->validate([
            'name'      => 'required',
            'last_name' => 'required',
            'password'  => 'required|min:8',
            'img'       => 'nullable|image|mimes:jpeg,png,jpg,webp'
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        if ($request->hasFile('img')) {
            $filename = time() . '_' . $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('users', $filename, 'public');
        }

        $user->update([
            'name'      => $request->name,
            'last_name' => $request->last_name,
            'password'  => Hash::make($request->password),
            'img'       => $path ?? $user->img,
            'role'      => $request->role ?? $user->role,
        ]);

        return response()->json([
            'message' => 'تم التعديل بنجاح',
            'user'    => $user,
        ], 200);
    }

    public function userinfo()
    {
        return response()->json([
            'users' => User::all()
        ], 200);
    }

    public function OneUserinfo($id)
    {
        $user = User::find($id);

        if (!$user)
            return response()->json(['message' => 'المستخدم غير موجود'], 404);

        return response()->json(['user' => $user], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    public function UserDelete($id)
    {
        if (!User::find($id)) {
            return response()->json(['message' => 'المستخدم غير موجود']);
        }

        User::destroy($id);

        return response()->json(['message' => 'تم حذف المستخدم']);
    }


    // ❗ تسجيل أو إنشاء حساب برقم الهاتف
    public function registerWithPhone(Request $request)
    {
        $request->validate([
            'name'  => 'required',
            'phone' => 'required|regex:/^(010|011|012|015)[0-9]{8}$/'
        ]);

        $user = User::firstOrCreate(
            ['phone' => $request->phone],
            ['name' => $request->name]
        );

        $token = $user->createToken('eihapkaramvuejs')->accessToken;

        return response()->json([
            'success' => true,
            'user'    => $user,
            'token'   => $token
        ]);
    }

    // ❗ تسجيل دخول برقم هاتف
    public function loginWithPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^(010|011|012|015)[0-9]{8}$/'
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user)
            return response()->json(['message' => 'رقم الهاتف غير مسجل'], 401);

        $token = $user->createToken('eihapkaramvuejs')->accessToken;

        return response()->json([
            'success' => true,
            'user'    => $user,
            'token'   => $token
        ]);
    }

    public function logoutphone(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'تم تسجيل الخروج']);
    }
}
