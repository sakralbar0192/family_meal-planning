<template>
  <section class="mf-root">
    <h2>Рецепты (mf-recipes)</h2>
    <p>Микрофронт: библиотека, редактор, импорт. API через BFF — см. contracts/bff-routes.md.</p>
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
</style>
