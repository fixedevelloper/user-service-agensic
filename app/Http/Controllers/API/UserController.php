<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\BalanceHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function creditBalance(Request $request)
    {
        logger($request->all());
        // 1. Validation stricte
        // 'provider_reference' unique empêche le double crédit (Idempotence)
        $validator = Validator::make($request->all(), [
            'user_id'   => 'required|exists:users,id',
            'amount'    => 'required|numeric|min:0.00000001',
            'reference' => 'required|string|unique:balance_histories,provider_reference',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed or reference already processed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                // 2. Verrouillage de l'utilisateur pour éviter les conflits de lecture/écriture
                $user = User::where('id', $request->user_id)->lockForUpdate()->first();

                $previousBalance = $user->balance;

                // 3. Mise à jour du solde de l'utilisateur
                // Utilisation de increment pour la sécurité SQL atomique
                $user->increment('balance', $request->amount);

                // Rafraîchir l'instance pour avoir le nouveau solde
                $user->refresh();

                // 4. Création de l'historique (Audit Trail)
                BalanceHistory::create([
                    'user_id'            => $user->id,
                    'amount'             => $request->amount,
                    'previous_balance'   => $previousBalance,
                    'new_balance'        => $user->balance,
                    'type'               => 'deposit',
                    'provider_reference' => $request->reference,
                    'description'        => 'Credit via NowPayments',
                    'metadata'           => [
                        'ip_address' => $request->ip(),
                        'processed_at' => now()->toDateTimeString()
                    ]
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Balance updated successfully',
                    'new_balance' => $user->balance
                ], 200);
            });

        } catch (\Exception $e) {
            Log::error("Erreur critique lors du crédit utilisateur : " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
    public function debitBalance(Request $request)
    {
        logger("Demande de débit :", $request->all());

        // 1. Validation de la requête
        $validator = Validator::make($request->all(), [
            'user_id'   => 'required|exists:users,id',
            'amount'    => 'required|numeric|min:0.00000001',
            'reference' => 'required|string|unique:balance_histories,provider_reference',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed or reference already processed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                // 2. Verrouillage de l'utilisateur (Crucial pour éviter le double débit simultané)
                $user = User::where('id', $request->user_id)->lockForUpdate()->first();

                // 3. Vérification du solde suffisant
                if ($user->balance < $request->amount) {
                    return response()->json([
                        'error' => 'Insufficient balance',
                        'current_balance' => $user->balance
                    ], 400);
                }

                $previousBalance = $user->balance;

                // 4. Débit du compte
                // Utilisation de decrement (atomique en SQL)
                $user->decrement('balance', $request->amount);

                // Rafraîchir pour obtenir le nouveau solde après SQL
                $user->refresh();

                // 5. Enregistrement dans l'historique
                BalanceHistory::create([
                    'user_id'            => $user->id,
                    'amount'             => -$request->amount, // On stocke souvent en négatif pour les débits
                    'previous_balance'   => $previousBalance,
                    'new_balance'        => $user->balance,
                    'type'               => 'withdrawal', // Ou 'debit'
                    'provider_reference' => $request->reference,
                    'description'        => 'Débit / Retrait via Service',
                    'metadata'           => [
                        'ip_address' => $request->ip(),
                        'processed_at' => now()->toDateTimeString()
                    ]
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Balance debited successfully',
                    'new_balance' => $user->balance
                ], 200);
            });

        } catch (\Exception $e) {
            Log::error("Erreur critique lors du débit utilisateur : " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
