<script setup lang="ts">
import { computed, useSlots } from 'vue';

const props = withDefaults(
  defineProps<{
    titleLevel?: 1 | 2;
    ariaLabel?: string;
    /** `centeredTitle`: одна строка, заголовок по центру (вход / регистрация). */
    layout?: 'default' | 'centeredTitle';
    /** Для `default` + `center`: на ≥768 заголовок по центру TopBar (сетка 1fr / auto / 1fr, DS / UiAppHeader). */
    titleAlign?: 'start' | 'center';
  }>(),
  {
    titleLevel: 2,
    ariaLabel: undefined,
    layout: 'default',
    titleAlign: 'start',
  },
);

const slots = useSlots();

const titleTag = computed(() => (props.titleLevel === 1 ? 'h1' : 'h2'));

const hasEyebrow = computed(() => Boolean(slots.eyebrow));
const hasTitle = computed(() => Boolean(slots.title));
const hasSubline = computed(() => Boolean(slots.subline));
const hasLeading = computed(() => Boolean(slots.leading));
const hasActions = computed(() => Boolean(slots.actions));
const hasTitleBlock = computed(() => Boolean(slots.titleBlock));

/** Трёхколоночная сетка 1fr / auto / 1fr — как в DS / UiAppHeader (Figma C, Storybook). */
const balancedTitleRow = computed(
  () => props.layout === 'default' && props.titleAlign === 'center' && !hasTitleBlock.value,
);

const headerClass = computed(() => [
  'ui-app-header',
  props.layout === 'centeredTitle' && 'ui-app-header--centered-title',
  props.titleAlign === 'center' && 'ui-app-header--title-center',
  balancedTitleRow.value && 'ui-app-header--title-center-balanced',
]);
</script>

<template>
  <header class="ui-app-header-root" :class="headerClass" :aria-label="ariaLabel">
    <template v-if="layout === 'centeredTitle'">
      <div class="ui-app-header__centered">
        <component
          :is="titleTag"
          v-if="hasTitle"
          class="ui-app-header__title"
          :class="titleLevel === 1 ? 'ui-app-header__title--shell' : 'ui-app-header__title--screen'"
        >
          <slot name="title" />
        </component>
      </div>
    </template>
    <template v-else-if="balancedTitleRow">
      <div class="ui-app-header__balanced">
        <div class="ui-app-header__balanced-start">
          <div v-if="hasLeading" class="ui-app-header__leading">
            <slot name="leading" />
          </div>
        </div>
        <div class="ui-app-header__titles">
          <p v-if="hasEyebrow" class="ui-app-header__eyebrow">
            <slot name="eyebrow" />
          </p>
          <component
            :is="titleTag"
            v-if="hasTitle"
            class="ui-app-header__title"
            :class="titleLevel === 1 ? 'ui-app-header__title--shell' : 'ui-app-header__title--screen'"
          >
            <slot name="title" />
          </component>
          <div v-if="hasSubline" class="ui-app-header__subline">
            <slot name="subline" />
          </div>
        </div>
        <div class="ui-app-header__balanced-end">
          <div v-if="hasActions" class="ui-app-header__actions">
            <slot name="actions" />
          </div>
        </div>
      </div>
    </template>
    <template v-else>
      <div class="ui-app-header__main">
        <div v-if="hasLeading" class="ui-app-header__leading">
          <slot name="leading" />
        </div>
        <div v-if="hasTitleBlock" class="ui-app-header__title-block">
          <slot name="titleBlock" />
        </div>
        <div v-else class="ui-app-header__titles">
          <p v-if="hasEyebrow" class="ui-app-header__eyebrow">
            <slot name="eyebrow" />
          </p>
          <component
            :is="titleTag"
            v-if="hasTitle"
            class="ui-app-header__title"
            :class="titleLevel === 1 ? 'ui-app-header__title--shell' : 'ui-app-header__title--screen'"
          >
            <slot name="title" />
          </component>
          <div v-if="hasSubline" class="ui-app-header__subline">
            <slot name="subline" />
          </div>
        </div>
      </div>
      <div v-if="hasActions" class="ui-app-header__actions">
        <slot name="actions" />
      </div>
    </template>
  </header>
</template>

<style scoped>
.ui-app-header-root {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  padding: var(--space-md) var(--space-xl);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
  color: var(--color-text-primary);
  font-family: Inter, system-ui, sans-serif;
}

.ui-app-header-root.ui-app-header--centered-title {
  flex-direction: row;
  align-items: center;
  justify-content: center;
  min-height: var(--touch-target);
}

.ui-app-header__centered {
  display: flex;
  width: 100%;
  justify-content: center;
  align-items: center;
}

.ui-app-header__main {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  min-width: 0;
  flex: 1;
}

.ui-app-header__leading {
  align-self: flex-start;
}

.ui-app-header__title-block {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  min-width: 0;
  flex: 1;
}

.ui-app-header__title-block :deep(h1),
.ui-app-header__title-block :deep(h2) {
  margin: 0;
  font-weight: 600;
  line-height: 1.2;
  font-size: var(--font-size-title);
}

.ui-app-header__titles {
  display: grid;
  gap: var(--space-xs);
  min-width: 0;
}

