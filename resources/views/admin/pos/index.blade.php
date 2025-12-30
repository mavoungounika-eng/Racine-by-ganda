@extends('layouts.admin')

@section('title', 'Point de Vente (POS)')
@section('page-title', 'Point de Vente - Boutique Physique')

@push('styles')
<style>
    .pos-container {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 1.5rem;
        height: calc(100vh - 200px);
    }

    .pos-left {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .pos-right {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 20px;
        padding: 1.5rem;
        overflow-y: auto;
    }

    .scan-section {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 20px;
        padding: 1.5rem;
    }

    .scan-input {
        background: rgba(22, 13, 12, 0.8);
        border: 2px solid rgba(212, 165, 116, 0.2);
        border-radius: 12px;
        padding: 1rem 1.25rem;
        color: #e2e8f0;
        font-size: 1.1rem;
        width: 100%;
        transition: all 0.3s;
    }

    .scan-input:focus {
        outline: none;
        border-color: #ED5F1E;
        box-shadow: 0 0 0 4px rgba(237, 95, 30, 0.1);
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        max-height: calc(100vh - 400px);
        overflow-y: auto;
        padding: 1rem 0;
    }

    .product-card {
        background: rgba(22, 13, 12, 0.6);
        border: 1px solid rgba(212, 165, 116, 0.1);
        border-radius: 12px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s;
    }

    .product-card:hover {
        border-color: #ED5F1E;
        transform: translateY(-2px);
    }

    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        border-bottom: 1px solid rgba(212, 165, 116, 0.1);
    }

    .cart-total {
        font-size: 1.5rem;
        font-weight: bold;
        color: #ED5F1E;
        padding: 1rem 0;
        border-top: 2px solid rgba(212, 165, 116, 0.2);
        margin-top: 1rem;
    }

    .btn-pos {
        background: linear-gradient(135deg, #ED5F1E 0%, #FFB800 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 1rem 2rem;
        font-weight: 600;
        width: 100%;
        margin-top: 1rem;
        transition: all 0.3s;
    }

    .btn-pos:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(237, 95, 30, 0.3);
    }

    .btn-pos:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .alert-pos {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1rem;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .quantity-btn {
        background: rgba(237, 95, 30, 0.2);
        border: 1px solid #ED5F1E;
        color: #ED5F1E;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .quantity-btn:hover {
        background: rgba(237, 95, 30, 0.3);
    }
</style>
@endpush

@section('content')
<div class="pos-container">
    <!-- Zone gauche : Scan et produits -->
    <div class="pos-left">
        <!-- Section Scan -->
        <div class="scan-section">
            <h3 class="text-white mb-3">
                <i class="fas fa-barcode text-warning mr-2"></i>
                Scanner un produit
            </h3>
            <input 
                type="text" 
                id="barcode-input" 
                class="scan-input" 
                placeholder="Scannez le code-barres ou entrez le SKU/ID"
                autofocus
            >
            <small class="text-muted mt-2 d-block">
                Le champ est en focus automatique pour faciliter le scan
            </small>
        </div>

        <!-- Liste des produits scannés -->
        <div class="scan-section" style="flex: 1;">
            <h3 class="text-white mb-3">
                <i class="fas fa-shopping-cart text-warning mr-2"></i>
                Panier (<span id="cart-count">0</span>)
            </h3>
            <div id="product-grid" class="product-grid">
                <p class="text-muted text-center">Aucun produit scanné</p>
            </div>
        </div>
    </div>

    <!-- Zone droite : Panier et paiement -->
    <div class="pos-right">
        <h3 class="text-white mb-3">
            <i class="fas fa-receipt text-warning mr-2"></i>
            Récapitulatif
        </h3>

        <div id="alert-container"></div>

        <div id="cart-items" class="mb-3">
            <p class="text-muted text-center">Panier vide</p>
        </div>

        <div class="cart-total">
            Total: <span id="cart-total">0</span> FCFA
        </div>

        <form id="pos-form">
            <div class="mb-3">
                <label class="text-white mb-2">Nom du client (optionnel)</label>
                <input type="text" name="customer_name" class="form-control" placeholder="Client boutique">
            </div>

            <div class="mb-3">
                <label class="text-white mb-2">Email (optionnel)</label>
                <input type="email" name="customer_email" class="form-control" placeholder="email@example.com">
            </div>

            <div class="mb-3">
                <label class="text-white mb-2">Téléphone (optionnel)</label>
                <input type="text" name="customer_phone" class="form-control" placeholder="+242 06 123 4567">
            </div>

            <div class="mb-3">
                <label class="text-white mb-2">Mode de paiement <span class="text-danger">*</span></label>
                <select name="payment_method" id="payment_method" class="form-control" required>
                    <option value="cash">💵 Espèces</option>
                    <option value="card">💳 Carte bancaire (TPE)</option>
                    <option value="mobile_money">📱 Mobile Money</option>
                </select>
            </div>

            {{-- Champ provider Mobile Money (affiché conditionnellement) --}}
            <div class="mb-3" id="mobile_money_provider_group" style="display: none;">
                <label class="text-white mb-2">Opérateur Mobile Money <span class="text-danger">*</span></label>
                <select name="mobile_money_provider" id="mobile_money_provider" class="form-control">
                    <option value="mtn_momo">MTN MoMo</option>
                    <option value="airtel_money">Airtel Money</option>
                </select>
                <small class="text-muted">Le numéro de téléphone est requis pour Mobile Money</small>
            </div>

            <button type="submit" class="btn-pos" id="submit-btn" disabled>
                <i class="fas fa-check mr-2"></i>
                Valider la vente
            </button>
        </form>
    </div>
</div>

<!-- Modal de confirmation -->
<div class="modal fade" id="success-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: rgba(22, 13, 12, 0.95); border: 1px solid rgba(212, 165, 116, 0.3);">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white">Commande créée avec succès !</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-white">
                <p>Numéro de commande: <strong id="order-number-display"></strong></p>
                <p>Total: <strong id="order-total-display"></strong> FCFA</p>
                <div id="payment-info" class="mt-3 p-3 rounded" style="background: rgba(237, 95, 30, 0.1); border: 1px solid rgba(237, 95, 30, 0.3);">
                    <p class="mb-0" id="payment-message"></p>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <a href="#" id="view-order-link" class="btn btn-primary">Voir la commande</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let cart = [];
const barcodeInput = document.getElementById('barcode-input');
const productGrid = document.getElementById('product-grid');
const cartItems = document.getElementById('cart-items');
const cartTotal = document.getElementById('cart-total');
const cartCount = document.getElementById('cart-count');
const submitBtn = document.getElementById('submit-btn');
const alertContainer = document.getElementById('alert-container');

// Gestion du scan
barcodeInput.addEventListener('keypress', async function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const code = this.value.trim();
        if (code) {
            await searchProduct(code);
            this.value = '';
        }
    }
});

