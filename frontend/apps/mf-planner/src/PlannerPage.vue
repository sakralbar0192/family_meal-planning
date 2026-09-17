<script setup lang="ts">
import {
  bffErrorFromResponse,
  bffErrorMessage,
  bffPath,
  type BuildListResponse,
  type RecipeListResponse,
  type RecipeSummary,
  type SlotAssignment,
  type WeekPlanResponse,
} from '@meal/bff-client';
import { setShellHeader } from '@meal/shell-chrome';
import { computed, h, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { MEAL_SLOT_LABELS } from './mealSlots';
import { useBff } from './useBff';
import { UiButton } from '@meal/ui-kit';

const route = useRoute();
const router = useRouter();
const bff = useBff();

const week = ref<WeekPlanResponse | null>(null);
const loading = ref(true);
const error = ref('');
const slotError = ref('');

const recipeTitles = ref<Record<string, string>>({});
const sidebarSearch = ref('');
const sidebarMealCategory = ref('');
const sidebarMaxCook = ref<number | ''>('');
const activeSlotId = ref('');

const shoppingFrom = ref('');
const shoppingTo = ref('');
const buildBusy = ref(false);
const buildError = ref('');

const monthOpen = ref(false);
const calYear = ref(new Date().getFullYear());
const calMonth = ref(new Date().getMonth());

const dragRecipeId = ref<string | null>(null);
const dragOverSlotId = ref<string | null>(null);

function todayISODate(): string {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function addDays(iso: string, delta: number): string {
  const [y, mo, da] = iso.split('-').map(Number);
  const d = new Date(Date.UTC(y, mo - 1, da));
  d.setUTCDate(d.getUTCDate() + delta);
  const yy = d.getUTCFullYear();
  const mm = String(d.getUTCMonth() + 1).padStart(2, '0');
  const dd = String(d.getUTCDate()).padStart(2, '0');
  return `${yy}-${mm}-${dd}`;
}

const anchorDate = computed(() => {
  const a = route.query.anchorDate as string | undefined;
  const f = route.query.focusDate as string | undefined;
  return (a || f || todayISODate()) as string;
});

const slotOptions = computed(() => {
  if (!week.value) {
    return [];
  }
  const out: { id: string; label: string }[] = [];
  for (const day of week.value.days) {
    for (const s of day.slots) {
      out.push({
        id: s.slotId,
        label: `${day.date} — ${MEAL_SLOT_LABELS[s.slotCode]}`,
      });
    }
  }
  return out;
});

async function loadRecipeTitles(ids: Set<string>): Promise<void> {
  if (ids.size === 0) {
    return;
  }
  try {
    const res = await bff.json<RecipeListResponse>(bffPath('/recipes', { limit: 100, offset: 0 }));
    const m: Record<string, string> = { ...recipeTitles.value };
    for (const it of res.items) {
      if (ids.has(it.id)) {
        m[it.id] = it.title;
      }
    }
    recipeTitles.value = m;
  } catch {
    /* ignore */
  }
}

async function loadWeek(): Promise<void> {
  loading.value = true;
  error.value = '';
  slotError.value = '';
  try {
    const q: Record<string, string | undefined> = {
      anchorDate: anchorDate.value,
      focusDate: (route.query.focusDate as string) || undefined,
      recipeSearch: sidebarSearch.value || (route.query.recipeSearch as string) || undefined,
    };
    const w = await bff.json<WeekPlanResponse>(bffPath('/plan/week', q));
    week.value = w;
    if (w.recipeSearchHint && !sidebarSearch.value) {
      sidebarSearch.value = w.recipeSearchHint;
    }
    const ids = new Set<string>();
    for (const d of w.days) {
      for (const s of d.slots) {
        for (const id of s.recipeIds) {
          ids.add(id);
        }
      }
    }
    await loadRecipeTitles(ids);
    if (!shoppingFrom.value && !shoppingTo.value) {
      shoppingFrom.value = w.weekStart;
      shoppingTo.value = w.weekEnd;
    }
    if (!activeSlotId.value && slotOptions.value.length) {
      activeSlotId.value = slotOptions.value[0].id;
    }
  } catch (e) {
    week.value = null;
    error.value = bffErrorMessage(e);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  applyPlannerShell();
  sidebarSearch.value = (route.query.recipeSearch as string) || '';
  monthOpen.value = route.query.pickDay === '1';
  void loadWeek();
  void loadSidebarRecipes();
});

watch(
  () => [route.query.anchorDate, route.query.focusDate, route.query.recipeSearch],
  () => {
    if (route.query.recipeSearch) {
      sidebarSearch.value = route.query.recipeSearch as string;
    }
    void loadWeek();
  },
);

watch(sidebarSearch, () => {
  void loadWeek();
  void loadSidebarRecipes();
});

watch([sidebarMealCategory, sidebarMaxCook], () => {
  void loadSidebarRecipes();
});

function titleForRecipe(id: string): string {
  return recipeTitles.value[id] ?? id.slice(0, 8) + '…';
}

function findSlot(slotId: string): SlotAssignment | null {
  if (!week.value) {
    return null;
  }
  for (const d of week.value.days) {
    for (const s of d.slots) {
      if (s.slotId === slotId) {
        return s;
      }
    }
  }
  return null;
}

async function patchSlot(slot: SlotAssignment, recipeIds: string[], isRetry = false): Promise<void> {
  slotError.value = '';
  const res = await bff.fetch(`/plan/slots/${slot.slotId}`, {
    method: 'PATCH',
    body: JSON.stringify({ recipeIds, expectedVersion: slot.version }),
  });
  if (!res.ok) {
    const err = await bffErrorFromResponse(res);
    if (err.code === 'VERSION_CONFLICT' && !isRetry) {
      await loadWeek();
      const refreshed = findSlot(slot.slotId);
      if (!refreshed) {
        slotError.value =
          'Данные плана устарели (другое окно или вкладка). Обновите страницу и повторите действие.';
        return;
      }
      await patchSlot(refreshed, recipeIds, true);
      return;
    }
    if (err.code === 'VERSION_CONFLICT') {
      slotError.value =
        'Данные плана устарели (другое окно или вкладка). Обновляем неделю — повторите действие.';
      await loadWeek();
      return;
    }
    slotError.value = err.message;
    return;
  }
  await loadWeek();
}

async function removeRecipeFromSlot(slot: SlotAssignment, recipeId: string): Promise<void> {
  const next = slot.recipeIds.filter((id) => id !== recipeId);
  await patchSlot(slot, next);
}

async function addRecipeToActiveSlot(recipeId: string): Promise<void> {
  const slot = activeSlotId.value ? findSlot(activeSlotId.value) : null;
  if (!slot) {
    slotError.value = 'Выберите слот.';
    return;
  }
  await moveRecipeToSlot(recipeId, slot.slotId);
}

function findSlotForRecipe(recipeId: string): SlotAssignment | null {
  if (!week.value) {
    return null;
  }
  for (const d of week.value.days) {
    for (const s of d.slots) {
      if (s.recipeIds.includes(recipeId)) {
        return s;
      }
    }
  }
  return null;
}

async function moveRecipeToSlot(recipeId: string, targetSlotId: string): Promise<void> {
  const target = findSlot(targetSlotId);
  if (!target) {
    return;
  }
  const source = findSlotForRecipe(recipeId);
  if (source?.slotId === targetSlotId) {
    return;
  }
  if (source) {
    await patchSlot(
      source,
      source.recipeIds.filter((id) => id !== recipeId),
    );
    const refreshed = findSlot(targetSlotId);
    if (refreshed && !refreshed.recipeIds.includes(recipeId)) {
      await patchSlot(refreshed, [...refreshed.recipeIds, recipeId]);
    }
    return;
  }
  if (!target.recipeIds.includes(recipeId)) {
    await patchSlot(target, [...target.recipeIds, recipeId]);
  }
}

function onRecipeDragStart(event: DragEvent, recipeId: string): void {
  dragRecipeId.value = recipeId;
  event.dataTransfer?.setData('text/recipe-id', recipeId);
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
  }
}

function onSlotDragOver(event: DragEvent, slotId: string): void {
  event.preventDefault();
  dragOverSlotId.value = slotId;
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move';
  }
}

