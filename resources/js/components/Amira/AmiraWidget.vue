<template>
    <div class="amira-widget-container">
        <Transition name="amira-slide">
            <div v-if="isOpen" class="amira-panel card border-0 shadow-lg">
                <AmiraChat @close="toggleChat" />
            </div>
        </Transition>

        <button
            @click="toggleChat"
            class="amira-toggle-btn btn rounded-circle shadow"
            :aria-label="isOpen ? 'Fermer Amira' : 'Ouvrir l\'assistant Amira'"
            :title="isOpen ? '' : 'Besoin d\'aide ?'"
        >
            <svg v-if="!isOpen" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <svg v-else width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span v-if="!isOpen" class="amira-notif-dot"></span>
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import AmiraChat from './AmiraChat.vue';

const isOpen = ref(false);
const toggleChat = () => { isOpen.value = !isOpen.value; };
</script>

<style scoped>
.amira-widget-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 1050;
}

.amira-panel {
    position: absolute;
    bottom: 4.5rem;
    right: 0;
    width: 360px;
    height: 520px;
    max-height: calc(100vh - 120px);
    border-radius: 1rem !important;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.amira-toggle-btn {
    width: 56px;
    height: 56px;
    background: var(--racine-orange, #ED5F1E);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
}

.amira-toggle-btn:hover,
.amira-toggle-btn:focus {
    background: #d4501a;
    color: white;
    transform: scale(1.06);
}

.amira-notif-dot {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 10px;
    height: 10px;
    background: #f87171;
    border: 2px solid white;
    border-radius: 50%;
}

.amira-slide-enter-active,
.amira-slide-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.amira-slide-enter-from,
.amira-slide-leave-to {
    opacity: 0;
    transform: translateY(12px) scale(0.97);
}

@media (max-width: 576px) {
    .amira-panel {
        width: calc(100vw - 2rem);
        right: -0.5rem;
    }
}
</style>
