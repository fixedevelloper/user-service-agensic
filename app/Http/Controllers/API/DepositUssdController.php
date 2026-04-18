<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Helpers;
use App\Http\Resources\DepositUssdResource;
use App\Models\DepositUssd;
use App\Notifications\DepositProcessed;
use App\Notifications\DepositUssdProcessed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class DepositUssdController extends Controller
{


    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $perPage = $request->query('per_page', 15);

        $ussdDeposits = DepositUssd::with('user')
            // Recherche multicritère (Référence ou Nom utilisateur)
            ->when($search, function ($query, $search) {
                $query->where('reference', 'like', "%{$search}%")
                    ->orWhere('ussd_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            })
            // Filtre par statut (En attente, Complété, etc.)
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            // Filtre par code pays (ex: CM, CI, SN)
            ->when($request->query('country'), function ($query, $country) {
                $query->where('country_code', $country);
            })
            ->latest()
            ->paginate($perPage);

        // Transformation via la Resource
        $resourceCollection = DepositUssdResource::collection($ussdDeposits);

        // Retour structuré pour le Dashboard Next.js
        return Helpers::success([
            'items' => $resourceCollection,
            'pagination' => [
                'current_page' => $ussdDeposits->currentPage(),
                'last_page'    => $ussdDeposits->lastPage(),
                'per_page'     => $ussdDeposits->perPage(),
                'total'        => $ussdDeposits->total(),
                'has_more'     => $ussdDeposits->hasMorePages(),
            ]
        ]);
    }
    /**
     * Affiche les détails d'un dépôt USSD spécifique.
     * * @param  int|string $id
     * @return JsonResponse
     */
    public function show($id)
    {
        // 1. Récupération avec l'utilisateur associé
        // On utilise find() pour un contrôle total sur la réponse en cas d'absence
        $ussdDeposit = DepositUssd::with('user')->find($id);

        // 2. Gestion du cas "Non trouvé"
        if (!$ussdDeposit) {
            return Helpers::error("Le dépôt USSD avec l'ID {$id} est introuvable.", 404);
        }

        // 3. Retourne la ressource formatée
        // La ressource gérera la transformation de l'image de preuve en URL complète
        return Helpers::success(new DepositUssdResource($ussdDeposit));
    }


    /**
     * Traite la validation (Approbation ou Rejet) d'un dépôt USSD.
     * @param Request $request
     * @param DepositUssd $deposit
     * @return JsonResponse|mixed
     */
    public function validateStatus(Request $request, $id)
    {
        $deposit=DepositUssd::find($id);

        // 1. Sécurité : Vérifier si le dépôt n'a pas déjà été traité
        if ($deposit->status !== 'processing') {
            return Helpers::error("Cette transaction a déjà été traitée (Statut actuel : {$deposit->status}).", 422);
        }

        // 2. Validation de la requête
        $request->validate([
            'status'     => 'required|in:success,failed',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $status = $request->status;

        try {
            return DB::transaction(function () use ($request, $deposit, $status) {

                // 3. Mise à jour des informations de base du dépôt
                $updateData = [
                    'status'     => $status,
                    'admin_note' => $request->admin_note,
                ];

                if ($status === 'completed') {
                    $updateData['completed_at'] = now();
                }

                $deposit->update($updateData);

                // 4. Logique spécifique au succès : Créditer l'utilisateur
                if ($status === 'completed') {
                    // On utilise increment() pour la sécurité des calculs financiers
                    $deposit->user->increment('balance', $deposit->amount);

                    $message = "Le dépôt a été approuvé et le compte crédité.";
                } else {
                    $message = "Le dépôt a été rejeté conformément à votre décision.";
                }

                // 5. Retourner la ressource mise à jour
                return Helpers::success(
                    new DepositUssdResource($deposit->load('user')),
                    $message
                );
            });

        } catch (\Exception $e) {
            Log::error("Erreur validation USSD (ID: {$deposit->id}): " . $e->getMessage());
            return Helpers::error("Une erreur interne est survenue lors du traitement.", 500);
        }
    }
    /**
     * PHASE 1 : Initialisation (Appelé juste avant de lancer l'USSD)
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'       => 'required|numeric',
            'country_code' => 'required|string',
            'ussd_code'    => 'required|string',
        ]);

        $deposit = DepositUssd::create([
            'user_id'      => $request->header('X-User-Id'),
            'amount'       => $validated['amount'],
            'country_code' => $validated['country_code'],
            'ussd_code'    => $validated['ussd_code'],
            'status'       => 'pending', // L'utilisateur va composer le code
        ]);
        Notification::route('telegram', config('services.telegram-bot-api.group_id'))
            ->notify(new DepositUssdProcessed($deposit));
        return response()->json(['status' => 'success', 'data' => $deposit]);
    }

    /**
     * PHASE 2 : Upload de la preuve (Appelé quand l'utilisateur revient)
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function uploadProof(Request $request, $id)
    {
        $request->validate([
            'proof' => 'required|image|max:2048',
            'reference' => 'nullable|string'
        ]);

        $deposit = DepositUssd::findOrFail($id);

        if ($request->hasFile('proof')) {
            $path = $request->file('proof')->store('deposits/proofs', 'public');

            $deposit->update([
                'proof'     => Storage::url($path),
                'reference' => $request->reference,
                'status'    => 'processing' // Passe en attente de validation admin
            ]);
            Notification::route('telegram', config('services.telegram-bot-api.group_id'))
                ->notify(new DepositUssdProcessed($deposit));
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Preuve reçue, traitement en cours',
            'data'    => $deposit
        ]);
    }
}
