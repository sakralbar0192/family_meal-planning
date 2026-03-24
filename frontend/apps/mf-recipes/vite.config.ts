import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import federation from '@originjs/vite-plugin-federation';

export default defineConfig({
  plugins: [
    vue(),
    federation({
      name: 'mf_recipes',
      filename: 'remoteEntry.js',
      exposes: {
        './Entry': './src/Entry.vue',
        './Library': './src/Library.vue',
        './ImportView': './src/ImportView.vue',
        './RecipeDetail': './src/RecipeDetail.vue',
        './RecipeForm': './src/RecipeForm.vue',
      },
      shared: {
        vue: { singleton: true, requiredVersion: '^3.4.0' },
        'vue-router': { singleton: true, requiredVersion: '^4.4.0' },
      },
    }),
  ],
  server: {
    port: 5174,
    strictPort: true,
    cors: true,
  },
  build: {
    target: 'esnext',
    minify: false,
    cssCodeSplit: false,
  },
});