function onSlotDragLeave(slotId: string): void {
  if (dragOverSlotId.value === slotId) {
    dragOverSlotId.value = null;
  }
}

async function onSlotDrop(event: DragEvent, slot: SlotAssignment): Promise<void> {
  event.preventDefault();
  dragOverSlotId.value = null;
  const recipeId = event.dataTransfer?.getData('text/recipe-id') || dragRecipeId.value;
  dragRecipeId.value = null;
  if (!recipeId) {
    return;
  }
  await moveRecipeToSlot(recipeId, slot.slotId);
}

function onDragEnd(): void {
  dragRecipeId.value = null;
  dragOverSlotId.value = null;
}

const sidebarRecipes = ref<RecipeSummary[]>([]);

async function loadSidebarRecipes(): Promise<void> {
  try {
    const res = await bff.json<RecipeListResponse>(
      bffPath('/recipes', {
        q: sidebarSearch.value || undefined,
        mealCategory: sidebarMealCategory.value || undefined,
        maxCookTimeMinutes: sidebarMaxCook.value === '' ? undefined : sidebarMaxCook.value,
        limit: 50,
        offset: 0,
      }),
    );
    sidebarRecipes.value = res.items;
  } catch {
    sidebarRecipes.value = [];
  }
}

function shiftWeek(delta: number): void {
  const next = addDays(anchorDate.value, delta);
  void router.replace({ path: '/planner', query: { ...route.query, anchorDate: next, focusDate: next } });
}

