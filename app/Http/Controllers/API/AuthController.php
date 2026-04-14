<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Http\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // -----------------------------
    // Inscription (client ou agent)
    // -----------------------------
    public function register(Request $request)
    {
        DB::beginTransaction();
        $request->validate([
            'phone'=>'required|string|unique:users,phone',
            'password'=>'required|string|min:6',
            'fullname'=>'required|string',
            'email'=>'required|string',
            'role'=>'required|in:customer,agent',
        ]);

        $user = User::create([
            'email'=>$request->email,
            'name'=>$request->fullname,
            'phone'=>$request->phone,
            'password'=>Hash::make($request->password),
            'role'=>$request->role,
        ]);


        $token = $user->createToken('api_token')->plainTextToken;

        DB::commit();
        return response()->json([
            'data'=>[
                'user'=>$user,
                'token'=>$token
            ]
        ]);
    }

    // -----------------------------
    // Login
    // -----------------------------
    public function login(Request $request)
    {

        $request->validate([
            'phone'=>'required|string',
            'password'=>'required|string',
        ]);
        $user = User::where('phone',$request->phone)->first();

        if(!$user || !Hash::check($request->password,$user->password)){
            throw ValidationException::withMessages([
                'phone'=>['Numéro ou mot de passe incorrect.']
            ]);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        $user->update(['last_login_at'=>now()]);
        return response()->json([
           'data'=>[
               'user'=>$user,
               'token'=>$token
           ]
        ]);
    }

    // -----------------------------
    // Logout
    // -----------------------------
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message'=>'Déconnexion réussie']);
    }

    // -----------------------------
    // Infos du profil connecté
    // -----------------------------
    public function me(Request $request,$id)
    {
        $user=User::with('country')->find($id);
        return Helpers::success($user);
    }
    public function profile(Request $request)
    {
        $userId = $request->header('X-User-Id');
        $user = User::find($userId);

        return response()->json($user);
    }
    public function getUsers(Request $request)
    {
        $users=User::with('country')->get();
        return Helpers::success($users);
    }
}
