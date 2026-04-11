<!-- Amira Chat Widget v3.0 - RACINE BY GANDA -->
<style>
    #amira-widget {
        --amira-primary: #4B1DF2;
        --amira-primary-dark: #3A16BD;
        --amira-gold: #D4AF37;
        --amira-black: #11001F;
        --amira-white: #FAFAFA;
        --amira-gray: #F0F0F5;
        --amira-radius: 20px;
        --amira-shadow: 0 10px 40px rgba(17, 0, 31, 0.2);
        
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        font-family: 'Inter', -apple-system, sans-serif;
    }

    /* Bouton Flottant */
    .amira-toggle {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--amira-primary) 0%, var(--amira-primary-dark) 100%);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 20px rgba(75, 29, 242, 0.4);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .amira-toggle::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 100%);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .amira-toggle:hover {
        transform: scale(1.08);
        box-shadow: 0 8px 30px rgba(75, 29, 242, 0.5);
    }

    .amira-toggle:hover::before {
        opacity: 1;
    }

    .amira-toggle svg {
        width: 28px;
        height: 28px;
        fill: white;
        transition: transform 0.3s;
    }

    .amira-toggle.open svg {
        transform: rotate(90deg);
    }

    /* Badge notification */
    .amira-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        width: 20px;
        height: 20px;
        background: var(--amira-gold);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        color: var(--amira-black);
        animation: amira-pulse 2s infinite;
    }

    @keyframes amira-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    /* Chat Window */
    .amira-chat {
        position: absolute;
        bottom: 80px;
        right: 0;
        width: 380px;
        max-width: calc(100vw - 48px);
        height: 550px;
        max-height: calc(100vh - 120px);
        background: var(--amira-white);
        border-radius: var(--amira-radius);
        box-shadow: var(--amira-shadow);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px) scale(0.95);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .amira-chat.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }

    /* Header */
    .amira-header {
        background: linear-gradient(135deg, var(--amira-primary) 0%, var(--amira-primary-dark) 100%);
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        position: relative;
        overflow: hidden;
    }

    .amira-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -30%;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    }

    .amira-avatar {
        width: 48px;
        height: 48px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        position: relative;
        flex-shrink: 0;
    }

    .amira-avatar::after {
        content: '';
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 12px;
        height: 12px;
        background: #22C55E;
        border: 2px solid var(--amira-primary);
        border-radius: 50%;
    }

    .amira-header-info {
        flex: 1;
        color: white;
    }

    .amira-header-info h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .amira-header-info p {
        margin: 2px 0 0;
        font-size: 0.8rem;
        opacity: 0.85;
    }

    .amira-close {
        background: rgba(255,255,255,0.15);
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }

    .amira-close:hover {
        background: rgba(255,255,255,0.25);
    }

    .amira-close svg {
        width: 16px;
        height: 16px;
        fill: white;
    }

    /* Messages */
    .amira-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 16px;
        background: linear-gradient(180deg, var(--amira-gray) 0%, var(--amira-white) 100%);
    }

    .amira-messages::-webkit-scrollbar {
        width: 4px;
    }

    .amira-messages::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.1);
        border-radius: 2px;
    }

    .amira-message {
        display: flex;
        gap: 10px;
        animation: amira-fadeIn 0.3s ease;
    }

    @keyframes amira-fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .amira-message.user {
        flex-direction: row-reverse;
    }

    .amira-message-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .amira-message.bot .amira-message-avatar {
        background: linear-gradient(135deg, var(--amira-primary) 0%, var(--amira-primary-dark) 100%);
        color: white;
    }

    .amira-message.user .amira-message-avatar {
        background: var(--amira-gray);
        color: var(--amira-black);
    }

    .amira-message-content {
        max-width: 75%;
        padding: 12px 16px;
        border-radius: 16px;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .amira-message.bot .amira-message-content {
        background: white;
        color: var(--amira-black);
        border-bottom-left-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .amira-message.user .amira-message-content {
        background: linear-gradient(135deg, var(--amira-primary) 0%, var(--amira-primary-dark) 100%);
        color: white;
        border-bottom-right-radius: 4px;
    }

    .amira-message-content strong {
        font-weight: 600;
    }

    .amira-message-content a {
        color: var(--amira-gold);
        text-decoration: underline;
    }

    .amira-message.user .amira-message-content a {
        color: rgba(255,255,255,0.9);
    }

    /* Typing indicator */
    .amira-typing {
        display: flex;
        gap: 4px;
        padding: 12px 16px;
    }

    .amira-typing span {
        width: 8px;
        height: 8px;
        background: var(--amira-primary);
        border-radius: 50%;
        animation: amira-typing 1.4s infinite ease-in-out;
    }

    .amira-typing span:nth-child(2) { animation-delay: 0.2s; }
    .amira-typing span:nth-child(3) { animation-delay: 0.4s; }

    @keyframes amira-typing {
        0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
        40% { transform: scale(1); opacity: 1; }
    }

    /* Quick Actions */
    .amira-quick-actions {
        padding: 12px 20px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        border-top: 1px solid var(--amira-gray);
        background: white;
    }

    .amira-quick-btn {
        padding: 6px 14px;
        background: var(--amira-gray);
        border: none;
        border-radius: 20px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--amira-black);
    }

    .amira-quick-btn:hover {
        background: var(--amira-primary);
        color: white;
    }

    /* Input */
    .amira-input-area {
        padding: 16px 20px;
        background: white;
        border-top: 1px solid var(--amira-gray);
    }

    .amira-input-wrapper {
        display: flex;
        gap: 10px;
        align-items: center;
        background: var(--amira-gray);
        border-radius: 25px;
        padding: 4px 4px 4px 20px;
        transition: box-shadow 0.2s;
    }

    .amira-input-wrapper:focus-within {
        box-shadow: 0 0 0 2px var(--amira-primary);
    }

    .amira-input {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 0.95rem;
        outline: none;
        padding: 8px 0;
        color: var(--amira-black);
    }

    .amira-input::placeholder {
        color: #999;
    }

    .amira-send {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--amira-primary) 0%, var(--amira-primary-dark) 100%);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s;
    }

    .amira-send:hover {
        transform: scale(1.05);
    }

    .amira-send:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .amira-send svg {
        width: 18px;
        height: 18px;
        fill: white;
    }

    /* Mobile */
    @media (max-width: 480px) {
        #amira-widget {
            bottom: 16px;
            right: 16px;
        }

        .amira-chat {
            width: calc(100vw - 32px);
            height: calc(100vh - 100px);
            bottom: 76px;
            right: -8px;
            border-radius: 16px;
        }

        .amira-toggle {
            width: 56px;
            height: 56px;
        }
    }
