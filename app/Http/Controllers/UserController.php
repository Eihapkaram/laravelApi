<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'last_name' => 'required',
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
        ]);


        // إنشاء التوكن
        $token = $user->createToken('eihapkaramvuejs')->accessToken;

        return response()->json([
            'message' => 'تم التسجيل بنجاح، برجاء التحقق من بريدك الإلكتروني',
            'token' => $token,
        ], 200);
    }


public function userUpdate(Request $request,$id)
    {
        $this->validate($request, [
            'name' => 'required',
            'password' => 'required|min:8',
            'last_name' => 'required',
            'img' => 'image|mimes:jpeg,png,jpg,gif,webp'
        ]);

        if ($request->hasFile('img')) {
            $imge = $request->file('img')->getClientOriginalName();
            $path = $request->file('img')->storeAs('users', $imge, 'public');
        }
$user =User::find($id) ;
if(!$user){
    return response()->json([
            'message' => 'المستخدم غير موجود',
        ], 404);
}


       $user->update([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role ?? 'customer',
            'img' => $path ?? 'null',
        ]);


        return response()->json([
            'message' => 'تم تعديل بنجاح',
            'user' => $user ,
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
        $userdata = User::get();

        return response()->json(['user' => $userdata], 200);
    }

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

    public function UserDelete($id)
    {
        if (! User::find($id)) {
            return response()->json([
                'message' => 'لم يتم ايجاد حساب المستخدم',
            ]);
        }
        User::find($id)->delete();

        return response()->json([
            'message' => 'تم ازاله حساب  المستخدم',
        ]);
    }
}
