{{-- FOOTER PREMIUM RACINE BY GANDA - COMPOSANT UNIFIÉ --}}
<footer class="footer-premium">
    
    {{-- MAIN FOOTER --}}
    <div class="footer-main">
        <div class="container">
            <div class="footer-grid">
                {{-- Colonne 1: Brand --}}
                <div class="footer-brand">
                    <div class="brand-logo">
                        <img src="{{ asset('images/logo-racine.png') }}" alt="RACINE BY GANDA" onerror="this.style.display='none'">
                        <span>RACINE BY GANDA</span>
                    </div>
                    <p class="brand-tagline">Mode Africaine Premium</p>
                    <p class="brand-description">
                        Des créations uniques qui célèbrent l'héritage africain avec une touche contemporaine. Chaque pièce raconte une histoire.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Pinterest">
                            <i class="fab fa-pinterest-p"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="TikTok">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    </div>
                </div>
                
                {{-- Colonne 2: Boutique --}}
                <div class="footer-links-col">
                    <h4>Boutique</h4>
                    <ul>
                        <li><a href="{{ route('frontend.shop') }}"><i class="fas fa-chevron-right"></i> Tous les produits</a></li>
                        <li><a href="{{ route('frontend.creators') }}"><i class="fas fa-chevron-right"></i> Nos créateurs</a></li>
                        <li><a href="{{ route('frontend.showroom') }}"><i class="fas fa-chevron-right"></i> Showroom virtuel</a></li>
                        <li><a href="{{ route('frontend.atelier') }}"><i class="fas fa-chevron-right"></i> L'Atelier</a></li>
                        <li><a href="{{ route('cart.index') }}"><i class="fas fa-chevron-right"></i> Mon panier</a></li>
                    </ul>
                </div>
                
                {{-- Colonne 3: Découverte --}}
                <div class="footer-links-col">
                    <h4>Découverte</h4>
                    <ul>
                        <li><a href="{{ route('frontend.portfolio') }}"><i class="fas fa-chevron-right"></i> Portfolio</a></li>
                        <li><a href="{{ route('frontend.albums') }}"><i class="fas fa-chevron-right"></i> Albums</a></li>
                        <li><a href="{{ route('frontend.events') }}"><i class="fas fa-chevron-right"></i> Événements</a></li>
                        <li><a href="{{ route('frontend.ceo') }}"><i class="fas fa-chevron-right"></i> Amira Ganda</a></li>
                        <li><a href="{{ route('frontend.brand-guidelines') }}"><i class="fas fa-chevron-right"></i> Charte Graphique</a></li>
                    </ul>
                </div>
                
                {{-- Colonne 4: Informations --}}
                <div class="footer-links-col">
                    <h4>Informations</h4>
                    <ul>
                        <li><a href="{{ route('frontend.about') }}"><i class="fas fa-chevron-right"></i> Notre histoire</a></li>
                        <li><a href="{{ route('frontend.contact') }}"><i class="fas fa-chevron-right"></i> Contact</a></li>
                        <li><a href="{{ route('frontend.shipping') }}"><i class="fas fa-chevron-right"></i> Livraison</a></li>
                        <li><a href="{{ route('frontend.returns') }}"><i class="fas fa-chevron-right"></i> Retours & Échanges</a></li>
                        <li><a href="{{ route('frontend.help') }}"><i class="fas fa-chevron-right"></i> FAQ & Aide</a></li>
                    </ul>
                </div>
                
                {{-- Colonne 5: Légal --}}
                <div class="footer-links-col">
                    <h4>Légal</h4>
                    <ul>
                        <li><a href="{{ route('frontend.terms') }}"><i class="fas fa-chevron-right"></i> Conditions Générales</a></li>
                        <li><a href="{{ route('frontend.privacy') }}"><i class="fas fa-chevron-right"></i> Confidentialité</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Cookies</a></li>
                    </ul>
                </div>
                
                {{-- Colonne 6: Contact --}}
                <div class="footer-contact-col">
                    <h4>Contact</h4>
                    <div class="contact-items">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-text">
                                <span>République du Congo, Pointe-Noire</span>
                                <span>Centre ville, Galerie NF</span>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="contact-text">
                                <span>+237 6XX XXX XXX</span>
                                <span>Lun-Sam: 9h-18h</span>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-text">
                                <span>contact@racine.cm</span>
                                <span>support@racine.cm</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- BOTTOM BAR --}}
    <div class="footer-bottom">
        <div class="container">
            <div class="bottom-content">
                <div class="copyright">
                    <p>© {{ date('Y') }} <strong>RACINE BY GANDA</strong>. Tous droits réservés.</p>
                    <p class="dev-credit">
                        Développé par <a href="#" class="dev-link"><strong>NIKA DIGITAL HUB</strong></a> 
                        <span class="dev-separator">|</span> 
                        <span class="dev-desc">Solutions Web & Communication</span>
                        <span class="dev-flag">🇨🇬</span>
                        <span class="dev-country">République du Congo</span>
                    </p>
                </div>
                <div class="legal-links">
                    <a href="{{ route('frontend.terms') }}">CGV</a>
                    <span>•</span>
                    <a href="{{ route('frontend.privacy') }}">Confidentialité</a>
                    <span>•</span>
                    <a href="#">Cookies</a>
                </div>
                <div class="payment-methods">
                    <span>Paiement sécurisé</span>
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fas fa-mobile-alt" title="Mobile Money"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

