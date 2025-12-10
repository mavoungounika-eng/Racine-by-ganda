@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Retours & Échanges - RACINE BY GANDA')

@push('styles')
<style>
    .returns-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 5rem 0;
        margin-top: -70px;
        padding-top: calc(5rem + 70px);
        text-align: center;
    }
    
    .returns-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .returns-hero p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.15rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .returns-content {
        padding: 4rem 0;
        background: #F8F6F3;
    }
    
    /* GUARANTEE */
    .guarantee-banner {
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        border-radius: 24px;
        padding: 3rem;
        text-align: center;
        color: white;
        margin-bottom: 4rem;
    }
    
    .guarantee-banner h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }
    
    .guarantee-banner p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 1.5rem;
    }
    
    .guarantee-features {
        display: flex;
        justify-content: center;
        gap: 3rem;
        flex-wrap: wrap;
    }
    
    .guarantee-feature {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 500;
    }
    
    .guarantee-feature i {
        font-size: 1.5rem;
    }
    
    /* STEPS */
    .steps-section {
        margin-bottom: 4rem;
    }
    
    .section-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .section-tag {
        display: inline-block;
        background: rgba(212, 165, 116, 0.1);
        color: #8B5A2B;
        padding: 0.5rem 1.5rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }
    
    .section-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        font-weight: 600;
        color: #2C1810;
    }
    
    .steps-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem;
        position: relative;
    }
    
    .steps-grid::before {
        content: '';
        position: absolute;
        top: 50px;
        left: 15%;
        right: 15%;
        height: 2px;
        background: linear-gradient(90deg, #D4A574, #8B5A2B);
    }
    
    .step-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    
    .step-number {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
        margin: 0 auto 1.25rem;
    }
    
    .step-card h3 {
        font-size: 1.15rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.75rem;
    }
    
    .step-card p {
        color: #8B7355;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    /* CONDITIONS */
    .conditions-section {
        background: white;
        border-radius: 24px;
        padding: 3rem;
        margin-bottom: 4rem;
    }
    
    .conditions-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
    }
    
    .condition-column h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .condition-column h3 i.fa-check-circle {
        color: #22C55E;
    }
    
    .condition-column h3 i.fa-times-circle {
        color: #EF4444;
    }
    
    .condition-column ul {
        list-style: none;
        padding: 0;
    }
    
    .condition-column li {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem 0;
        color: #5C4A3D;
        border-bottom: 1px solid #E5DDD3;
    }
    
    .condition-column li:last-child {
        border-bottom: none;
    }
    
    .condition-column li i {
        margin-top: 0.25rem;
    }
    
    .accept-list li i {
        color: #22C55E;
    }
    
    .refuse-list li i {
        color: #EF4444;
    }
    
    /* REFUND */
    .refund-section {
        background: white;
        border-radius: 24px;
        padding: 3rem;
        margin-bottom: 4rem;
    }
    
    .refund-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
    
    .refund-card {
        background: #F8F6F3;
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
    }
    
    .refund-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, rgba(212, 165, 116, 0.1) 0%, rgba(139, 90, 43, 0.1) 100%);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.25rem;
        font-size: 1.75rem;
        color: #D4A574;
    }
    
    .refund-card h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .refund-card p {
        color: #8B7355;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    /* FAQ */
    .faq-section {
        background: white;
        border-radius: 24px;
        padding: 3rem;
    }
    
    .faq-item {
        background: #F8F6F3;
        border-radius: 16px;
        margin-bottom: 1rem;
        overflow: hidden;
    }
    
    .faq-question {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .faq-question:hover {
        background: rgba(212, 165, 116, 0.1);
    }
    
    .faq-question h4 {
        font-size: 1.05rem;
        font-weight: 600;
        color: #2C1810;
        margin: 0;
    }
    
    .faq-question i {
        color: #D4A574;
        transition: transform 0.3s;
    }
    
    .faq-answer {
        padding: 0 1.5rem 1.5rem;
        color: #5C4A3D;
        line-height: 1.8;
        display: none;
    }
    
    .faq-item.active .faq-answer {
        display: block;
    }
    
    .faq-item.active .faq-question i {
        transform: rotate(180deg);
    }
    
    /* CTA */
    .cta-section {
        margin-top: 4rem;
        text-align: center;
    }
    
    .cta-box {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        border-radius: 24px;
        padding: 3rem;
        color: white;
    }
    
    .cta-box h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        margin-bottom: 0.75rem;
    }
    
    .cta-box p {
        opacity: 0.8;
        margin-bottom: 1.5rem;
    }
    
    .btn-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        color: white;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(212, 165, 116, 0.3);
        color: white;
    }
    
    @media (max-width: 1024px) {
        .steps-grid { grid-template-columns: repeat(2, 1fr); }
        .steps-grid::before { display: none; }
        .conditions-grid { grid-template-columns: 1fr; }
        .refund-grid { grid-template-columns: 1fr; }
    }
    
    @media (max-width: 768px) {
        .returns-hero h1 { font-size: 2.5rem; }
        .steps-grid { grid-template-columns: 1fr; }
        .guarantee-features { flex-direction: column; gap: 1rem; }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="returns-hero">
    <div class="container">
        @php
            $heroSection = $cmsPage?->section('hero');
            $heroData = $heroSection?->data ?? [];
        @endphp
        <h1>{{ $heroData['title'] ?? $cmsPage?->title ?? 'Retours & Échanges' }}</h1>
        <p>{{ $heroData['description'] ?? 'Satisfait ou remboursé. Nous voulons que vous aimiez chaque pièce de votre garde-robe RACINE.' }}</p>
    </div>
</section>

<!-- CONTENT -->
<section class="returns-content">
    <div class="container">
        <!-- GUARANTEE -->
        <div class="guarantee-banner">
            <h2>30 jours pour changer d'avis</h2>
            <p>Nous vous offrons 30 jours après réception pour retourner ou échanger votre article.</p>
            <div class="guarantee-features">
                <div class="guarantee-feature">
                    <i class="fas fa-undo"></i>
                    <span>Retours gratuits en France</span>
                </div>
                <div class="guarantee-feature">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Échanges illimités</span>
                </div>
                <div class="guarantee-feature">
                    <i class="fas fa-euro-sign"></i>
                    <span>Remboursement sous 7 jours</span>
                </div>
            </div>
        </div>
        
        <!-- STEPS -->
        <div class="steps-section">
            <div class="section-header">
                <span class="section-tag">Procédure</span>
                <h2 class="section-title">Comment retourner un article ?</h2>
            </div>
            
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Demande en ligne</h3>
                    <p>Connectez-vous à votre compte et sélectionnez les articles à retourner.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Étiquette prépayée</h3>
                    <p>Recevez votre étiquette de retour gratuite par email.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Envoi du colis</h3>
                    <p>Déposez votre colis en point relais ou bureau de poste.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3>Remboursement</h3>
                    <p>Recevez votre remboursement sous 5-7 jours ouvrés.</p>
                </div>
            </div>
        </div>
        
        <!-- CONDITIONS -->
        <div class="conditions-section">
            <div class="section-header">
                <span class="section-tag">Conditions</span>
                <h2 class="section-title">Conditions de retour</h2>
            </div>
            
            <div class="conditions-grid">
                <div class="condition-column">
                    <h3><i class="fas fa-check-circle"></i> Articles acceptés</h3>
                    <ul class="accept-list">
                        <li><i class="fas fa-check"></i> Article non porté, non lavé</li>
                        <li><i class="fas fa-check"></i> Étiquettes d'origine attachées</li>
                        <li><i class="fas fa-check"></i> Emballage d'origine intact</li>
                        <li><i class="fas fa-check"></i> Retour dans les 30 jours</li>
                        <li><i class="fas fa-check"></i> Accessoires et emballages inclus</li>
                    </ul>
                </div>
                <div class="condition-column">
                    <h3><i class="fas fa-times-circle"></i> Articles non acceptés</h3>
                    <ul class="refuse-list">
                        <li><i class="fas fa-times"></i> Articles portés ou lavés</li>
                        <li><i class="fas fa-times"></i> Étiquettes retirées ou endommagées</li>
                        <li><i class="fas fa-times"></i> Articles personnalisés ou sur-mesure</li>
                        <li><i class="fas fa-times"></i> Sous-vêtements et maillots de bain</li>
                        <li><i class="fas fa-times"></i> Articles soldés à -50% ou plus</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- REFUND -->
        <div class="refund-section">
            <div class="section-header">
                <span class="section-tag">Remboursement</span>
                <h2 class="section-title">Options de remboursement</h2>
            </div>
            
            <div class="refund-grid">
                <div class="refund-card">
                    <div class="refund-icon"><i class="fas fa-credit-card"></i></div>
                    <h3>Carte bancaire</h3>
                    <p>Remboursement sur le moyen de paiement d'origine sous 5-7 jours ouvrés.</p>
                </div>
                <div class="refund-card">
                    <div class="refund-icon"><i class="fas fa-wallet"></i></div>
                    <h3>Avoir boutique</h3>
                    <p>Avoir valable 1 an, utilisable sur tout le site. Crédité sous 24h.</p>
                </div>
                <div class="refund-card">
                    <div class="refund-icon"><i class="fas fa-exchange-alt"></i></div>
                    <h3>Échange</h3>
                    <p>Échangez contre une autre taille ou un autre produit. Livraison offerte.</p>
                </div>
            </div>
        </div>
        
        <!-- FAQ -->
        <div class="faq-section">
            <div class="section-header">
                <span class="section-tag">FAQ</span>
                <h2 class="section-title">Questions fréquentes</h2>
            </div>
            
            <div class="faq-item active">
                <div class="faq-question">
                    <h4>Combien coûte le retour ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Les retours sont gratuits en France métropolitaine. Pour les autres destinations, les frais de retour sont à la charge du client.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>Puis-je échanger contre une autre taille ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Oui ! L'échange contre une autre taille est gratuit. Si la taille souhaitée n'est plus disponible, vous serez intégralement remboursé.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>Quand serai-je remboursé ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Le remboursement est effectué sous 5-7 jours ouvrés après réception et vérification de l'article. Vous recevrez un email de confirmation.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>Puis-je retourner un article soldé ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Les articles soldés jusqu'à -40% peuvent être retournés. Au-delà de -50% de réduction, les articles sont ni repris ni échangés.
                </div>
            </div>
        </div>
        
        <!-- CTA -->
        <div class="cta-section">
            <div class="cta-box">
                <h2>Besoin d'aide pour votre retour ?</h2>
                <p>Notre service client est disponible pour vous accompagner dans vos démarches.</p>
                <a href="{{ route('frontend.contact') }}" class="btn-cta">
                    <i class="fas fa-headset"></i> Nous contacter
                </a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', function() {
        const item = this.parentElement;
        const wasActive = item.classList.contains('active');
        
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
        
        if (!wasActive) {
            item.classList.add('active');
        }
    });
});
</script>
@endpush
