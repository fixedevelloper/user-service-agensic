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
}
