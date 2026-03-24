import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import federation from '@originjs/vite-plugin-federation';

const recipesRemote =
  process.env.VITE_MF_RECIPES_URL ?? 'http://127.0.0.1:5174/assets/remoteEntry.js';
const plannerRemote =
  process.env.VITE_MF_PLANNER_URL ?? 'http://127.0.0.1:5175/assets/remoteEntry.js';
const shoppingRemote =
  process.env.VITE_MF_SHOPPING_URL ?? 'http://127.0.0.1:5176/assets/remoteEntry.js';

export default defineConfig({
  plugins: [
    vue(),
    federation({
      name: 'host',
      remotes: {
        mf_recipes: recipesRemote,
        mf_planner: plannerRemote,
        mf_shopping: shoppingRemote,
      },
      shared: {
        vue: { singleton: true, requiredVersion: '^3.4.0' },
        'vue-router': { singleton: true, requiredVersion: '^4.4.0' },
      },
    }),
  ],
  server: {
    port: 5173,
    strictPort: true,
  },
  build: {
    target: 'esnext',
    minify: false,
    cssCodeSplit: false,
  },
});
