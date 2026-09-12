import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { UiAppHeader } from './index';

const meta: Meta<typeof UiAppHeader> = {
  title: 'UI Kit/UiAppHeader',
  component: UiAppHeader,
  parameters: {
    layout: 'fullscreen',
    docs: {
      description: {
        component:
          'TopBar по макету Figma: страница **01 — Токены и UI Kit**, секция **C**, фрейм **UiAppHeader — все сторис** (`3390:2430`). Слоты + классы `ui-app-header-link--ghost` / `ui-app-header-link--accent`.',
      },
    },
  },
};

export default meta;
type Story = StoryObj<typeof UiAppHeader>;

export const Library: Story = {
  render: () => ({
    components: { UiAppHeader },
    template: `
      <div style="padding: 16px; background: var(--color-bg); max-width: 1120px; margin: 0 auto;">
        <UiAppHeader aria-label="Шапка библиотеки">
          <template #title>Рецепты</template>
          <template #actions>
            <a href="#" class="ui-app-header-link--accent">Импорт по URL</a>
          </template>
        </UiAppHeader>
      </div>
    `,
  }),
};

export const Planner: Story = {
  render: () => ({
    components: { UiAppHeader },
    template: `
      <div style="padding: 16px; background: var(--color-bg); max-width: 1120px; margin: 0 auto;">
        <UiAppHeader aria-label="Шапка планировщика" title-align="center">
          <template #leading>
            <a href="#" class="ui-app-header-link--accent">К библиотеке</a>
          </template>
          <template #title>Планировщик</template>
          <template #actions>
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-sm); justify-content: flex-end">
              <span class="ui-app-header-meta">10 марта 2026</span>
              <a href="#" class="ui-app-header-link">Список покупок</a>
            </div>
          </template>
        </UiAppHeader>
      </div>
    `,
  }),
};

export const ShoppingList: Story = {
  render: () => ({
    components: { UiAppHeader },
    template: `
      <div style="padding: 16px; background: var(--color-bg); max-width: 1120px; margin: 0 auto;">
        <UiAppHeader aria-label="Шапка списка покупок">
          <template #title>Список покупок</template>
          <template #subline>
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: var(--space-sm)">
              <span>Период: 10—16 марта 2026</span>
              <a href="#" class="ui-app-header-link--accent">Изменить период</a>
            </div>
          </template>
          <template #actions>
            <a href="#" class="ui-app-header-link--accent">К планировщику</a>
          </template>
        </UiAppHeader>
      </div>
    `,
  }),
};

export const ShellSignIn: Story = {
  render: () => ({
    components: { UiAppHeader },
    template: `
      <div style="padding: 16px; background: var(--color-bg); max-width: 1120px; margin: 0 auto;">
        <UiAppHeader layout="centeredTitle" :title-level="1" aria-label="Шапка входа">
          <template #title>Вход в аккаунт</template>
        </UiAppHeader>
      </div>
    `,
  }),
};

export const ShellSignUp: Story = {
  render: () => ({
    components: { UiAppHeader },
    template: `
      <div style="padding: 16px; background: var(--color-bg); max-width: 1120px; margin: 0 auto;">
        <UiAppHeader layout="centeredTitle" :title-level="1" aria-label="Шапка регистрации">
          <template #title>Регистрация</template>
        </UiAppHeader>
      </div>
    `,
  }),
};

/** Экран просмотра рецепта: `RecipeDetail.vue` — действия под шапкой */
export const RecipeView: Story = {
  render: () => ({
    components: { UiAppHeader },
    template: `
      <div style="padding: 16px; background: var(--color-bg); max-width: 1120px; margin: 0 auto;">
        <UiAppHeader aria-label="Шапка рецепта" title-align="center">
          <template #leading>
            <a href="#" class="ui-app-header-link--accent">К библиотеке</a>
          </template>
          <template #title>Борщ с говядиной</template>
          <template #actions>
            <button type="button" class="ui-app-header-link--accent">В план</button>
          </template>
        </UiAppHeader>
        <div style="margin-top: 12px; display: flex; flex-wrap: wrap; gap: var(--space-sm); align-items: center;">
          <a href="#" class="ui-app-header-link ui-app-header-link--secondary">Редактировать</a>
        </div>
      </div>
    `,
  }),
};

/** Экран редактора: `RecipeForm.vue` */
export const RecipeEdit: Story = {
  render: () => ({
    components: { UiAppHeader },
    template: `
      <div style="padding: 16px; background: var(--color-bg); max-width: 1120px; margin: 0 auto;">
        <UiAppHeader aria-label="Шапка редактора рецепта">
          <template #title>Редактор рецепта</template>
          <template #actions>
            <a href="#" class="ui-app-header-link--accent">К библиотеке</a>
          </template>
        </UiAppHeader>
      </div>
    `,
  }),
};
