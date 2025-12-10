@extends('layouts.frontend')

@section('title', $cmsPage?->seo_title ?? $cmsPage?->title ?? 'Événements - RACINE BY GANDA')

@push('styles')
<style>
    .events-hero {
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        padding: 5rem 0;
        margin-top: -70px;
        padding-top: calc(5rem + 70px);
        position: relative;
        overflow: hidden;
    }
    
    .events-hero::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 50%;
        height: 100%;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23D4A574' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    
    .events-hero h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        color: white;
        margin-bottom: 1rem;
    }
    
    .events-hero p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.1rem;
        max-width: 600px;
    }
    
    .events-section {
        padding: 5rem 0;
        background: #F8F6F3;
    }
    
    .section-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .section-header h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.5rem;
        color: #2C1810;
        margin-bottom: 0.5rem;
    }
    
    .section-header .line {
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #D4A574, #ED5F1E);
        margin: 0 auto;
    }
    
    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
    }
    
    .event-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        transition: all 0.4s;
    }
    
    .event-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
    }
    
    .event-image {
        height: 220px;
        position: relative;
        overflow: hidden;
    }
    
    .event-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .event-card:hover .event-image img {
        transform: scale(1.1);
    }
    
    .event-date-badge {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: white;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        text-align: center;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }
    
    .event-date-badge .day {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ED5F1E;
        line-height: 1;
    }
    
    .event-date-badge .month {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #5C4A3D;
        letter-spacing: 1px;
    }
    
    .event-type {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        color: white;
        padding: 0.35rem 0.85rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .event-content {
        padding: 1.5rem;
    }
    
    .event-content h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.4rem;
        color: #2C1810;
        margin-bottom: 0.75rem;
    }
    
    .event-meta {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .event-meta span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #8B7355;
        font-size: 0.9rem;
    }
    
    .event-meta i {
        color: #D4A574;
        width: 18px;
    }
    
    .event-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 1rem;
        border-top: 1px solid #E5DDD3;
    }
    
    .event-price {
        font-weight: 700;
        color: #ED5F1E;
        font-size: 1.1rem;
    }
    
    .event-price.free {
        color: #22C55E;
    }
    
    .btn-event {
        padding: 0.6rem 1.25rem;
        background: linear-gradient(135deg, #2C1810 0%, #1a0f09 100%);
        color: white;
        border-radius: 25px;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-event:hover {
        background: linear-gradient(135deg, #ED5F1E 0%, #c44b12 100%);
        color: white;
    }
    
    /* Featured Event */
    .featured-event {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        display: grid;
        grid-template-columns: 1fr 1fr;
        margin-bottom: 4rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    }
    
    .featured-event-image {
        height: 100%;
        min-height: 400px;
    }
    
    .featured-event-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .featured-event-content {
        padding: 3rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .featured-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #D4A574 0%, #8B5A2B 100%);
        color: white;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        width: fit-content;
        margin-bottom: 1rem;
    }
    
    .featured-event-content h2 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.25rem;
        color: #2C1810;
        margin-bottom: 1rem;
    }
    
    .featured-event-content p {
        color: #5C4A3D;
        margin-bottom: 1.5rem;
        line-height: 1.7;
    }
    
    .countdown {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .countdown-item {
        text-align: center;
        background: #F8F6F3;
        padding: 1rem;
        border-radius: 12px;
        min-width: 70px;
    }
    
    .countdown-item .number {
        font-size: 1.75rem;
        font-weight: 700;
        color: #ED5F1E;
    }
    
    .countdown-item .label {
        font-size: 0.75rem;
        color: #8B7355;
        text-transform: uppercase;
    }
    
    @media (max-width: 992px) {
        .featured-event {
            grid-template-columns: 1fr;
        }
        
        .featured-event-image {
            min-height: 300px;
        }
    }
    
    @media (max-width: 768px) {
        .events-hero h1 {
            font-size: 2.5rem;
        }
        
        .events-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<!-- HERO -->
<section class="events-hero">
    <div class="container">
        @php
            $heroSection = $cmsPage?->section('hero');
            $heroData = $heroSection?->data ?? [];
        @endphp
        <h1>{!! $heroData['title'] ?? '✨ Événements' !!}</h1>
        <p>{{ $heroData['description'] ?? 'Défilés, expositions, ateliers et rencontres exclusives. Vivez l\'expérience RACINE BY GANDA.' }}</p>
    </div>
</section>

<!-- FEATURED EVENT -->
<section style="padding: 4rem 0; background: #F8F6F3;">
    <div class="container">
        <div class="featured-event">
            <div class="featured-event-image">
                <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800" alt="Événement à la une">
            </div>
            <div class="featured-event-content">
                <span class="featured-badge"><i class="fas fa-star"></i> Prochain Événement</span>
                <h2>Grand Défilé RACINE 2025</h2>
                <p>
                    Découvrez notre nouvelle collection lors d'un défilé exceptionnel au cœur de Pointe-Noire. 
                    Une soirée placée sous le signe de l'élégance africaine et de la créativité.
                </p>
                
                <div class="event-meta">
                    <span><i class="fas fa-calendar-alt"></i> 15 Février 2025 - 19h00</span>
                    <span><i class="fas fa-map-marker-alt"></i> Hôtel Atlantic Palace, Pointe-Noire</span>
                    <span><i class="fas fa-users"></i> 200 places disponibles</span>
                </div>
                
                <div class="countdown">
                    <div class="countdown-item">
                        <div class="number" id="days">--</div>
                        <div class="label">Jours</div>
                    </div>
                    <div class="countdown-item">
                        <div class="number" id="hours">--</div>
                        <div class="label">Heures</div>
                    </div>
                    <div class="countdown-item">
                        <div class="number" id="minutes">--</div>
                        <div class="label">Min</div>
                    </div>
                    <div class="countdown-item">
                        <div class="number" id="seconds">--</div>
                        <div class="label">Sec</div>
                    </div>
                </div>
                
                <a href="#" class="btn-event" style="width: fit-content; padding: 1rem 2rem;">
                    <i class="fas fa-ticket-alt"></i> Réserver ma place
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ALL EVENTS -->
<section class="events-section">
    <div class="container">
        <div class="section-header">
            <h2>Tous nos Événements</h2>
            <div class="line"></div>
        </div>
        
        <div class="events-grid">
            <!-- Event 1 -->
            <div class="event-card">
                <div class="event-image">
                    <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600" alt="Exposition">
                    <div class="event-date-badge">
                        <div class="day">20</div>
                        <div class="month">Jan</div>
                    </div>
                    <span class="event-type">Exposition</span>
                </div>
                <div class="event-content">
                    <h3>Exposition "Racines & Modernité"</h3>
                    <div class="event-meta">
                        <span><i class="fas fa-clock"></i> 10h00 - 18h00</span>
                        <span><i class="fas fa-map-marker-alt"></i> Galerie d'Art, Brazzaville</span>
                    </div>
                    <div class="event-footer">
                        <span class="event-price free">Entrée libre</span>
                        <a href="#" class="btn-event">En savoir plus</a>
                    </div>
                </div>
            </div>
            
            <!-- Event 2 -->
            <div class="event-card">
                <div class="event-image">
                    <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=600" alt="Atelier">
                    <div class="event-date-badge">
                        <div class="day">28</div>
                        <div class="month">Jan</div>
                    </div>
                    <span class="event-type">Atelier</span>
                </div>
                <div class="event-content">
                    <h3>Atelier Couture Africaine</h3>
                    <div class="event-meta">
                        <span><i class="fas fa-clock"></i> 14h00 - 17h00</span>
                        <span><i class="fas fa-map-marker-alt"></i> Atelier RACINE, Pointe-Noire</span>
                    </div>
                    <div class="event-footer">
                        <span class="event-price">25 000 FCFA</span>
                        <a href="#" class="btn-event">S'inscrire</a>
                    </div>
                </div>
            </div>
            
            <!-- Event 3 -->
            <div class="event-card">
                <div class="event-image">
                    <img src="https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=600" alt="Vente Privée">
                    <div class="event-date-badge">
                        <div class="day">05</div>
                        <div class="month">Fév</div>
                    </div>
                    <span class="event-type">Vente Privée</span>
                </div>
                <div class="event-content">
                    <h3>Vente Privée VIP</h3>
                    <div class="event-meta">
                        <span><i class="fas fa-clock"></i> 16h00 - 21h00</span>
                        <span><i class="fas fa-map-marker-alt"></i> Showroom RACINE</span>
                    </div>
                    <div class="event-footer">
                        <span class="event-price">Sur invitation</span>
                        <a href="#" class="btn-event">Demander accès</a>
                    </div>
                </div>
            </div>
            
            <!-- Event 4 -->
            <div class="event-card">
                <div class="event-image">
                    <img src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600" alt="Rencontre">
                    <div class="event-date-badge">
                        <div class="day">10</div>
                        <div class="month">Fév</div>
                    </div>
                    <span class="event-type">Rencontre</span>
                </div>
                <div class="event-content">
                    <h3>Rencontre avec Amira Ganda</h3>
                    <div class="event-meta">
                        <span><i class="fas fa-clock"></i> 15h00 - 17h00</span>
                        <span><i class="fas fa-map-marker-alt"></i> Librairie Les Dépêches</span>
                    </div>
                    <div class="event-footer">
                        <span class="event-price free">Entrée libre</span>
                        <a href="#" class="btn-event">Réserver</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
// Countdown to next event
const eventDate = new Date('2025-02-15T19:00:00').getTime();

const countdown = setInterval(() => {
    const now = new Date().getTime();
    const distance = eventDate - now;
    
    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    document.getElementById('days').textContent = days;
    document.getElementById('hours').textContent = hours;
    document.getElementById('minutes').textContent = minutes;
    document.getElementById('seconds').textContent = seconds;
    
    if (distance < 0) {
        clearInterval(countdown);
        document.getElementById('days').textContent = '0';
        document.getElementById('hours').textContent = '0';
        document.getElementById('minutes').textContent = '0';
        document.getElementById('seconds').textContent = '0';
    }
}, 1000);
</script>
@endpush
