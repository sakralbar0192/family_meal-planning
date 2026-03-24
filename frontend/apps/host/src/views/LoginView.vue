<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSession } from '../composables/useSession';

const router = useRouter();
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
    await router.push({ name: 'home' });
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Ошибка входа';
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <section class="auth-card">
    <h2>Вход</h2>
    <form class="form" @submit.prevent="onSubmit">
      <label class="field">
        <span>Email</span>
        <input
          v-model="email"
          type="email"
          autocomplete="username"
          required
          data-testid="login-email"
        />
      </label>
      <label class="field">
        <span>Пароль</span>
        <input
          v-model="password"
          type="password"
          autocomplete="current-password"
          required
          minlength="8"
          data-testid="login-password"
        />
      </label>
      <p v-if="error" class="err" data-testid="login-error">{{ error }}</p>
      <button type="submit" class="btn" :disabled="busy" data-testid="login-submit">
        {{ busy ? '…' : 'Войти' }}
      </button>
    </form>
    <p class="hint">
      Нет аккаунта?
      <RouterLink to="/register">Регистрация</RouterLink>
    </p>
  </section>
</template>

<style scoped>
.auth-card {
  max-width: 22rem;
  margin: 0 auto;
  padding: var(--space-xl);
  background: var(--color-surface);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
h2 {
  margin: 0 0 var(--space-lg);
  font-size: var(--font-size-title);
}
.form {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}
.field {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  font-size: var(--font-size-caption);
  color: var(--color-text-secondary);
}
.field input {
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  font-size: var(--font-size-body);
}
.err {
  margin: 0;
  color: var(--color-text-primary);
  font-size: var(--font-size-caption);
}
.btn {
  margin-top: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-md);
  border: none;
  background: var(--color-text-primary);
  color: var(--color-bg);
  font-weight: 600;
  cursor: pointer;
}
.btn:disabled {
  opacity: 0.6;
  cursor: default;
}
.hint {
  margin: var(--space-lg) 0 0;
  font-size: var(--font-size-body);
  color: var(--color-text-secondary);
}
.hint a {
  color: inherit;
  font-weight: 600;
}
</style>
