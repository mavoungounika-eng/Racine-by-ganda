@extends('layouts.creator')
@section('title', 'Configurez votre boutique')

@push('styles')
<style nonce="{{ csp_nonce() }}">
.ob-wrap { min-height: 70vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
.ob-card { background: #fff; border-radius: 1.5rem; box-shadow: 0 8px 40px rgba(0,0,0,.1); max-width: 600px; width: 100%; overflow: hidden; }
.ob-header { background: linear-gradient(135deg, #160D0C 0%, #2a1a18 100%); padding: 2rem 2rem 1.5rem; text-align: center; }
.ob-header h1 { color: #FFB800; font-size: 1.5rem; font-weight: 700; margin: 0 0 .25rem; }
.ob-header p { color: rgba(255,255,255,.75); font-size: .9rem; margin: 0; }
.ob-steps { display: flex; border-bottom: 2px solid #f0ebe5; }
.ob-step { flex: 1; padding: .65rem .25rem; text-align: center; font-size: .72rem; font-weight: 600; color: #aaa; border-bottom: 3px solid transparent; }
.ob-step.active { color: #ED5F1E; border-bottom-color: #ED5F1E; }
.ob-step.done { color: #15803d; border-bottom-color: #15803d; }
.ob-body { padding: 2rem; }
.ob-pane { display: none; }
.ob-pane.active { display: block; }
.ob-pane h2 { font-size: 1.1rem; font-weight: 700; color: #160D0C; margin: 0 0 .5rem; }
.ob-pane p { color: #666; font-size: .88rem; margin: 0 0 1.25rem; }
.ob-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
.btn-ob-next { padding: .65rem 1.5rem; background: #ED5F1E; color: #fff; border: none; border-radius: .6rem; font-weight: 600; font-size: .9rem; cursor: pointer; }
.btn-ob-next:hover { background: #d44f10; }
.btn-ob-skip { color: #aaa; font-size: .82rem; background: none; border: none; cursor: pointer; text-decoration: underline; }
.ob-item { display: flex; align-items: flex-start; gap: 1rem; padding: .9rem; background: #fafafa; border: 1px solid #eee; border-radius: .75rem; margin-bottom: .75rem; }
.ob-item-icon { width: 40px; height: 40px; border-radius: .5rem; background: linear-gradient(135deg, #ED5F1E, #FFB800); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ob-item-icon svg { fill: white; }
.ob-item-text strong { display: block; font-size: .88rem; color: #160D0C; }
.ob-item-text span { font-size: .8rem; color: #666; }
.ob-badge { display: inline-block; padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700; background: rgba(237,95,30,.12); color: #ED5F1E; margin-bottom: .75rem; }
</style>
@endpush

@section('content')
<div class="ob-wrap">
<div class="ob-card">
    <div class="ob-header">
        <h1>Configurez votre boutique</h1>
        <p>4 étapes pour commencer à vendre sur Racine</p>
    </div>

    <div class="ob-steps">
        <div class="ob-step active" id="step-tab-1">1. Boutique</div>
        <div class="ob-step" id="step-tab-2">2. Produit</div>
        <div class="ob-step" id="step-tab-3">3. Paiement</div>
        <div class="ob-step" id="step-tab-4">4. Activer</div>
    </div>

    <div class="ob-body">
        {{-- Étape 1 --}}
        <div class="ob-pane active" id="pane-1">
            <h2>Votre profil boutique</h2>
            <p>Présentez votre univers créatif aux acheteurs.</p>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg></div>
                <div class="ob-item-text"><strong>Nom de marque et description</strong><span>Votre identité visible par tous les acheteurs</span></div>
            </div>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg></div>
                <div class="ob-item-text"><strong>Photo de couverture et logo</strong><span>Images haute qualité pour inspirer confiance</span></div>
            </div>
            <div class="ob-actions">
                <form action="{{ route('onboarding.skip') }}" method="POST">@csrf<button class="btn-ob-skip">Faire plus tard</button></form>
                <button class="btn-ob-next" onclick="obNext(2)">Continuer →</button>
            </div>
        </div>

        {{-- Étape 2 --}}
        <div class="ob-pane" id="pane-2">
            <h2>Votre premier produit</h2>
            <p>Commencez avec un produit phare. Vous pourrez en ajouter autant que votre plan le permet.</p>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg></div>
                <div class="ob-item-text"><strong>Photos, description, prix</strong><span>Des fiches produit claires = plus de ventes</span></div>
            </div>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5L5 5.01C3.9 5.01 3 5.9 3 7v10c0 1.1.9 1.99 2 1.99L16 19c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16z"/></svg></div>
                <div class="ob-item-text"><strong>Catégorie et stock</strong><span>Classez bien pour être trouvé facilement</span></div>
            </div>
            <div class="ob-actions">
                <button class="btn-ob-skip" onclick="obNext(1)">← Retour</button>
                <button class="btn-ob-next" onclick="obNext(3)">Continuer →</button>
            </div>
        </div>

        {{-- Étape 3 --}}
        <div class="ob-pane" id="pane-3">
            <h2>Configurez vos paiements</h2>
            <p>Connectez votre compte pour recevoir vos revenus directement.</p>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg></div>
                <div class="ob-item-text"><strong>Stripe Connect</strong><span>Pour les paiements par carte bancaire internationale</span></div>
            </div>
            <div class="ob-item">
                <div class="ob-item-icon"><svg width="18" height="18" viewBox="0 0 24 24"><path d="M15.5 1h-8C6.12 1 5 2.12 5 3.5v17C5 21.88 6.12 23 7.5 23h8c1.38 0 2.5-1.12 2.5-2.5v-17C18 2.12 16.88 1 15.5 1zm-4 21c-.83 0-1.5-.67-1.5-1.5S10.67 19 11.5 19s1.5.67 1.5 1.5S12.33 22 11.5 22zm4.5-4H7V4h9v14z"/></svg></div>
                <div class="ob-item-text"><strong>Mobile Money</strong><span>Orange Money, MTN MoMo, Wave — retraits XAF</span></div>
            </div>
            <div class="ob-actions">
                <button class="btn-ob-skip" onclick="obNext(2)">← Retour</button>
                <button class="btn-ob-next" onclick="obNext(4)">Continuer →</button>
            </div>
        </div>

        {{-- Étape 4 --}}
        <div class="ob-pane" id="pane-4">
            <h2>Activez votre boutique</h2>
            <p>Tout est prêt. En activant votre boutique, vos produits deviennent visibles sur la marketplace.</p>
            <div style="background: linear-gradient(135deg, rgba(237,95,30,.06), rgba(255,184,0,.06)); border: 1.5px solid rgba(237,95,30,.2); border-radius: .75rem; padding: 1.25rem; margin-bottom: 1rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <svg width="24" height="24" fill="#ED5F1E" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                    <div>
                        <strong style="font-size:.9rem;color:#160D0C;">Votre boutique sera en ligne</strong>
                        <div style="font-size:.8rem;color:#666;">Les acheteurs pourront découvrir vos créations</div>
                    </div>
                </div>
            </div>
            <div class="ob-actions">
                <button class="btn-ob-skip" onclick="obNext(3)">← Retour</button>
                <form action="{{ route('onboarding.creator.complete') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-ob-next" style="background:#15803d;">Activer ma boutique →</button>
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
    document.querySelectorAll('.ob-step').forEach(s => { s.classList.remove('active'); });
    document.getElementById('pane-' + step)?.classList.add('active');
    document.getElementById('step-tab-' + step)?.classList.add('active');
    for (let i = 1; i < step; i++) {
        document.getElementById('step-tab-' + i)?.classList.add('done');
    }
}
</script>
@endpush
