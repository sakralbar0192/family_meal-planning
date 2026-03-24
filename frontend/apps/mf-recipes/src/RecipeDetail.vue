<script setup lang="ts">
import type { Recipe } from '@meal/bff-client';
import { onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useBff } from './useBff';

const route = useRoute();
const router = useRouter();
const bff = useBff();

const recipe = ref<Recipe | null>(null);
const loading = ref(true);
const error = ref('');
const monthOpen = ref(false);
const pickDate = ref('');

function todayISODate(): string {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

async function load(): Promise<void> {
  const id = route.params.id as string;
  if (!id) {
    return;
  }
  loading.value = true;
  error.value = '';
  try {
    recipe.value = await bff.json<Recipe>(`/recipes/${id}`);
  } catch (e) {
    recipe.value = null;
    error.value = e instanceof Error ? e.message : 'Ошибка';
  } finally {
    loading.value = false;
  }
}

onMounted(load);
watch(
  () => route.params.id,
  () => load(),
);

function openMonthPicker(): void {
  pickDate.value = todayISODate();
  monthOpen.value = true;
}

function goPlannerWithDate(): void {
  if (!recipe.value) {
    return;
  }
  monthOpen.value = false;
  void router.push({
    path: '/planner',
    query: {
      anchorDate: pickDate.value,
      focusDate: pickDate.value,
      recipeSearch: recipe.value.title,
    },
  });
}
</script>

<template>
  <section class="mf-root">
    <p v-if="loading" class="muted">Загрузка…</p>
    <p v-else-if="error" class="err">{{ error }}</p>
    <template v-else-if="recipe">
      <header class="head">
        <RouterLink class="back" to="/recipes">← К библиотеке</RouterLink>
        <h2>{{ recipe.title }}</h2>
        <div class="actions">
          <button type="button" class="btn" @click="openMonthPicker">В план</button>
          <RouterLink class="btn secondary" :to="`/recipes/${recipe.id}/edit`">Редактировать</RouterLink>
        </div>
      </header>
      <p v-if="recipe.cookTimeMinutes != null" class="muted">Время: {{ recipe.cookTimeMinutes }} мин</p>
      <p v-if="recipe.mealCategory" class="muted">Приём пищи: {{ recipe.mealCategory }}</p>

      <section v-if="recipe.nutrition" class="block">
        <h3>Пищевая ценность</h3>
        <ul class="nutr">
          <li v-if="recipe.nutrition.proteinG != null">Белки: {{ recipe.nutrition.proteinG }} г</li>
          <li v-if="recipe.nutrition.fatG != null">Жиры: {{ recipe.nutrition.fatG }} г</li>
          <li v-if="recipe.nutrition.carbsG != null">Углеводы: {{ recipe.nutrition.carbsG }} г</li>
          <li v-if="recipe.nutrition.calories != null">Ккал: {{ recipe.nutrition.calories }}</li>
        </ul>
      </section>
      <p v-else class="muted">Пищевая ценность не заполнена — укажите в редакторе.</p>

      <section class="block">
        <h3>Ингредиенты</h3>
        <ul>
          <li v-for="(ing, i) in recipe.ingredients" :key="i">
            {{ ing.name }}
            <template v-if="ing.quantity != null"> — {{ ing.quantity }} {{ ing.unit ?? '' }}</template>
            <template v-else> — по вкусу</template>
            <span class="muted"> ({{ ing.productCategory }})</span>
          </li>
        </ul>
      </section>

      <section v-if="recipe.steps?.length" class="block">
        <h3>Шаги</h3>
        <ol>
          <li v-for="(s, i) in recipe.steps" :key="i">{{ s }}</li>
        </ol>
      </section>
    </template>

    <div v-if="monthOpen" class="modal-backdrop" role="dialog" aria-modal="true">
      <div class="modal">
        <h3>Выберите день</h3>
        <p class="muted">Далее откроется планировщик с поиском по названию рецепта.</p>
        <label>
          Дата
          <input v-model="pickDate" type="date" />
        </label>
        <div class="modal-actions">
          <button type="button" class="btn secondary" @click="monthOpen = false">Отмена</button>
          <button type="button" class="btn" @click="goPlannerWithDate">Перейти в планировщик</button>
        </div>
      </div>
    </div>
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
.head {
  margin-bottom: var(--space-md);
}
.back {
  display: inline-block;
  margin-bottom: var(--space-sm);
  color: var(--color-text-secondary);
  text-decoration: none;
}
h2 {
  margin: 0 0 var(--space-sm);
  font-size: var(--font-size-title);
}
.actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  margin-top: var(--space-md);
}
.btn {
  display: inline-flex;
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-md);
  border: none;
  background: var(--color-text-primary);
  color: var(--color-bg);
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
}
.btn.secondary {
  background: transparent;
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
}
.block {
  margin-top: var(--space-lg);
}
.block h3 {
  margin: 0 0 var(--space-sm);
  font-size: var(--font-size-body);
}
.muted {
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
}
.err {
  color: #b00020;
}
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
  padding: var(--space-lg);
}
.modal {
  background: var(--color-surface);
  border-radius: var(--radius-md);
  padding: var(--space-lg);
  max-width: 22rem;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}
.modal label {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  font-size: var(--font-size-caption);
}
.modal input {
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-sm);
}
</style>
