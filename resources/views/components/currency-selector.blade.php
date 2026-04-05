<div class="currency-selector dropdown">
    <button class="btn btn-link dropdown-toggle" type="button" id="currencySelector" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="currency-symbol">{{ current_currency_symbol() }}</span>
        <span class="currency-code">{{ session('currency', config('currency.default')) }}</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="currencySelector">
        @foreach(config('currency.supported') as $code)
            <li>
                <form action="{{ route('currency.switch') }}" method="POST">
                    @csrf
                    <input type="hidden" name="currency" value="{{ $code }}">
                    <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center @if(session('currency', config('currency.default')) == $code) active @endif">
                        <span>{{ $code }} ({{ config("currency.symbols.{$code}") }})</span>
                        @if(session('currency', config('currency.default')) == $code)
                            <i class="fas fa-check small"></i>
                        @endif
                    </button>
                </form>
            </li>
        @endforeach
    </ul>
</div>

<style>
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
