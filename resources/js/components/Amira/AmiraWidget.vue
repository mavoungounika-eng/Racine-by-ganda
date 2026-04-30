<template>
    <div class="fixed bottom-6 right-6 z-50">
        <!-- Chat Interface -->
        <transition
            enter-active-class="transform transition ease-out duration-300"
            enter-from-class="translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transform transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100 scale-100"
            leave-to-class="translate-y-4 opacity-0 scale-95"
        >
            <div 
                v-if="isOpen"
                class="absolute bottom-16 right-0 w-80 sm:w-96 bg-white/90 backdrop-blur-md border border-white/20 shadow-2xl rounded-2xl overflow-hidden flex flex-col max-h-[600px]"
                style="height: calc(100vh - 120px);"
            >
                <AmiraChat @close="toggleChat" />
            </div>
        </transition>

        <!-- Toggle Button -->
        <button 
            @click="toggleChat"
            class="group relative flex items-center justify-center w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300"
        >
            <span class="sr-only">Ouvrir l'assistant Amira</span>
            
            <!-- Icon -->
            <svg 
                v-if="!isOpen" 
                class="w-7 h-7 text-white" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            
            <svg 
                v-else 
                class="w-7 h-7 text-white" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>

            <!-- Notification Badge (optional) -->
            <span class="absolute top-0 right-0 w-3 h-3 bg-red-400 border-2 border-white rounded-full"></span>
            
            <!-- Tooltip -->
            <div class="absolute right-full mr-4 px-3 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                Besoin d'aide ?
            </div>
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import AmiraChat from './AmiraChat.vue';

const isOpen = ref(false);

const toggleChat = () => {
    isOpen.value = !isOpen.value;
};
</script>
