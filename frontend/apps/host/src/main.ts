import { createApp } from 'vue';
import '@fontsource/inter/400.css';
import '@fontsource/inter/600.css';
import '@meal/ui-tokens/dist/tokens.css';
import './shell-header-extras.css';
import App from './App.vue';
import router from './router';

async function bootstrap(): Promise<void> {
  if (import.meta.env.VITE_DEMO_MODE === '1') {
    const { startDemoMocks } = await import('./demo/browser');
    await startDemoMocks();
  }

  createApp(App).use(router).mount('#app');
}

void bootstrap();
