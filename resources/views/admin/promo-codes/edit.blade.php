@extends('layouts.admin-master')

@section('title', 'Modifier un code promo')
@section('page-title', 'Modifier un code promo')
@section('page-subtitle', 'Mise à jour du code ' . $promoCode->code)

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-10">

        {{-- Stats rapides --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card card-racine">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">
                            <i class="fas fa-chart-bar me-1"></i>Utilisations
                        </div>
                        <div class="h4 fw-bold mb-0 mt-2 text-racine-black">
                            {{ $promoCode->used_count }}{{ $promoCode->max_uses ? ' / '.$promoCode->max_uses : '' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-racine">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">
                            <i class="fas fa-users me-1"></i>Commandes liées
                        </div>
                        <div class="h4 fw-bold mb-0 mt-2 text-racine-black">
                            {{ $promoCode->usages_count ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-racine">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">
                            <i class="fas fa-calendar-plus me-1"></i>Créé le
                        </div>
                        <div class="h6 fw-bold mb-0 mt-2 text-racine-black">
                            {{ $promoCode->created_at?->format('d/m/Y') ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-racine">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">
                            <i class="fas fa-toggle-on me-1"></i>Statut actuel
                        </div>
                        <div class="h6 fw-bold mb-0 mt-2">
                            @if($promoCode->is_active && $promoCode->isValid())
                                <span class="text-success"><i class="fas fa-check-circle me-1"></i>Actif</span>
                            @elseif(!$promoCode->is_active)
                                <span class="text-secondary"><i class="fas fa-pause-circle me-1"></i>Désactivé</span>
                            @else
                                <span class="text-danger"><i class="fas fa-times-circle me-1"></i>Invalide</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-racine">
            <div class="card-header bg-transparent border-bottom-2 border-racine-beige py-4">
                <h3 class="mb-0 fw-bold">
                    <i class="fas fa-edit text-racine-orange me-2"></i>
                    Modifier <code class="text-racine-orange">{{ $promoCode->code }}</code>
                </h3>
                <p class="text-muted mb-0 mt-2">
                    Les modifications n affectent pas les commandes déjà passées avec ce code.
                </p>
            </div>

            <div class="card-body">
                @include('admin.promo-codes._form', [
                    'action'      => route('admin.promo-codes.update', $promoCode),
                    'method'      => 'PUT',
                    'promoCode'   => $promoCode,
                    'submitLabel' => 'Mettre à jour',
                ])
            </div>
        </div>
    </div>
</div>

@endsection
