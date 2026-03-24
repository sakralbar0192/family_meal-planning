import type { StorybookConfig } from '@storybook/vue3-vite';
import vue from '@vitejs/plugin-vue';
import { mergeConfig } from 'vite';

const config: StorybookConfig = {
  framework: '@storybook/vue3-vite',
  stories: ['../packages/ui-kit/src/**/*.stories.ts'],
  addons: ['@storybook/addon-essentials'],
  async viteFinal(baseConfig) {
    return mergeConfig(baseConfig, {
      plugins: [vue()],
    });
  },
};

export default config;
