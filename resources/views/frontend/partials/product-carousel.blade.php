{{-- Carrousel d'images produit --}}
@if($product->images->count() > 0)
<div id="productCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
    {{-- Indicators --}}
    <div class="carousel-indicators">
        @foreach($product->images as $index => $image)
        <button type="button" 
                data-bs-target="#productCarousel" 
                data-bs-slide-to="{{ $index }}" 
                class="{{ $index === 0 ? 'active' : '' }}"
                aria-current="{{ $index === 0 ? 'true' : 'false' }}" 
                aria-label="Image {{ $index + 1 }}"></button>
        @endforeach
    </div>

    {{-- Slides --}}
    <div class="carousel-inner rounded-3 overflow-hidden">
        @foreach($product->images as $index => $image)
        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
            <img src="{{ asset('storage/' . $image->image_path) }}" 
                 class="d-block w-100" 
                 alt="{{ $product->title }}"
                 style="height: 500px; object-fit: cover;">
            @if($image->is_main)
            <div class="position-absolute top-0 start-0 m-3">
                <span class="badge bg-success">
                    <i class="fas fa-star me-1"></i>Image principale
                </span>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Controls --}}
    @if($product->images->count() > 1)
    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Précédent</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Suivant</span>
    </button>
    @endif
</div>

{{-- Thumbnails --}}
@if($product->images->count() > 1)
<div class="row g-2 mb-4">
    @foreach($product->images as $index => $image)
    <div class="col-3 col-md-2">
        <img src="{{ asset('storage/' . $image->image_path) }}" 
             class="img-thumbnail cursor-pointer" 
             alt="Miniature {{ $index + 1 }}"
             style="height: 80px; object-fit: cover; cursor: pointer;"
             onclick="document.querySelector('[data-bs-slide-to=\'{{ $index }}\']').click()">
    </div>
    @endforeach
</div>
@endif
@else
{{-- Fallback image principale --}}
@if($product->main_image)
<div class="mb-4">
    <img src="{{ asset('storage/' . $product->main_image) }}" 
         class="img-fluid rounded-3" 
         alt="{{ $product->title }}"
         style="max-height: 500px; width: 100%; object-fit: cover;">
</div>
@else
<div class="mb-4 bg-light rounded-3 d-flex align-items-center justify-content-center" style="height: 500px;">
    <div class="text-center text-muted">
        <i class="fas fa-image fa-4x mb-3"></i>
        <p>Aucune image disponible</p>
    </div>
</div>
@endif
@endif
