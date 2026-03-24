<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSession } from '../composables/useSession';

const router = useRouter();
const { register, login } = useSession();

const email = ref('');
const password = ref('');
const error = ref('');
const busy = ref(false);

async function onSubmit(): Promise<void> {
  error.value = '';
  busy.value = true;
  try {
    await register(email.value.trim(), password.value);
    await login(email.value.trim(), password.value);
    await router.push({ name: 'home' });
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Ошибка регистрации';
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <section class="auth-card">
    <p class="eyebrow">Auth</p>
    <h2>Регистрация</h2>
    <form class="form" @submit.prevent="onSubmit">
      <label class="field">
        <span>Email</span>
        <input
          v-model="email"
          type="email"
          autocomplete="username"
          required
          data-testid="register-email"
        />
      </label>
      <label class="field">
        <span>Пароль (мин. 8 символов)</span>
        <input
          v-model="password"
          type="password"
          autocomplete="new-password"
          required
          minlength="8"
          data-testid="register-password"
        />
      </label>
      <p v-if="error" class="err" data-testid="register-error">{{ error }}</p>
      <button type="submit" class="btn" :disabled="busy" data-testid="register-submit">
        {{ busy ? '…' : 'Создать аккаунт' }}
      </button>
    </form>
    <p class="hint">
      Уже есть аккаунт?
      <RouterLink to="/login">Вход</RouterLink>
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
.field input {
  min-height: var(--input-min-height);
  padding: 0 var(--space-md);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: var(--color-text-primary);
  font-size: var(--font-size-body);
}
.err {
  margin: 0;
  color: var(--color-error);
  font-size: var(--font-size-caption);
}
.btn {
  min-height: var(--button-min-height);
  padding: 0 var(--space-md);
  border-radius: var(--radius-md);
  border: none;
  background: var(--color-accent);
  color: var(--color-text-on-accent);
  font-weight: 600;
  cursor: pointer;
}
.btn:hover {
  background: var(--color-accent-hover);
}
.btn:disabled {
  opacity: 0.6;
  cursor: default;
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
