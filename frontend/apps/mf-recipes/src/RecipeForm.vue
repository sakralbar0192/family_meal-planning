<script setup lang="ts">
import type { Ingredient, Recipe } from '@meal/bff-client';
import { bffErrorMessage } from '@meal/bff-client';
import { onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useBff } from './useBff';

const route = useRoute();
const router = useRouter();
const bff = useBff();

const isCreate = ref(route.name === 'recipe-new');
const loading = ref(!isCreate.value);
const saving = ref(false);
const error = ref('');

const title = ref('');
const stepsText = ref('');
const cookTimeMinutes = ref<number | ''>('');
const mealCategory = ref('');
const sourceUrl = ref('');

type IngredientRow = Ingredient & { toTaste: boolean };

const ingredients = reactive<IngredientRow[]>([
  { name: '', productCategory: 'other', quantity: null, unit: '', toTaste: false },
]);

function addIngredient(): void {
  ingredients.push({ name: '', productCategory: 'other', quantity: null, unit: '', toTaste: false });
}

function removeIngredient(i: number): void {
  if (ingredients.length > 1) {
    ingredients.splice(i, 1);
  }
}

function onToTasteChange(ing: IngredientRow): void {
  if (ing.toTaste) {
    ing.quantity = null;
    ing.unit = '';
  }
}

function parseSteps(): string[] {
  return stepsText.value
    .split('\n')
    .map((s) => s.trim())
    .filter(Boolean);
}

function payloadFromForm(): Record<string, unknown> {
  const ings = ingredients
    .filter((i) => i.name.trim() !== '')
    .map((i) => ({
      name: i.name.trim(),
      productCategory: (i.productCategory.trim() || 'other').toLowerCase(),
      quantity:
        i.toTaste || i.quantity == null || i.quantity === '' ? null : Number(i.quantity),
      unit: i.toTaste ? undefined : i.unit?.trim() || undefined,
    }));
  const body: Record<string, unknown> = {
    title: title.value.trim(),
    ingredients: ings,
    steps: parseSteps(),
  };
  if (cookTimeMinutes.value !== '') {
    body.cookTimeMinutes = Number(cookTimeMinutes.value);
  }
  if (mealCategory.value.trim()) {
    body.mealCategory = mealCategory.value.trim();
  }
  if (sourceUrl.value.trim()) {
    body.sourceUrl = sourceUrl.value.trim();
  }
  return body;
}

async function loadEdit(): Promise<void> {
  const id = route.params.id as string;
  loading.value = true;
  error.value = '';
  try {
    const r = await bff.json<Recipe>(`/recipes/${id}`);
    title.value = r.title;
    stepsText.value = (r.steps ?? []).join('\n');
    cookTimeMinutes.value = r.cookTimeMinutes ?? '';
    mealCategory.value = r.mealCategory ?? '';
    sourceUrl.value = r.sourceUrl ?? '';
    ingredients.splice(
      0,
      ingredients.length,
      ...(r.ingredients.length
        ? r.ingredients.map((x) => ({
            ...x,
            toTaste: x.quantity == null && x.unit == null,
          }))
        : [{ name: '', productCategory: 'other', quantity: null, unit: '', toTaste: false }]),
    );
  } catch (e) {
    error.value = bffErrorMessage(e);
  } finally {
    loading.value = false;
  }
}

function applyImportDraft(): void {
  if (route.query.fromImport !== '1') {
    return;
  }
  const raw = sessionStorage.getItem('meal_import_draft');
  if (!raw) {
    return;
  }
  try {
    const d = JSON.parse(raw) as {
      title?: string;
      steps?: string[];
      ingredients?: Array<{ name?: string; quantity?: number | null; unit?: string; productCategory?: string }>;
      sourceUrl?: string | null;
      cookTimeMinutes?: number | null;
      mealCategory?: string | null;
    };
    title.value = d.title ?? '';
    stepsText.value = (d.steps ?? []).join('\n');
    cookTimeMinutes.value = d.cookTimeMinutes ?? '';
    mealCategory.value = d.mealCategory ?? '';
    sourceUrl.value = d.sourceUrl ?? '';
    const ings = (d.ingredients ?? []).map((x) => ({
      name: x.name ?? '',
      productCategory: x.productCategory ?? 'other',
      quantity: x.quantity ?? null,
      unit: x.unit ?? '',
      toTaste: (x.quantity == null || x.quantity === undefined) && !x.unit,
    }));
    ingredients.splice(
      0,
      ingredients.length,
      ...(ings.length
        ? ings
        : [{ name: '', productCategory: 'other', quantity: null, unit: '', toTaste: false }]),
    );
    sessionStorage.removeItem('meal_import_draft');
  } catch {
    sessionStorage.removeItem('meal_import_draft');
  }
}

onMounted(() => {
  isCreate.value = route.name === 'recipe-new';
  if (!isCreate.value) {
    void loadEdit();
  } else {
    loading.value = false;
    applyImportDraft();
  }
});

