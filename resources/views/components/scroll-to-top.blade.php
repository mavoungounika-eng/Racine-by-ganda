{{-- SCROLL TO TOP BUTTON - RACINE BY GANDA --}}
<button id="scroll-to-top" 
        class="scroll-to-top-btn" 
        aria-label="Retour en haut de la page"
        title="Retour en haut">
    <i class="fas fa-arrow-up"></i>
</button>

<style>
    /* ===== SCROLL TO TOP BUTTON RACINE BY GANDA ===== */
    .scroll-to-top-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        color: #160D0C;
        font-size: 18px;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        box-shadow: 0 4px 15px rgba(237, 95, 30, 0.4);
        transition: all 0.3s ease;
        opacity: 0;
        transform: translateY(20px);
    }
    
    .scroll-to-top-btn.show {
        display: flex;
        opacity: 1;
        transform: translateY(0);
    }
    
    .scroll-to-top-btn:hover {
        background: linear-gradient(135deg, #FFB800 0%, #ED5F1E 100%);
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(237, 95, 30, 0.6);
    }
    
    .scroll-to-top-btn:active {
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(237, 95, 30, 0.5);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .scroll-to-top-btn {
            width: 45px;
            height: 45px;
            bottom: 20px;
            right: 20px;
            font-size: 16px;
        }
    }
</style>

<script>
    (function() {
        const scrollBtn = document.getElementById('scroll-to-top');
        
        if (!scrollBtn) return;
        
        // Afficher/masquer le bouton selon le scroll
        function toggleScrollButton() {
            if (window.pageYOffset > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        }
        
        // Scroll vers le haut
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Écouter le scroll
        window.addEventListener('scroll', toggleScrollButton);
        
        // Vérifier au chargement
        toggleScrollButton();
    })();
</script>


