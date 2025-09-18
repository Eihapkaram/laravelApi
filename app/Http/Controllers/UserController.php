<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function register(Request $Request){
        $this->validate($Request,[

            'name'=> 'required',
            'email'=> 'required|email',
            'password'=> 'required|min:8',
        ]);
$user = User::create([
    'name'=> $Request->name,
            'email'=> $Request->email,
            'password'=> bcrypt($Request->password),
]);
$token = $user->createToken('eihapkaramvuejs')->accessToken;
return response()->json(['Token'=> $token ],200);
    }
    public function Login(Request $Request){
        $data = [
        'email' => $Request->email,
        'password' => $Request->password,
        ];
if(auth()->attempt($data)){
    $token = auth()->user()->createToken('eihapkaramvuejs')->accessToken;
    return response()->json(['token'=>$token],200);

}
else{
return response()->json(['error'=>"field login"],401);
}
    }

    public function userinfo(){
    $userdata = auth()->user();
    return response()->json(['user'=> $userdata],200);
    }
}
