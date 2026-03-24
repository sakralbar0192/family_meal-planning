<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { UiButton, UiInput } from '@meal/ui-kit';
import { useSession } from '../composables/useSession';

const router = useRouter();
const route = useRoute();
const { login } = useSession();

const email = ref('');
const password = ref('');
const error = ref('');
const busy = ref(false);

async function onSubmit(): Promise<void> {
  error.value = '';
  busy.value = true;
  try {
    await login(email.value.trim(), password.value);
    const redir = route.query.redirect;
    if (typeof redir === 'string' && redir.startsWith('/') && !redir.startsWith('//')) {
      await router.push(redir);
    } else {
      await router.push({ name: 'home' });
    }
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Ошибка входа';
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <section class="auth-card">
    <p class="eyebrow">Auth</p>
    <h2>Вход</h2>
    <form class="form" @submit.prevent="onSubmit">
      <label class="field">
        <UiInput
          v-model="email"
          label="Email"
          type="email"
          placeholder="user@example.com"
          data-testid="login-email"
        />
      </label>
      <label class="field">
        <UiInput
          v-model="password"
          label="Пароль"
          type="password"
          placeholder="********"
          data-testid="login-password"
        />
      </label>
      <p v-if="error" class="err" data-testid="login-error">{{ error }}</p>
      <UiButton type="submit" :disabled="busy" data-testid="login-submit">
        {{ busy ? '…' : 'Войти' }}
      </UiButton>
    </form>
    <p class="hint">
      Нет аккаунта?
      <RouterLink to="/register">Регистрация</RouterLink>
    </p>
  </section>
</template>

<style scoped>
.auth-card {
  max-width: 28rem;
  margin: 0 auto;
  padding: var(--space-lg);
  background: var(--color-bg-elevated);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  display: grid;
  gap: var(--space-sm);
}
.eyebrow {
  margin: 0;
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
h2 {
  margin: 0;
  font-size: var(--font-size-title);
}
.form {
  display: grid;
  gap: var(--space-md);
  margin-top: var(--space-xs);
}
.field {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  font-size: var(--font-size-caption);
  color: var(--color-text-secondary);
}
.err {
  margin: 0;
  color: var(--color-error);
  font-size: var(--font-size-caption);
}
.hint {
  margin: var(--space-sm) 0 0;
  font-size: var(--font-size-caption);
  color: var(--color-text-secondary);
}
.hint a {
  color: var(--color-text-primary);
  font-weight: 600;
  text-decoration: none;
}
@media (min-width: 768px) {
  .auth-card {
    padding: var(--space-xl);
  }
}
</style>
