<?php

namespace App\Http\Controllers\Pos;

use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosCouponController extends PosApiController
{
    /**
     * Valider un code promo pour POS
     * 
     * GET /api/pos/coupons/validate?code=XXXX&amount=Y
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
        ]);

        $code = strtoupper(trim($validated['code']));
        $amount = (float)$validated['amount'];

        // Trouver le code promo
        $promo = PromoCode::where('code', $code)->first();

        if (!$promo) {
            return $this->error('PROMO_NOT_FOUND', 'Code promo invalide ou expiré', null, 404);
        }

        // Vérifier si le code est actif
        if (!$promo->is_active) {
            return $this->error('PROMO_INACTIVE', 'Ce code promo n\'est pas actif');
        }

        // Vérifier les dates
        $now = now();
        if ($promo->starts_at && $now < $promo->starts_at) {
            return $this->error('PROMO_NOT_STARTED', 'Ce code promo n\'est pas encore valide');
        }

        if ($promo->expires_at && $now > $promo->expires_at) {
            return $this->error('PROMO_EXPIRED', 'Ce code promo a expiré');
        }

        // Vérifier les usages limités
        if ($promo->max_uses && $promo->used_count >= $promo->max_uses) {
            return $this->error('PROMO_LIMIT_REACHED', 'Ce code promo a atteint sa limite d\'utilisation');
        }

        // Vérifier le montant minimum
        if ($promo->min_amount && $amount < $promo->min_amount) {
            return $this->error(
                'PROMO_MIN_AMOUNT',
                sprintf('Montant minimum requis : %s FCFA', number_format($promo->min_amount, 0, ',', ' '))
            );
        }

        // Calculer la remise
        $discountValue = 0;
        if ($promo->type === 'percent') {
            $discountValue = ($amount * $promo->value) / 100;
        } else if ($promo->type === 'fixed') {
            $discountValue = min($promo->value, $amount); // Ne pas dépasser le montant
        }

        $finalAmount = max(0, $amount - $discountValue);

        return $this->success([
            'code' => $promo->code,
            'name' => $promo->name,
            'description' => $promo->description,
            'type' => $promo->type,
            'value' => $promo->value,
            'discount_type' => $promo->type,
            'discount_value' => round($discountValue, 2),
            'original_amount' => round($amount, 2),
            'final_amount' => round($finalAmount, 2),
        ], 'Code promo validé avec succès');
    }
}
