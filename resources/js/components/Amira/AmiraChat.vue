<template>
    <div class="flex flex-col h-full bg-white/50">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-indigo-500/90 to-purple-600/90 backdrop-blur-sm text-white">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                    <span class="text-sm font-bold">A</span>
                </div>
                <div>
                    <h3 class="font-medium text-sm">Amira</h3>
                    <p class="text-xs text-indigo-100">Assistante virtuelle</p>
                </div>
            </div>
            <button @click="$emit('close')" class="text-white/80 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4 scrollbar-thin scrollbar-thumb-gray-200" ref="messagesContainer">
            <!-- Welcome Message -->
            <div class="flex justify-start">
                <div class="max-w-[85%] bg-white border border-indigo-50 rounded-2xl rounded-tl-none p-3 shadow-sm">
                    <p class="text-sm text-gray-700">Bonjour ! Je suis Amira. Comment puis-je vous aider aujourd'hui ?</p>
                </div>
            </div>

            <!-- Conversation -->
            <div 
                v-for="(msg, index) in messages" 
                :key="index"
                class="flex"
                :class="msg.isUser ? 'justify-end' : 'justify-start'"
            >
                <div 
                    class="max-w-[85%] p-3 rounded-2xl shadow-sm text-sm"
                    :class="[
                        msg.isUser 
                            ? 'bg-indigo-600 text-white rounded-tr-none' 
                            : 'bg-white border border-indigo-50 text-gray-700 rounded-tl-none'
                    ]"
                >
                    {{ msg.text }}
                </div>
            </div>

            <!-- Loading Indicator -->
            <div v-if="isLoading" class="flex justify-start">
                <div class="bg-white border border-indigo-50 rounded-2xl rounded-tl-none p-3 shadow-sm">
                    <div class="flex space-x-1.5">
                        <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white border-t border-gray-100">
            <form @submit.prevent="sendMessage" class="relative">
                <input 
                    type="text" 
                    v-model="inputMessage" 
                    placeholder="Posez votre question..."
                    class="w-full pl-4 pr-12 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all"
                    :disabled="isLoading"
                >
                <button 
                    type="submit" 
                    class="absolute right-2 top-2 p-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!inputMessage.trim() || isLoading"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
            <div class="mt-2 text-center">
                <p class="text-[10px] text-gray-400">Amira peut faire des erreurs. Vérifiez les informations importantes.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
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
    
    // Add user message
    messages.value.push({
        text: userQuestion,
        isUser: true
    });
    
    inputMessage.value = '';
    isLoading.value = true;
    scrollToBottom();

    try {
        const response = await axios.post('/api/amira/ask', {
            question: userQuestion
        });

        // Add Amira response
        messages.value.push({
            text: response.data.answer,
            isUser: false
        });
    } catch (error) {
        console.error('Amira Error:', error);
        messages.value.push({
            text: "Désolé, je rencontre des difficultés techniques pour le moment. Veuillez réessayer plus tard.",
            isUser: false
        });
    } finally {
        isLoading.value = false;
        scrollToBottom();
    }
};
</script>
