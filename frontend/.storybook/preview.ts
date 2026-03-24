import type { Preview } from '@storybook/vue3-vite';
import '@meal/ui-tokens/dist/tokens.css';

const preview: Preview = {
  parameters: {
    layout: 'centered',
    controls: { expanded: true },
  },
};

export default preview;
