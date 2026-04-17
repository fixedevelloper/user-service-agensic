<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Helpers;
use App\Models\Deposit;
use App\Models\Country;
use App\Models\Operator;
use App\Notifications\DepositProcessed;
use App\Notifications\TransactionProcessed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DepositController extends Controller
{
    /**
     * Liste des pays avec leurs opérateurs
     */
    public function getCountries()
    {
        $countries = Country::with('operators')
            ->where('status', 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    public function getOperatorsList(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'country_code' => 'required|string',
        ]);

        $operators = Operator::with('country')
            ->whereHas('country', function ($query) use ($validated) {
                $query->where('iso', $validated['country_code']);
            })
            ->get();

        return Helpers::success($operators);
    }

    /**
     * Créer un dépôt et générer un lien de paiement
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createDeposit(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'operator_id' => 'required|exists:operators,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $user = Auth::user();

        DB::beginTransaction();

        try {

            // 🔥 Génération référence
            $reference = strtoupper(Str::random(10));

            // 💾 Création dépôt (PAS DE CREDIT ICI)
            $deposit = Deposit::create([
                'user_id' => $user->id,
                'operator_id' => $request->operator_id,
                'amount' => $request->amount,
                'status' => 'pending',
                'reference' => $reference
            ]);

            // 📡 Appel microservice transaction
            $response = Http::withToken(env('API_SERVICE_TOKEN'))
                ->post(env('TRANSACTION_SERVICE_URL') . "/deposit", [
                    'user_id' => $user->id,
                    'phone' => $user->phone,
                    'amount' => $request->amount,
                    'name' => $user->name,
                    'order_id' => $reference,
                    'return_url' => route('deposit.return'),
                    'webhook_url' => route('deposit.webhook'),
                ]);

            if (!$response->successful()) {
                logger($response);
                throw new \Exception('Erreur service paiement');
            }

            $data = $response->json();

            if (!isset($data['payment_url'])) {
                throw new \Exception('Réponse invalide paiement');
            }
            Notification::route('telegram', config('services.telegram-bot-api.group_id'))
                ->notify(new DepositProcessed($deposit));
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'deposit' => $deposit,
                    'payment_url' => $data['payment_url'],
                    'token' => $data['token'] ?? null
                ]
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les dépôts de l'utilisateur
     */
    public function myDeposits()
    {
        $user = Auth::user();
        $deposits = Deposit::with(['operator', 'operator.country'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deposits
        ]);
    }

    /**
     * Callback après paiement pour mettre à jour le statut
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentCallback(Request $request)
    {
        $request->validate([
            'reference' => 'required|exists:deposits,reference',
            'status' => 'required|in:success,failed'
        ]);

        $deposit = Deposit::where('reference', $request->reference)->first();
        $deposit->status = $request->status;
        $deposit->completed_at = now();
        $deposit->save();

        return response()->json([
            'success' => true,
            'data' => $deposit
        ]);
    }
    public function webhook(Request $request)
    {
        Log::info('MoneyFusion Webhook', $request->all());
        $reference = $request->orderId; // ou mapping token → reference
        $event = $request->event;

        $deposit = Deposit::where('reference', $reference)->first();

        if (!$deposit) {
            return response()->json(['error' => 'Deposit not found'], 404);
        }

        // 🔒 anti double traitement
        if ($deposit->status !== 'pending') {
            return response()->json(['status' => 'already processed']);
        }

        if ($event === 'payin.session.completed') {

            DB::transaction(function () use ($deposit) {

                $user = $deposit->user;

                // 💰 crédit wallet ici SEULEMENT
                $user->balance += $deposit->amount;
                $user->save();

                $deposit->update(['status' => 'success']);
            });

        } elseif ($event === 'payin.session.cancelled') {

            $deposit->update(['status' => 'failed']);
        }

        return response()->json(['status' => 'ok']);
    }
}
