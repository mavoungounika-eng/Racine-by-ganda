{{-- Animation de chargement avec logo R qui se décroche (LEGACY) --}}
{{-- Utiliser maintenant @include('components.racine-logo-animation', ['variant' => 'splash']) --}}
<div id="racine-loader" class="racine-loader">
    <div class="racine-loader-container">
        {{-- Lettre R principale qui se décroche --}}
        <div class="racine-logo-animated">
            <span class="racine-letter racine-letter-1">R</span>
            <span class="racine-letter racine-letter-2">A</span>
            <span class="racine-letter racine-letter-3">C</span>
            <span class="racine-letter racine-letter-4">I</span>
            <span class="racine-letter racine-letter-5">N</span>
            <span class="racine-letter racine-letter-6">E</span>
        </div>
        <div class="racine-loader-subtitle">Chargement...</div>
    </div>
</div>

<style>
/* ===== RACINE LOADER ANIMATION ===== */
.racine-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #160D0C 0%, #2C1810 50%, #1a0f09 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 1;
    transition: opacity 0.5s ease-out;
}

.racine-loader.hidden {
    opacity: 0;
    pointer-events: none;
}

.racine-loader-container {
    text-align: center;
}

.racine-logo-animated {
    display: inline-flex;
    gap: 0;
    font-family: 'Cormorant Garamond', serif;
    font-weight: 700;
    font-size: 5rem;
    letter-spacing: 0;
    position: relative;
}

