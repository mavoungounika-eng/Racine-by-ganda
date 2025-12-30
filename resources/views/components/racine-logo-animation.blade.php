{{-- ============================================ --}}
{{-- RACINE LOGO ANIMATION PREMIUM --}}
{{-- Animation du logo "R" avec segments décomposés --}}
{{-- ============================================ --}}

<div class="racine-logo-anim-container" data-variant="{{ $variant ?? 'splash' }}" data-theme="{{ $theme ?? 'dark' }}">
    <svg class="racine-logo-svg" viewBox="0 0 200 240" xmlns="http://www.w3.org/2000/svg">
        {{-- Segment 1: Trait vertical gauche (Orange) --}}
        <path class="racine-segment racine-segment-1" 
              d="M 40 30 L 40 180" 
              stroke="#ED5F1E" 
              stroke-width="18" 
              stroke-linecap="round"
              fill="none"
              stroke-dasharray="150"
              stroke-dashoffset="150"/>
        
        {{-- Segment 2: Barre horizontale supérieure (Jaune) --}}
        <path class="racine-segment racine-segment-2" 
              d="M 40 70 L 140 70" 
              stroke="#FFB800" 
              stroke-width="16" 
              stroke-linecap="round"
              fill="none"
              stroke-dasharray="100"
              stroke-dashoffset="100"/>
        
        {{-- Segment 3: Diagonale centrale (Orange) --}}
        <path class="racine-segment racine-segment-3" 
              d="M 40 110 L 120 180" 
              stroke="#ED5F1E" 
              stroke-width="18" 
              stroke-linecap="round"
              fill="none"
              stroke-dasharray="110"
              stroke-dashoffset="110"/>
        
        {{-- Segment 4: Courbe droite supérieure (Blanc) --}}
        <path class="racine-segment racine-segment-4" 
              d="M 80 50 Q 140 50 160 90" 
              stroke="#FFFFFF" 
              stroke-width="14" 
              stroke-linecap="round"
              fill="none"
              stroke-dasharray="90"
              stroke-dashoffset="90"/>
        
        {{-- Segment 5: Petite jambe droite (Orange foncé) --}}
        <path class="racine-segment racine-segment-5" 
              d="M 120 150 L 120 180" 
              stroke="#ED5F1E" 
              stroke-width="14" 
              stroke-linecap="round"
              fill="none"
              stroke-dasharray="30"
              stroke-dashoffset="30"/>
        
        {{-- Glow effect --}}
        <defs>
            <filter id="racine-glow">
                <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
                <feMerge>
                    <feMergeNode in="coloredBlur"/>
                    <feMergeNode in="SourceGraphic"/>
                </feMerge>
            </filter>
            <filter id="racine-glow-strong">
                <feGaussianBlur stdDeviation="8" result="coloredBlur"/>
                <feMerge>
                    <feMergeNode in="coloredBlur"/>
                    <feMergeNode in="SourceGraphic"/>
                </feMerge>
            </filter>
        </defs>
    </svg>
    
    {{-- Background pattern africain subtil --}}
    <div class="racine-pattern-overlay"></div>
    
    {{-- Glassmorphism overlay --}}
    <div class="racine-glass-overlay"></div>
</div>

<style>
/* ===== RACINE LOGO ANIMATION PREMIUM ===== */

.racine-logo-anim-container {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    min-height: 200px;
    overflow: hidden;
}

