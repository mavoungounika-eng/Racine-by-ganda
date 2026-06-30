<div class="currency-selector dropdown">
    <button class="btn btn-link dropdown-toggle" type="button" id="currencySelector" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="currency-symbol">{{ current_currency_symbol() }}</span>
        <span class="currency-code">{{ session('currency', config('currency.default')) }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="currencySelector">
        @foreach(config('currency.supported') as $code)
            <li>
                <button type="button"
                        class="dropdown-item d-flex justify-content-between align-items-center @if(session('currency', config('currency.default')) == $code) active @endif"
                        onclick="switchCurrency('{{ $code }}')">
                    <span>{{ $code }} ({{ config("currency.symbols.{$code}") }})</span>
                    @if(session('currency', config('currency.default')) == $code)
                        <i class="fas fa-check small"></i>
                    @endif
                </button>
            </li>
        @endforeach
    </ul>
</div>

<style nonce="{{ csp_nonce() }}">
.currency-selector .btn-link {
    color: var(--text-color, #333);
    text-decoration: none;
    font-weight: 500;
    padding: 0.5rem;
}
.currency-selector .dropdown-item.active {
    background-color: var(--primary-color, #007bff);
}
.currency-selector .currency-symbol {
    margin-right: 2px;
}
</style>

<script nonce="{{ csp_nonce() }}">
function switchCurrency(code) {
    fetch('{{ route('currency.switch') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ currency: code }),
    })
    .then(r => r.ok ? window.location.reload() : Promise.reject(r))
    .catch(() => window.location.reload());
}
</script>
