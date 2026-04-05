<?php

namespace App\Services;

use App\Models\CreatorProfile;
use App\Models\CreatorDocument;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion du KYC Contractuel (SaaS Pur).
 * 
 * ⚠️ Ce KYC n'autorise pas l'encaissement (délégué aux gateways directes).
 * Il autorise uniquement l'utilisation commerciale de la plateforme RACINE.
 */
class CreatorKycContractualService
{
    /**
     * Vérifie si un créateur a soumis tous les documents contractuels obligatoires.
     */
    public function checkContractualStatus(CreatorProfile $profile): array
    {
        $requiredTypes = ['identity_card', 'registration_certificate', 'tax_id'];
        $submittedTypes = $profile->documents()->pluck('document_type')->toArray();
        
        $missing = array_diff($requiredTypes, $submittedTypes);
        $allVerified = $profile->documents()->whereIn('document_type', $requiredTypes)->where('is_verified', true)->count() === count($requiredTypes);

        return [
            'is_complete' => empty($missing),
            'is_verified' => $allVerified,
            'missing_documents' => $missing,
            'status' => $allVerified ? 'verified' : (empty($missing) ? 'pending_approval' : 'incomplete'),
        ];
    }

    /**
     * Approuve manuellement un document contractuel.
     */
    public function verifyDocument(CreatorDocument $document, int $adminId, ?string $notes = null): bool
    {
        try {
            $document->update([
                'is_verified' => true,
                'verified_at' => now(),
                'verified_by' => $adminId,
                'verification_notes' => $notes,
            ]);

            Log::info("KYC: Document #{$document->id} ({$document->document_type}) approuvé par Admin #{$adminId}");

            // Auto-activation du profil si tous les docs sont OK
            $this->autoActivateProfile($document->creatorProfile);

            return true;
        } catch (\Exception $e) {
            Log::error("KYC: Échec de l'approbation du document #{$document->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Active le profil si le KYC contractuel est complet.
     */
    protected function autoActivateProfile(CreatorProfile $profile): void
    {
        $status = $this->checkContractualStatus($profile);
        
        if ($status['is_verified'] && $profile->status !== 'active') {
            $profile->update(['status' => 'active']);
            Log::info("KYC: Profil Créateur #{$profile->id} activé suite à validation KYC contractuelle.");
        }
    }
}
