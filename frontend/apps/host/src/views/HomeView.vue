<script setup lang="ts">
import { createBffClient, resolveBffBaseUrl } from '@meal/bff-client';
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useSession } from '../composables/useSession';

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
  <section class="home">
    <header class="hero">
      <h2>Добро пожаловать</h2>
      <p class="muted">
        Планируйте питание на неделю, собирайте рецепты и формируйте список покупок.
      </p>
      <p class="bff">{{ bffStatus }}</p>
    </header>

    <article
      v-if="isLoggedIn === true"
      class="session-card session-card-ok"
      data-testid="session-banner"
    >
      <h3>Сессия активна</h3>
      <p class="muted">Можно переходить к рецептам и планировщику.</p>
    </article>

    <article
      v-else-if="isLoggedIn === false"
      class="session-card"
      data-testid="session-guest"
    >
      <h3>Гостевой режим</h3>
      <p class="muted">Войдите или зарегистрируйтесь, чтобы открыть рабочие разделы.</p>
    </article>

    <nav v-if="isLoggedIn" class="tiles" aria-label="Разделы">
      <RouterLink class="tile tile-primary" to="/recipes">Рецепты</RouterLink>
      <RouterLink class="tile tile-secondary" to="/planner">Планировщик</RouterLink>
    </nav>
  </section>
</template>

<style scoped>
.home {
  display: grid;
  gap: var(--space-md);
  width: 100%;
}

.hero {
  display: grid;
  gap: var(--space-sm);
  padding: var(--space-lg);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
}

h2 {
  margin: 0;
  font-size: var(--font-size-title);
}

h3 {
  margin: 0;
  font-size: var(--font-size-body);
}

.bff {
  margin: 0;
  font-size: var(--font-size-caption);
  color: var(--color-text-muted);
}

.session-card {
  display: grid;
  gap: var(--space-xs);
  margin: 0;
  padding: var(--space-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
}

.session-card-ok {
  border-color: color-mix(in srgb, var(--color-success) 45%, var(--color-border));
  background: color-mix(in srgb, var(--color-surface) 86%, var(--color-success));
}

.tiles {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-md);
}

.tile {
  display: inline-flex;
  min-height: var(--touch-target);
  padding: var(--space-md);
  border-radius: var(--radius-md);
  border: 1px solid transparent;
  font-weight: 600;
  font-size: var(--font-size-body);
  text-decoration: none;
}

.tile-primary {
  background: var(--color-accent);
  color: var(--color-text-on-accent);
}

.tile-primary:hover {
  background: var(--color-accent-hover);
}

.tile-secondary {
  border-color: var(--color-border);
  color: var(--color-text-primary);
  background: var(--color-surface);
}

.tile-secondary:hover {
  background: color-mix(in srgb, var(--color-surface) 92%, var(--color-text-primary));
}

.muted {
  margin: 0;
  color: var(--color-text-muted);
  font-size: var(--font-size-body);
}

@media (min-width: 768px) {
  .tiles {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1200px) {
  .hero {
    grid-template-columns: 1fr auto;
    align-items: end;
    column-gap: var(--space-xl);
  }
}
</style>
