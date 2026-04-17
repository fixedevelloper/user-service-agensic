<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DepositUssd;
use App\Notifications\DepositProcessed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class DepositUssdController extends Controller
{
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
            ->notify(new DepositProcessed($deposit));
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
                ->notify(new DepositProcessed($deposit));
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Preuve reçue, traitement en cours',
            'data'    => $deposit
        ]);
    }
}
