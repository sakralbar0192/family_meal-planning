import type { Meta, StoryObj } from '@storybook/vue3-vite';
import { UiButton } from './index';

const meta: Meta<typeof UiButton> = {
  title: 'UI Kit/UiButton',
  component: UiButton,
  args: { variant: 'primary', size: 'md' },
  parameters: {
    layout: 'fullscreen',
    docs: {
      description: {
        component:
          'Кнопки по макету Figma: страница **01 — Токены и UI Kit**, секция **C**, фрейм **UiButton — все сторис** (`3394:1513`). Варианты `primary` / `secondary` / `danger`, размеры `md` / `sm`. Text-стили шапки (`ui-app-header-link--accent`, `--ghost`) — сторя **UI Kit/UiAppHeader** и `UiAppHeader.vue`.',
      },
    },
  },
};

export default meta;
type Story = StoryObj<typeof UiButton>;

export const Primary: Story = { args: {}, render: (args) => ({ components: { UiButton }, setup: () => ({ args }), template: '<UiButton v-bind="args">Primary</UiButton>' }) };
export const Secondary: Story = { args: { variant: 'secondary' }, render: (args) => ({ components: { UiButton }, setup: () => ({ args }), template: '<UiButton v-bind="args">Secondary</UiButton>' }) };
export const DangerSmall: Story = { args: { variant: 'danger', size: 'sm' }, render: (args) => ({ components: { UiButton }, setup: () => ({ args }), template: '<UiButton v-bind="args">Delete</UiButton>' }) };

/** Зеркало фрейма Figma `3394:1513` — все комбинации `UiButton` на одном экране. */
export const AllVariants: Story = {
  render: () => ({
    components: { UiButton },
    template: `
      <div style="padding: 24px 48px 48px; background: var(--color-bg); max-width: 1120px; margin: 0 auto; display: flex; flex-direction: column; gap: 32px;">
        <div>
          <p style="margin: 0 0 8px; font-size: 14px; font-weight: 600;">UiButton — матрица (код)</p>
          <p style="margin: 0; font-size: 12px; color: var(--color-text-muted); line-height: 1.4;">
            Figma: <strong>UiButton — все сторис</strong> (<code>3394:1513</code>). Ниже только <code>UiButton</code>;
            text / шапка / легаси <code>.btn</code> — см. макет и подписи в Figma.
          </p>
        </div>
        <section>
          <h3 style="margin: 0 0 12px; font-size: 13px; font-weight: 600;">Размер md</h3>
          <div style="display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
              <span style="font-size: 12px; color: var(--color-text-muted); width: 140px;">primary</span>
              <UiButton>Default</UiButton>
              <UiButton disabled>Disabled</UiButton>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
              <span style="font-size: 12px; color: var(--color-text-muted); width: 140px;">secondary</span>
              <UiButton variant="secondary">Default</UiButton>
              <UiButton variant="secondary" disabled>Disabled</UiButton>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
              <span style="font-size: 12px; color: var(--color-text-muted); width: 140px;">danger</span>
              <UiButton variant="danger">Default</UiButton>
              <UiButton variant="danger" disabled>Disabled</UiButton>
            </div>
          </div>
        </section>
        <section>
          <h3 style="margin: 0 0 12px; font-size: 13px; font-weight: 600;">Размер sm</h3>
          <div style="display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
              <span style="font-size: 12px; color: var(--color-text-muted); width: 140px;">primary</span>
              <UiButton size="sm">Default</UiButton>
              <UiButton size="sm" disabled>Disabled</UiButton>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
              <span style="font-size: 12px; color: var(--color-text-muted); width: 140px;">secondary</span>
              <UiButton size="sm" variant="secondary">Default</UiButton>
              <UiButton size="sm" variant="secondary" disabled>Disabled</UiButton>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
              <span style="font-size: 12px; color: var(--color-text-muted); width: 140px;">danger</span>
              <UiButton size="sm" variant="danger">Default</UiButton>
              <UiButton size="sm" variant="danger" disabled>Disabled</UiButton>
            </div>
          </div>
        </section>
      </div>
    `,
  }),
};
