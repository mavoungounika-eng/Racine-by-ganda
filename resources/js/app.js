import './bootstrap';
import 'bootstrap';
import { createApp } from 'vue';
import AmiraWidget from './components/Amira/AmiraWidget.vue';

// Initialisation de l'application Vue pour Amira
const amiraApp = createApp({});
amiraApp.component('amira-widget', AmiraWidget);

// Montage si l'élément existe
if (document.getElementById('amira-app')) {
    amiraApp.mount('#amira-app');
}

