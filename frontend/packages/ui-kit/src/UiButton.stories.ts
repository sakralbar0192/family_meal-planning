import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { UiButton } from './index';

const meta: Meta<typeof UiButton> = {
  title: 'UI Kit/UiButton',
  component: UiButton,
  args: { variant: 'primary', size: 'md' },
};

export default meta;
type Story = StoryObj<typeof UiButton>;

export const Primary: Story = { args: {}, render: (args) => ({ components: { UiButton }, setup: () => ({ args }), template: '<UiButton v-bind="args">Primary</UiButton>' }) };
export const Secondary: Story = { args: { variant: 'secondary' }, render: (args) => ({ components: { UiButton }, setup: () => ({ args }), template: '<UiButton v-bind="args">Secondary</UiButton>' }) };
export const DangerSmall: Story = { args: { variant: 'danger', size: 'sm' }, render: (args) => ({ components: { UiButton }, setup: () => ({ args }), template: '<UiButton v-bind="args">Delete</UiButton>' }) };
