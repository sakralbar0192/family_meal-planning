<script setup lang="ts">
import { createBffClient, resolveBffBaseUrl } from '@meal/bff-client';
import { setShellHeader } from '@meal/shell-chrome';
import { h, onMounted, ref, watch } from 'vue';

const bffLine = ref('');

function pushEntryShell(): void {
  setShellHeader({
    ariaLabel: 'Микрофронт рецептов',
    title: 'Рецепты (mf-recipes)',
    showAppNav: false,
    sublineRender: () =>
      h('div', null, [
        h('p', { class: 'mf-entry-intro' }, 'Микрофронт: библиотека, редактор, импорт. API через BFF — см. contracts/bff-routes.md.'),
        h('p', { class: 'mf-entry-meta' }, bffLine.value),
      ]),
  });
}

watch(bffLine, pushEntryShell);

onMounted(async () => {
  const bff = createBffClient(resolveBffBaseUrl(import.meta.env.VITE_BFF_BASE_URL));
  try {
    await bff.json<{ status: string }>('/health');
    bffLine.value = 'Запросы к BFF с credentials: include (проверка /health ok).';
  } catch {
    bffLine.value = 'BFF недоступен — поднимите стек или задайте VITE_BFF_BASE_URL.';
  }
  pushEntryShell();
});
</script>

<template>
  <section class="mf-root" />
</template>

<style scoped>
.mf-root {
  font-family: Inter, system-ui, sans-serif;
  padding: var(--space-lg);
  color: var(--color-text-primary);
  background: var(--color-bg);
}
</style>

<style>
.mf-entry-intro {
  margin: 0;
  font-size: var(--font-size-body);
  color: var(--color-text-secondary);
}
.mf-entry-meta {
  margin: var(--space-sm) 0 0;
  font-size: var(--font-size-caption);
  color: var(--color-text-muted);
}
</style>