</style>

<div id="amira-widget">
    <!-- Toggle Button -->
    <button class="amira-toggle" id="amira-toggle" aria-label="Ouvrir le chat">
        <svg viewBox="0 0 24 24" id="amira-icon-chat">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
        </svg>
        <svg viewBox="0 0 24 24" id="amira-icon-close" style="display:none;">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>
    </button>

    <!-- Chat Window -->
    <div class="amira-chat" id="amira-chat">
        <!-- Header -->
        <div class="amira-header">
            <div class="amira-avatar">🤖</div>
            <div class="amira-header-info">
                <h3>Amira</h3>
                <p>Assistante RACINE BY GANDA</p>
            </div>
            <button class="amira-close" id="amira-close-btn" aria-label="Fermer">
                <svg viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>

        <!-- Messages -->
        <div class="amira-messages" id="amira-messages">
            <div class="amira-message bot">
                <div class="amira-message-avatar">🤖</div>
                <div class="amira-message-content">
                    Bonjour ! 👋 Je suis <strong>Amira</strong>, l'assistante virtuelle de RACINE BY GANDA. Comment puis-je vous aider ?
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="amira-quick-actions">
            <button class="amira-quick-btn" data-message="Où est ma commande ?">📦 Ma commande</button>
            <button class="amira-quick-btn" data-message="Quels sont les délais de livraison ?">🚚 Livraison</button>
            <button class="amira-quick-btn" data-message="Comment faire un retour ?">↩️ Retours</button>
            <button class="amira-quick-btn" data-message="/aide">❓ Aide</button>
        </div>

        <!-- Input -->
        <div class="amira-input-area">
            <form class="amira-input-wrapper" id="amira-form">
                <input 
                    type="text" 
                    class="amira-input" 
                    id="amira-input"
                    placeholder="Écrivez votre message..."
                    autocomplete="off"
                    maxlength="500"
                >
                <button type="submit" class="amira-send" id="amira-send" aria-label="Envoyer">
                    <svg viewBox="0 0 24 24">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const widget = document.getElementById('amira-widget');
    const toggle = document.getElementById('amira-toggle');
    const chat = document.getElementById('amira-chat');
    const closeBtn = document.getElementById('amira-close-btn');
    const form = document.getElementById('amira-form');
    const input = document.getElementById('amira-input');
    const messages = document.getElementById('amira-messages');
    const iconChat = document.getElementById('amira-icon-chat');
    const iconClose = document.getElementById('amira-icon-close');

    let isOpen = false;

    // Toggle chat
    function toggleChat() {
        isOpen = !isOpen;
        chat.classList.toggle('open', isOpen);
        toggle.classList.toggle('open', isOpen);
        iconChat.style.display = isOpen ? 'none' : 'block';
        iconClose.style.display = isOpen ? 'block' : 'none';
        
        if (isOpen) {
            input.focus();
        }
    }

    toggle.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);

    // Quick actions
    document.querySelectorAll('.amira-quick-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const message = btn.dataset.message;
            if (message) {
                sendMessage(message);
            }
        });
    });

    // Form submit
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const message = input.value.trim();
        if (message) {
            sendMessage(message);
        }
    });

    // Send message
    async function sendMessage(message) {
        // Add user message
        addMessage(message, 'user');
        input.value = '';
        input.disabled = true;

        // Show typing
        const typingId = showTyping();

        try {
            const response = await fetch('{{ route("amira.message") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message })
            });

            const data = await response.json();
            hideTyping(typingId);

            if (data.message) {
                addMessage(data.message, 'bot');
            }
        } catch (error) {
            hideTyping(typingId);
            addMessage('Désolée, une erreur est survenue. Réessayez plus tard.', 'bot');
            console.error('Amira Error:', error);
        }

        input.disabled = false;
        input.focus();
    }

    // Add message to chat
    function addMessage(text, type) {
        const div = document.createElement('div');
        div.className = `amira-message ${type}`;
        
        // Format text (markdown-like)
        let formattedText = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank">$1</a>')
            .replace(/\n/g, '<br>');

        const avatar = type === 'bot' ? '🤖' : '👤';
        
        div.innerHTML = `
            <div class="amira-message-avatar">${avatar}</div>
            <div class="amira-message-content">${formattedText}</div>
        `;
        
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    // Show typing indicator
    function showTyping() {
        const id = 'typing-' + Date.now();
        const div = document.createElement('div');
        div.id = id;
        div.className = 'amira-message bot';
        div.innerHTML = `
            <div class="amira-message-avatar">🤖</div>
            <div class="amira-message-content">
                <div class="amira-typing">
                    <span></span><span></span><span></span>
                </div>
            </div>
        `;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
        return id;
    }

    // Hide typing indicator
    function hideTyping(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    // Keyboard shortcut (Escape to close)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) {
            toggleChat();
        }
    });
})();
</script>