.racine-logo-svg {
    width: 200px;
    height: 240px;
    filter: url(#racine-glow);
    position: relative;
    z-index: 2;
}

/* Variante Splash Screen */
.racine-logo-anim-container[data-variant="splash"] {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: linear-gradient(135deg, #0a0605 0%, #160D0C 50%, #1a0f09 100%);
    z-index: 99999;
}

/* Variante Hover */
.racine-logo-anim-container[data-variant="hover"] {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80px;
    height: 96px;
    min-height: auto;
    pointer-events: none;
}

.racine-logo-anim-container[data-variant="hover"] .racine-logo-svg {
    width: 80px;
    height: 96px;
}

/* Variante Background */
.racine-logo-anim-container[data-variant="background"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.04;
    pointer-events: none;
    z-index: 0;
}

.racine-logo-anim-container[data-variant="background"] .racine-logo-svg {
    width: 400px;
    height: 480px;
    opacity: 0.3;
}

/* Variante Spinner AJAX */
.racine-logo-anim-container[data-variant="spinner"] {
    width: 60px;
    height: 72px;
    min-height: auto;
    margin: 20px auto;
}

.racine-logo-anim-container[data-variant="spinner"] .racine-logo-svg {
    width: 60px;
    height: 72px;
    animation: spinR 2s ease-in-out infinite;
}

/* Variante Modal */
.racine-logo-anim-container[data-variant="modal"] {
    width: 120px;
    height: 144px;
    min-height: auto;
    margin: 20px auto 30px;
}

.racine-logo-anim-container[data-variant="modal"] .racine-logo-svg {
    width: 120px;
    height: 144px;
}

/* Animations des segments */
.racine-segment {
    filter: url(#racine-glow);
    transition: all 0.3s ease;
}

.racine-logo-anim-container.active .racine-segment-1 {
    animation: drawSegment1 1s ease-out forwards;
}

.racine-logo-anim-container.active .racine-segment-2 {
    animation: drawSegment2 0.8s ease-out 0.2s forwards;
}

.racine-logo-anim-container.active .racine-segment-3 {
    animation: drawSegment3 1s ease-out 0.4s forwards;
}

.racine-logo-anim-container.active .racine-segment-4 {
    animation: drawSegment4 0.9s ease-out 0.6s forwards;
}

.racine-logo-anim-container.active .racine-segment-5 {
    animation: drawSegment5 0.6s ease-out 0.8s forwards;
}

/* Dessin des segments */
@keyframes drawSegment1 {
    to {
        stroke-dashoffset: 0;
        filter: url(#racine-glow-strong);
    }
}

@keyframes drawSegment2 {
    to {
        stroke-dashoffset: 0;
        filter: url(#racine-glow-strong);
    }
}

@keyframes drawSegment3 {
    to {
        stroke-dashoffset: 0;
        filter: url(#racine-glow-strong);
    }
}

@keyframes drawSegment4 {
    to {
        stroke-dashoffset: 0;
        filter: url(#racine-glow-strong);
    }
}

@keyframes drawSegment5 {
    to {
        stroke-dashoffset: 0;
        filter: url(#racine-glow-strong);
    }
}

/* Animation de pulsation après dessin */
.racine-logo-anim-container.animated .racine-segment-1,
.racine-logo-anim-container.animated .racine-segment-2,
.racine-logo-anim-container.animated .racine-segment-3 {
    animation: pulseSegment 2s ease-in-out infinite;
}

@keyframes pulseSegment {
    0%, 100% {
        opacity: 1;
        filter: url(#racine-glow-strong);
    }
    50% {
        opacity: 0.8;
        filter: url(#racine-glow);
    }
}

/* Spinner rotation */
@keyframes spinR {
    0%, 100% {
        transform: rotate(0deg);
        opacity: 1;
    }
    50% {
        transform: rotate(180deg);
        opacity: 0.7;
    }
}

/* Hover effect */
.racine-logo-anim-container[data-variant="hover"].hover-active .racine-segment {
    animation: vibrateSegment 0.6s ease-out;
}

@keyframes vibrateSegment {
    0%, 100% {
        transform: translate(0, 0);
        filter: url(#racine-glow);
    }
    25% {
        transform: translate(2px, -2px);
        filter: url(#racine-glow-strong);
    }
    50% {
        transform: translate(-2px, 2px);
        filter: url(#racine-glow-strong);
    }
    75% {
        transform: translate(2px, 2px);
        filter: url(#racine-glow-strong);
    }
}

/* Background pattern africain */
.racine-pattern-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.03;
    background-image: 
        repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(237, 95, 30, 0.1) 10px, rgba(237, 95, 30, 0.1) 20px),
        repeating-linear-gradient(-45deg, transparent, transparent 10px, rgba(255, 184, 0, 0.1) 10px, rgba(255, 184, 0, 0.1) 20px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 1;
}

/* Glassmorphism overlay */
.racine-glass-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.02);
    backdrop-filter: blur(1px);
    pointer-events: none;
    z-index: 1;
}

/* Mode clair */
.racine-logo-anim-container[data-theme="light"] {
    background: linear-gradient(135deg, #f8f6f3 0%, #e8e5e0 50%, #f5f3f0 100%);
}

.racine-logo-anim-container[data-theme="light"] .racine-pattern-overlay {
    opacity: 0.02;
}

.racine-logo-anim-container[data-theme="light"] .racine-glass-overlay {
    background: rgba(0, 0, 0, 0.01);
}

/* Responsive */
@media (max-width: 768px) {
    .racine-logo-svg {
        width: 150px;
        height: 180px;
    }
    
    .racine-logo-anim-container[data-variant="splash"] .racine-logo-svg {
        width: 150px;
        height: 180px;
    }
    
    .racine-logo-anim-container[data-variant="hover"] .racine-logo-svg {
        width: 60px;
        height: 72px;
    }
}

/* Masquage progressif */
.racine-logo-anim-container.fade-out {
    animation: fadeOut 0.5s ease-out forwards;
}

@keyframes fadeOut {
    to {
        opacity: 0;
        visibility: hidden;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const containers = document.querySelectorAll('.racine-logo-anim-container');
    
    containers.forEach(container => {
        const variant = container.getAttribute('data-variant');
        
        // Activation automatique selon la variante
        if (variant === 'splash') {
            // Splash screen: animation immédiate
            setTimeout(() => {
                container.classList.add('active');
            }, 100);
            
            // Animation complète après dessin
            setTimeout(() => {
                container.classList.add('animated');
            }, 1800);
            
            // Masquage après 2 secondes
            setTimeout(() => {
                container.classList.add('fade-out');
                setTimeout(() => {
                    container.style.display = 'none';
                }, 500);
            }, 2000);
        } else if (variant === 'hover') {
            // Hover: activer au survol du parent
            const parent = container.parentElement;
            if (parent) {
                parent.addEventListener('mouseenter', () => {
                    container.classList.add('hover-active');
                    container.classList.add('active');
                });
                parent.addEventListener('mouseleave', () => {
                    container.classList.remove('hover-active');
                });
            }
        } else if (variant === 'background') {
            // Background: animation continue subtile
            container.classList.add('active');
            container.classList.add('animated');
        } else if (variant === 'spinner') {
            // Spinner AJAX: animation continue
            container.classList.add('active');
            container.classList.add('animated');
        } else {
            // Par défaut: activer immédiatement
            container.classList.add('active');
            setTimeout(() => {
                container.classList.add('animated');
            }, 1500);
        }
    });
});

// Fonction globale pour activer l'animation
window.racineLogoAnimation = {
    show: function(variant = 'splash') {
        const container = document.querySelector(`.racine-logo-anim-container[data-variant="${variant}"]`);
        if (container) {
            container.style.display = 'flex';
            container.classList.remove('fade-out');
            container.classList.add('active');
            setTimeout(() => {
                container.classList.add('animated');
            }, 1500);
        }
    },
    
    hide: function(variant = 'splash') {
        const container = document.querySelector(`.racine-logo-anim-container[data-variant="${variant}"]`);
        if (container) {
            container.classList.add('fade-out');
            setTimeout(() => {
                container.style.display = 'none';
            }, 500);
        }
    }
};
</script>