.racine-letter {
    display: inline-block;
    color: #ED5F1E;
    text-shadow: 0 0 20px rgba(237, 95, 30, 0.5);
    transform-origin: center;
    transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

/* Animation : Les lettres se décrochent et rebondissent */
.racine-loader.active .racine-letter-1 {
    animation: decrocheR 1.2s ease-out 0s, floatR 2s ease-in-out 1.5s infinite;
    transform: translate(-50px, -30px) rotate(-15deg) scale(1.2);
}

.racine-loader.active .racine-letter-2 {
    animation: decrocheA 1.2s ease-out 0.1s, floatA 2s ease-in-out 1.6s infinite;
    transform: translate(-30px, -20px) rotate(-10deg) scale(1.1);
}

.racine-loader.active .racine-letter-3 {
    animation: decrocheC 1.2s ease-out 0.2s, floatC 2s ease-in-out 1.7s infinite;
    transform: translate(-10px, -15px) rotate(-5deg) scale(1.05);
}

.racine-loader.active .racine-letter-4 {
    animation: decrocheI 1.2s ease-out 0.3s, floatI 2s ease-in-out 1.8s infinite;
    transform: translate(10px, -15px) rotate(5deg) scale(1.05);
}

.racine-loader.active .racine-letter-5 {
    animation: decrocheN 1.2s ease-out 0.4s, floatN 2s ease-in-out 1.9s infinite;
    transform: translate(30px, -20px) rotate(10deg) scale(1.1);
}

.racine-loader.active .racine-letter-6 {
    animation: decrocheE 1.2s ease-out 0.5s, floatE 2s ease-in-out 2s infinite;
    transform: translate(50px, -30px) rotate(15deg) scale(1.2);
}

/* Animations de décrochage */
@keyframes decrocheR {
    0% {
        transform: translate(0, 0) rotate(0deg) scale(1);
        opacity: 1;
    }
    50% {
        transform: translate(-60px, -40px) rotate(-20deg) scale(1.3);
        opacity: 0.8;
    }
    100% {
        transform: translate(-50px, -30px) rotate(-15deg) scale(1.2);
        opacity: 1;
    }
}

@keyframes decrocheA {
    0% {
        transform: translate(0, 0) rotate(0deg) scale(1);
        opacity: 1;
    }
    50% {
        transform: translate(-40px, -30px) rotate(-15deg) scale(1.2);
        opacity: 0.8;
    }
    100% {
        transform: translate(-30px, -20px) rotate(-10deg) scale(1.1);
        opacity: 1;
    }
}

@keyframes decrocheC {
    0% {
        transform: translate(0, 0) rotate(0deg) scale(1);
        opacity: 1;
    }
    50% {
        transform: translate(-20px, -25px) rotate(-10deg) scale(1.15);
        opacity: 0.8;
    }
    100% {
        transform: translate(-10px, -15px) rotate(-5deg) scale(1.05);
        opacity: 1;
    }
}

@keyframes decrocheI {
    0% {
        transform: translate(0, 0) rotate(0deg) scale(1);
        opacity: 1;
    }
    50% {
        transform: translate(20px, -25px) rotate(10deg) scale(1.15);
        opacity: 0.8;
    }
    100% {
        transform: translate(10px, -15px) rotate(5deg) scale(1.05);
        opacity: 1;
    }
}

@keyframes decrocheN {
    0% {
        transform: translate(0, 0) rotate(0deg) scale(1);
        opacity: 1;
    }
    50% {
        transform: translate(40px, -30px) rotate(15deg) scale(1.2);
        opacity: 0.8;
    }
    100% {
        transform: translate(30px, -20px) rotate(10deg) scale(1.1);
        opacity: 1;
    }
}

@keyframes decrocheE {
    0% {
        transform: translate(0, 0) rotate(0deg) scale(1);
        opacity: 1;
    }
    50% {
        transform: translate(60px, -40px) rotate(20deg) scale(1.3);
        opacity: 0.8;
    }
    100% {
        transform: translate(50px, -30px) rotate(15deg) scale(1.2);
        opacity: 1;
    }
}

/* Animations de flottement continu */
@keyframes floatR {
    0%, 100% {
        transform: translate(-50px, -30px) rotate(-15deg) scale(1.2);
    }
    50% {
        transform: translate(-55px, -35px) rotate(-18deg) scale(1.25);
    }
}

@keyframes floatA {
    0%, 100% {
        transform: translate(-30px, -20px) rotate(-10deg) scale(1.1);
    }
    50% {
        transform: translate(-33px, -23px) rotate(-12deg) scale(1.12);
    }
}

@keyframes floatC {
    0%, 100% {
        transform: translate(-10px, -15px) rotate(-5deg) scale(1.05);
    }
    50% {
        transform: translate(-12px, -18px) rotate(-7deg) scale(1.08);
    }
}

@keyframes floatI {
    0%, 100% {
        transform: translate(10px, -15px) rotate(5deg) scale(1.05);
    }
    50% {
        transform: translate(12px, -18px) rotate(7deg) scale(1.08);
    }
}

@keyframes floatN {
    0%, 100% {
        transform: translate(30px, -20px) rotate(10deg) scale(1.1);
    }
    50% {
        transform: translate(33px, -23px) rotate(12deg) scale(1.12);
    }
}

@keyframes floatE {
    0%, 100% {
        transform: translate(50px, -30px) rotate(15deg) scale(1.2);
    }
    50% {
        transform: translate(55px, -35px) rotate(18deg) scale(1.25);
    }
}

/* Sous-titre */
.racine-loader-subtitle {
    margin-top: 2rem;
    color: rgba(237, 95, 30, 0.7);
    font-size: 1rem;
    font-weight: 300;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .racine-logo-animated {
        font-size: 3.5rem;
    }
    
    .racine-loader.active .racine-letter-1 {
        transform: translate(-35px, -20px) rotate(-12deg) scale(1.1);
    }
    
    .racine-loader.active .racine-letter-2 {
        transform: translate(-20px, -15px) rotate(-8deg) scale(1.05);
    }
    
    .racine-loader.active .racine-letter-3 {
        transform: translate(-8px, -10px) rotate(-4deg) scale(1.02);
    }
    
    .racine-loader.active .racine-letter-4 {
        transform: translate(8px, -10px) rotate(4deg) scale(1.02);
    }
    
    .racine-loader.active .racine-letter-5 {
        transform: translate(20px, -15px) rotate(8deg) scale(1.05);
    }
    
    .racine-loader.active .racine-letter-6 {
        transform: translate(35px, -20px) rotate(12deg) scale(1.1);
    }
    
    .racine-loader-subtitle {
        font-size: 0.85rem;
    }
}

/* Effet de particules (optionnel) */
.racine-loader::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 20% 50%, rgba(237, 95, 30, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(200, 162, 125, 0.1) 0%, transparent 50%);
    animation: backgroundShift 8s ease-in-out infinite;
}

@keyframes backgroundShift {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 0.8;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loader = document.getElementById('racine-loader');
    
    if (loader) {
        // Activer l'animation après un court délai
        setTimeout(() => {
            loader.classList.add('active');
        }, 100);
        
        // Masquer le loader quand la page est chargée
        window.addEventListener('load', function() {
            setTimeout(() => {
                loader.classList.add('hidden');
                // Supprimer complètement après la transition
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }, 800); // Délai minimal pour voir l'animation
        });
        
        // Fallback : masquer après 3 secondes maximum
        setTimeout(() => {
            if (!loader.classList.contains('hidden')) {
                loader.classList.add('hidden');
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }
        }, 3000);
    }
});
</script>

