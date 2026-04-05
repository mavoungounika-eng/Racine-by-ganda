{{-- Galerie d'Images --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-images me-2"></i>Galerie d'Images
        </h5>
    </div>
    <div class="card-body">
        {{-- Upload Form --}}
        <form action="{{ route('admin.products.images.upload', $product) }}" 
              method="POST" 
              enctype="multipart/form-data" 
              class="mb-4">
            @csrf
            <div class="mb-3">
                <label for="images" class="form-label">Ajouter des images (max 10)</label>
                <input type="file" 
                       class="form-control" 
                       id="images" 
                       name="images[]" 
                       multiple 
                       accept="image/*" 
                       required>
                <small class="text-muted">Formats acceptés : JPEG, PNG, JPG, WEBP (max 2MB par image)</small>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload me-2"></i>Télécharger
            </button>
        </form>

        {{-- Images Grid --}}
        @if($product->images->count() > 0)
        <div id="images-grid" class="row g-3">
            @foreach($product->images as $image)
            <div class="col-md-3" data-image-id="{{ $image->id }}">
                <div class="card h-100 position-relative">
                    {{-- Image principale badge --}}
                    @if($image->is_main)
                    <div class="position-absolute top-0 start-0 m-2">
                        <span class="badge bg-success">
                            <i class="fas fa-star me-1"></i>Principale
                        </span>
                    </div>
                    @endif

                    {{-- Image --}}
                    <img src="{{ asset('storage/' . $image->image_path) }}" 
                         class="card-img-top" 
                         alt="Image produit"
                         style="height: 200px; object-fit: cover;">

                    {{-- Actions --}}
                    <div class="card-body p-2">
                        <div class="btn-group w-100" role="group">
                            {{-- Set as main --}}
                            @if(!$image->is_main)
                            <form action="{{ route('admin.products.images.set-main', [$product, $image]) }}" 
                                  method="POST" 
                                  class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="btn btn-sm btn-outline-primary" 
                                        title="Définir comme principale">
                                    <i class="fas fa-star"></i>
                                </button>
                            </form>
                            @endif

                            {{-- Delete --}}
                            <form action="{{ route('admin.products.images.destroy', [$product, $image]) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('Supprimer cette image ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="btn btn-sm btn-outline-danger" 
                                        title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Sortable.js pour réorganiser --}}
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const grid = document.getElementById('images-grid');
                if (grid) {
                    new Sortable(grid, {
                        animation: 150,
                        handle: '.card',
                        onEnd: function(evt) {
                            // Récupérer le nouvel ordre
                            const order = Array.from(grid.children).map(el => 
                                el.getAttribute('data-image-id')
                            );

                            // Envoyer au serveur
                            fetch('{{ route("admin.products.images.reorder", $product) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ order: order })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Ordre mis à jour avec succès
                                }
                            });
                        }
                    });
                }
            });
        </script>
        @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Aucune image pour ce produit. Ajoutez-en pour créer une galerie.
        </div>
        @endif
    </div>
</div>