watch(
  () => route.name,
  (n) => {
    isCreate.value = n === 'recipe-new';
    if (isCreate.value) {
      title.value = '';
      stepsText.value = '';
      cookTimeMinutes.value = '';
      mealCategory.value = '';
      sourceUrl.value = '';
      ingredients.splice(0, ingredients.length, {
        name: '',
        productCategory: 'other',
        quantity: null,
        unit: '',
        toTaste: false,
      });
      loading.value = false;
      applyImportDraft();
    } else {
      void loadEdit();
    }
  },
);

watch(
  () => route.params.id,
  () => {
    if (route.name === 'recipe-edit') {
      void loadEdit();
    }
  },
);

function validateForm(): string | null {
  if (!title.value.trim()) {
    return 'Укажите название рецепта.';
  }
  const named = ingredients.filter((i) => i.name.trim() !== '');
  if (named.length === 0) {
    return 'Добавьте хотя бы один ингредиент с названием.';
  }
  for (const i of named) {
    if (!i.productCategory.trim()) {
      return `У ингредиента «${i.name.trim()}» укажите категорию продукта (для списка покупок).`;
    }
    if (!i.toTaste && i.quantity != null && i.quantity !== '' && Number(i.quantity) < 0) {
      return `Количество для «${i.name.trim()}» не может быть отрицательным.`;
    }
  }
  if (cookTimeMinutes.value !== '' && Number(cookTimeMinutes.value) < 1) {
    return 'Время приготовления должно быть не меньше 1 минуты.';
  }
  return null;
}

async function save(): Promise<void> {
  saving.value = true;
  error.value = '';
  const v = validateForm();
  if (v) {
    error.value = v;
    saving.value = false;
    return;
  }
  const body = payloadFromForm();
  try {
    if (isCreate.value) {
      const created = await bff.json<Recipe>('/recipes', {
        method: 'POST',
        body: JSON.stringify(body),
      });
      await router.push(`/recipes/${created.id}`);
    } else {
      const id = route.params.id as string;
      await bff.json<Recipe>(`/recipes/${id}`, {
        method: 'PATCH',
        body: JSON.stringify(body),
      });
      await router.push(`/recipes/${id}`);
    }
  } catch (e) {
    error.value = bffErrorMessage(e);
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <section class="mf-root">
    <header class="head">
      <RouterLink class="back" to="/recipes">← Назад</RouterLink>
      <h2>{{ isCreate ? 'Новый рецепт' : 'Редактирование' }}</h2>
    </header>

    <p v-if="loading" class="muted">Загрузка…</p>
    <form v-else class="form" @submit.prevent="save">
      <label>
        Название *
        <input v-model="title" required />
      </label>
      <label>
        Шаги (каждый с новой строки)
        <textarea v-model="stepsText" rows="6"></textarea>
      </label>
      <label>
        Время (мин)
        <input v-model.number="cookTimeMinutes" type="number" min="1" />
      </label>
      <label>
        Категория приёма пищи
        <input v-model="mealCategory" />
      </label>
      <label>
        Источник (URL)
        <input v-model="sourceUrl" type="url" />
      </label>

      <fieldset>
        <legend>Ингредиенты *</legend>
        <div v-for="(ing, i) in ingredients" :key="i" class="ing-row">
          <input v-model="ing.name" placeholder="Название" />
          <label class="inline">
            <input v-model="ing.toTaste" type="checkbox" @change="onToTasteChange(ing)" />
            по вкусу
          </label>
          <input
            v-model.number="ing.quantity"
            type="number"
            step="any"
            placeholder="Кол-во"
            :disabled="ing.toTaste"
          />
          <input v-model="ing.unit" placeholder="Ед." :disabled="ing.toTaste" />
          <input v-model="ing.productCategory" placeholder="Категория продукта" />
          <button type="button" class="btn small secondary" @click="removeIngredient(i)">−</button>
        </div>
        <button type="button" class="btn secondary small" @click="addIngredient">Добавить строку</button>
      </fieldset>

      <p v-if="error" class="err">{{ error }}</p>
      <button type="submit" class="btn" :disabled="saving">{{ saving ? 'Сохранение…' : 'Сохранить' }}</button>
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
  margin-bottom: var(--space-sm);
  color: var(--color-text-secondary);
  text-decoration: none;
}
.form {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  max-width: 40rem;
}
label {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  font-size: var(--font-size-caption);
}
input,
textarea {
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: inherit;
}
.inline {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
  font-size: var(--font-size-caption);
  white-space: nowrap;
}
.ing-row {
  display: grid;
  grid-template-columns: 2fr auto 1fr 1fr 1.5fr auto;
  gap: var(--space-sm);
  margin-bottom: var(--space-sm);
  align-items: center;
}
@media (max-width: 700px) {
  .ing-row {
    grid-template-columns: 1fr;
  }
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
.btn.secondary {
  background: transparent;
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
}
.btn.small {
  padding: var(--space-xs);
}
.err {
  color: #b00020;
}
.muted {
  color: var(--color-text-muted);
}
</style>
