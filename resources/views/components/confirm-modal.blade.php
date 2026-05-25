{{-- Composant modal de confirmation universel --}}
{{-- Usage JS : ConfirmModal.show({ title, message, confirmText, confirmClass, onConfirm }) --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:1px solid #ED5F1E;border-radius:12px;overflow:hidden;">
            <div class="modal-header" style="background:#160D0C;border-bottom:1px solid #ED5F1E;">
                <h5 class="modal-title text-white fw-semibold" id="confirmModalTitle">Confirmation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="confirmModalMessage" style="color:#160D0C;padding:1.5rem;"></div>
            <div class="modal-footer" style="border-top:1px solid rgba(22,13,12,0.1);">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn" id="confirmModalBtn">Confirmer</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
window.ConfirmModal = {
    _modal: null,
    show({ title = 'Confirmation', message = '', confirmText = 'Confirmer', confirmClass = 'btn-danger', onConfirm = () => {} } = {}) {
        if (!this._modal) {
            this._modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        }
        document.getElementById('confirmModalTitle').textContent = title;
        document.getElementById('confirmModalMessage').textContent = message;
        const btn = document.getElementById('confirmModalBtn');
        btn.textContent = confirmText;
        btn.className = 'btn ' + confirmClass;
        btn.onclick = () => { this._modal.hide(); onConfirm(); };
        this._modal.show();
    }
};
</script>
@endpush