@push('styles')
<style>
    /* ===== FOOTER PREMIUM STYLES RACINE BY GANDA ===== */
    .footer-premium {
        background: linear-gradient(180deg, #1a0f09 0%, #0d0806 100%);
        color: white;
        font-family: 'Cormorant Garamond', serif;
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        box-sizing: border-box;
    }
    
    /* Main Footer */
    .footer-main {
        padding: 4rem 0 3rem;
    }
    .footer-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 1fr 1fr 1.2fr;
        gap: 2.5rem;
    }
    
    /* Brand Column */
    .footer-brand .brand-logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
    }
    .footer-brand .brand-logo img {
        height: 45px;
        width: 45px;
        object-fit: contain;
    }
    .footer-brand .brand-logo span {
        font-size: 1.75rem;
        font-weight: 700;
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: 2px;
    }
    .footer-brand .brand-tagline {
        color: #ED5F1E;
        font-size: 0.9rem;
        letter-spacing: 1px;
        margin-bottom: 1rem;
    }
    .footer-brand .brand-description {
        color: rgba(255, 255, 255, 0.6);
        line-height: 1.7;
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }
    .social-links {
        display: flex;
        gap: 0.75rem;
    }
    .social-link {
        width: 42px;
        height: 42px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        transition: all 0.3s;
        font-size: 1rem;
    }
    .social-link:hover {
        background: #ED5F1E;
        border-color: #ED5F1E;
        color: white;
        transform: translateY(-3px);
    }
    
    /* Links Columns */
    .footer-links-col h4,
    .footer-contact-col h4 {
        font-size: 1.15rem;
        font-weight: 600;
        color: white;
        margin-bottom: 1.5rem;
        position: relative;
        padding-bottom: 0.75rem;
    }
    .footer-links-col h4::after,
    .footer-contact-col h4::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 2px;
        background: linear-gradient(90deg, #ED5F1E, transparent);
    }
    .footer-links-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .footer-links-col ul li {
        margin-bottom: 0.85rem;
    }
    .footer-links-col ul li a {
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
        font-size: 0.95rem;
    }
    .footer-links-col ul li a i {
        font-size: 0.6rem;
        color: #ED5F1E;
        transition: transform 0.3s;
    }
    .footer-links-col ul li a:hover {
        color: #ED5F1E;
        padding-left: 5px;
    }
    .footer-links-col ul li a:hover i {
        transform: translateX(3px);
    }
    
    /* Contact Column */
    .contact-items {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    .contact-item {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }
    .contact-icon {
        width: 40px;
        height: 40px;
        background: rgba(237, 95, 30, 0.15);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ED5F1E;
        flex-shrink: 0;
    }
    .contact-text {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }
    .contact-text span:first-child {
        color: white;
        font-weight: 500;
    }
    .contact-text span:last-child {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.85rem;
    }
    
    /* Bottom Bar */
    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        padding: 1.5rem 0;
        background: rgba(0, 0, 0, 0.2);
    }
    .bottom-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
    }
    .copyright p {
        margin: 0;
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.9rem;
    }
    .copyright strong {
        color: #ED5F1E;
    }
    .dev-credit {
        margin-top: 0.5rem !important;
        font-size: 0.8rem !important;
        color: rgba(255, 255, 255, 0.4);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .dev-link {
        color: #FFB800 !important;
        text-decoration: none;
        transition: all 0.3s;
    }
    .dev-link:hover {
        color: #ED5F1E !important;
        text-shadow: 0 0 10px rgba(237, 95, 30, 0.5);
    }
    .dev-separator {
        color: rgba(255, 255, 255, 0.2);
    }
    .dev-desc {
        color: rgba(255, 255, 255, 0.5);
    }
    .dev-flag {
        font-size: 1rem;
    }
    .dev-country {
        color: rgba(255, 255, 255, 0.4);
        font-style: italic;
    }
    .legal-links {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .legal-links a {
        color: rgba(255, 255, 255, 0.5);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s;
    }
    .legal-links a:hover {
        color: #ED5F1E;
    }
    .legal-links span {
        color: rgba(255, 255, 255, 0.3);
    }
    .payment-methods {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .payment-methods > span {
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.85rem;
    }
    .payment-icons {
        display: flex;
        gap: 0.75rem;
        font-size: 1.5rem;
        color: rgba(255, 255, 255, 0.4);
    }
    .payment-icons i {
        transition: color 0.3s;
    }
    .payment-icons i:hover {
        color: white;
    }
    
    /* Responsive Footer */
    @media (max-width: 1200px) {
        .footer-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
    }
    @media (max-width: 768px) {
        .footer-grid {
            grid-template-columns: 1fr;
            gap: 2rem;
            text-align: center;
        }
        .footer-links-col h4::after,
        .footer-contact-col h4::after {
            left: 50%;
            transform: translateX(-50%);
        }
        .footer-links-col ul li a {
            justify-content: center;
        }
        .social-links {
            justify-content: center;
        }
        .contact-item {
            justify-content: center;
        }
        .bottom-content {
            flex-direction: column;
            text-align: center;
        }
        .dev-credit {
            justify-content: center;
        }
    }
</style>
@endpush


