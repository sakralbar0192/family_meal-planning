<script setup lang="ts">
import type { RecipeDraft } from '@meal/bff-client';
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useBff } from './useBff';

const bff = useBff();
const router = useRouter();

const url = ref('');
const loading = ref(false);
const error = ref('');

async function submit(): Promise<void> {
  loading.value = true;
  error.value = '';
  try {
    const draft = await bff.json<RecipeDraft>('/import/url', {
      method: 'POST',
      body: JSON.stringify({ url: url.value.trim() }),
    });
    sessionStorage.setItem('meal_import_draft', JSON.stringify(draft));
    await router.push({ path: '/recipes/new', query: { fromImport: '1' } });
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Импорт не удался';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <section class="mf-root">
    <RouterLink class="back" to="/recipes">← К библиотеке</RouterLink>
    <h2>Импорт по URL</h2>
    <p class="muted">
      Разрешены только хосты из списка на сервере (см. IMPORT_ALLOWED_HOSTS). Для проверки используйте
      разрешённый домен, например example.com.
    </p>
    <form class="form" @submit.prevent="submit">
      <label>
        URL рецепта
        <input v-model="url" type="url" required placeholder="https://example.com/recipe" />
      </label>
      <p v-if="error" class="err">{{ error }}</p>
      <button type="submit" class="btn" :disabled="loading">
        {{ loading ? 'Импорт…' : 'Импортировать' }}
      </button>
    </form>
  </section>
</template>

<style scoped>
.mf-root {
  font-family: Inter, system-ui, sans-serif;
  padding: var(--space-lg);
  color: var(--color-text-primary);
  background: var(--color-surface);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
.back {
  display: inline-block;
  margin-bottom: var(--space-md);
  color: var(--color-text-secondary);
  text-decoration: none;
}
.form {
  max-width: 32rem;
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}
label {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}
input {
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: inherit;
}
.btn {
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-md);
  border: none;
  background: var(--color-text-primary);
  color: var(--color-bg);
  font-weight: 600;
  cursor: pointer;
}
.muted {
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
}
.err {
  color: #b00020;
}
</style>
