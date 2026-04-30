<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StorePromoCodeRequest;
use App\Http\Requests\UpdatePromoCodeRequest;
use App\Models\PromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPromoCodeController extends AdminController
{
    /**
     * Liste des codes promo avec filtres + recherche + tri.
     */
    public function index(Request $request): View
    {
        $query = PromoCode::query()->withCount('usages');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('status')) {
            $status = $request->get('status');
            $now = now();
            match ($status) {
                'active'   => $query->where('is_active', true)
                                    ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                                    ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now)),
                'expired'  => $query->whereNotNull('expires_at')->where('expires_at', '<', $now),
                'upcoming' => $query->whereNotNull('starts_at')->where('starts_at', '>', $now),
                default    => null,
            };
        }

        $sortBy  = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSort = ['code', 'name', 'type', 'value', 'used_count', 'expires_at', 'created_at'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');

        $promoCodes = $query->paginate(15)->withQueryString();

        return view('admin.promo-codes.index', compact('promoCodes'));
    }

    /**
     * Formulaire de création.
     */
    public function create(): View
    {
        return view('admin.promo-codes.create');
    }

    /**
     * Création du code promo.
     */
    public function store(StorePromoCodeRequest $request): RedirectResponse
    {
        PromoCode::create($request->validated());

        return redirect()
            ->route('admin.promo-codes.index')
            ->with('success', 'Code promo créé avec succès.');
    }

    /**
     * Formulaire d édition.
     */
    public function edit(PromoCode $promoCode): View
    {
        $promoCode->loadCount('usages');
        return view('admin.promo-codes.edit', compact('promoCode'));
    }

    /**
     * Mise à jour.
     */
    public function update(UpdatePromoCodeRequest $request, PromoCode $promoCode): RedirectResponse
    {
        $promoCode->update($request->validated());

        return redirect()
            ->route('admin.promo-codes.index')
            ->with('success', 'Code promo mis à jour avec succès.');
    }

    /**
     * Suppression (refusée si déjà utilisé).
     */
    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        if ($promoCode->usages()->count() > 0) {
            return redirect()
                ->route('admin.promo-codes.index')
                ->with('error', 'Impossible de supprimer ce code promo car il a déjà été utilisé. Désactivez-le plutôt.');
        }

        $promoCode->delete();

        return redirect()
            ->route('admin.promo-codes.index')
            ->with('success', 'Code promo supprimé avec succès.');
    }
}
