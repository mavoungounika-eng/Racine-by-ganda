{{-- Modal de succès avec animation logo R --}}
<div class="modal fade" id="successModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="background: linear-gradient(135deg, #1a0f09 0%, #2C1810 100%); border-radius: 20px; overflow: hidden;">
            <div class="modal-body text-center p-5">
                {{-- Animation logo R -- Désactivée --}}
                {{-- @include('components.racine-logo-animation', ['variant' => 'modal', 'theme' => 'dark']) --}}
                
                <h4 class="text-white mt-4 mb-3" style="font-family: 'Cormorant Garamond', serif; font-size: 1.8rem;">
                    {{ $title ?? 'Succès !' }}
                </h4>
                <p class="text-white-50 mb-4">
                    {{ $message ?? 'L\'opération a été effectuée avec succès.' }}
                </p>
                <button type="button" class="btn btn-primary px-5 py-2" data-dismiss="modal" style="background: #ED5F1E; border: none; border-radius: 25px;">
                    Continuer
                </button>
            </div>
        </div>
    </div>
</div>

