<script setup lang="ts">
import { setShellHeader } from '@meal/shell-chrome';
import { h, onMounted, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useSession } from '../composables/useSession';
import { getBff } from '../bff';
import { isDemoMode } from '../demo/mode';

const { isLoggedIn, refreshSession } = useSession();
const router = useRouter();

const bffStatus = ref('BFF: …');

function applyHomeShell(): void {
  setShellHeader({
    ariaLabel: 'Главная страница',
    title: 'Добро пожаловать',
    showAppNav: true,
    leadingRender: null,
    actionsRender: null,
    sublineRender: () =>
      h('div', { class: 'shell-host-welcome' }, [
        h(
          'p',
          { class: 'shell-host-welcome__lead' },
          'Планируйте питание на любой срок, собирайте рецепты и формируйте список покупок.',
        ),
        h('p', { class: 'shell-host-welcome__bff' }, bffStatus.value),
      ]),
  });
}

watch(bffStatus, applyHomeShell);

onMounted(async () => {
  applyHomeShell();
  const bff = getBff();
  try {
    const health = await bff.json<{ status: string }>('/health');
    bffStatus.value = health?.status === 'ok' ? 'BFF: ok' : 'BFF: неожиданный ответ';
  } catch {
    bffStatus.value = 'BFF: нет связи';
  }
  await refreshSession();
  applyHomeShell();
  if (isDemoMode()) {
    await router.replace('/recipes');
    return;
  }
});
</script>

<template>
  <section class="home">
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

h3 {
  margin: 0;
  font-size: var(--font-size-body);
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
</style>
