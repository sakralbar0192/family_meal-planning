<script setup lang="ts">
import { createBffClient, resolveBffBaseUrl } from '@meal/bff-client';
import { onMounted, ref } from 'vue';

const bffLine = ref('');
onMounted(async () => {
  const bff = createBffClient(resolveBffBaseUrl(import.meta.env.VITE_BFF_BASE_URL));
  try {
    await bff.json<{ status: string }>('/health');
    bffLine.value = 'Далее: POST /shopping/build, GET списка и строки — через тот же клиент.';
  } catch {
    bffLine.value = 'BFF недоступен — поднимите bff-web или проверьте URL.';
  }
});
</script>

<template>
  <section class="mf-root">
    <h2>Список покупок (mf-shopping)</h2>
    <p>Агрегация по периоду, группировка, экспорт в буфер.</p>
    <p v-if="bffLine" class="meta">{{ bffLine }}</p>
  </section>
</template>

<style scoped>
.mf-root {
  font-family: Inter, system-ui, sans-serif;
  padding: var(--space-lg);
  color: var(--color-text-primary);
  background: var(--color-surface);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
h2 {
  margin: 0 0 var(--space-sm);
  font-size: var(--font-size-title);
}
p {
  margin: 0;
  font-size: var(--font-size-body);
  color: var(--color-text-secondary);
}
.meta {
  margin-top: var(--space-sm);
  font-size: var(--font-size-caption);
  color: var(--color-text-muted);
}
</style>
