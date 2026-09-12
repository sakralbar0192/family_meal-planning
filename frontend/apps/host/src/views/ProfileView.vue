<script setup lang="ts">
import { bffErrorFromResponse } from '@meal/bff-client';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { UiButton, UiInput } from '@meal/ui-kit';
import { getBff } from '../bff';
import { useSession } from '../composables/useSession';

const router = useRouter();
const { logout } = useSession();

const currentPassword = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const error = ref('');
const success = ref('');
const busy = ref(false);

async function onSubmit(): Promise<void> {
  error.value = '';
  success.value = '';
  if (newPassword.value.length < 8) {
    error.value = 'Новый пароль должен быть не короче 8 символов.';
    return;
  }
  if (newPassword.value !== confirmPassword.value) {
    error.value = 'Пароли не совпадают.';
    return;
  }
  busy.value = true;
  try {
    const bff = getBff();
    const r = await bff.fetch('/auth/password', {
      method: 'POST',
      body: JSON.stringify({
        currentPassword: currentPassword.value,
        newPassword: newPassword.value,
      }),
    });
    if (!r.ok) {
      const err = await bffErrorFromResponse(r);
      throw new Error(err.message);
    }
    success.value = 'Пароль обновлён.';
    currentPassword.value = '';
    newPassword.value = '';
    confirmPassword.value = '';
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Не удалось сменить пароль.';
  } finally {
    busy.value = false;
  }
}

async function onLogout(): Promise<void> {
  await logout();
  await router.push({ name: 'login' });
}
</script>

<template>
  <section class="auth-card" aria-label="Профиль">
    <h2 class="heading">Профиль</h2>
    <p class="muted">Смена пароля и выход из аккаунта.</p>

    <form class="form" @submit.prevent="onSubmit">
      <UiInput
        v-model="currentPassword"
        label="Текущий пароль"
        type="password"
        autocomplete="current-password"
        data-testid="profile-current-password"
      />
      <UiInput
        v-model="newPassword"
        label="Новый пароль"
        type="password"
        autocomplete="new-password"
        data-testid="profile-new-password"
      />
      <UiInput
        v-model="confirmPassword"
        label="Подтверждение пароля"
        type="password"
        autocomplete="new-password"
        data-testid="profile-confirm-password"
      />
      <p v-if="error" class="err" data-testid="profile-error">{{ error }}</p>
      <p v-if="success" class="ok" data-testid="profile-success">{{ success }}</p>
      <UiButton type="submit" :disabled="busy" data-testid="profile-submit">
        {{ busy ? '…' : 'Сменить пароль' }}
      </UiButton>
    </form>

    <UiButton variant="secondary" data-testid="profile-logout" @click="onLogout">Выйти</UiButton>
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
  gap: var(--space-md);
}
.heading {
  margin: 0;
  font-size: var(--font-size-title);
}
.muted {
  margin: 0;
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
}
.form {
  display: grid;
  gap: var(--space-md);
}
.err {
  margin: 0;
  color: var(--color-error);
  font-size: var(--font-size-caption);
}
.ok {
  margin: 0;
  color: var(--color-success, #16a34a);
  font-size: var(--font-size-caption);
}
@media (min-width: 768px) {
  .auth-card {
    padding: var(--space-xl);
  }
}
</style>
