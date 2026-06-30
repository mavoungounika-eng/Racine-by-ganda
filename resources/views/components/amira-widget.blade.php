@props(['space' => 'client'])

@php
    $colors = [
        'client'  => ['bg' => '#ED5F1E', 'badge' => 'Client'],
        'creator' => ['bg' => '#FFB800', 'badge' => 'Créateur'],
        'admin'   => ['bg' => '#160D0C', 'badge' => 'Admin'],
    ];
    $c = $colors[$space] ?? $colors['client'];
@endphp

<div id="amira-widget" style="position:fixed;bottom:24px;right:24px;z-index:9999;font-family:'Coco Gothic',sans-serif;">

    {{-- Bouton bulle --}}
    <button id="amira-toggle" onclick="amiraToggle()"
        style="width:56px;height:56px;border-radius:50%;background:{{ $c['bg'] }};border:none;cursor:pointer;box-shadow:0 4px 20px rgba(0,0,0,0.25);display:flex;align-items:center;justify-content:center;transition:transform .2s;"
        title="Parler à Amira"
        onmouseover="this.style.transform='scale(1.1)'"
        onmouseout="this.style.transform='scale(1)'">
        <svg width="24" height="24" fill="white" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 3 1 4.3L2 22l5.7-1C9 21.64 10.46 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.41 0-2.75-.36-3.93-1.01L5 20l1.01-3.07C5.36 15.75 5 14.41 5 13c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7z"/></svg>
    </button>

    {{-- Fenêtre chat --}}
    <div id="amira-panel" style="display:none;position:absolute;bottom:70px;right:0;width:340px;background:#fff;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,0.18);overflow:hidden;flex-direction:column;">

        {{-- Header --}}
        <div style="background:{{ $c['bg'] }};padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:.75rem;">
                <div style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;">
                    <svg width="18" height="18" fill="white" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 3 1 4.3L2 22l5.7-1C9 21.64 10.46 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2z"/></svg>
                </div>
                <div>
                    <div style="color:white;font-weight:700;font-size:.9rem;">Amira</div>
                    <div style="color:rgba(255,255,255,.8);font-size:.75rem;">{{ $c['badge'] }}</div>
                </div>
            </div>
            <button onclick="amiraToggle()" style="background:none;border:none;cursor:pointer;color:white;font-size:1.2rem;line-height:1;">&times;</button>
        </div>

        {{-- Messages --}}
        <div id="amira-messages" style="height:280px;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:.75rem;background:#fafafa;">
            <div class="amira-msg amira-bot" style="background:white;border:1px solid #eee;border-radius:12px 12px 12px 4px;padding:.75rem 1rem;font-size:.85rem;color:#160D0C;max-width:85%;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                Bonjour ! Je suis Amira 👋 Comment puis-je vous aider aujourd'hui ?
            </div>
        </div>

        {{-- Input --}}
        <div style="padding:.75rem;border-top:1px solid #eee;display:flex;gap:.5rem;background:white;">
            <input id="amira-input" type="text" placeholder="Posez votre question…"
                style="flex:1;border:1px solid #ddd;border-radius:8px;padding:.6rem .8rem;font-size:.85rem;outline:none;color:#160D0C;"
                onkeydown="if(event.key==='Enter')amiraSend()"
                onfocus="this.style.borderColor='{{ $c['bg'] }}'"
                onblur="this.style.borderColor='#ddd'">
            <button onclick="amiraSend()"
                style="width:38px;height:38px;border-radius:8px;background:{{ $c['bg'] }};border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="16" height="16" fill="white" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function() {
    const SPACE   = '{{ $space }}';
    const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let isOpen    = false;
    let isLoading = false;

    window.amiraToggle = function() {
        isOpen = !isOpen;
        const panel = document.getElementById('amira-panel');
        panel.style.display = isOpen ? 'flex' : 'none';
        if (isOpen) {
            setTimeout(() => document.getElementById('amira-input')?.focus(), 50);
        }
    };

    window.amiraSend = async function() {
        if (isLoading) return;
        const input = document.getElementById('amira-input');
        const msg   = (input.value || '').trim();
        if (!msg) return;

        input.value = '';
        amiraAddMsg(msg, 'user');
        isLoading = true;
        amiraAddTyping();

        try {
            const res = await fetch('/api/amira/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: msg, space: SPACE }),
            });

            const data = await res.json();
            amiraRemoveTyping();
            amiraAddMsg(data.answer || "Je n'ai pas pu répondre. Réessaie !", 'bot');
        } catch (e) {
            amiraRemoveTyping();
            amiraAddMsg("Une erreur est survenue. Réessaie dans un instant.", 'bot');
        } finally {
            isLoading = false;
        }
    };

    function amiraAddMsg(text, who) {
        const box = document.getElementById('amira-messages');
        const div = document.createElement('div');
        const isBot = who === 'bot';
        div.style.cssText = isBot
            ? 'background:white;border:1px solid #eee;border-radius:12px 12px 12px 4px;padding:.75rem 1rem;font-size:.85rem;color:#160D0C;max-width:85%;align-self:flex-start;box-shadow:0 1px 4px rgba(0,0,0,.06);'
            : 'background:{{ $c['bg'] }};border-radius:12px 12px 4px 12px;padding:.75rem 1rem;font-size:.85rem;color:white;max-width:85%;align-self:flex-end;';
        div.textContent = text;
        box.appendChild(div);
        box.scrollTop = box.scrollHeight;
    }

    function amiraAddTyping() {
        const box = document.getElementById('amira-messages');
        const div = document.createElement('div');
        div.id = 'amira-typing';
        div.style.cssText = 'background:white;border:1px solid #eee;border-radius:12px 12px 12px 4px;padding:.75rem 1rem;font-size:.85rem;color:#999;max-width:85%;align-self:flex-start;';
        div.textContent = 'Amira réfléchit…';
        box.appendChild(div);
        box.scrollTop = box.scrollHeight;
    }

    function amiraRemoveTyping() {
        document.getElementById('amira-typing')?.remove();
    }
})();
</script>
@endpush
