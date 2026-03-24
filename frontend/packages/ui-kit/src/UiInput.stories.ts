import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { ref } from 'vue';
import { UiInput } from './index';

const meta: Meta<typeof UiInput> = {
  title: 'UI Kit/UiInput',
  component: UiInput,
  args: { label: 'Email', placeholder: 'user@example.com', type: 'email' },
};

export default meta;
type Story = StoryObj<typeof UiInput>;

export const Default: Story = {
  render: (args) => ({
    components: { UiInput },
    setup: () => ({ args, model: ref('') }),
    template: '<UiInput v-bind="args" v-model="model" />',
  }),
};

export const WithoutLabel: Story = {
  args: { label: '' },
  render: (args) => ({
    components: { UiInput },
    setup: () => ({ args, model: ref('') }),
    template: '<UiInput v-bind="args" v-model="model" />',
  }),
};
