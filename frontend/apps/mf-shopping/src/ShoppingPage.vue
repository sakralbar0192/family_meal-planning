<script setup lang="ts">
import type { ShoppingLine, ShoppingListDetail } from '@meal/bff-client';
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useBff } from './useBff';

const route = useRoute();
const router = useRouter();
const bff = useBff();

const detail = ref<ShoppingListDetail | null>(null);
const loading = ref(true);
const error = ref('');

const manualName = ref('');
const manualQty = ref<number | ''>('');
const manualUnit = ref('');
const manualCat = ref('');

const listId = computed(() => route.params.listId as string);

const showEmpty = computed(() => {
  if (loading.value || !detail.value) {
    return false;
  }
  const n = detail.value.lines?.length ?? 0;
  if (n > 0) {
    return false;
  }
  return detail.value.empty === true || route.query.empty === '1' || n === 0;
});

async function load(): Promise<void> {
  if (!listId.value) {
    return;
  }
  loading.value = true;
  error.value = '';
  try {
    detail.value = await bff.json<ShoppingListDetail>(`/shopping/lists/${listId.value}`);
  } catch (e) {
    detail.value = null;
    error.value = e instanceof Error ? e.message : 'Ошибка';
  } finally {
    loading.value = false;
  }
}

onMounted(load);
watch(listId, () => load());

const grouped = computed(() => {
  const lines = detail.value?.lines ?? [];
  const m = new Map<string, ShoppingLine[]>();
  for (const line of lines) {
    const key = line.productCategory?.trim() || '_other';
    if (!m.has(key)) {
      m.set(key, []);
    }
    m.get(key)!.push(line);
  }
  const keys = [...m.keys()].sort((a, b) => {
    if (a === '_other') {
      return 1;
    }
    if (b === '_other') {
      return -1;
    }
    return a.localeCompare(b);
  });
  return keys.map((k) => ({ category: k === '_other' ? 'Без категории' : k, lines: m.get(k)! }));
});

async function togglePurchased(line: ShoppingLine): Promise<void> {
  const res = await bff.fetch(`/shopping/lists/${listId.value}/lines/${line.lineId}`, {
    method: 'PATCH',
    body: JSON.stringify({ purchased: !line.purchased }),
  });
  if (!res.ok) {
    error.value = await res.text();
    return;
  }
  await load();
}

async function removeLine(line: ShoppingLine): Promise<void> {
  if (!confirm(`Удалить «${line.displayName}»?`)) {
    return;
  }
  const res = await bff.fetch(`/shopping/lists/${listId.value}/lines/${line.lineId}`, {
    method: 'DELETE',
  });
  if (!res.ok) {
    error.value = await res.text();
    return;
  }
  await load();
}

async function addManual(): Promise<void> {
  if (!manualName.value.trim()) {
    return;
  }
  const body: Record<string, unknown> = { displayName: manualName.value.trim() };
  if (manualQty.value !== '') {
    body.quantity = Number(manualQty.value);
  }
  if (manualUnit.value.trim()) {
    body.unit = manualUnit.value.trim();
  }
  if (manualCat.value.trim()) {
    body.productCategory = manualCat.value.trim();
  }
  const res = await bff.fetch(`/shopping/lists/${listId.value}/lines`, {
    method: 'POST',
    body: JSON.stringify(body),
  });
  if (!res.ok) {
    error.value = await res.text();
    return;
  }
  manualName.value = '';
  manualQty.value = '';
  manualUnit.value = '';
  manualCat.value = '';
  await load();
}

async function exportText(): Promise<void> {
  const lines = detail.value?.lines ?? [];
  const parts: string[] = [];
  if (detail.value) {
    parts.push(`Период: ${detail.value.from} — ${detail.value.to}`);
  }
  for (const g of grouped.value) {
    parts.push(`\n[${g.category}]`);
    for (const l of g.lines) {
      const q = l.quantity != null ? `${l.quantity} ${l.unit ?? ''}`.trim() : '';
      parts.push(`${l.purchased ? '✓' : '○'} ${l.displayName}${q ? ` — ${q}` : ''}`);
    }
  }
  const text = parts.join('\n');
  try {
    await navigator.clipboard.writeText(text);
  } catch {
    error.value = 'Не удалось скопировать в буфер';
  }
}