.ui-app-header__eyebrow {
  margin: 0;
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.ui-app-header__title {
  margin: 0;
  font-weight: 600;
  line-height: 1.2;
}

.ui-app-header__title--shell {
  font-size: var(--font-size-heading);
}

.ui-app-header__title--screen {
  font-size: var(--font-size-title);
}

.ui-app-header__subline {
  margin: 0;
  font-size: var(--font-size-caption);
  color: var(--color-text-secondary);
}

.ui-app-header__subline:empty {
  display: none;
}

.ui-app-header__subline :deep(.muted) {
  color: var(--color-text-muted);
}

.ui-app-header__actions {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-sm);
}

.ui-app-header__balanced {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  width: 100%;
  min-width: 0;
}

.ui-app-header__balanced .ui-app-header__titles {
  text-align: center;
}

.ui-app-header__balanced-start {
  align-self: flex-start;
  min-width: 0;
}

.ui-app-header__balanced-end {
  align-self: stretch;
  min-width: 0;
}

@media (min-width: 768px) {
  .ui-app-header-root:not(.ui-app-header--centered-title) {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    flex-wrap: nowrap;
    gap: var(--space-md);
  }

  .ui-app-header-root.ui-app-header--title-center-balanced:not(.ui-app-header--centered-title)
    > .ui-app-header__balanced {
    flex: 1 1 auto;
    width: 100%;
    min-width: 0;
  }

  .ui-app-header__balanced {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    gap: var(--space-md);
  }

  .ui-app-header__balanced-start {
    justify-self: start;
    align-self: center;
  }

  .ui-app-header__balanced .ui-app-header__titles {
    justify-self: center;
    max-width: 100%;
  }

  .ui-app-header__balanced-end {
    justify-self: end;
    align-self: center;
  }

  .ui-app-header-root:not(.ui-app-header--centered-title) .ui-app-header__main {
    flex-direction: row;
    align-items: center;
    gap: var(--space-lg);
    flex: 1;
    min-width: 0;
  }

  .ui-app-header-root.ui-app-header--title-center:not(.ui-app-header--title-center-balanced) .ui-app-header__titles {
    flex: 1;
    text-align: center;
    justify-items: center;
  }

  .ui-app-header-root.ui-app-header--title-center:not(.ui-app-header--title-center-balanced) .ui-app-header__title {
    justify-self: center;
  }

  .ui-app-header-root:not(.ui-app-header--centered-title) .ui-app-header__title-block {
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-md);
  }

  .ui-app-header__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-sm);
    flex-shrink: 0;
  }
}
</style>

<style>
/* Global link/button look for slots (RouterLink, <a>, <button>) */
.ui-app-header-link {
  box-sizing: border-box;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: var(--touch-target);
  padding: 0 var(--space-md);
  border-radius: var(--radius-md);
  border: 1px solid transparent;
  background: var(--color-accent);
  color: var(--color-text-on-accent);
  font-weight: 600;
  font-size: var(--font-size-body);
  font-family: inherit;
  text-decoration: none;
  cursor: pointer;
}

.ui-app-header-link:hover {
  background: var(--color-accent-hover);
}

.ui-app-header-link--secondary {
  background: transparent;
  color: var(--color-text-primary);
  border-color: var(--color-border);
}

.ui-app-header-link--secondary:hover {
  background: color-mix(in srgb, var(--color-surface) 92%, var(--color-text-primary));
}

.ui-app-header-link--back {
  background: var(--color-surface);
  color: var(--color-text-secondary);
  border-color: var(--color-border);
}

.ui-app-header-link--back:hover {
  background: color-mix(in srgb, var(--color-surface) 88%, var(--color-text-primary));
}

/* DS / Button / Ghost — текстовая кнопка в шапке */
.ui-app-header-link--ghost {
  background: transparent;
  border: none;
  color: var(--color-text-primary);
  font-weight: 500;
  min-height: var(--touch-target);
  padding: 10px 20px;
  border-radius: var(--radius-md);
}

.ui-app-header-link--ghost:hover {
  background: color-mix(in srgb, var(--color-surface) 88%, var(--color-text-primary));
}

/* Текстовая ссылка accent (14 medium в макете) */
.ui-app-header-link--accent {
  background: transparent;
  border: none;
  color: var(--color-accent);
  font-weight: 500;
  font-size: var(--font-size-body);
  min-height: var(--touch-target);
  padding: 0 var(--space-sm);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-family: inherit;
  cursor: pointer;
}

button.ui-app-header-link--accent {
  appearance: none;
}

.ui-app-header-link--accent:hover {
  color: var(--color-accent-hover);
}

a.ui-app-header-link--accent:focus-visible,
button.ui-app-header-link--accent:focus-visible {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 2px;
}

.ui-app-header-meta {
  font-size: var(--font-size-body);
  color: var(--color-text-muted);
  font-weight: 400;
  line-height: 1.3;
  align-self: center;
}

.ui-app-header-link.router-link-active,
.ui-app-header-link.router-link-exact-active,
.ui-app-header-link--accent.router-link-active,
.ui-app-header-link--accent.router-link-exact-active,
.ui-app-header-link--ghost.router-link-active,
.ui-app-header-link--ghost.router-link-exact-active {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 2px;
}

.ui-app-header__actions > * {
  width: 100%;
}

@media (min-width: 768px) {
  .ui-app-header__actions > * {
    width: auto;
  }
}
</style>
