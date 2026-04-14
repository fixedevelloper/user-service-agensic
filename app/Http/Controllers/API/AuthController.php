<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Http\Helpers\Helpers;
use http\Exception;
use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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


    public function login(Request $request)
    {
        Log::info("Utilisateur connecté : ", ['data' => $request->all()]);
        // 1. Validation des champs
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // 2. Recherche de l'utilisateur
            $user = User::where('phone', $request->phone)->first();

            // 3. Vérification des identifiants
            if (!$user || !Hash::check($request->password, $user->password)) {
                // LOG : Tentative de connexion échouée (utile pour détecter les attaques brute force)
                Log::warning("Échec de connexion pour le téléphone : {$request->phone}", [
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent')
                ]);

                throw ValidationException::withMessages([
                    'phone' => ['Les identifiants fournis sont incorrects.']
                ]);
            }

            // 4. Gestion du Token (Sanctum)
            $token = $user->createToken('api_token')->plainTextToken;

            // 5. Mise à jour des infos de connexion
            $user->update([
                'last_login_at' => now(),
                // 'last_login_ip' => $request->ip() // Optionnel : si vous avez cette colonne
            ]);

            // LOG : Succès
            Log::info("Utilisateur connecté : ID {$user->id}", ['phone' => $user->phone]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ]);

        } catch (ValidationException $e) {
            // On laisse Laravel gérer les erreurs de validation (422)
            throw $e;
        } catch (Exception $e) {
            // 6. Gestion des erreurs critiques (BDD, serveur, etc.)
            Log::error("Erreur critique lors du login : " . $e->getMessage(), [
                'phone' => $request->phone,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur technique est survenue. Veuillez réessayer plus tard.'
            ], 500);
        }
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
