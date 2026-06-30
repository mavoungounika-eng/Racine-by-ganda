{{-- Modal de confirmation universel (JS + form POST) --}}
{{-- Usage JS : ConfirmModal.show({ title, message, consequence, confirmText, onConfirm }) --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0" style="border-radius:1rem;overflow:hidden;">
            <div class="modal-header border-0 pb-0" style="background:#fef2f2;padding:1.25rem 1.5rem .75rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <div style="width:40px;height:40px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="20" height="20" fill="#dc2626" viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                    </div>
                    <h5 class="modal-title mb-0 fw-bold" id="confirmModalTitle" style="font-size:1rem;color:#160D0C;">Confirmation</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" style="padding:1rem 1.5rem;">
                <p id="confirmModalMessage" style="color:#555;font-size:.9rem;margin:0 0 .75rem;"></p>
                <div id="confirmModalConsequence" style="display:none;align-items:center;gap:.5rem;padding:.6rem .75rem;background:#fff7ed;border-radius:.5rem;border:1px solid #fed7aa;">
                    <svg width="16" height="16" fill="#ea580c" style="flex-shrink:0" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    <span id="confirmModalConsequenceText" style="font-size:.8rem;color:#9a3412;font-weight:500;"></span>
                </div>
            </div>
            <div class="modal-footer border-0" style="padding:.75rem 1.5rem 1.25rem;gap:.5rem;">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:.5rem;font-size:.875rem;">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmModalBtn" style="border-radius:.5rem;font-size:.875rem;">Confirmer</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ csp_nonce() }}">
window.ConfirmModal = {
    _modal: null,
    show({ title = 'Confirmer cette action ?', message = '', consequence = '', confirmText = 'Confirmer', confirmClass = 'btn-danger', onConfirm = () => {} } = {}) {
        if (!this._modal) {
            this._modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        }
        document.getElementById('confirmModalTitle').textContent = title;
        document.getElementById('confirmModalMessage').textContent = message;

        const cEl = document.getElementById('confirmModalConsequence');
        if (consequence) {
            document.getElementById('confirmModalConsequenceText').textContent = consequence;
            cEl.style.display = 'flex';
        } else {
            cEl.style.display = 'none';
        }

        const btn = document.getElementById('confirmModalBtn');
        btn.textContent = confirmText;
        btn.className = 'btn ' + confirmClass;
        btn.onclick = () => { this._modal.hide(); onConfirm(); };
        this._modal.show();
    }
};
</script>
@endpush
