<script setup lang="ts">
import { resetShellHeader, shellHeaderState } from '@meal/shell-chrome';
import { UiAppHeader } from '@meal/ui-kit';
import { watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import ShellVnode from './components/ShellVnode.vue';
import { APP_ROUTES } from './app/routes';
import { useSession } from './composables/useSession';
import { isDemoMode } from './demo/mode';

const route = useRoute();
const demoMode = isDemoMode();
const { isLoggedIn, logout } = useSession();

watch(
  () => route.name,
  (name) => {
    if (name === 'login' || name === 'register' || name === 'profile') {
      resetShellHeader();
    }
  },
);

async function onLogout(): Promise<void> {
  await logout();
}
</script>

<template>
  <div class="shell">
    <UiAppHeader
      v-if="route.name === 'login' && !demoMode"
      class="shell-header"
      layout="centeredTitle"
      :title-level="1"
      aria-label="Шапка входа"
    >
      <template #title>Вход в аккаунт</template>
    </UiAppHeader>

    <UiAppHeader
      v-else-if="route.name === 'register' && !demoMode"
      class="shell-header"
      layout="centeredTitle"
      :title-level="1"
      aria-label="Шапка регистрации"
    >
      <template #title>Регистрация</template>
    </UiAppHeader>

    <UiAppHeader
      v-else-if="route.name === 'profile'"
      class="shell-header"
      layout="centeredTitle"
      :title-level="1"
      aria-label="Шапка профиля"
    >
      <template #title>Профиль</template>
    </UiAppHeader>

    <UiAppHeader
      v-else
      class="shell-header"
      :layout="shellHeaderState.layout"
      :title-level="shellHeaderState.titleLevel"
      :title-align="shellHeaderState.titleAlign"
      :aria-label="shellHeaderState.ariaLabel || 'Приложение'"
    >
      <template v-if="shellHeaderState.eyebrow" #eyebrow>{{ shellHeaderState.eyebrow }}</template>
      <template v-if="shellHeaderState.title" #title>{{ shellHeaderState.title }}</template>
      <template v-if="shellHeaderState.leadingRender" #leading>
        <ShellVnode :factory="shellHeaderState.leadingRender" />
      </template>
      <template v-if="shellHeaderState.sublineRender" #subline>
        <ShellVnode :factory="shellHeaderState.sublineRender" />
      </template>
      <template v-if="shellHeaderState.actionsRender" #actions>
        <ShellVnode :factory="shellHeaderState.actionsRender" />
      </template>
    </UiAppHeader>

    <div v-if="shellHeaderState.showAppNav" class="shell-app-nav">
      <nav class="shell-nav" aria-label="Основная навигация">
        <template v-if="demoMode || isLoggedIn">
          <RouterLink class="ui-app-header-link" to="/recipes" data-testid="nav-recipes">Рецепты</RouterLink>
          <RouterLink class="ui-app-header-link" to="/planner" data-testid="nav-planner">Планировщик</RouterLink>
          <template v-if="!demoMode">
            <RouterLink class="ui-app-header-link ui-app-header-link--secondary" to="/profile" data-testid="nav-profile">
              Профиль
            </RouterLink>
            <button
              type="button"
              class="ui-app-header-link ui-app-header-link--secondary"
              data-testid="logout-button"
              @click="onLogout"
            >
              Выйти
            </button>
          </template>
        </template>
        <template v-else>
          <RouterLink class="ui-app-header-link ui-app-header-link--secondary" :to="APP_ROUTES.LOGIN" data-testid="nav-login">
            Вход
          </RouterLink>
          <RouterLink class="ui-app-header-link" :to="APP_ROUTES.REGISTER" data-testid="nav-register">Регистрация</RouterLink>
        </template>
      </nav>
    </div>

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

.shell-header {
  margin: 0 auto var(--space-sm);
  max-width: 960px;
}

/* Глобальная навигация вне карточки TopBar (Figma: в TopBar только контент экрана) */
.shell-app-nav {
  margin: 0 auto var(--space-lg);
  max-width: 960px;
  display: flex;
  justify-content: flex-end;
}

.shell-nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: var(--space-sm);
}

.content {
  max-width: 960px;
  margin: 0 auto;
}

@media (min-width: 768px) {
  .shell {
    padding: var(--space-lg);
  }

  .shell-header {
    margin-bottom: var(--space-sm);
  }

  .shell-app-nav {
    margin-bottom: var(--space-xl);
  }
}

@media (min-width: 1200px) {
  .shell {
    padding: var(--space-xl);
  }

  .shell-header,
  .shell-app-nav,
  .content {
    max-width: 1120px;
  }
}
</style>