async function searchProduct(code) {
    try {
        const response = await fetch('{{ route("admin.pos.search-product") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ code })
        });

        const data = await response.json();

        if (data.success) {
            addToCart(data.product);
            showAlert('success', 'Produit ajouté au panier');
        } else {
            showAlert('danger', data.message || 'Produit non trouvé');
        }
    } catch (error) {
        showAlert('danger', 'Erreur lors de la recherche');
        console.error(error);
    }
}

function addToCart(product) {
    const existing = cart.find(item => item.id === product.id);
    
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({
            ...product,
            quantity: 1
        });
    }
    
    updateCartDisplay();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartDisplay();
}

function updateQuantity(productId, delta) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        item.quantity = Math.max(1, item.quantity + delta);
        updateCartDisplay();
    }
}

function updateCartDisplay() {
    // Mettre à jour le compteur
    cartCount.textContent = cart.length;

    // Mettre à jour la grille de produits
    if (cart.length === 0) {
        productGrid.innerHTML = '<p class="text-muted text-center">Aucun produit scanné</p>';
    } else {
        productGrid.innerHTML = cart.map(item => `
            <div class="product-card">
                <div class="text-white mb-2">
                    <strong>${item.title}</strong>
                </div>
                <div class="text-muted small mb-2">
                    ${item.price.toLocaleString()} FCFA × ${item.quantity}
                </div>
                <div class="quantity-controls">
                    <button class="quantity-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                    <span class="text-white">${item.quantity}</span>
                    <button class="quantity-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                    <button class="quantity-btn ml-auto" onclick="removeFromCart(${item.id})" style="background: rgba(220, 53, 69, 0.2); border-color: #dc3545; color: #dc3545;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    // Mettre à jour le récapitulatif
    if (cart.length === 0) {
        cartItems.innerHTML = '<p class="text-muted text-center">Panier vide</p>';
        cartTotal.textContent = '0';
        submitBtn.disabled = true;
    } else {
        const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        cartTotal.textContent = total.toLocaleString();
        
        cartItems.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div>
                    <strong class="text-white">${item.title}</strong>
                    <div class="text-muted small">
                        ${item.price.toLocaleString()} FCFA × ${item.quantity} = ${(item.price * item.quantity).toLocaleString()} FCFA
                    </div>
                </div>
            </div>
        `).join('');
        
        submitBtn.disabled = false;
    }
}

