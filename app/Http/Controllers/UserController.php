<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\WelcomeUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // =============================
    // Register (Email + Password)
    // =============================
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'last_name' => 'required',
            'img' => 'image|mimes:jpeg,png,jpg,gif,webp'
        ]);

        // رفع الصورة
        if ($request->hasFile('img')) {
            $imge = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('users', $imge, 'public');
        }

        // إنشاء المستخدم
        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),  // صح
            'role' => $request->role ?? 'customer',
            'img' => $path ?? null,
        ]);

        // إنشاء Access Token
        $token = $user->createToken('eihapkaramvuejs')->accessToken;

        // إرسال إشعار
        $user->notify(new WelcomeUser($user));

        return response()->json([
            'message' => 'تم التسجيل بنجاح، برجاء التحقق من بريدك الإلكتروني',
            'token' => $token,
        ], 200);
    }


    // =============================
    // User Update (FIXED)
    // =============================
    public function userUpdate(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'password' => 'required|min:8',
            'last_name' => 'required',
            'img' => 'image|mimes:jpeg,png,jpg,gif,webp'
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'المستخدم غير موجود',
            ], 404);
        }

        // رفع صورة جديدة لو موجودة
        if ($request->hasFile('img')) {
            $imge = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('users', $imge, 'public');
        }

        // ❌ المشكلة كانت هنا:
        // email & phone كانو يتجابو من auth()->user() غلط

        $user->update([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $user->email,   // صح
            'phone' => $user->phone,   // صح
            'password' => Hash::make($request->password),
            'role' => $request->role ?? $user->role,
            'img' => $path ?? $user->img,
        ]);

        return response()->json([
            'message' => 'تم التعديل بنجاح',
            'user' => $user,
        ], 200);
    }


    // =============================
    // Login (Email + Password)
    // =============================
    public function Login(Request $request)
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        if (auth()->attempt($data)) {

            $token = auth()->user()->createToken('eihapkaramvuejs')->accessToken;

            return response()->json(['token' => $token], 200);
        }

        return response()->json(['error' => 'بيانات تسجيل الدخول غير صحيحة'], 401);
    }


    // =============================
    // Get All Users
    // =============================
    public function userinfo()
    {
        return response()->json(['user' => User::get()], 200);
    }

    public function OneUserinfo($id)
    {
        return response()->json(['user' => User::find($id)], 200);
    }


    // =============================
    // Logout
    // =============================
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'تم تسجيل الخروج',
        ]);
    }


    // =============================
    // Delete User
    // =============================
    public function UserDelete($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'لم يتم ايجاد حساب المستخدم']);
        }

        $user->delete();

        return response()->json(['message' => 'تم ازاله حساب المستخدم']);
    }


    // =============================
    // Register with Phone
    // =============================
    public function registerWithPhone(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => [
                'required',
                'regex:/^(011|012|015|010)[0-9]{8}$/'
            ],
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            $user = User::create([
                'phone' => $request->phone,
                'name' => $request->name,
            ]);
        }

        $token = $user->createToken('API Token')->accessToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ]);
    }


    // =============================
    // Login with Phone
    // =============================
    public function loginWithPhone(Request $request)
    {
        $request->validate([
            'phone' => [
                'required',
                'regex:/^(011|012|015|010)[0-9]{8}$/'
            ],
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'user' => 'الرقم غير مسجل',
            ], 401);
        }

        $token = $user->createToken('API Token')->accessToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ], 200);
    }


    // =============================
    // Logout Phone
    // =============================
    public function logoutphone(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }
}
