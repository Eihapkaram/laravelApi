<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\WelcomeUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /* ============================
        🔹 Register (Email)
       ============================ */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'nullable|string',
            'img' => 'nullable|image',
        ]);

        $path = null;
        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('users', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role ?? 'customer',
            'img' => $path,
        ]);

        $token = $user->createToken('auth')->accessToken;

        $user->notify(new WelcomeUser($user));

        return response()->json([
            'message' => 'تم التسجيل بنجاح، برجاء التحقق من بريدك الإلكتروني',
            'token' => $token
        ]);
    }

    /* ============================
        🔹 Login (Email)
       ============================ */
    public function login(Request $request)
    {
        $data = $request->only('email', 'password');

        if (!Auth::attempt($data)) {
            return response()->json(['message' => 'بيانات الدخول أو الاستعادة غير صحيحة.'], 401);
        }

        $user = Auth::user();
        $user->update(['last_seen' => now()]);

        return response()->json([
            'token' => $user->createToken('auth')->accessToken
        ]);
    }

    /* ============================
        🔹 Update User
       ============================ */
    public function userUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'last_name' => 'required',
            'password' => 'required|min:8',
            'img' => 'nullable|image'
        ]);

        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'المستخدم غير موجود'], 404);

        $path = $user->img;
        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('users', 'public');
        }

        $user->update([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'password' => bcrypt($request->password),
            'img' => $path,
        ]);

        return response()->json(['message' => 'تم التعديل بنجاح', 'user' => $user]);
    }

    /* ============================
        🔹 Register With Phone
       ============================ */
    public function registerWithPhone(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required|unique:users|regex:/^(010|011|012|015)[0-9]{8}$/',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone
        ]);

        $token = $user->createToken('auth')->accessToken;

        return response()->json(['success' => true, 'user' => $user, 'token' => $token]);
    }

    /* ============================
        🔹 Login With Phone
       ============================ */
    public function loginWithPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|regex:/^(010|011|012|015)[0-9]{8}$/',
            'password' => 'required|min:8'
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول أو الاستعادة غير صحيحة.'], 401);
        }

        $user->update(['last_seen' => now()]);

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $user->createToken('auth')->accessToken
        ]);
    }

    /* ============================
        🔹 Logout
       ============================ */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    /* ============================
        🔹 Get Security Question
       ============================ */
    public function getSecurityQuestion(Request $request)
    {
        $request->validate(['identifier' => 'required']);

        $user = User::where('email', $request->identifier)
                    ->orWhere('phone', $request->identifier)
                    ->first();

        if (!$user) return response()->json(['message' => 'بيانات الدخول أو الاستعادة غير صحيحة.'], 404);

        return response()->json(['question' => $user->security_question]);
    }

    /* ============================
        🔹 Reset Password With Security Answer
       ============================ */
    public function resetPasswordWithSecurity(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required',
            'security_answer' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $data['identifier'])
                    ->orWhere('phone', $data['identifier'])
                    ->first();

        if (!$user || !Hash::check(strtolower($data['security_answer']), $user->security_answer)) {
            return response()->json(['message' => 'إجابة السؤال غير صحيحة'], 403);
        }

        $user->update(['password' => Hash::make($data['new_password'])]);

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح']);
    }
}
