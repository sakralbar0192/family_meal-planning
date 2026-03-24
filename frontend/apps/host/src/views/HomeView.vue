<script setup lang="ts">
import { createBffClient, resolveBffBaseUrl } from '@meal/bff-client';
import { defineAsyncComponent, onMounted, ref } from 'vue';
import { useSession } from '../composables/useSession';

const RecipesRemote = defineAsyncComponent(() => import('mf_recipes/Entry'));
const PlannerRemote = defineAsyncComponent(() => import('mf_planner/Entry'));
const ShoppingRemote = defineAsyncComponent(() => import('mf_shopping/Entry'));

const { isLoggedIn, refreshSession } = useSession();

const bffStatus = ref('BFF: …');
onMounted(async () => {
  const bff = createBffClient(resolveBffBaseUrl(import.meta.env.VITE_BFF_BASE_URL));
  try {
    const health = await bff.json<{ status: string }>('/health');
    bffStatus.value = health?.status === 'ok' ? 'BFF: ok' : 'BFF: неожиданный ответ';
  } catch {
    bffStatus.value = 'BFF: нет связи';
  }
  await refreshSession();
});
</script>

<template>
  <div class="home">
    <p class="bff">{{ bffStatus }}</p>
    <p
      v-if="isLoggedIn === true"
      class="session-ok"
      data-testid="session-banner"
    >
      Сессия активна (cookie BFF).
    </p>
    <p v-else-if="isLoggedIn === false" class="session-out" data-testid="session-guest">
      Войдите, чтобы работать с рецептами и планом.
    </p>

    <main class="grid">
      <Suspense>
        <RecipesRemote />
        <template #fallback>
          <p class="fallback">Загрузка mf-recipes…</p>
        </template>
      </Suspense>

      <Suspense>
        <PlannerRemote />
        <template #fallback>
          <p class="fallback">Загрузка mf-planner…</p>
        </template>
      </Suspense>

      <Suspense>
        <ShoppingRemote />
        <template #fallback>
          <p class="fallback">Загрузка mf-shopping…</p>
        </template>
      </Suspense>
    </main>
  </div>
</template>

<style scoped>
.home {
  width: 100%;
}
.bff {
  margin: 0 0 var(--space-sm);
  font-size: var(--font-size-caption);
  color: var(--color-text-muted);
}
.session-ok {
  margin: 0 0 var(--space-md);
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-md);
  background: color-mix(in srgb, var(--color-surface) 90%, var(--color-text-primary));
  border: 1px solid var(--color-border);
  font-size: var(--font-size-body);
}
.session-out {
  margin: 0 0 var(--space-md);
  color: var(--color-text-secondary);
  font-size: var(--font-size-body);
}
.grid {
  display: grid;
  gap: var(--space-lg);
  grid-template-columns: 1fr;
}
@media (min-width: 900px) {
  .grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
.fallback {
  padding: var(--space-lg);
  color: var(--color-text-muted);
  font-size: var(--font-size-body);
}
</style>
