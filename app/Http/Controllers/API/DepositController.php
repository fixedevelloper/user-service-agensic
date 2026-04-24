<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Helpers;
use App\Http\Resources\CountryResource;
use App\Http\Resources\DepositResource;
use App\Http\Resources\OperatorResource;
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

        return Helpers::success(CountryResource::collection($countries));
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

        return Helpers::success(OperatorResource::collection($operators));
    }

    public function index(Request $request)
    {
        $search = $request->query('search');

        $deposits = Deposit::with(['user', 'operator'])
            // Recherche par référence
            ->when($search, function ($query, $search) {
                $query->where('reference', 'like', "%{$search}%")
                    // Recherche croisée dans la relation User
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    // Recherche par nom de l'opérateur
                    ->orWhereHas('operator', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            // Filtre par statut (optionnel mais utile)
            ->when($request->query('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($request->query('per_page', 10));

        // IMPORTANT : On retourne la ressource avec les données de pagination
        $resourceCollection = DepositResource::collection($deposits);

        return Helpers::success([
            'items' => $resourceCollection,
            'pagination' => [
                'current_page' => $deposits->currentPage(),
                'last_page'    => $deposits->lastPage(),
                'per_page'     => $deposits->perPage(),
                'total'        => $deposits->total(),
                'next_page_url'=> $deposits->nextPageUrl(),
                'prev_page_url'=> $deposits->previousPageUrl(),
            ]
        ]);
    }
    /**
     * Affiche les détails d'un dépôt spécifique.
     * * @param  int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // 1. Récupération avec Eager Loading pour les relations nécessaires au Dashboard
        // On utilise find() pour pouvoir gérer l'erreur manuellement avec notre Helper
        $deposit = Deposit::with(['user', 'operator.country'])
            ->find($id);

        // 2. Gestion de l'erreur 404 (Entité non trouvée)
        if (!$deposit) {
            return Helpers::error("Ce dépôt est introuvable ou a été supprimé.", 404);
        }

        // 3. Retourne la ressource formatée via votre Helper success
        return Helpers::success(new DepositResource($deposit));
    }
    /**
     * Créer un dépôt et générer un lien de paiement
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createDeposit(Request $request)
    {
       $deposit = Deposit::create([
        'user_id'     => $request->user_id,
        'amount'      => $request->amount,
        'reference'   => $request->reference,
        'provider_token'=> $request->token,
        'operator_id' => $request->operator_id,
        'status'      => 'pending',
    ]);

    return response()->json(['success' => true]);
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
