import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { UiButton, UiRecipeCard } from './index';

const meta: Meta<typeof UiRecipeCard> = {
  title: 'UI Kit/UiRecipeCard',
  component: UiRecipeCard,
  args: {
    title: 'Борщ с говядиной',
    meta: '45 мин',
    badge: 'Обед',
  },
};

export default meta;
type Story = StoryObj<typeof UiRecipeCard>;

export const Default: Story = {
  render: (args) => ({
    components: { UiRecipeCard, UiButton },
    setup: () => ({ args }),
    template: `
      <div style="width: 320px;">
        <UiRecipeCard v-bind="args">
          <template #actions>
            <UiButton size="sm">В план</UiButton>
            <UiButton size="sm" variant="secondary">Изменить</UiButton>
          </template>
        </UiRecipeCard>
      </div>
    `,
  }),
};
