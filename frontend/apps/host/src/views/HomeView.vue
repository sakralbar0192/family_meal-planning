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

    <nav v-if="isLoggedIn" class="tiles" aria-label="Разделы">
      <RouterLink class="tile" to="/recipes">Рецепты</RouterLink>
      <RouterLink class="tile" to="/planner">Планировщик</RouterLink>
    </nav>
    <p v-else class="hint muted">После входа откроются разделы «Рецепты» и «Планировщик».</p>
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
.tiles {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
  margin-top: var(--space-lg);
}
.tile {
  display: inline-flex;
  padding: var(--space-lg) var(--space-xl);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text-primary);
  font-weight: 600;
  text-decoration: none;
}
.tile:hover {
  background: color-mix(in srgb, var(--color-surface) 92%, var(--color-text-primary));
}
.hint {
  margin-top: var(--space-md);
}
.muted {
  color: var(--color-text-muted);
}
</style>
