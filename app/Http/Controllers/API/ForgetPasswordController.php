<?php


namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ForgetPasswordController extends Controller
{
    protected $whatsapp;

    // On injecte le service WhatsApp via le constructeur
    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Étape 1 : Envoi de l'OTP
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|exists:users,phone' // On vérifie que le numéro existe en BD
        ]);

        $phone = $request->phone;
        $code = (string) rand(1000, 9999);

        // Sauvegarde ou mise à jour du code OTP
        OtpCode::updateOrCreate(
            ['identifier' => $phone],
            [
                'code' => $code, // Tu peux hasher ici pour plus de sécurité : Hash::make($code)
                'used' => false,
                'expires_at' => now()->addMinutes(10)
            ]
        );

        // Envoi via l'API officielle WhatsApp
        $success = $this->whatsapp->sendOtp($phone, $code);

        if ($success) {
            return response()->json([
                'status' => 'success',
                'message' => 'Code envoyé sur WhatsApp'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => "Erreur lors de l'envoi du message WhatsApp"
        ], 500);
    }

    /**
     * Étape 2 (Optionnelle) : Vérifier l'OTP avant le reset
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'otp'   => 'required|digits:4'
        ]);

        $otp = OtpCode::where('identifier', $request->phone)
            ->where('code', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'Code invalide ou expiré'], 422);
        }

        return response()->json(['message' => 'Code valide']);
    }

    /**
     * Étape 3 : Réinitialisation finale
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone'    => 'required|exists:users,phone',
            'otp'      => 'required|digits:4',
            'password' => 'required|min:8|confirmed'
        ]);

        // 1. Vérification finale de l'OTP (Sécurité)
        $otp = OtpCode::where('identifier', $request->phone)
            ->where('code', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'Session de réinitialisation expirée'], 422);
        }

        // 2. Mise à jour de l'utilisateur
        $user = User::where('phone', $request->phone)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // 3. Marquer l'OTP comme utilisé ou le supprimer
        $otp->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }
}
