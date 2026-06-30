{{--
    Composant flash messages Bootstrap 5.
    Inclure une fois dans chaque layout juste après <body> ou avant le contenu principal.
    Usage : <x-flash />
--}}

@if(session()->hasAny(['success', 'error', 'warning', 'info']))
<div id="flash-container" style="position:fixed;top:1.25rem;right:1.25rem;z-index:10000;min-width:300px;max-width:420px;display:flex;flex-direction:column;gap:.5rem;" role="alert" aria-live="polite">

    @if(session('success'))
    <div class="d-flex align-items-start gap-3 p-3 rounded-3 shadow-sm" style="background:#f0fdf4;border:1.5px solid #86efac;">
        <svg width="20" height="20" fill="#15803d" style="flex-shrink:0;margin-top:2px" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/><path d="M10 17l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
        <div style="flex:1;font-size:.875rem;color:#15803d;font-weight:500;">{{ session('success') }}</div>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#15803d;font-size:1.1rem;line-height:1;padding:0;">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="d-flex align-items-start gap-3 p-3 rounded-3 shadow-sm" style="background:#fef2f2;border:1.5px solid #fca5a5;">
        <svg width="20" height="20" fill="#dc2626" style="flex-shrink:0;margin-top:2px" viewBox="0 0 24 24"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>
        <div style="flex:1;font-size:.875rem;color:#dc2626;font-weight:500;">{{ session('error') }}</div>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#dc2626;font-size:1.1rem;line-height:1;padding:0;">&times;</button>
    </div>
    @endif

    @if(session('warning'))
    <div class="d-flex align-items-start gap-3 p-3 rounded-3 shadow-sm" style="background:#fffbeb;border:1.5px solid #fcd34d;">
        <svg width="20" height="20" fill="#d97706" style="flex-shrink:0;margin-top:2px" viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
        <div style="flex:1;font-size:.875rem;color:#92400e;font-weight:500;">{{ session('warning') }}</div>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#92400e;font-size:1.1rem;line-height:1;padding:0;">&times;</button>
    </div>
    @endif

    @if(session('info'))
    <div class="d-flex align-items-start gap-3 p-3 rounded-3 shadow-sm" style="background:#eff6ff;border:1.5px solid #93c5fd;">
        <svg width="20" height="20" fill="#2563eb" style="flex-shrink:0;margin-top:2px" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        <div style="flex:1;font-size:.875rem;color:#1e40af;font-weight:500;">{{ session('info') }}</div>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:#1e40af;font-size:1.1rem;line-height:1;padding:0;">&times;</button>
    </div>
    @endif

</div>

<script nonce="{{ csp_nonce() }}">
(function() {
    setTimeout(function() {
        var c = document.getElementById('flash-container');
        if (c) { c.style.opacity = '0'; c.style.transition = 'opacity .5s'; setTimeout(function(){ c.remove(); }, 500); }
    }, 5000);
})();
</script>
@endif
