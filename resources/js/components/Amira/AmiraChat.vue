<template>
    <div class="d-flex flex-column h-100">
        <!-- Header -->
        <div class="amira-chat-header d-flex align-items-center justify-content-between px-3 py-2 flex-shrink-0">
            <div class="d-flex align-items-center gap-2">
                <div class="amira-avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0">
                    A
                </div>
                <div>
                    <div class="fw-medium text-white" style="font-size: 0.9rem; line-height: 1.2;">Amira</div>
                    <div class="text-white-50" style="font-size: 0.75rem;">Assistante virtuelle</div>
                </div>
            </div>
            <button @click="$emit('close')" class="btn-close btn-close-white" aria-label="Fermer"></button>
        </div>

        <!-- Messages area -->
        <div class="amira-messages flex-grow-1 p-3 overflow-y-auto" ref="messagesContainer">
            <!-- Welcome -->
            <div class="d-flex justify-content-start mb-3">
                <div class="amira-bubble amira-bubble--bot">
                    Bonjour ! Je suis Amira. Comment puis-je vous aider aujourd'hui ?
                </div>
            </div>

            <!-- Conversation -->
            <div
                v-for="(msg, index) in messages"
                :key="index"
                class="d-flex mb-3"
                :class="msg.isUser ? 'justify-content-end' : 'justify-content-start'"
            >
                <div class="amira-bubble" :class="msg.isUser ? 'amira-bubble--user' : 'amira-bubble--bot'">
                    {{ msg.text }}
                </div>
            </div>

            <!-- Loading dots -->
            <div v-if="isLoading" class="d-flex justify-content-start mb-3">
                <div class="amira-bubble amira-bubble--bot amira-bubble--loading">
                    <span class="amira-dot"></span>
                    <span class="amira-dot"></span>
                    <span class="amira-dot"></span>
                </div>
            </div>
        </div>

        <!-- Input area -->
        <div class="p-3 border-top bg-white flex-shrink-0">
            <form @submit.prevent="sendMessage" class="d-flex gap-2">
                <input
                    type="text"
                    v-model="inputMessage"
                    placeholder="Posez votre question..."
                    class="form-control form-control-sm"
                    :disabled="isLoading"
                >
                <button
                    type="submit"
                    class="btn btn-sm amira-send-btn flex-shrink-0"
                    :disabled="!inputMessage.trim() || isLoading"
                    aria-label="Envoyer"
                >
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
            <p class="text-muted text-center mt-2 mb-0" style="font-size: 0.65rem;">
                Amira peut faire des erreurs. Vérifiez les informations importantes.
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import axios from 'axios';

const emit = defineEmits(['close']);

const messages = ref([]);
const inputMessage = ref('');
const isLoading = ref(false);
const messagesContainer = ref(null);

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const sendMessage = async () => {
    if (!inputMessage.value.trim() || isLoading.value) return;

    const userQuestion = inputMessage.value.trim();
    messages.value.push({ text: userQuestion, isUser: true });
    inputMessage.value = '';
    isLoading.value = true;
    scrollToBottom();

    try {
        const response = await axios.post('/api/amira/ask', { question: userQuestion });
        messages.value.push({ text: response.data.answer, isUser: false });
    } catch {
        messages.value.push({
            text: 'Désolé, je rencontre des difficultés techniques pour le moment. Veuillez réessayer plus tard.',
            isUser: false,
        });
    } finally {
        isLoading.value = false;
        scrollToBottom();
    }
};
</script>

<style scoped>
.amira-chat-header {
    background: var(--racine-orange, #ED5F1E);
    min-height: 58px;
}

.amira-avatar {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.2);
    font-size: 0.85rem;
}

.amira-messages {
    background: #f9f9f9;
}

.amira-bubble {
    max-width: 85%;
    padding: 0.55rem 0.85rem;
    border-radius: 1rem;
    font-size: 0.85rem;
    line-height: 1.5;
}

.amira-bubble--bot {
    background: white;
    border: 1px solid #eee;
    border-top-left-radius: 0.25rem;
    color: #333;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.amira-bubble--user {
    background: var(--racine-orange, #ED5F1E);
    color: white;
    border-top-right-radius: 0.25rem;
}

.amira-bubble--loading {
    display: flex;
    gap: 5px;
    align-items: center;
    padding: 0.75rem 1rem;
}

.amira-dot {
    width: 7px;
    height: 7px;
    background: #ccc;
    border-radius: 50%;
    animation: amira-bounce 1.2s ease-in-out infinite;
}

.amira-dot:nth-child(2) { animation-delay: 160ms; }
.amira-dot:nth-child(3) { animation-delay: 320ms; }

@keyframes amira-bounce {
    0%, 80%, 100% { transform: scale(0.7); opacity: 0.4; }
    40% { transform: scale(1.2); opacity: 1; }
}

.amira-send-btn {
    background: var(--racine-orange, #ED5F1E);
    color: white;
    border: none;
    padding: 0.375rem 0.625rem;
}

.amira-send-btn:hover:not(:disabled),
.amira-send-btn:focus:not(:disabled) {
    background: #d4501a;
    color: white;
}

.amira-send-btn:disabled {
    opacity: 0.45;
}
</style>