function showAlert(type, message) {
    alertContainer.innerHTML = `
        <div class="alert alert-${type} alert-pos alert-dismissible fade show">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    setTimeout(() => {
        alertContainer.innerHTML = '';
    }, 3000);
}

// Gestion de l'affichage conditionnel du provider Mobile Money
document.getElementById('payment_method').addEventListener('change', function() {
    const mobileMoneyGroup = document.getElementById('mobile_money_provider_group');
    const phoneInput = document.querySelector('input[name="customer_phone"]');
    
    if (this.value === 'mobile_money') {
        mobileMoneyGroup.style.display = 'block';
        phoneInput.required = true;
    } else {
        mobileMoneyGroup.style.display = 'none';
        phoneInput.required = false;
    }
});

// Soumission du formulaire
document.getElementById('pos-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (cart.length === 0) {
        showAlert('warning', 'Le panier est vide');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Traitement...';

    const formData = new FormData(this);
    formData.append('items', JSON.stringify(cart.map(item => ({
        product_id: item.id,
        quantity: item.quantity
    }))));

    try {
        const response = await fetch('{{ route("admin.pos.create-order") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Afficher le modal de succès
            document.getElementById('order-number-display').textContent = data.order.order_number;
            document.getElementById('order-total-display').textContent = data.order.total.toLocaleString();
            document.getElementById('view-order-link').href = `/admin/orders/${data.order.id}`;
            
            // Afficher les informations de paiement
            const paymentInfo = document.getElementById('payment-info');
            const paymentMessage = document.getElementById('payment-message');
            
            if (data.payment) {
                let message = '';
                let bgColor = 'rgba(34, 197, 94, 0.1)';
                let borderColor = 'rgba(34, 197, 94, 0.3)';
                
                if (data.payment.status === 'paid') {
                    message = `<i class="fas fa-check-circle text-success mr-2"></i>${data.payment.message || 'Paiement confirmé'}`;
                } else if (data.payment.status === 'pending') {
                    message = `<i class="fas fa-clock text-warning mr-2"></i>${data.payment.message || 'Paiement en attente'}`;
                    bgColor = 'rgba(251, 191, 36, 0.1)';
                    borderColor = 'rgba(251, 191, 36, 0.3)';
                    
                    if (data.payment.transaction_id) {
                        message += `<br><small class="text-muted">Transaction ID: ${data.payment.transaction_id}</small>`;
                    }
                } else {
                    message = `<i class="fas fa-exclamation-circle text-danger mr-2"></i>${data.payment.message || 'Erreur de paiement'}`;
                    bgColor = 'rgba(239, 68, 68, 0.1)';
                    borderColor = 'rgba(239, 68, 68, 0.3)';
                }
                
                paymentMessage.innerHTML = message;
                paymentInfo.style.background = bgColor;
                paymentInfo.style.borderColor = borderColor;
                paymentInfo.style.display = 'block';
            } else {
                paymentInfo.style.display = 'none';
            }
            
            $('#success-modal').modal('show');
            
            // Réinitialiser le panier
            cart = [];
            updateCartDisplay();
            this.reset();
            
            // Remettre le focus sur le scan
            barcodeInput.focus();
        } else {
            showAlert('danger', data.message || 'Erreur lors de la création de la commande');
        }
    } catch (error) {
        showAlert('danger', 'Erreur lors de la création de la commande');
        console.error(error);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check mr-2"></i> Valider la vente';
    }
});

// Réinitialiser après fermeture du modal
$('#success-modal').on('hidden.bs.modal', function() {
    barcodeInput.focus();
});
</script>
@endpush

