<script setup lang="ts">
import { RouterLink } from 'vue-router';
import { useSession } from './composables/useSession';

const { isLoggedIn, logout } = useSession();

async function onLogout(): Promise<void> {
  await logout();
}
</script>

<template>
  <div class="shell">
    <header class="topbar">
      <div class="topbar-text">
        <p class="eyebrow">Family meal planning</p>
        <h1>Планировщик питания</h1>
      </div>
      <nav class="nav" aria-label="Основная навигация">
        <template v-if="isLoggedIn">
          <RouterLink class="nav-link" to="/recipes" data-testid="nav-recipes">Рецепты</RouterLink>
          <RouterLink class="nav-link" to="/planner" data-testid="nav-planner">Планировщик</RouterLink>
          <button
            type="button"
            class="nav-link nav-link-ghost"
            data-testid="logout-button"
            @click="onLogout"
          >
            Выйти
          </button>
        </template>
        <template v-else>
          <RouterLink class="nav-link nav-link-ghost" to="/login" data-testid="nav-login">Вход</RouterLink>
          <RouterLink class="nav-link" to="/register" data-testid="nav-register">Регистрация</RouterLink>
        </template>
      </nav>
    </header>

    <main class="content">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.shell {
  min-height: 100vh;
  background: var(--color-bg);
  color: var(--color-text-primary);
  font-family: Inter, system-ui, sans-serif;
  padding: var(--space-md);
}

.topbar {
  display: grid;
  gap: var(--space-md);
  margin: 0 auto var(--space-lg);
  max-width: 960px;
  padding: var(--space-lg);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
}

.topbar-text {
  display: grid;
  gap: var(--space-xs);
}

.eyebrow {
  margin: 0;
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

h1 {
  margin: 0;
  font-size: var(--font-size-heading);
}

.nav {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-sm);
}

.nav-link {
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
  text-decoration: none;
}

.nav-link:hover {
  background: var(--color-accent-hover);
}

.nav-link-ghost {
  background: transparent;
  color: var(--color-text-primary);
  border-color: var(--color-border);
}

.nav-link-ghost:hover {
  background: color-mix(in srgb, var(--color-surface) 92%, var(--color-text-primary));
}

.nav-link.router-link-active {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 2px;
}

.content {
  max-width: 960px;
  margin: 0 auto;
}

button.nav-link {
  width: 100%;
  font: inherit;
  cursor: pointer;
}

@media (min-width: 768px) {
  .shell {
    padding: var(--space-lg);
  }

  .topbar {
    margin-bottom: var(--space-xl);
  }

  .nav {
    grid-template-columns: repeat(3, max-content);
    justify-content: start;
  }

  button.nav-link {
    width: auto;
  }
}

@media (min-width: 1200px) {
  .shell {
    padding: var(--space-xl);
  }

  .topbar,
  .content {
    max-width: 1120px;
  }
}
</style>
