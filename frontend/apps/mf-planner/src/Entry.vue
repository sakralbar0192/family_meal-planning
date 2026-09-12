<script setup lang="ts">
import { createBffClient, resolveBffBaseUrl } from '@meal/bff-client';
import { setShellHeader } from '@meal/shell-chrome';
import { h, onMounted, ref, watch } from 'vue';

const bffLine = ref('');

function pushEntryShell(): void {
  setShellHeader({
    ariaLabel: 'Микрофронт планировщика',
    title: 'Планировщик (mf-planner)',
    showAppNav: false,
    sublineRender: () =>
      h('div', null, [
        h('p', { class: 'mf-entry-intro' }, 'Неделя, слоты, DnD, панель периода для списка покупок.'),
        h('p', { class: 'mf-entry-meta' }, bffLine.value),
      ]),
  });
}

watch(bffLine, pushEntryShell);

onMounted(async () => {
  const bff = createBffClient(resolveBffBaseUrl(import.meta.env.VITE_BFF_BASE_URL));
  try {
    await bff.json<{ status: string }>('/health');
    bffLine.value = 'Клиент BFF: credentials include; далее GET /plan/week и PATCH слотов.';
  } catch {
    bffLine.value = 'BFF недоступен — см. frontend/README.md (CORS, VITE_BFF_BASE_URL).';
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
