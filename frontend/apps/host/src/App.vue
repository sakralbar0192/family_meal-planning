<script setup lang="ts">
import { defineAsyncComponent } from 'vue';

const RecipesRemote = defineAsyncComponent(() => import('mf_recipes/Entry'));
const PlannerRemote = defineAsyncComponent(() => import('mf_planner/Entry'));
const ShoppingRemote = defineAsyncComponent(() => import('mf_shopping/Entry'));
</script>

<template>
  <div class="shell">
    <header class="top">
      <h1>Планировщик питания</h1>
      <p class="sub">
        Host + Module Federation. Для разработки поднимите remotes: см.
        frontend/README.md
      </p>
    </header>

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
.shell {
  min-height: 100vh;
  background: var(--color-bg);
  color: var(--color-text-primary);
  font-family: Inter, system-ui, sans-serif;
  padding: var(--space-xl);
}
.top {
  margin-bottom: var(--space-xl);
}
h1 {
  margin: 0 0 var(--space-sm);
  font-size: var(--font-size-heading);
}
.sub {
  margin: 0;
  max-width: 48rem;
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
