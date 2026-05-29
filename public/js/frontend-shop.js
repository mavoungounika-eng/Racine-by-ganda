/* ===================================================
   RACINE BY GANDA - Frontend Shop JavaScript
   Page boutique - Scripts extraits de shop.blade.php
   Version: 2.1 (feedback toast ajout panier)
   =================================================== */

document.addEventListener('DOMContentLoaded', function () {
    // View toggle
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const grid = document.getElementById('productsGrid');
            if (this.dataset.view === 'list') {
                grid.classList.add('list-view');
            } else {
                grid.classList.remove('list-view');
            }
        });
    });

    // Filter collapse
    document.querySelectorAll('.filter-title').forEach(title => {
        title.addEventListener('click', function () {
            this.classList.toggle('collapsed');
            const options = this.nextElementSibling;
            if (options) {
                options.style.display = this.classList.contains('collapsed') ? 'none' : 'flex';
            }
        });
    });

    // AJAX - Ajout au panier (utilise Racine.Ajax)
    document.querySelectorAll('.quick-add-form').forEach(form => {
        if (window.Racine && window.Racine.Ajax) {
            window.Racine.Ajax.handleFormSubmit(form, {
                onSuccess: (data) => {
                    if (data.count !== undefined) {
                        window.Racine.Ajax.updateCartCount(data.count);
                    }
                    window.Racine.Utils.showNotification(
                        data.message || 'Produit ajouté au panier !',
                        'success'
                    );
                },
                onError: (data) => {
                    window.Racine.Utils.showNotification(
                        data.message || 'Impossible d\'ajouter ce produit. Réessaie.',
                        'error'
                    );
                },
                loadingText: 'Ajout...'
            });
        } else {
            console.warn('[Shop] Racine.Ajax not loaded, using fallback');
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                alert('Veuillez recharger la page (core/ajax.js non chargé)');
            });
        }
    });

    // Wishlist toggle (utilise Racine.Ajax)
    document.querySelectorAll('.wishlist-toggle-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const productId = formData.get('product_id');
            const button = this.querySelector('.wishlist-btn');

            if (window.Racine && window.Racine.Ajax) {
                // toggleWishlist affiche déjà son propre toast via data.message
                window.Racine.Ajax.toggleWishlist(productId, button);
            } else {
                console.warn('[Shop] Racine.Ajax not loaded');
            }
        });
    });
});
