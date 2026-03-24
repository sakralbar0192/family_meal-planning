<script setup lang="ts">
import type { Recipe } from '@meal/bff-client';
import { bffErrorMessage } from '@meal/bff-client';
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
    error.value = bffErrorMessage(e);
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
        <div class="title-wrap">
          <p class="eyebrow">Recipe</p>
          <h2>{{ recipe.title }}</h2>
        </div>
        <div class="actions">
          <button type="button" class="btn" @click="openMonthPicker">В план</button>
          <RouterLink class="btn secondary" :to="`/recipes/${recipe.id}/edit`">Редактировать</RouterLink>
        </div>
      </header>

      <section class="meta-row">
        <p v-if="recipe.cookTimeMinutes != null" class="meta-chip">
          Время: {{ recipe.cookTimeMinutes }} мин
        </p>
        <p v-if="recipe.mealCategory" class="meta-chip">Приём пищи: {{ recipe.mealCategory }}</p>
      </section>

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
  padding: var(--space-md);
  color: var(--color-text-primary);
  background: var(--color-surface);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
.head {
  display: grid;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}
.back {
  display: inline-flex;
  min-height: var(--touch-target);
  align-items: center;
  justify-content: center;
  padding: 0 var(--space-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  color: var(--color-text-secondary);
  text-decoration: none;
  justify-self: start;
}
.title-wrap {
  display: grid;
  gap: var(--space-xs);
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
.actions {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-sm);
}
.btn {
  display: inline-flex;
  min-height: var(--button-min-height);
  align-items: center;
  justify-content: center;
  padding: 0 var(--space-md);
  border-radius: var(--radius-md);
  border: none;
  background: var(--color-accent);
  color: var(--color-text-on-accent);
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
}
.btn:hover {
  background: var(--color-accent-hover);
}
.btn.secondary {
  background: transparent;
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
}
.btn.secondary:hover {
  background: color-mix(in srgb, var(--color-surface) 92%, var(--color-text-primary));
}
.meta-row {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
}
.meta-chip {
  margin: 0;
  padding: var(--space-xs) var(--space-sm);
  border-radius: 999px;
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
  color: var(--color-text-secondary);
  font-size: var(--font-size-caption);
}
.block {
  margin-top: var(--space-lg);
  padding: var(--space-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-elevated);
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
  background: var(--color-overlay);
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
  min-height: var(--input-min-height);
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
.modal-actions {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-sm);
}
@media (min-width: 768px) {
  .mf-root {
    padding: var(--space-lg);
  }
  .actions {
    grid-template-columns: repeat(2, max-content);
    justify-content: start;
  }
  .modal-actions {
    grid-template-columns: repeat(2, max-content);
    justify-content: end;
  }
}
</style>
