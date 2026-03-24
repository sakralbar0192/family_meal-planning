import { createApp } from 'vue';
import '@meal/ui-tokens/dist/tokens.css';
import App from './App.vue';
import router from './router';

createApp(App).use(router).mount('#app');
