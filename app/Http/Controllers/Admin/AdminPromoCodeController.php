<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StorePromoCodeRequest;
use App\Http\Requests\UpdatePromoCodeRequest;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
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

    public function dataPromoCodes(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PromoCode::class);
        $query = PromoCode::query();
        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('is_active') && $request->get('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }
        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function bulkDisable(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'])['ids'];
        $count = PromoCode::whereIn('id', $ids)->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => $count.' code(s) désactivé(s)']);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'])['ids'];
        // Only delete unused codes
        $count = PromoCode::whereIn('id', $ids)->where('used_count', 0)->delete();
        $skipped = count($ids) - $count;
        $msg = $count.' code(s) supprimé(s)';
        if ($skipped > 0) {
            $msg .= ' ('.$skipped.' ignoré(s) car déjà utilisé(s))';
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function exportCsv(Request $request): \Illuminate\Http\Response
    {
        $this->authorize('viewAny', PromoCode::class);
        $query = PromoCode::query();
        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) { $q->where('code','like',"%{$s}%")->orWhere('name','like',"%{$s}%"); });
        }
        if ($request->filled('type'))                              $query->where('type',      $request->get('type'));
        if ($request->filled('is_active') && $request->get('is_active') !== '') $query->where('is_active', $request->boolean('is_active'));
        $rows = $query->orderBy('created_at','desc')->get();
        $csv  = "\xEF\xBB\xBF";
        $csv .= "ID,Code,Nom,Type,Valeur,Utilisations,Limite,Expire,Statut\n";
        foreach ($rows as $r) {
            $csv .= implode(',', [
                $r->id,
                '"'.str_replace('"','""',$r->code).'"',
                '"'.str_replace('"','""',$r->name ?? '').'"',
                $r->type ?? '',
                $r->discount_value ?? '',
                $r->used_count ?? 0,
                $r->max_uses ?? '',
                $r->expires_at ? $r->expires_at->format('Y-m-d') : '',
                $r->is_active ? 'active' : 'inactive',
            ])."\n";
        }
        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="promo-codes_'.now()->format('Y-m-d').'.csv"',
        ]);
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
