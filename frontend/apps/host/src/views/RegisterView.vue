<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { UiButton, UiInput } from '@meal/ui-kit';
import { APP_ROUTES } from '../app/routes';
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
  <section class="auth-card" aria-label="Форма регистрации">
    <form class="form" @submit.prevent="onSubmit">
      <label class="field">
        <UiInput
          v-model="email"
          label="Email"
          type="email"
          placeholder="user@example.com"
          autocomplete="username"
          required
          data-testid="register-email"
        />
      </label>
      <label class="field">
        <UiInput
          v-model="password"
          label="Пароль (мин. 8 символов)"
          type="password"
          placeholder="********"
          autocomplete="new-password"
          required
          minlength="8"
          data-testid="register-password"
        />
      </label>
      <p v-if="error" class="err" data-testid="register-error">{{ error }}</p>
      <UiButton type="submit" :disabled="busy" data-testid="register-submit">
        {{ busy ? '…' : 'Создать аккаунт' }}
      </UiButton>
    </form>
    <p class="hint">
      Уже есть аккаунт?
      <RouterLink :to="APP_ROUTES.LOGIN">Вход</RouterLink>
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
.form {
  display: grid;
  gap: var(--space-md);
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
