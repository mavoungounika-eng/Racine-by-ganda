@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Centre d\'aide - RACINE BY GANDA')

@push('styles')
<style>
    .help-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 5rem 0;
        margin-top: -70px;
        padding-top: calc(5rem + 70px);
        text-align: center;
    }
    
    .help-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .help-hero p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.15rem;
        max-width: 600px;
        margin: 0 auto 2rem;
    }
    
    .search-box {
        max-width: 600px;
        margin: 0 auto;
        position: relative;
    }
    
    .search-box input {
        width: 100%;
        padding: 1.25rem 1.5rem 1.25rem 3.5rem;
        border: none;
        border-radius: 50px;
        font-size: 1rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }
    
    .search-box input:focus {
        outline: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }
    
    .search-box i {
        position: absolute;
        left: 1.5rem;
        top: 50%;
        transform: translateY(-50%);
        color: #8B7355;
    }
    
    /* QUICK LINKS */
    .quick-links {
        padding: 4rem 0;
        background: #F8F6F3;
    }
    
    .links-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }
    
    .link-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .link-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
    }
    
    .link-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, rgba(212, 165, 116, 0.1) 0%, rgba(139, 90, 43, 0.1) 100%);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.25rem;
        font-size: 1.75rem;
        color: #D4A574;
    }
    
    .link-card h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .link-card p {
        color: #8B7355;
        font-size: 0.9rem;
    }
    
    /* FAQ SECTION */
    .faq-section {
        padding: 5rem 0;
        background: white;
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
    
    .faq-tabs {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 3rem;
        flex-wrap: wrap;
    }
    
    .faq-tab {
        padding: 0.75rem 1.5rem;
        border: 2px solid #E5DDD3;
        background: white;
        border-radius: 30px;
        color: #5C4A3D;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .faq-tab:hover, .faq-tab.active {
        background: #2C1810;
        border-color: #2C1810;
        color: white;
    }
    
    .faq-content {
        max-width: 800px;
        margin: 0 auto;
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
    
    /* GUIDES */
    .guides-section {
        padding: 5rem 0;
        background: #F8F6F3;
    }
    
    .guides-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
    
    .guide-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.3s;
        text-decoration: none;
    }
    
    .guide-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
    }
    
    .guide-image {
        height: 180px;
        overflow: hidden;
    }
    
    .guide-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .guide-card:hover .guide-image img {
        transform: scale(1.1);
    }
    
    .guide-content {
        padding: 1.5rem;
    }
    
    .guide-tag {
        display: inline-block;
        background: rgba(212, 165, 116, 0.1);
        color: #8B5A2B;
        padding: 0.3rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
    }
    
    .guide-content h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.35rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .guide-content p {
        color: #8B7355;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    /* CONTACT CTA */
    .contact-cta {
        padding: 5rem 0;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
    }
    
    .cta-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
    }
    
    .cta-text h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .cta-text p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }
    
    .btn-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2rem;
        background: white;
        color: #2C1810;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        color: #2C1810;
    }
    
    .contact-options {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    .contact-option {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        color: white;
    }
    
    .contact-option i {
        font-size: 2rem;
        margin-bottom: 0.75rem;
    }
    
    .contact-option h4 {
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }
    
    .contact-option span {
        opacity: 0.8;
        font-size: 0.9rem;
    }
    
    @media (max-width: 1024px) {
        .links-grid { grid-template-columns: repeat(2, 1fr); }
        .guides-grid { grid-template-columns: 1fr; }
        .cta-content { grid-template-columns: 1fr; }
    }
    
    @media (max-width: 768px) {
        .links-grid { grid-template-columns: 1fr; }
        .help-hero h1 { font-size: 2.5rem; }
        .contact-options { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="help-hero">
    <div class="container">
        @php
            $heroSection = $cmsPage?->section('hero');
            $heroData = $heroSection?->data ?? [];
        @endphp
        <h1>{{ $heroData['title'] ?? $cmsPage?->title ?? 'Comment pouvons-nous vous aider ?' }}</h1>
        <p>{{ $heroData['description'] ?? 'Trouvez rapidement les réponses à vos questions ou contactez notre équipe.' }}</p>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Rechercher une question...">
        </div>
    </div>
</section>

<!-- QUICK LINKS -->
<section class="quick-links">
    <div class="container">
        <div class="links-grid">
            <a href="#commandes" class="link-card">
                <div class="link-icon"><i class="fas fa-shopping-bag"></i></div>
                <h3>Commandes</h3>
                <p>Suivi, modification, annulation</p>
            </a>
            <a href="#livraison" class="link-card">
                <div class="link-icon"><i class="fas fa-truck"></i></div>
                <h3>Livraison</h3>
                <p>Délais, zones, tarifs</p>
            </a>
            <a href="#retours" class="link-card">
                <div class="link-icon"><i class="fas fa-rotate-left"></i></div>
                <h3>Retours</h3>
                <p>Échanges, remboursements</p>
            </a>
            <a href="#paiement" class="link-card">
                <div class="link-icon"><i class="fas fa-credit-card"></i></div>
                <h3>Paiement</h3>
                <p>Moyens, sécurité, facilités</p>
            </a>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="faq-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">FAQ</span>
            <h2 class="section-title">Questions fréquentes</h2>
        </div>
        
        <div class="faq-tabs">
            <button class="faq-tab active" data-category="all">Tout</button>
            <button class="faq-tab" data-category="commandes">Commandes</button>
            <button class="faq-tab" data-category="livraison">Livraison</button>
            <button class="faq-tab" data-category="retours">Retours</button>
            <button class="faq-tab" data-category="produits">Produits</button>
        </div>
        
        <div class="faq-content">
            <div class="faq-item active" data-category="commandes">
                <div class="faq-question">
                    <h4>Comment suivre ma commande ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Vous pouvez suivre votre commande depuis votre espace client ou en cliquant sur le lien de suivi envoyé par email. Vous recevrez des notifications à chaque étape : préparation, expédition et livraison.
                </div>
            </div>
            
            <div class="faq-item" data-category="commandes">
                <div class="faq-question">
                    <h4>Puis-je modifier ma commande après validation ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Les modifications sont possibles tant que la commande n'est pas en préparation. Contactez-nous rapidement par email ou téléphone pour toute demande de modification.
                </div>
            </div>
            
            <div class="faq-item" data-category="livraison">
                <div class="faq-question">
                    <h4>Quels sont les délais de livraison ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    France métropolitaine : 3-5 jours ouvrés. DOM-TOM : 7-14 jours ouvrés. International : 10-21 jours selon la destination. Les délais sont indicatifs et peuvent varier pendant les périodes de forte activité.
                </div>
            </div>
            
            <div class="faq-item" data-category="livraison">
                <div class="faq-question">
                    <h4>La livraison est-elle gratuite ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Oui, la livraison est offerte en France métropolitaine pour toute commande supérieure à 100€. En dessous de ce montant, les frais de port sont de 5,90€.
                </div>
            </div>
            
            <div class="faq-item" data-category="retours">
                <div class="faq-question">
                    <h4>Comment effectuer un retour ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Vous disposez de 30 jours après réception pour retourner un article. Connectez-vous à votre espace client, sélectionnez la commande concernée et suivez les instructions. Une étiquette de retour prépayée vous sera envoyée.
                </div>
            </div>
            
            <div class="faq-item" data-category="retours">
                <div class="faq-question">
                    <h4>Dans quel délai serai-je remboursé ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Le remboursement est effectué sous 5-7 jours ouvrés après réception et vérification de l'article retourné. Vous recevrez un email de confirmation dès que le remboursement sera traité.
                </div>
            </div>
            
            <div class="faq-item" data-category="produits">
                <div class="faq-question">
                    <h4>Comment choisir ma taille ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Consultez notre guide des tailles disponible sur chaque fiche produit. Mesurez-vous selon les instructions et comparez avec nos tableaux. En cas de doute, n'hésitez pas à nous contacter, nous vous conseillerons.
                </div>
            </div>
            
            <div class="faq-item" data-category="produits">
                <div class="faq-question">
                    <h4>Comment entretenir mes vêtements en wax ?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Lavez vos vêtements en wax à 30°C maximum, à l'envers, avec des couleurs similaires. Évitez le sèche-linge et repassez sur l'envers. Ces précautions préservent l'éclat des couleurs.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- GUIDES -->
<section class="guides-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Guides</span>
            <h2 class="section-title">Nos tutoriels</h2>
        </div>
        
        <div class="guides-grid">
            <a href="#" class="guide-card">
                <div class="guide-image">
                    <img src="https://images.unsplash.com/photo-1590735213920-68192a487bc2?w=400" alt="Guide tailles">
                </div>
                <div class="guide-content">
                    <span class="guide-tag">Guide</span>
                    <h3>Comment prendre ses mesures</h3>
                    <p>Toutes les astuces pour choisir la bonne taille.</p>
                </div>
            </a>
            <a href="#" class="guide-card">
                <div class="guide-image">
                    <img src="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=400" alt="Entretien wax">
                </div>
                <div class="guide-content">
                    <span class="guide-tag">Entretien</span>
                    <h3>Prendre soin de ses vêtements wax</h3>
                    <p>Conseils pour préserver vos pièces africaines.</p>
                </div>
            </a>
            <a href="#" class="guide-card">
                <div class="guide-image">
                    <img src="https://images.unsplash.com/photo-1551232864-3f0890e580d9?w=400" alt="Style">
                </div>
                <div class="guide-content">
                    <span class="guide-tag">Style</span>
                    <h3>Associer le wax au quotidien</h3>
                    <p>Idées de looks pour toutes les occasions.</p>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- CONTACT CTA -->
<section class="contact-cta">
    <div class="container">
        <div class="cta-content">
            <div class="cta-text">
                <h2>Besoin d'aide supplémentaire ?</h2>
                <p>Notre équipe est disponible pour répondre à toutes vos questions.</p>
                <a href="{{ route('frontend.contact') }}" class="btn-cta">
                    <i class="fas fa-envelope"></i> Nous contacter
                </a>
            </div>
            <div class="contact-options">
                <div class="contact-option">
                    <i class="fas fa-envelope"></i>
                    <h4>Email</h4>
                    <span>contact@racine-ganda.com</span>
                </div>
                <div class="contact-option">
                    <i class="fas fa-phone"></i>
                    <h4>Téléphone</h4>
                    <span>+33 1 23 45 67 89</span>
                </div>
                <div class="contact-option">
                    <i class="fas fa-comments"></i>
                    <h4>Chat</h4>
                    <span>En direct sur le site</span>
                </div>
                <div class="contact-option">
                    <i class="fas fa-clock"></i>
                    <h4>Horaires</h4>
                    <span>Lun-Ven, 9h-18h</span>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
// FAQ accordion
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

// FAQ tabs filter
document.querySelectorAll('.faq-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.faq-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        const category = this.dataset.category;
        
        document.querySelectorAll('.faq-item').forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
</script>
@endpush
