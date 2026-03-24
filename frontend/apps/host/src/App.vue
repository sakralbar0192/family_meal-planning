<script setup lang="ts">
import { useSession } from './composables/useSession';

const { isLoggedIn, logout } = useSession();

async function onLogout(): Promise<void> {
  await logout();
}
</script>

<template>
  <div class="shell">
    <header class="top">
      <div class="head-row">
        <div>
          <h1>Планировщик питания</h1>
          <p class="sub">
            Host + Module Federation. Remotes: см. frontend/README.md
          </p>
        </div>
        <nav class="nav" aria-label="Аккаунт">
          <template v-if="isLoggedIn">
            <button
              type="button"
              class="linkish"
              data-testid="logout-button"
              @click="onLogout"
            >
              Выйти
            </button>
          </template>
          <template v-else>
            <RouterLink to="/login" data-testid="nav-login">Вход</RouterLink>
            <RouterLink to="/register" data-testid="nav-register">Регистрация</RouterLink>
          </template>
        </nav>
      </div>
    </header>

    <RouterView />
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
.head-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-md);
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
.nav {
  display: flex;
  gap: var(--space-md);
  align-items: center;
  font-size: var(--font-size-body);
}
.nav a {
  color: var(--color-text-primary);
  font-weight: 600;
  text-decoration: none;
}
.nav a.router-link-active {
  text-decoration: underline;
}
.linkish {
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  font-weight: 600;
  color: var(--color-text-primary);
  cursor: pointer;
  text-decoration: underline;
}
</style>