function goPlanner(): void {
  void router.push({ path: '/planner' });
}
</script>

<template>
  <section class="mf-root">
    <header class="head">
      <RouterLink class="back" to="/planner">← Планировщик</RouterLink>
      <h2>Список покупок</h2>
      <p v-if="detail" class="period">
        Период: {{ detail.from }} — {{ detail.to }}
        <button type="button" class="linkish" @click="goPlanner">Изменить период</button>
      </p>
    </header>

    <p v-if="loading" class="muted">Загрузка…</p>
    <p v-else-if="error" class="err">{{ error }}</p>

    <template v-else-if="detail">
      <div v-if="showEmpty" class="empty soft">
        <p>За период не было назначений — можно добавить продукты вручную или вернуться в планировщик.</p>
        <button type="button" class="btn secondary" @click="goPlanner">Планировщик</button>
      </div>
      <div class="toolbar">
        <button type="button" class="btn secondary" @click="exportText">Копировать список</button>
      </div>

      <div v-for="g in grouped" :key="g.category" class="group">
        <h3>{{ g.category }}</h3>
        <ul class="lines">
          <li v-for="line in g.lines" :key="line.lineId" class="line">
            <label class="check">
              <input
                type="checkbox"
                :checked="line.purchased"
                @change="togglePurchased(line)"
              />
              <span :class="{ done: line.purchased }">{{ line.displayName }}</span>
            </label>
            <span v-if="line.quantity != null" class="qty">
              {{ line.quantity }} {{ line.unit ?? '' }}
            </span>
            <span v-if="line.mergeNote" class="muted">{{ line.mergeNote }}</span>
            <button type="button" class="btn danger small" @click="removeLine(line)">Удалить</button>
          </li>
        </ul>
      </div>

      <section class="manual">
        <h3>Свой продукт</h3>
        <div class="manual-row">
          <input v-model="manualName" placeholder="Название" />
          <input v-model.number="manualQty" type="number" step="any" placeholder="Кол-во" />
          <input v-model="manualUnit" placeholder="Ед." />
          <input v-model="manualCat" placeholder="Категория" />
          <button type="button" class="btn" @click="addManual">Добавить</button>
        </div>
      </section>
    </template>
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
.period {
  font-size: var(--font-size-body);
  margin: var(--space-sm) 0 0;
}
.linkish {
  margin-left: var(--space-md);
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  color: var(--color-text-primary);
  text-decoration: underline;
  cursor: pointer;
}
.toolbar {
  margin: var(--space-md) 0;
}
.group {
  margin-bottom: var(--space-lg);
}
.group h3 {
  margin: 0 0 var(--space-sm);
  font-size: var(--font-size-body);
}
.lines {
  list-style: none;
  margin: 0;
  padding: 0;
}
.line {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-sm) 0;
  border-bottom: 1px solid var(--color-border);
  font-size: var(--font-size-caption);
}
.check {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  flex: 1;
  min-width: 10rem;
}
.done {
  text-decoration: line-through;
  color: var(--color-text-muted);
}
.qty {
  color: var(--color-text-secondary);
}
.manual {
  margin-top: var(--space-xl);
  padding-top: var(--space-lg);
  border-top: 1px solid var(--color-border);
}
.manual-row {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  margin-top: var(--space-sm);
}
.manual-row input {
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
.btn.secondary {
  background: transparent;
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
}
.btn.danger {
  background: color-mix(in srgb, #c00 80%, var(--color-text-primary));
  color: #fff;
}
.btn.small {
  padding: 2px 8px;
  font-size: var(--font-size-caption);
}
.empty {
  padding: var(--space-lg);
  text-align: center;
}
.empty.soft {
  padding: var(--space-md);
  margin-bottom: var(--space-lg);
  text-align: left;
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
}
.muted {
  color: var(--color-text-muted);
}
.err {
  color: #b00020;
}
</style>
