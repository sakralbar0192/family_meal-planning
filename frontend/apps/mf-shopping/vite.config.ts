import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import federation from '@originjs/vite-plugin-federation';

const base = process.env.VITE_DEMO_BASE ?? '/';

export default defineConfig({
  base,
  plugins: [
    vue(),
    federation({
      name: 'mf_shopping',
      filename: 'remoteEntry.js',
      exposes: {
        './Entry': './src/Entry.vue',
        './ShoppingPage': './src/ShoppingPage.vue',
      },
      shared: {
        vue: { singleton: true, requiredVersion: '^3.4.0', import: false },
        'vue-router': { singleton: true, requiredVersion: '^4.4.0', import: false },
        '@meal/shell-chrome': { singleton: true, requiredVersion: '0.0.1', import: false },
      },
    }),
  ],
  server: {
    port: 5176,
    strictPort: true,
    cors: true,
  },
  build: {
    target: 'esnext',
    minify: 'esbuild',
    cssCodeSplit: false,
  },
});