function presetWeek(): void {
  if (week.value) {
    shoppingFrom.value = week.value.weekStart;
    shoppingTo.value = week.value.weekEnd;
  }
}

function presetTodaySunday(): void {
  const t = todayISODate();
  shoppingFrom.value = t;
  const d = new Date(t + 'T12:00:00Z');
  const day = d.getUTCDay();
  const sunOff = day === 0 ? 0 : 7 - day;
  d.setUTCDate(d.getUTCDate() + sunOff);
  const yy = d.getUTCFullYear();
  const mm = String(d.getUTCMonth() + 1).padStart(2, '0');
  const dd = String(d.getUTCDate()).padStart(2, '0');
  shoppingTo.value = `${yy}-${mm}-${dd}`;
}

async function buildShopping(): Promise<void> {
  buildBusy.value = true;
  buildError.value = '';
  if (!shoppingFrom.value || !shoppingTo.value) {
    buildError.value = 'Укажите период «с» и «по».';
    buildBusy.value = false;
    return;
  }
  if (shoppingFrom.value > shoppingTo.value) {
    buildError.value = 'Дата «с» не может быть позже «по».';
    buildBusy.value = false;
    return;
  }
  try {
    const res = await bff.json<BuildListResponse>('/shopping/build', {
      method: 'POST',
      body: JSON.stringify({ from: shoppingFrom.value, to: shoppingTo.value }),
    });
    await router.push({
      path: `/shopping/${res.listId}`,
      query: res.empty ? { empty: '1' } : {},
    });
  } catch (e) {
    buildError.value = bffErrorMessage(e);
  } finally {
    buildBusy.value = false;
  }
}

/* --- месячный календарь (Пн–Вс) --- */
const calLabel = computed(() => {
  const d = new Date(calYear.value, calMonth.value, 1);
  return d.toLocaleString('ru', { month: 'long', year: 'numeric' });
});

