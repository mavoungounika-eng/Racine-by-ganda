@extends('layouts.creator')
@section('title', 'Télécharger POS Electron — RACINE BY GANDA')
@section('page-title', 'POS Electron')

@section('content')
<div style="max-width:680px;margin:0 auto;padding:2rem 1rem;">

    <x-page-header
        title="Application de caisse POS"
        subtitle="Gérez vos ventes physiques en ligne et hors ligne"
        :breadcrumbs="['Tableau de bord' => route('creator.dashboard'), 'POS Electron' => null]"
    />

    <div style="background:linear-gradient(135deg,#160D0C 0%,#2a1a18 100%);border-radius:1.25rem;padding:2rem;margin-bottom:1.5rem;text-align:center;">
        <div style="width:72px;height:72px;border-radius:1rem;background:linear-gradient(135deg,#ED5F1E,#FFB800);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <i class="fas fa-cash-register" style="font-size:2rem;color:white;"></i>
        </div>
        <h2 style="color:#FFB800;font-weight:700;font-size:1.4rem;margin:0 0 .5rem;">Racine POS Electron</h2>
        <p style="color:rgba(255,255,255,.7);font-size:.9rem;margin:0 0 1.5rem;">Version {{ $version }} — Application desktop pour Windows &amp; macOS</p>

        @if($releaseUrl !== '#')
            <a href="{{ $releaseUrl }}" download
               style="display:inline-flex;align-items:center;gap:.75rem;padding:.9rem 2rem;background:linear-gradient(135deg,#ED5F1E,#FFB800);color:white;border-radius:.75rem;text-decoration:none;font-weight:700;font-size:1rem;">
                <i class="fas fa-download"></i> Télécharger POS v{{ $version }}
            </a>
        @else
            <div style="display:inline-flex;align-items:center;gap:.75rem;padding:.9rem 2rem;background:rgba(255,255,255,.1);color:rgba(255,255,255,.5);border-radius:.75rem;font-size:.9rem;border:1px solid rgba(255,255,255,.15);">
                <i class="fas fa-clock"></i> Téléchargement disponible prochainement
            </div>
        @endif
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <div style="background:white;border-radius:1rem;padding:1.25rem;border:1px solid #eee;">
            <i class="fas fa-wifi-slash" style="color:#ED5F1E;font-size:1.25rem;margin-bottom:.5rem;display:block;"></i>
            <strong style="font-size:.9rem;color:#160D0C;">Mode hors ligne</strong>
            <p style="font-size:.8rem;color:#666;margin:.25rem 0 0;">Ventes sans connexion internet, synchronisation auto au retour</p>
        </div>
        <div style="background:white;border-radius:1rem;padding:1.25rem;border:1px solid #eee;">
            <i class="fas fa-print" style="color:#ED5F1E;font-size:1.25rem;margin-bottom:.5rem;display:block;"></i>
            <strong style="font-size:.9rem;color:#160D0C;">Impression tickets</strong>
            <p style="font-size:.8rem;color:#666;margin:.25rem 0 0;">Connexion imprimante thermique, tickets personnalisés</p>
        </div>
        <div style="background:white;border-radius:1rem;padding:1.25rem;border:1px solid #eee;">
            <i class="fas fa-chart-bar" style="color:#ED5F1E;font-size:1.25rem;margin-bottom:.5rem;display:block;"></i>
            <strong style="font-size:.9rem;color:#160D0C;">Stats physiques</strong>
            <p style="font-size:.8rem;color:#666;margin:.25rem 0 0;">Ventes physiques visibles dans votre dashboard</p>
        </div>
    </div>

    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:.75rem;padding:1rem 1.25rem;font-size:.85rem;color:#92400e;">
        <i class="fas fa-info-circle" style="margin-right:.5rem;"></i>
        Votre plan <strong>Signature</strong> inclut le POS Electron. Installez l'application sur votre PC ou Mac et connectez-vous avec vos identifiants Racine.
    </div>

</div>
@endsection
