<script setup lang="ts">
import {
  bffErrorFromResponse,
  bffErrorMessage,
  bffPath,
  type DayPlan,
  type WeekPlanResponse,
} from '@meal/bff-client';
import { onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { MEAL_SLOT_CODES, MEAL_SLOT_LABELS } from './mealSlots';
import { useBff } from './useBff';
import type { RecipeListResponse, RecipeSummary } from '@meal/bff-client';

const router = useRouter();
const bff = useBff();

const items = ref<RecipeSummary[]>([]);
const total = ref(0);
const loading = ref(true);
const error = ref('');

const q = ref('');
const mealCategory = ref('');
const maxCookTimeMinutes = ref<number | ''>('');

const planModalOpen = ref(false);
const planRecipe = ref<RecipeSummary | null>(null);
const planDate = ref('');
const planSlotCode = ref<string>(MEAL_SLOT_CODES[0]);
const planBusy = ref(false);
const planError = ref('');

async function loadList(): Promise<void> {
  loading.value = true;
  error.value = '';
  try {
    const path = bffPath('/recipes', {
      q: q.value || undefined,
      mealCategory: mealCategory.value || undefined,
      maxCookTimeMinutes:
        maxCookTimeMinutes.value === '' ? undefined : maxCookTimeMinutes.value,
      limit: 50,
      offset: 0,
    });
    const res = await bff.json<RecipeListResponse>(path);
    items.value = res.items;
    total.value = res.total;
  } catch (e) {
    error.value = bffErrorMessage(e);
  } finally {
    loading.value = false;
  }
}

onMounted(loadList);

function openPlanModal(r: RecipeSummary): void {
  planRecipe.value = r;
  planDate.value = todayISODate();
  planSlotCode.value = MEAL_SLOT_CODES[0];
  planError.value = '';
  planModalOpen.value = true;
}

function closePlanModal(): void {
  planModalOpen.value = false;
  planRecipe.value = null;
}

function todayISODate(): string {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function findSlot(week: WeekPlanResponse, date: string, code: string) {
  const day = week.days.find((d: DayPlan) => d.date === date);
  if (!day) {
    return null;
  }
  return day.slots.find((s) => s.slotCode === code) ?? null;
}

async function confirmAddToPlan(): Promise<void> {
  if (!planRecipe.value || !planDate.value) {
    return;
  }
  planBusy.value = true;
  planError.value = '';
  try {
    const week = await bff.json<WeekPlanResponse>(
      bffPath('/plan/week', { anchorDate: planDate.value }),
    );
    const slot = findSlot(week, planDate.value, planSlotCode.value);
    if (!slot) {
      planError.value = 'Слот не найден для выбранной даты.';
      return;
    }
    const nextIds = [...new Set([...slot.recipeIds, planRecipe.value.id])];
    const res = await bff.fetch(`/plan/slots/${slot.slotId}`, {
      method: 'PATCH',
      body: JSON.stringify({
        recipeIds: nextIds,
        expectedVersion: slot.version,
      }),
    });
    if (!res.ok) {
      const err = await bffErrorFromResponse(res);
      if (err.code === 'VERSION_CONFLICT') {
        planError.value =
          'План изменился. Закройте окно и откройте «В план» снова, либо обновите планировщик.';
        return;
      }
      planError.value = err.message;
      return;
    }
    closePlanModal();
    await router.push({
      path: '/planner',
      query: { anchorDate: planDate.value, focusDate: planDate.value },
    });
  } catch (e) {
    planError.value = bffErrorMessage(e);
  } finally {
    planBusy.value = false;
  }
}

async function deleteRecipe(r: RecipeSummary): Promise<void> {
  if (!confirm(`Удалить «${r.title}»?`)) {
    return;
  }
  try {
    const res = await bff.fetch(`/recipes/${r.id}`, { method: 'DELETE' });
    if (!res.ok) {
      error.value = (await bffErrorFromResponse(res)).message;
      return;
    }
    await loadList();
  } catch (e) {
    error.value = bffErrorMessage(e);
  }
}

</script>

<template>
  <section class="mf-root">
    <header class="head">
      <h2>Библиотека рецептов</h2>
      <div class="actions">
        <RouterLink class="btn secondary" to="/recipes/import">Импорт по URL</RouterLink>
        <RouterLink class="btn" to="/recipes/new">Создать рецепт</RouterLink>
        <RouterLink class="btn secondary" to="/planner?pickDay=1">Планировщик</RouterLink>
      </div>
    </header>

    <form class="filters" @submit.prevent="loadList">
      <input v-model="q" type="search" placeholder="Поиск по названию" aria-label="Поиск" />
      <input v-model="mealCategory" type="text" placeholder="Категория приёма пищи" />
      <input
        v-model.number="maxCookTimeMinutes"
        type="number"
        min="1"
        placeholder="Макс. время (мин)"
      />
      <button type="submit" class="btn">Найти</button>
    </form>

    <p v-if="loading" class="muted">Загрузка…</p>
    <p v-else-if="error" class="err">{{ error }}</p>
    <p v-else class="muted">Всего: {{ total }}</p>

    <ul v-if="!loading" class="cards">
      <li v-for="r in items" :key="r.id" class="card">
        <RouterLink class="card-main" :to="`/recipes/${r.id}`">
          <span class="title">{{ r.title }}</span>
          <span v-if="r.cookTimeMinutes != null" class="meta">{{ r.cookTimeMinutes }} мин</span>
          <span v-if="r.mealCategory" class="badge">{{ r.mealCategory }}</span>
        </RouterLink>
        <div class="card-actions">
          <button type="button" class="btn small" @click.stop="openPlanModal(r)">В план</button>
          <RouterLink class="btn small secondary" :to="`/recipes/${r.id}/edit`">Изменить</RouterLink>
          <button type="button" class="btn small danger" @click.stop="deleteRecipe(r)">Удалить</button>
        </div>
      </li>
    </ul>

    <div
      v-if="planModalOpen"
      class="modal-backdrop"
      role="dialog"
      aria-modal="true"
      aria-labelledby="plan-modal-title"
    >
      <div class="modal">
        <h3 id="plan-modal-title">Добавить в план</h3>
        <p v-if="planRecipe" class="muted">{{ planRecipe.title }}</p>
        <label>
          Дата
          <input v-model="planDate" type="date" />
        </label>
        <label>
          Приём пищи
          <select v-model="planSlotCode">
            <option v-for="c in MEAL_SLOT_CODES" :key="c" :value="c">
              {{ MEAL_SLOT_LABELS[c] }}
            </option>
          </select>
        </label>
        <p v-if="planError" class="err">{{ planError }}</p>
        <div class="modal-actions">
          <button type="button" class="btn secondary" :disabled="planBusy" @click="closePlanModal">
            Отмена
          </button>
          <button type="button" class="btn" :disabled="planBusy" @click="confirmAddToPlan">
            Добавить
          </button>
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
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-md);
}
h2 {
  margin: 0;
  font-size: var(--font-size-title);
}
.actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
}
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  margin-bottom: var(--space-lg);
}
.filters input {
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: inherit;
}
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-md);
  border: none;
  background: var(--color-text-primary);
  color: var(--color-bg);
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  font-size: var(--font-size-body);
}
.btn.secondary {
  background: transparent;
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
}
.btn.danger {
  background: color-mix(in srgb, #c00 85%, var(--color-text-primary));
  color: #fff;
}
.btn.small {
  padding: var(--space-xs) var(--space-sm);
  font-size: var(--font-size-caption);
}
.cards {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}
.card {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
}
.card-main {
  display: block;
  padding: var(--space-md);
  text-decoration: none;
  color: inherit;
}
.card-main:hover {
  background: color-mix(in srgb, var(--color-surface) 92%, var(--color-text-primary));
}
.title {
  display: block;
  font-weight: 700;
  font-size: var(--font-size-body);
}
.meta,
.muted {
  font-size: var(--font-size-caption);
  color: var(--color-text-muted);
}
.badge {
  display: inline-block;
  margin-top: var(--space-xs);
  padding: 2px 8px;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-surface) 80%, var(--color-text-primary));
  font-size: var(--font-size-caption);
}
.card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  border-top: 1px solid var(--color-border);
}
.err {
  color: #b00020;
  font-size: var(--font-size-body);
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
  max-width: 24rem;
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
.modal input,
.modal select {
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