const calCells = computed(() => {
  const first = new Date(calYear.value, calMonth.value, 1);
  const startPad = (first.getDay() + 6) % 7;
  const daysInMonth = new Date(calYear.value, calMonth.value + 1, 0).getDate();
  const cells: { d: number | null; iso: string | null; inMonth: boolean }[] = [];
  for (let i = 0; i < startPad; i++) {
    cells.push({ d: null, iso: null, inMonth: false });
  }
  for (let day = 1; day <= daysInMonth; day++) {
    const iso = `${calYear.value}-${String(calMonth.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    cells.push({ d: day, iso, inMonth: true });
  }
  while (cells.length % 7 !== 0) {
    cells.push({ d: null, iso: null, inMonth: false });
  }
  while (cells.length < 42) {
    cells.push({ d: null, iso: null, inMonth: false });
  }
  return cells;
});

function goShoppingPanel(): void {
  const panel = document.querySelector('[data-testid="planner-shop-panel"]');
  if (panel instanceof HTMLElement) {
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
  const from = document.querySelector('[data-testid="planner-shopping-date-from"]');
  if (from instanceof HTMLInputElement) {
    from.focus();
  }
}

function openCalendar(): void {
  const [y, m] = anchorDate.value.split('-').map(Number);
  calYear.value = y;
  calMonth.value = m - 1;
  monthOpen.value = true;
}

function pickCalendarDay(iso: string | null): void {
  if (!iso) {
    return;
  }
  monthOpen.value = false;
  void router.replace({
    path: '/planner',
    query: {
      anchorDate: iso,
      focusDate: iso,
      recipeSearch: (route.query.recipeSearch as string) || sidebarSearch.value || undefined,
    },
  });
}

function prevMonth(): void {
  if (calMonth.value === 0) {
    calMonth.value = 11;
    calYear.value -= 1;
  } else {
    calMonth.value -= 1;
  }
}

function nextMonth(): void {
  if (calMonth.value === 11) {
    calMonth.value = 0;
    calYear.value += 1;
  } else {
    calMonth.value += 1;
  }
}

function formatDateRu(iso: string): string {
  return new Date(`${iso}T12:00:00`).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

const plannerFocusLabel = computed(() => formatDateRu(anchorDate.value));

function applyPlannerShell(): void {
  setShellHeader({
    ariaLabel: 'Шапка планировщика',
    title: 'Планировщик',
    titleAlign: 'center',
    showAppNav: true,
    eyebrow: null,
    sublineRender: null,
    leadingRender: () =>
      h(RouterLink, { to: '/recipes', class: 'ui-app-header-link--accent' }, () => 'К библиотеке'),
    actionsRender: () =>
      h('div', { class: 'planner-header-actions' }, [
        h(
          'span',
          { class: 'ui-app-header-meta', 'data-testid': 'planner-week-range' },
          plannerFocusLabel.value,
        ),
        h(
          'button',
          {
            type: 'button',
            class: 'ui-app-header-link',
            'data-testid': 'planner-header-shopping-list',
            onClick: goShoppingPanel,
          },
          () => 'Список покупок',
        ),
      ]),
  });
}

watch(plannerFocusLabel, applyPlannerShell);
</script>

<template>
  <section class="mf-root">
    <div class="planner-week-bar">
      <UiButton type="button" variant="secondary" @click="shiftWeek(-7)">← Неделя</UiButton>
      <UiButton type="button" variant="secondary" @click="shiftWeek(7)">Неделя →</UiButton>
      <UiButton type="button" variant="secondary" @click="openCalendar">Календарь месяца</UiButton>
    </div>

    <section class="shop-panel" data-testid="planner-shop-panel">
      <h3>Список покупок на период</h3>
      <div class="shop-row">
        <label>
          С
          <input v-model="shoppingFrom" type="date" data-testid="planner-shopping-date-from" />
        </label>
        <label>
          По
          <input v-model="shoppingTo" type="date" data-testid="planner-shopping-date-to" />
        </label>
        <UiButton type="button" variant="secondary" @click="presetWeek">Текущая неделя</UiButton>
        <UiButton type="button" variant="secondary" @click="presetTodaySunday">Сегодня — вс</UiButton>
        <UiButton
          type="button"
          data-testid="planner-build-shopping-list"
          :disabled="buildBusy"
          @click="buildShopping"
        >
          {{ buildBusy ? '…' : 'Сформировать список покупок' }}
        </UiButton>
      </div>
      <p v-if="buildError" class="err">{{ buildError }}</p>
    </section>

    <p v-if="loading" class="muted">Загрузка плана…</p>
    <p v-else-if="error" class="err">{{ error }}</p>
    <p v-if="slotError" class="err">{{ slotError }}</p>

    <div v-else-if="week" class="layout">
      <aside class="sidebar">
        <h3>Рецепты рядом</h3>
        <p class="add-hint">
          На компьютере перетащите карточку в слот. На телефоне выберите слот и нажмите «В слот».
        </p>
        <input v-model="sidebarSearch" type="search" placeholder="Поиск" data-testid="planner-sidebar-search" />
        <label class="slot-pick">
          Приём пищи
          <input
            v-model="sidebarMealCategory"
            type="text"
            placeholder="Категория приёма пищи"
            data-testid="planner-sidebar-meal-category"
          />
        </label>
        <label class="slot-pick">
          Время готовки, мин (не больше)
          <input
            v-model.number="sidebarMaxCook"
            type="number"
            min="1"
            placeholder="Например 30"
            data-testid="planner-sidebar-max-cook"
          />
        </label>
        <label class="slot-pick">
          Слот для добавления
          <select v-model="activeSlotId" data-testid="planner-active-slot">
            <option v-for="o in slotOptions" :key="o.id" :value="o.id">{{ o.label }}</option>
          </select>
        </label>
        <ul class="rec-list" data-testid="planner-sidebar-recipes">
          <li
            v-for="r in sidebarRecipes"
            :key="r.id"
            :data-recipe-id="r.id"
            data-testid="planner-sidebar-recipe-row"
            draggable="true"
            class="draggable-recipe"
            @dragstart="onRecipeDragStart($event, r.id)"
            @dragend="onDragEnd"
          >
            <span>{{ r.title }}</span>
            <UiButton type="button" size="sm" data-testid="planner-add-to-slot" @click="addRecipeToActiveSlot(r.id)">
              В слот
            </UiButton>
          </li>
        </ul>
      </aside>

      <div class="week">
        <div
          v-for="day in week.days"
          :key="day.date"
          class="day"
          :data-focus="day.date === String(route.query.focusDate || '')"
        >
          <h4>{{ day.date }}</h4>
          <div
            v-for="slot in day.slots"
            :key="slot.slotId"
            class="slot"
            :class="{
              'slot--drag-over': dragOverSlotId === slot.slotId,
              'slot--active': activeSlotId === slot.slotId,
            }"
            data-testid="planner-slot-drop"
            :data-slot-id="slot.slotId"
            @click="activeSlotId = slot.slotId"
            @dragover="onSlotDragOver($event, slot.slotId)"
            @dragleave="onSlotDragLeave(slot.slotId)"
            @drop="onSlotDrop($event, slot)"
          >
            <div class="slot-head">{{ MEAL_SLOT_LABELS[slot.slotCode] }}</div>
            <ul class="slot-recipes">
              <li
                v-for="rid in slot.recipeIds"
                :key="rid"
                draggable="true"
                class="draggable-recipe"
                @dragstart="onRecipeDragStart($event, rid)"
                @dragend="onDragEnd"
              >
                {{ titleForRecipe(rid) }}
                <button
                  type="button"
                  class="link-remove"
                  title="Убрать"
                  aria-label="Убрать рецепт из слота"
                  @click="removeRecipeFromSlot(slot, rid)"
                >
                  ×
                </button>
              </li>
              <li v-if="!slot.recipeIds.length" class="muted">Пусто</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div v-if="monthOpen" class="modal-backdrop" @click.self="monthOpen = false">
      <div class="modal cal">
        <div class="cal-nav">
          <UiButton type="button" variant="secondary" @click="prevMonth">←</UiButton>
          <strong>{{ calLabel }}</strong>
          <UiButton type="button" variant="secondary" @click="nextMonth">→</UiButton>
        </div>
        <div class="dow">
          <span v-for="d in ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']" :key="d">{{ d }}</span>
        </div>
        <div class="grid">
          <button
            v-for="(c, i) in calCells"
            :key="i"
            type="button"
            class="cell"
            :disabled="!c.inMonth"
            @click="pickCalendarDay(c.iso)"
          >
            {{ c.d ?? '' }}
          </button>
        </div>
        <UiButton type="button" variant="secondary" @click="monthOpen = false">Закрыть</UiButton>
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

.planner-week-bar {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.shop-panel {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--space-md);
  margin-bottom: var(--space-lg);
}
.shop-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-md);
  margin-top: var(--space-sm);
}
.shop-row label {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  font-size: var(--font-size-caption);
}
.shop-row input {
  min-height: var(--input-min-height);
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
.layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-md);
}
.sidebar {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--space-md);
}
.sidebar input {
  width: 100%;
  margin: var(--space-sm) 0;
  min-height: var(--input-min-height);
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
.slot-pick {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  font-size: var(--font-size-caption);
  margin-bottom: var(--space-sm);
}
.slot-pick select {
  min-height: var(--input-min-height);
  padding: var(--space-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
.rec-list {
  list-style: none;
  margin: 0;
  padding: 0;
}
.rec-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-xs) 0;
  border-bottom: 1px solid var(--color-border);
  font-size: var(--font-size-caption);
}
.week {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}
.day {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--space-md);
}
.day[data-focus='true'] {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 2px;
}
.day h4 {
  margin: 0 0 var(--space-sm);
  font-size: var(--font-size-body);
}
.slot {
  margin-bottom: var(--space-sm);
  border-radius: var(--radius-md);
  padding: var(--space-xs);
  transition: background 0.15s ease, outline 0.15s ease;
}
.slot--drag-over {
  background: var(--color-accent-soft);
  outline: 2px dashed var(--color-accent);
  outline-offset: 2px;
}
.slot--active {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 2px;
}
.add-hint {
  margin: 0 0 var(--space-sm);
  color: var(--color-text-secondary);
  font-size: var(--font-size-caption);
  line-height: 1.4;
}
.draggable-recipe {
  cursor: grab;
}
.draggable-recipe:active {
  cursor: grabbing;
}
.slot-head {
  font-weight: 600;
  font-size: var(--font-size-caption);
  margin-bottom: var(--space-xs);
}
.slot-recipes {
  list-style: none;
  margin: 0;
  padding: 0;
}
.slot-recipes li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-sm);
  font-size: var(--font-size-caption);
}
.link-remove {
  min-height: var(--touch-target);
  min-width: var(--touch-target);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--color-surface);
  color: var(--color-error);
  cursor: pointer;
  font-size: 1.2rem;
  line-height: 1;
}
.muted {
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
}
.err {
  color: var(--color-error);
  font-size: var(--font-size-body);
}
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: var(--color-overlay);
  z-index: 40;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-lg);
}
.modal.cal {
  background: var(--color-surface);
  border-radius: var(--radius-md);
  padding: var(--space-lg);
  max-width: 24rem;
  width: 100%;
}
.cal-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--space-md);
}
.dow {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 2px;
  text-align: center;
  font-size: var(--font-size-caption);
  margin-bottom: var(--space-xs);
}
.grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  margin-bottom: var(--space-md);
}
.cell {
  min-height: max(48px, var(--touch-target));
  min-width: max(48px, var(--touch-target));
  aspect-ratio: 1;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg);
  cursor: pointer;
}
.cell:disabled {
  opacity: 0.25;
  cursor: default;
}
@media (min-width: 768px) {
  .mf-root {
    padding: var(--space-lg);
  }
  .planner-week-bar {
    grid-template-columns: repeat(3, max-content);
    align-items: center;
  }
  .shop-row {
    grid-template-columns: repeat(2, minmax(0, max-content)) 1fr;
    align-items: end;
  }
  .layout {
    grid-template-columns: 16rem 1fr;
    gap: var(--space-lg);
  }
}
@media (min-width: 1200px) {
  .shop-row {
    grid-template-columns: repeat(5, max-content);
  }
}
</style>
