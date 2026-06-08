<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailChangeCompleted;
use App\Mail\EmailChangeNewVerification;
use App\Mail\EmailChangeOldVerification;
use App\Models\EmailChange;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmailChangeController extends Controller
{
    /**
     * Request an email change.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function requestChange(Request $request)
    {
        $user = Auth::user();

        // Validation
        $validator = Validator::make($request->all(), [
            'new_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $newEmail = $request->input('new_email');

        // Vérifier si l'email est déjà le même
        if ($user->email === $newEmail) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The new email is the same as the current one.',
                ], 422);
            }

            return back()->withErrors(['new_email' => 'L\'email est déjà identique à l\'email actuel.'])->withInput();
        }

        // Supprimer toutes les anciennes demandes expirées ou en cours pour cet utilisateur
        EmailChange::where('user_id', $user->id)->delete();

        // Générer les tokens
        $tokens = EmailChange::generateTokens();

        // Créer la demande de changement
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'old_email' => $user->email,
            'new_email' => $newEmail,
            'old_email_token' => $tokens['old_email_token'],
            'new_email_token' => $tokens['new_email_token'],
            'expires_at' => now()->addHours(24),
        ]);

        // Envoyer les emails de vérification
        Mail::to($user->email)->queue(new EmailChangeOldVerification($emailChange, $user));
        Mail::to($newEmail)->queue(new EmailChangeNewVerification($emailChange, $user));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Email change verification sent. Please check both email addresses.',
            ], 200);
        }

        return back()->with('status', 'Des emails de vérification ont été envoyés aux deux adresses. Veuillez vérifier vos boîtes de réception.');
    }

    /**
     * Verify the old email.
     *
     * @param string $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyOldEmail(string $token)
    {
        $emailChange = EmailChange::where('old_email_token', $token)
            ->notExpired()
            ->first();

        if (!$emailChange) {
            return redirect()->route('login')->with('error', 'Le lien de vérification est invalide ou a expiré.');
        }

        // Marquer l'ancien email comme vérifié
        $emailChange->update([
            'old_email_verified_at' => now(),
        ]);

        // Vérifier si les deux emails sont vérifiés
        if ($emailChange->isFullyVerified()) {
            $this->completeEmailChange($emailChange);
            return redirect()->route('login')->with('status', 'Votre adresse email a été mise à jour avec succès.');
        }

        return redirect()->route('login')->with('status', 'Email ancien vérifié. Veuillez maintenant vérifier votre nouveau email.');
    }

    /**
     * Verify the new email.
     *
     * @param string $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyNewEmail(string $token)
    {
        $emailChange = EmailChange::where('new_email_token', $token)
            ->notExpired()
            ->first();

        if (!$emailChange) {
            return redirect()->route('login')->with('error', 'Le lien de vérification est invalide ou a expiré.');
        }

        // Marquer le nouvel email comme vérifié
        $emailChange->update([
            'new_email_verified_at' => now(),
        ]);

        // Vérifier si les deux emails sont vérifiés
        if ($emailChange->isFullyVerified()) {
            $this->completeEmailChange($emailChange);
            return redirect()->route('login')->with('status', 'Votre adresse email a été mise à jour avec succès.');
        }

        return redirect()->route('login')->with('status', 'Nouveau email vérifié. Veuillez maintenant vérifier votre ancien email.');
    }

    /**
     * Complete the email change process.
     *
     * @param EmailChange $emailChange
     * @return void
     */
    protected function completeEmailChange(EmailChange $emailChange): void
    {
        DB::transaction(function () use ($emailChange) {
            $user = $emailChange->user;

            // Mettre à jour l'email de l'utilisateur
            $user->update([
                'email' => $emailChange->new_email,
                'email_verified_at' => now(),
            ]);

            // Envoyer les notifications de confirmation aux deux adresses
            Mail::to($emailChange->old_email)->queue(new EmailChangeCompleted($user, $emailChange->old_email, $emailChange->new_email));
            Mail::to($emailChange->new_email)->queue(new EmailChangeCompleted($user, $emailChange->old_email, $emailChange->new_email));

            // Supprimer la demande de changement
            $emailChange->delete();
        });
    }
}
