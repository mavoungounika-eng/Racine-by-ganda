@extends('layouts.frontend')
@section('title', 'Bienvenue sur Racine')

@push('styles')
<style nonce="{{ csp_nonce() }}">
.ob-wrap { min-height: 100vh; background: #f8f6f3; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
.ob-card { background: #fff; border-radius: 1.5rem; box-shadow: 0 8px 40px rgba(0,0,0,.1); max-width: 560px; width: 100%; overflow: hidden; }
.ob-header { background: linear-gradient(135deg, #160D0C 0%, #2a1a18 100%); padding: 2rem 2rem 1.5rem; text-align: center; }
.ob-header h1 { color: #FFB800; font-size: 1.5rem; font-weight: 700; margin: 0 0 .25rem; }
.ob-header p { color: rgba(255,255,255,.75); font-size: .9rem; margin: 0; }
.ob-steps { display: flex; gap: 0; border-bottom: 2px solid #f0ebe5; }
.ob-step { flex: 1; padding: .75rem; text-align: center; font-size: .78rem; font-weight: 600; color: #aaa; border-bottom: 3px solid transparent; transition: all .2s; cursor: default; }
.ob-step.active { color: #ED5F1E; border-bottom-color: #ED5F1E; }
.ob-step.done { color: #15803d; border-bottom-color: #15803d; }
.ob-body { padding: 2rem; }
.ob-pane { display: none; }
.ob-pane.active { display: block; }
.ob-pane h2 { font-size: 1.1rem; font-weight: 700; color: #160D0C; margin: 0 0 .5rem; }
.ob-pane p { color: #666; font-size: .88rem; margin: 0 0 1.5rem; }
.ob-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
.btn-ob-next { padding: .65rem 1.5rem; background: #ED5F1E; color: #fff; border: none; border-radius: .6rem; font-weight: 600; font-size: .9rem; cursor: pointer; }
.btn-ob-next:hover { background: #d44f10; }
.btn-ob-skip { color: #aaa; font-size: .82rem; background: none; border: none; cursor: pointer; text-decoration: underline; }
.ob-item { display: flex; align-items: flex-start; gap: 1rem; padding: .9rem; background: #fafafa; border: 1px solid #eee; border-radius: .75rem; margin-bottom: .75rem; }
.ob-item-icon { width: 40px; height: 40px; border-radius: .5rem; background: linear-gradient(135deg, #ED5F1E, #FFB800); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ob-item-icon svg { fill: white; }
.ob-item-text strong { display: block; font-size: .88rem; color: #160D0C; }
.ob-item-text span { font-size: .8rem; color: #666; }
</style>
@endpush

@section('content')
<div class="ob-wrap">
<div class="ob-card">
    <div class="ob-header">
        <h1>Bienvenue sur Racine by Ganda !</h1>
        <p>Quelques étapes pour bien démarrer</p>
    </div>

    <div class="ob-steps">
        <div class="ob-step active" id="step-tab-1">1. Profil</div>
        <div class="ob-step" id="step-tab-2">2. Explorer</div>
        <div class="ob-step" id="step-tab-3">3. Commander</div>
    </div>

    <div class="ob-body">
        {{-- Étape 1 --}}
        <div class="ob-pane active" id="pane-1">
            <h2>Complétez votre profil</h2>
            <p>Un profil complet permet aux créateurs de mieux vous servir.</p>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg></div>
                <div class="ob-item-text"><strong>Votre nom complet</strong><span>Pour que les créateurs vous reconnaissent</span></div>
            </div>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg></div>
                <div class="ob-item-text"><strong>Votre téléphone</strong><span>Pour le suivi de vos livraisons</span></div>
            </div>
            <div class="ob-actions">
                <form action="{{ route('onboarding.skip') }}" method="POST" style="display:inline">@csrf<button type="submit" class="btn-ob-skip">Passer</button></form>
                <button class="btn-ob-next" onclick="obNext(2)">Continuer →</button>
            </div>
        </div>

        {{-- Étape 2 --}}
        <div class="ob-pane" id="pane-2">
            <h2>Explorez les créateurs</h2>
            <p>Racine réunit des créateurs africains d'exception. Voici comment les trouver.</p>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg></div>
                <div class="ob-item-text"><strong>Recherche par style, matière, région</strong><span>Filtres puissants pour trouver ce qui vous ressemble</span></div>
            </div>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg></div>
                <div class="ob-item-text"><strong>Sauvegardez vos favoris</strong><span>Créez votre liste de créateurs préférés</span></div>
            </div>
            <div class="ob-actions">
                <button class="btn-ob-skip" onclick="obNext(1)">← Retour</button>
                <button class="btn-ob-next" onclick="obNext(3)">Continuer →</button>
            </div>
        </div>

        {{-- Étape 3 --}}
        <div class="ob-pane" id="pane-3">
            <h2>Prêt à commander</h2>
            <p>Votre première commande est à portée de clic.</p>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 5.9 17 7 17h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63H19c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0 0 23.45 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg></div>
                <div class="ob-item-text"><strong>Paiement sécurisé</strong><span>Carte bancaire, Mobile Money (Orange, MTN, Wave)</span></div>
            </div>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg></div>
                <div class="ob-item-text"><strong>Suivi en temps réel</strong><span>Notifications à chaque étape de votre commande</span></div>
            </div>
            <div class="ob-actions">
                <button class="btn-ob-skip" onclick="obNext(2)">← Retour</button>
                <form action="{{ route('onboarding.client.complete') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-ob-next" style="background:#15803d;">C'est parti ! →</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
function obNext(step) {
    document.querySelectorAll('.ob-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.ob-step').forEach(s => s.classList.remove('active'));
    document.getElementById('pane-' + step)?.classList.add('active');
    document.getElementById('step-tab-' + step)?.classList.add('active');
    for (let i = 1; i < step; i++) {
        document.getElementById('step-tab-' + i)?.classList.add('done');
    }
}
</script>
@endpush
