import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { UiButton, UiModalShell } from './index';

const meta: Meta<typeof UiModalShell> = {
  title: 'UI Kit/UiModalShell',
  component: UiModalShell,
  args: { title: 'Добавить в план', open: true },
};

export default meta;
type Story = StoryObj<typeof UiModalShell>;

export const Default: Story = {
  render: (args) => ({
    components: { UiModalShell, UiButton },
    setup: () => ({ args }),
    template: `
      <UiModalShell v-bind="args">
        <p>Контент модального окна.</p>
        <template #actions>
          <UiButton variant="secondary">Отмена</UiButton>
          <UiButton>Сохранить</UiButton>
        </template>
      </UiModalShell>
    `,
  }),
};
