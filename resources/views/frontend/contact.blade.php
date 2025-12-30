@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Contact - RACINE BY GANDA')

@push('styles')
<style>
    .contact-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 5rem 0;
        margin-top: -70px;
        padding-top: calc(5rem + 70px);
        text-align: center;
    }
    
    .contact-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .contact-hero p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.15rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .contact-section {
        padding: 5rem 0;
        background: #F8F6F3;
    }
    
    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 3rem;
    }
    
    /* INFO CARDS */
    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .info-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        display: flex;
        gap: 1.5rem;
        transition: all 0.3s;
    }
    
    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
    }
    
    .info-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        flex-shrink: 0;
    }
    
    .info-content h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .info-content p {
        color: #8B7355;
        font-size: 1rem;
        line-height: 1.6;
    }
    
    .info-content a {
        color: #8B5A2B;
        text-decoration: none;
        font-weight: 500;
    }
    
    .info-content a:hover {
        text-decoration: underline;
    }
    
    /* SOCIAL */
    .social-card {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        border-radius: 20px;
        padding: 2rem;
        color: white;
    }
    
    .social-card h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .social-card p {
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 1.5rem;
    }
    
    .social-links {
        display: flex;
        gap: 1rem;
    }
    
    .social-link {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .social-link:hover {
        background: #D4A574;
        transform: translateY(-3px);
    }
    
    /* FORM */
    .contact-form-wrapper {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.08);
    }
    
    .form-header {
        margin-bottom: 2rem;
    }
    
    .form-header h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .form-header p {
        color: #8B7355;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        font-weight: 500;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 1.5px solid #E5DDD3;
        border-radius: 12px;
        font-size: 1rem;
        font-family: inherit;
        transition: all 0.3s;
        background: #FAFAFA;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #D4A574;
        background: white;
        box-shadow: 0 0 0 4px rgba(212, 165, 116, 0.1);
    }
    
    .form-group textarea {
        min-height: 150px;
        resize: vertical;
    }
    
    .btn-submit {
        width: 100%;
        padding: 1.1rem;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 40px rgba(44, 24, 16, 0.3);
    }
    
    /* MAP */
    .map-section {
        padding: 0 0 5rem;
        background: #F8F6F3;
    }
    
    .map-wrapper {
        border-radius: 24px;
        overflow: hidden;
        height: 400px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
    }
    
    .map-wrapper iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    
    /* FAQ PREVIEW */
    .faq-section {
        padding: 5rem 0;
        background: white;
    }
    
    .faq-header {
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
    
    .faq-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        max-width: 900px;
        margin: 0 auto;
    }
    
    .faq-item {
        background: #F8F6F3;
        border-radius: 16px;
        padding: 1.5rem;
    }
    
    .faq-item h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2C1810;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .faq-item h4 i {
        color: #D4A574;
    }
    
    .faq-item p {
        color: #8B7355;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    @media (max-width: 1024px) {
        .contact-grid { grid-template-columns: 1fr; }
    }
    
    @media (max-width: 768px) {
        .contact-hero h1 { font-size: 2.5rem; }
        .form-row { grid-template-columns: 1fr; }
        .faq-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="contact-hero">
    <div class="container">
        @php
            $heroSection = $cmsPage?->section('hero');
            $heroData = $heroSection?->data ?? [];
        @endphp
        <h1>{{ $heroData['title'] ?? $cmsPage?->title ?? 'Contactez-nous' }}</h1>
        <p>{{ $heroData['description'] ?? 'Une question, une suggestion ou besoin d\'aide ? Notre équipe est là pour vous accompagner.' }}</p>
    </div>
</section>

<!-- CONTACT -->
<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            <!-- INFO -->
            <div class="contact-info">
                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-envelope"></i></div>
                    <div class="info-content">
                        <h3>Email</h3>
                        <p>Pour toute question générale</p>
                        <a href="mailto:contact@racine-ganda.com">contact@racine-ganda.com</a>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-phone"></i></div>
                    <div class="info-content">
                        <h3>Téléphone</h3>
                        <p>Du lundi au vendredi, 9h-18h</p>
                        <a href="tel:+33123456789">+33 1 23 45 67 89</a>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="info-content">
                        <h3>Showroom</h3>
                        <p>Sur rendez-vous uniquement</p>
                        <span>15 Rue de la Mode, 75003 Paris</span>
                    </div>
                </div>
                
                <div class="social-card">
                    <h3>Suivez-nous</h3>
                    <p>Restez connecté pour découvrir nos dernières créations et actualités.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-pinterest"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- FORM -->
            <div class="contact-form-wrapper">
                <div class="form-header">
                    <h2>Envoyez-nous un message</h2>
                    <p>Nous vous répondrons dans les 24 heures</p>
                </div>
                
                <form action="#" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label>Prénom</label>
                            <input type="text" name="first_name" placeholder="Votre prénom" required>
                        </div>
                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" name="last_name" placeholder="Votre nom" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="votre@email.com" required>
                        </div>
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="tel" name="phone" placeholder="+33 6 12 34 56 78">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Sujet</label>
                        <select name="subject" required>
                            <option value="">Choisir un sujet</option>
                            <option value="order">Question sur une commande</option>
                            <option value="product">Question sur un produit</option>
                            <option value="return">Retour ou échange</option>
                            <option value="partnership">Partenariat</option>
                            <option value="press">Presse</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Votre message</label>
                        <textarea name="message" placeholder="Décrivez votre demande en détail..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i>
                        Envoyer le message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- MAP -->
<section class="map-section">
    <div class="container">
        <div class="map-wrapper">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.9916256937595!2d2.3522!3d48.8566!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDjCsDUxJzIzLjgiTiAywrAyMScwNy45IkU!5e0!3m2!1sfr!2sfr!4v1234567890" allowfullscreen loading="lazy"></iframe>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="faq-section">
    <div class="container">
        <div class="faq-header">
            <span class="section-tag">FAQ</span>
            <h2 class="section-title">Questions fréquentes</h2>
        </div>
        
        <div class="faq-grid">
            <div class="faq-item">
                <h4><i class="fas fa-truck"></i> Quels sont les délais de livraison ?</h4>
                <p>La livraison standard est de 3 à 7 jours ouvrés en France métropolitaine.</p>
            </div>
            <div class="faq-item">
                <h4><i class="fas fa-rotate-left"></i> Comment effectuer un retour ?</h4>
                <p>Vous disposez de 30 jours pour retourner un article non porté dans son emballage d'origine.</p>
            </div>
            <div class="faq-item">
                <h4><i class="fas fa-credit-card"></i> Quels moyens de paiement acceptez-vous ?</h4>
                <p>Nous acceptons CB, Visa, Mastercard, PayPal et le paiement en 3x sans frais.</p>
            </div>
            <div class="faq-item">
                <h4><i class="fas fa-ruler"></i> Comment choisir ma taille ?</h4>
                <p>Consultez notre guide des tailles disponible sur chaque fiche produit.</p>
            </div>
        </div>
    </div>
</section>
@endsection
