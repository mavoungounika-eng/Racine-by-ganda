@props(['product'])

<div class="product-reviews mt-5">
    <h4 class="mb-4">Avis clients ({{ $product->reviews_count }})</h4>

    @if($product->reviews_count > 0)
    <!-- Note moyenne -->
    <div class="mb-4">
        <div class="d-flex align-items-center mb-2">
            <strong class="mr-2">Note moyenne :</strong>
            <div class="rating">
                @for($i = 1; $i <= 5; $i++)
                    @if($i <= floor($product->average_rating))
                        <span class="icon-star text-warning"></span>
                    @elseif($i - 0.5 <= $product->average_rating)
                        <span class="icon-star-half text-warning"></span>
                    @else
                        <span class="icon-star-o text-muted"></span>
                    @endif
                @endfor
            </div>
            <span class="ml-2"><strong>{{ number_format($product->average_rating, 1) }}/5</strong></span>
        </div>
    </div>

    <!-- Liste des avis -->
    <div class="reviews-list">
        @foreach($product->reviews()->latest()->take(10)->get() as $review)
        <div class="review-item border-bottom pb-3 mb-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <strong>{{ $review->user->name ?? 'Client' }}</strong>
                    @if($review->is_verified_purchase)
                    <span class="badge badge-success ml-2">Achat vérifié</span>
                    @endif
                </div>
                <div class="rating">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="icon-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}"></span>
                    @endfor
                </div>
            </div>
            @if($review->comment)
            <p class="mb-2">{{ $review->comment }}</p>
            @endif
            <small class="text-muted">{{ $review->created_at->format('d/m/Y') }}</small>
        </div>
        @endforeach
    </div>
    @else
    <p class="text-muted">Aucun avis pour ce produit. Soyez le premier à laisser un avis !</p>
    @endif

    <!-- Formulaire d'avis (si utilisateur connecté et a acheté) -->
    @auth
    @php
        $hasPurchased = \App\Models\Order::where('user_id', auth()->id())
            ->where('payment_status', 'paid')
            ->whereHas('items', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->exists();
        
        $hasReviewed = \App\Models\Review::where('product_id', $product->id)
            ->where('user_id', auth()->id())
            ->exists();
    @endphp

    @if($hasPurchased && !$hasReviewed)
    <div class="review-form mt-4 border-top pt-4">
        <h5>Laisser un avis</h5>
        <form action="{{ route('reviews.store', $product) }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label>Note *</label>
                <div class="rating-input">
                    @for($i = 5; $i >= 1; $i--)
                    <input type="radio" name="rating" value="{{ $i }}" id="rating{{ $i }}" required>
                    <label for="rating{{ $i }}" class="icon-star"></label>
                    @endfor
                </div>
            </div>

            <div class="form-group">
                <label for="comment">Commentaire</label>
                <textarea name="comment" id="comment" class="form-control" rows="4" 
                          placeholder="Partagez votre expérience..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="icon-check mr-2"></i>
                Publier mon avis
            </button>
        </form>
    </div>
    @elseif($hasReviewed)
    <p class="text-muted mt-4">Vous avez déjà laissé un avis pour ce produit.</p>
    @else
    <p class="text-muted mt-4">Vous devez avoir acheté ce produit pour laisser un avis.</p>
    @endif
    @else
    <p class="text-muted mt-4">
        <a href="{{ route('login') }}">Connectez-vous</a> pour laisser un avis.
    </p>
    @endauth
</div>

@push('styles')
<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input label {
    font-size: 1.5rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.rating-input label:hover,
.rating-input label:hover ~ label,
.rating-input input[type="radio"]:checked ~ label {
    color: #ffc107;
}
</style>
@endpush

