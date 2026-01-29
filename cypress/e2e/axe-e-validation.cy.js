/**
 * ============================================
 * RACINE BY GANDA - AXE E Validation Tests
 * Cypress E2E Tests - JavaScript Cleanup
 * ============================================
 * 
 * Tests automatisés pour valider l'implémentation
 * AXE E (Phases B + C) : core JS, AJAX, scripts inline
 * 
 * Checklist source : VALIDATION_AXE_E.md
 * 
 * Usage:
 *   npx cypress run --spec "cypress/e2e/axe-e-validation.cy.js"
 *   npx cypress open (mode interactif)
 * ============================================
 */

// Configuration
const BASE_URL = Cypress.env('baseUrl') || 'http://localhost';

describe('AXE E — JavaScript Cleanup Validation', () => {

    // Hook global : vérification console avant chaque test
    beforeEach(() => {
        // Capturer erreurs console
        cy.on('window:before:load', (win) => {
            cy.spy(win.console, 'error').as('consoleError');
        });
    });

    // ============================================
    // TEST 0 : Console Globale (CRITIQUE)
    // ============================================

    context('TEST 0 — Console Globale & Namespace', () => {

        const pages = [
            { name: 'Home', url: '/' },
            { name: 'Boutique', url: '/boutique' },
            { name: 'Cart', url: '/cart' }
            // Note: /checkout nécessite authentification, skip pour tests automatisés
        ];

        pages.forEach(({ name, url }) => {
            it(`[${name}] Namespace Racine.* doit être disponible`, () => {
                cy.visit(url);

                // Vérifier window.Racine existe
                cy.window().its('Racine').should('exist');

                // Vérifier Racine.Utils existe et contient showNotification
                cy.window().its('Racine.Utils').should('exist');
                cy.window().its('Racine.Utils.showNotification').should('be.a', 'function');

                // Vérifier Racine.Ajax existe et contient addToCart
                cy.window().its('Racine.Ajax').should('exist');
                cy.window().its('Racine.Ajax.addToCart').should('be.a', 'function');
                cy.window().its('Racine.Ajax.toggleWishlist').should('be.a', 'function');

                // Vérifier alias global showNotification (rétrocompatibilité)
                cy.window().then((win) => {
                    expect(win.showNotification).to.be.a('function');
                });
            });

            it(`[${name}] Console ne doit contenir aucune erreur`, () => {
                cy.visit(url);
                cy.get('@consoleError').should('not.be.called');
            });
        });
    });

    // ============================================
    // TEST 1 : Shop (/shop)
    // ============================================

    context('TEST 1 — Boutique : Panier AJAX & Wishlist', () => {

        beforeEach(() => {
            cy.visit('/boutique');
        });

        it('Ajout au panier : AJAX, notification, badge mis à jour', () => {
            cy.intercept('POST', '**/cart/add').as('addToCart');

            cy.get('#cart-count-badge').then(($badge) => {
                const initialCount = parseInt($badge.text()) || 0;

                cy.get('.quick-add-form').first().submit();
                cy.wait('@addToCart').its('response.statusCode').should('eq', 200);
                cy.get('#cart-count-badge').should('contain', initialCount + 1);
            });

            cy.get('@consoleError').should('not.be.called');
        });

        it('Produit ajouté doit être présent dans /cart', () => {
            cy.intercept('POST', '**/cart/add').as('addToCart');
            cy.get('.quick-add-form').first().submit();
            cy.wait('@addToCart');

            cy.visit('/cart');
            cy.get('.cart-item').should('exist');
        });

        it('Toggle wishlist : icône far → fas', () => {
            cy.intercept('POST', '**/wishlist/toggle').as('toggleWishlist');

            cy.get('.wishlist-toggle-form').first().within(() => {
                cy.get('.wishlist-btn i').then(($icon) => {
                    const initialClass = $icon.hasClass('far') ? 'far' : 'fas';
                    cy.get('form').submit();
                    cy.wait('@toggleWishlist');

                    if (initialClass === 'far') {
                        cy.get('.wishlist-btn i').should('have.class', 'fas');
                    } else {
                        cy.get('.wishlist-btn i').should('have.class', 'far');
                    }
                });
            });

            cy.get('@consoleError').should('not.be.called');
        });
    });

    // ============================================
    // TEST 2 : Product (/product/{id})
    // ============================================

    context('TEST 2 — Product : Quantité, Panier, Wishlist, Tabs', () => {

        beforeEach(() => {
            cy.visit('/shop');
            cy.get('.product-card a').first().click();
            cy.url().should('include', '/product/');
        });

        it('Quantité : boutons +/- avec limites', () => {
            cy.get('#qtyInput').invoke('attr', 'max').then((maxStock) => {
                const max = parseInt(maxStock);

                cy.get('#qtyInput').should('have.value', '1');
                cy.window().invoke('changeQty', 1);
                cy.get('#qtyInput').should('have.value', '2');

                cy.window().invoke('changeQty', -1);
                cy.get('#qtyInput').should('have.value', '1');

                // Limite min
                cy.window().invoke('changeQty', -1);
                cy.get('#qtyInput').should('have.value', '1');
            });

            // Sync input hidden
            cy.get('#cartQty').should(($hidden) => {
                const qtyInputVal = Cypress.$('#qtyInput').val();
                expect($hidden.val()).to.equal(qtyInputVal);
            });
        });

        it('Sélection taille : classe active', () => {
            cy.get('.size-btn').then(($buttons) => {
                if ($buttons.length > 0) {
                    cy.get('.size-btn').first().click();
                    cy.get('.size-btn').first().should('have.class', 'active');

                    cy.get('.size-btn').eq(1).click();
                    cy.get('.size-btn').eq(1).should('have.class', 'active');
                    cy.get('.size-btn').first().should('not.have.class', 'active');
                }
            });
        });

        it('Ajout panier AJAX : loader → succès → badge', () => {
            cy.intercept('POST', '**/cart/add').as('addToCart');

            cy.get('#cart-count-badge').then(($badge) => {
                const initialCount = parseInt($badge.text()) || 0;

                cy.get('#add-to-cart-form').submit();
                cy.get('#add-to-cart-btn').should('contain', 'Ajout');

                cy.wait('@addToCart').its('response.statusCode').should('eq', 200);
                cy.get('#add-to-cart-btn').should('contain', 'Ajouté');
                cy.get('#cart-count-badge', { timeout: 3000 }).should('contain', initialCount + 1);

                cy.wait(2500);
                cy.get('#add-to-cart-btn').should('contain', 'Ajouter au panier');
            });
        });

        it('Tabs : changement de contenu', () => {
            cy.get('.tab-btn').then(($tabs) => {
                if ($tabs.length > 1) {
                    cy.get('.tab-btn[data-tab="specs"]').click();
                    cy.get('.tab-btn[data-tab="specs"]').should('have.class', 'active');
                    cy.get('#specs').should('have.class', 'active');

                    cy.get('.tab-btn[data-tab="description"]').click();
                    cy.get('.tab-btn[data-tab="description"]').should('have.class', 'active');
                    cy.get('#description').should('have.class', 'active');
                }
            });
        });

        it('Gallery : changement image principale', () => {
            cy.get('.thumbnail').then(($thumbs) => {
                if ($thumbs.length > 1) {
                    cy.get('#mainImage').invoke('attr', 'src').then((initialSrc) => {
                        cy.get('.thumbnail').eq(1).click();
                        cy.get('#mainImage').invoke('attr', 'src').should('not.equal', initialSrc);
                        cy.get('.thumbnail').eq(1).should('have.class', 'active');
                    });
                }
            });
        });

        it('Console propre', () => {
            cy.get('@consoleError').should('not.be.called');
        });
    });

    // ============================================
    // TEST 3 : Cart (/cart)
    // ============================================

    context('TEST 3 — Cart : Quantité Updates', () => {

        before(() => {
            cy.visit('/boutique');
            cy.get('.quick-add-form').first().submit();
            cy.wait(1000);
        });

        beforeEach(() => {
            cy.visit('/cart');
        });

        it('Boutons +/- changent quantité et soumettent', () => {
            cy.get('.cart-item').should('exist');

            cy.get('.qty-input').first().invoke('val').then((initialQty) => {
                const initial = parseInt(initialQty);

                cy.get('.qty-btn').contains('+').first().click();
                cy.url().should('include', '/cart');
                cy.get('.qty-input').first().should('have.value', (initial + 1).toString());
            });
        });

        it('Quantité persiste après reload', () => {
            cy.get('.qty-input').first().invoke('val').then((currentQty) => {
                cy.reload();
                cy.get('.qty-input').first().should('have.value', currentQty);
            });
        });

        it('Console propre', () => {
            cy.get('@consoleError').should('not.be.called');
        });
    });

    // ============================================
    // TEST 4 : Checkout (/checkout)
    // ============================================

    // TEST 4 SKIPPED: /checkout nécessite authentification Laravel
    // Validation manuelle requise
    context.skip('TEST 4 — Checkout : Shipping Calculator (REQUIRES AUTH)', () => {

        before(() => {
            cy.visit('/boutique');
            cy.get('.quick-add-form').first().submit();
            cy.wait(1000);
        });

        beforeEach(() => {
            cy.visit('/checkout');
        });

        it('Domicile → 2 000 FCFA, total recalculé', () => {
            cy.get('#total-display').invoke('attr', 'data-subtotal').then((subtotal) => {
                const sub = parseInt(subtotal);

                cy.get('input[value="home_delivery"]').check();
                cy.get('#shipping-cost-display').should('contain', '2 000 FCFA');

                const expectedTotal = (sub + 2000).toLocaleString('fr-FR');
                cy.get('#total-display').should('contain', expectedTotal);
            });
        });

        it('Showroom → Gratuit, total recalculé', () => {
            cy.get('#total-display').invoke('attr', 'data-subtotal').then((subtotal) => {
                const sub = parseInt(subtotal);

                cy.get('input[value="showroom_pickup"]').check();
                cy.get('#shipping-cost-display').should('match', /0|Gratuit/i);

                const expectedTotal = sub.toLocaleString('fr-FR');
                cy.get('#total-display').should('contain', expectedTotal);
            });
        });

        it('Changement dynamique sans reload', () => {
            cy.get('input[value="home_delivery"]').check();
            cy.get('#shipping-cost-display').should('contain', '2 000');

            cy.get('input[value="showroom_pickup"]').check();
            cy.get('#shipping-cost-display').should('match', /0|Gratuit/i);

            cy.url().should('include', '/checkout');
        });

        it('Format FCFA correct', () => {
            cy.get('input[value="home_delivery"]').check();
            cy.get('#shipping-cost-display').invoke('text').should('match', /2\s000\sFCFA/);
        });

        it('Console propre', () => {
            cy.get('@consoleError').should('not.be.called');
        });
    });

    // ============================================
    // Rapport Final
    // ============================================

    after(() => {
        cy.log('✅ AXE E Validation Tests COMPLÉTÉS');
        cy.log('Vérifications manuelles requises :');
        cy.log('- Animations visuelles (badge panier scale)');
        cy.log('- Notifications toast (apparence, position)');
        cy.log('- Styles visuels (couleurs, transitions)');
    });
});
