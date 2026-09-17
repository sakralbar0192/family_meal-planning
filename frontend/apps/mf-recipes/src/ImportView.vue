<script setup lang="ts">
import type { RecipeDraft } from '@meal/bff-client';
import { bffErrorMessage, isBffHttpError } from '@meal/bff-client';
import { setShellHeader } from '@meal/shell-chrome';
import { h, onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useBff } from './useBff';

const bff = useBff();
const router = useRouter();

const url = ref('');
const loading = ref(false);
const error = ref('');

onMounted(() => {
  setShellHeader({
    ariaLabel: 'Шапка импорта',
    eyebrow: 'Import',
    title: 'Импорт по URL',
    showAppNav: true,
    leadingRender: () =>
      h(RouterLink, { to: '/recipes', class: 'ui-app-header-link ui-app-header-link--back' }, () => '← К библиотеке'),
    sublineRender: null,
    actionsRender: null,
  });
});

function importErrorMessage(e: unknown): string {
  if (!isBffHttpError(e)) {
    return bffErrorMessage(e);
  }
  switch (e.code) {
    case 'URL_NOT_ALLOWED':
      return 'Этот сайт не в списке разрешённых (IMPORT_ALLOWED_HOSTS). Введите рецепт вручную или укажите URL с разрешённого хоста.';
    case 'INVALID_URL':
      return 'Некорректный URL.';
    case 'UPSTREAM_TIMEOUT':
      return 'Сервер долго ждал ответ сайта. Попробуйте позже.';
    case 'FETCH_FAILED':
      return 'Не удалось загрузить страницу. Проверьте URL и сеть.';
    case 'PARSE_FAILED':
      return 'Не удалось извлечь рецепт со страницы. Создайте рецепт вручную.';
    default:
      return e.message;
  }
}

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
    error.value = importErrorMessage(e);
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <section class="mf-root">
    <p class="muted">
      Поддерживаются импорт с <strong>eda.ru</strong> (страницы открываются на <strong>eda.rambler.ru</strong>) и <strong>povarenok.ru</strong>.
      Для локальной проверки можно использовать фикстуру:
      <code>http://import-fixtures/eda/borsch.html</code>
    </p>
    <form class="form" @submit.prevent="submit">
      <label>
        URL рецепта
        <input v-model="url" type="url" required placeholder="https://eda.ru/recepty/..." />
      </label>
      <p v-if="error" class="err" data-testid="import-error">{{ error }}</p>
      <button type="submit" class="btn" :disabled="loading">
        {{ loading ? 'Импорт…' : 'Импортировать' }}
      </button>
    </form>
  </section>
</template>

<style scoped>
.mf-root {
  font-family: Inter, system-ui, sans-serif;
  padding: var(--space-md);
  color: var(--color-text-primary);
  background: var(--color-surface);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  display: grid;
  gap: var(--space-sm);
}
.form {
  max-width: 44rem;
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  margin-top: var(--space-sm);
}
label {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}
input {
  min-height: var(--input-min-height);
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: inherit;
}
.btn {
  min-height: var(--button-min-height);
  padding: 0 var(--space-md);
  border-radius: var(--radius-md);
  border: none;
  background: var(--color-accent);
  color: var(--color-text-on-accent);
  font-weight: 600;
  font-size: var(--font-size-button);
  cursor: pointer;
}
.btn:hover {
  background: var(--color-accent-hover);
}
.muted {
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
}
.err {
  color: var(--color-error);
}
@media (min-width: 768px) {
  .mf-root {
    padding: var(--space-lg);
  }
}
</style>
